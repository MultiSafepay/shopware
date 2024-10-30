<?php declare(strict_types=1);
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Shopware
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MltisafeMultiSafepayPayment;

use Enlight_Controller_Action;
use Enlight_Event_EventArgs;
use Exception;
use MltisafeMultiSafepayPayment\Service\LoggerService;
use MltisafeMultiSafepayPayment\Service\PaymentMethodsService;
use MltisafeMultiSafepayPayment\Subscriber\PaymentMethodsInstaller;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
use Shopware\Bundle\AttributeBundle\Service\TypeMappingInterface;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Models\Payment\Payment;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Class MltisafeMultiSafepayPayment
 *
 * @package MltisafeMultiSafepayPayment
 */
class MltisafeMultiSafepayPayment extends Plugin
{
    /**
     * Deleted payment methods
     *
     * @var array
     */
    public const DELETED_PAYMENT_METHODS = [
        'INGHOME',
        'BABYGIFTCARD',
        'EROTIEKBON',
        'NATIONALEVERWENCADEAUBON',
        'SANTANDER'
    ];

    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch'
        ];
    }

    /**
     * On order post-dispatch
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onOrderPostDispatch(Enlight_Event_EventArgs $args): void
    {
        /** @var Enlight_Controller_Action $controller */
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir(__DIR__ . '/Resources/views');
        if ((string)$request->getActionName() === 'load') {
            $view->extendsTemplate('backend/order/view/list/multisafepay_list.js');
            $view->extendsTemplate('backend/order/controller/multisafepay_list.js');
        }
    }

    /**
     * Install plugin
     *
     * @param InstallContext $context
     */
    public function install(InstallContext $context): void
    {
        $this->installAttributes();
        $this->installMultiSafepayQuoteNumber();
    }

    /**
     * Install payment methods
     *
     * @param ActivateContext $context
     * @throws Exception|ClientExceptionInterface
     */
    private function installPaymentMethods(ActivateContext $context): void
    {
        $configReader = $this->container->get('shopware.plugin.config_reader');
        $pluginConfig = $configReader ? $configReader->getByPluginName('MltisafeMultiSafepayPayment') : null;

        if (empty($pluginConfig['msp_api_key'])) {
            throw new RuntimeException('Please fill in the API Key in the MultiSafepay settings, and save it.');
        }
        $paymentMethodInstaller = new PaymentMethodsInstaller($this->container);
        $paymentMethodInstaller->installPaymentMethodsWithoutShop();
    }

    /**
     * Get payment method options
     *
     * @param string $paymentMethodId
     * @param string $paymentMethodName
     * @return array
     */
    private function getPaymentMethodOptions(string $paymentMethodId, string $paymentMethodName): array
    {
        $template = '';
        if ($paymentMethodId === 'APPLEPAY') {
            $template = 'multisafepay_applepay.tpl';
        }

        return [
            'name' => 'multisafepay_' . $paymentMethodId,
            'description' => $paymentMethodName,
            'action' => 'MultiSafepayPayment',
            'active' => 0,
            'position' => 0,
            'additionalDescription' => '',
            'template' => $template
        ];
    }

    /**
     * Method to check if the payment method is installed
     *
     * @param string $paymentMethodFullName
     * @return bool
     */
    private function isPaymentMethodInstalled(string $paymentMethodFullName): bool
    {
        return (bool)Shopware()
            ->Models()
            ->getRepository(Payment::class)
            ->findOneBy(
                ['name' => $paymentMethodFullName]
            );
    }

    /**
     * Set min and max amounts
     *
     * @param $paymentMethodId
     * @param array $paymentMethodAmounts
     */
    private function setMinAndMaxAmounts($paymentMethodId, array $paymentMethodAmounts): void
    {
        if (!empty($paymentMethodAmounts['min_amount']) || !empty($paymentMethodAmounts['max_amount'])) {
            $dataLoader = $this->container->get('shopware_attribute.data_loader');
            $attributes = $dataLoader ? $dataLoader->load('s_core_paymentmeans_attributes', $paymentMethodId) : [];

            $attributes['msp_min_amount'] = $paymentMethodAmounts['min_amount'];
            $attributes['msp_max_amount'] = $paymentMethodAmounts['max_amount'] ?? 0.0;
            $dataPersister = $this->container->get('shopware_attribute.data_persister');
            if (!is_null($dataPersister)) {
                $dataPersister->persist($attributes, 's_core_paymentmeans_attributes', $paymentMethodId);
            }
        }
    }

    /**
     * Update payment methods
     *
     * @param UpdateContext $context
     * @throws Exception|ClientExceptionInterface
     */
    private function updatePaymentMethods(UpdateContext $context): void
    {
        $installer = $this->container->get('shopware.plugin_payment_installer');
        $paymentMethodsService = new PaymentMethodsService($this->container);
        $paymentMethods = $paymentMethodsService->loadPaymentMethods(true, false);

        if (!empty($paymentMethods)) {
            foreach ($paymentMethods as $paymentMethod) {
                $paymentMethodId = $paymentMethod['id'];
                $paymentMethodName = $paymentMethod['name'];
                $options = [
                    'name' => 'multisafepay_' . $paymentMethodId,
                    'description' => $paymentMethodName
                ];
                $amounts = [
                    'min_amount' => $paymentMethod['allowed_amount']['min'],
                    'max_amount' => $paymentMethod['allowed_amount']['max']
                ];

                if (!$this->isPaymentMethodInstalled('multisafepay_' . $paymentMethodId)) {
                    $options = $this->getPaymentMethodOptions($paymentMethodId, $paymentMethodName);
                } elseif ($paymentMethodId === 'GENERIC') {
                    unset($options['description']);
                }
                $payment = $installer ? $installer->createOrUpdate($context->getPlugin(), $options) : null;
                if (!is_null($payment)) {
                    $this->setMinAndMaxAmounts($payment->getId(), $amounts);
                    $allowedCountries = $paymentMethodsService->processAllowedCountries($paymentMethod);
                    if (!empty($allowedCountries)) {
                        $countryIds = $paymentMethodsService->getCountryIdsForPaymentMethod($allowedCountries);
                        $paymentMethodsService->addCountriesForPaymentMethod($payment, $countryIds);
                    }
                }
            }
            $this->deletePaymentMethods();
        }
    }

    /**
     * Install attributes
     *
     * @return void
     */
    private function installAttributes(): void
    {
        $attributeCrudService = $this->container->get('shopware_attribute.crud_service');
        if (!is_null($attributeCrudService)) {
            $attributeCrudService->update(
                's_core_paymentmeans_attributes',
                'msp_min_amount',
                TypeMappingInterface::TYPE_FLOAT,
                [
                    'position' => -100,
                    'label' => 'MultiSafepay Minimum Order Total',
                    'supportText' => 'Only applicable to MultiSafepay payment methods',
                    'helpText' => 'Payment method will be hidden when cart is less than amount',
                    'displayInBackend' => true
                ]
            );

            $attributeCrudService->update(
                's_core_paymentmeans_attributes',
                'msp_max_amount',
                TypeMappingInterface::TYPE_FLOAT,
                [
                    'position' => -99,
                    'label' => 'MultiSafepay Maximum Order Total',
                    'supportText' => 'Only applicable to MultiSafepay payment methods',
                    'helpText' => 'Payment method will be hidden when cart exceeds amount',
                    'displayInBackend' => true
                ]
            );
        }

        $crudService = $this->container->get('shopware_attribute.crud_service');
        if (!is_null($crudService)) {
            $crudService->update(
                's_order_attributes',
                'multisafepay_payment_link',
                TypeMappingInterface::TYPE_STRING,
                [
                    'position' => -100,
                    'label' => 'MultiSafepay Backend orders payment link',
                    'displayInBackend' => true
                ]
            );
        }
    }

    /**
     * Installs MultiSafepay Quote Number
     *
     * Use an INSERT IGNORE statement to prevent duplicate entries.
     * This method is called during the installation process.
     *
     * @return void
     */
    private function installMultiSafepayQuoteNumber(): void
    {
        $db = $this->container->get('dbal_connection');
        if (!is_null($db)) {
            $sql = "INSERT IGNORE INTO `s_order_number` (`number`, `name`, `desc`) VALUES (0, 'msp_quote_number', 'MultiSafepay Quote Number')";
            $db->executeUpdate($sql);
        }
    }

    /**
     * Update plugin
     *
     * @param UpdateContext $context
     * @throws Exception|ClientExceptionInterface
     */
    public function update(UpdateContext $context): void
    {
        $this->updateAttributes($context);
        $this->updatePaymentMethods($context);
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
        parent::update($context);
    }

    /**
     * Update attributes
     *
     * @param UpdateContext $context
     */
    public function updateAttributes(UpdateContext $context): void
    {
        $crudService = $this->container->get('shopware_attribute.crud_service');
        if (!is_null($crudService)) {
            $crudService->update(
                's_order_attributes',
                'multisafepay_payment_link',
                TypeMappingInterface::TYPE_STRING,
                [
                    'position' => -100,
                    'label' => 'MultiSafepay Backend orders payment link',
                    'displayInBackend' => true
                ]
            );
        }
    }

    /**
     * Uninstall plugin
     *
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context): void
    {
        $this->unsetActiveFlag(
            $context->getPlugin()->getPayments()
        );
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * Deactivate plugin
     *
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context): void
    {
        $this->unsetActiveFlag(
            $context->getPlugin()->getPayments()
        );
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * Activate plugin
     *
     * @param ActivateContext $context
     * @throws Exception|ClientExceptionInterface
     */
    public function activate(ActivateContext $context): void
    {
        $this->installPaymentMethods($context);
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    /**
     * Unset the active flag for a payment method
     *
     * @param $payments
     */
    private function unsetActiveFlag($payments): void
    {
        $em = $this->container->get('models');
        foreach ($payments as $payment) {
            $payment->setActive(false);
        }
        if (!is_null($em)) {
            try {
                $em->flush();
            } catch (Exception $exception) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::ERROR,
                    'Could not unset the active flag in the database',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Exception' => $exception->getMessage()
                    ]
                );
            }
        }
    }

    /**
     * Disable the payment methods that are no longer supported
     *
     * @throws Exception
     */
    private function deletePaymentMethods(): void
    {
        $containerModels = $this->container->get('models');
        $paymentRepository = $containerModels ? $containerModels->getRepository(Payment::class) : null;

        if (!is_null($paymentRepository)) {
            foreach (self::DELETED_PAYMENT_METHODS as $paymentMethod) {
                /** @var Payment|null $payment */
                $payment = $paymentRepository->findOneBy(
                    ['name' => 'multisafepay_' . $paymentMethod]
                );
                if (is_null($payment)) {
                    continue;
                }

                $paymentId = $payment->getId();
                $this->unsetActiveFlag(
                    [$paymentRepository->find($paymentId)]
                );
            }
        }
    }
}
