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
namespace MltisafeMultiSafepayPayment\Subscriber;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Exception;
use MltisafeMultiSafepayPayment\Service\LoggerService;
use MltisafeMultiSafepayPayment\Service\PaymentMethodsService;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use Shopware\Components\Plugin\PaymentInstaller;
use Shopware\Models\Country\Country;
use Shopware\Models\Payment\Payment;

/**
 * Class PaymentMethodsInstaller
 *
 * @package MltisafeMultiSafepayPayment\Subscriber
 */
class PaymentMethodsInstaller
{
    /**
     * @var string
     */
    private const NAME = 'MltisafeMultiSafepayPayment';

    /**
     * @var mixed
     */
    private $container;

    /**
     * @var PaymentMethodsService
     */
    private $paymentMethods;

    /**
     * PaymentMethodsInstaller constructor
     *
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
        $this->paymentMethods = new PaymentMethodsService($this->container);
    }

    /**
     * Install payment methods without a shop
     *
     * @return void
     */
    public function installPaymentMethodsWithoutShop(): void
    {
        $paymentMethods = $this->paymentMethods->loadPaymentMethods(true, false);
        $this->disableInactiveMultiSafepayPaymentMethods($paymentMethods);
        $this->installPaymentMethodsInShopware($paymentMethods);
    }

    /**
     * Disable inactive MultiSafepay payment methods
     *
     * @param PaymentMethod[] $paymentMethods
     * @return void
     */
    private function disableInactiveMultiSafepayPaymentMethods(array $paymentMethods): void
    {
        $paymentMeans = Shopware()
            ->Models()
            ->getRepository(Payment::class)
            ->findAll();

        foreach ($paymentMeans as $paymentMean) {
            $keepPaymentMethodActive = false;

            foreach ($paymentMethods as $paymentMethod) {
                // If the payment method is still active. If so, don't do anything
                $paymentMethodId = explode('_', $paymentMean->getName())[1] ?? '';
                if ($paymentMethod['id'] === $paymentMethodId) {
                    $keepPaymentMethodActive = true;
                    break;
                }
            }

            if (!$keepPaymentMethodActive && (strpos($paymentMean->getName(), 'multisafepay') !== false)) {
                $this->unsetActiveFlag($paymentMean);
            }
        }
    }

    /**
     * Set the min and max amounts for a payment method
     *
     * @param $paymentMethodId
     * @param array $paymentMethod
     * @return void
     */
    private function setMinAndMaxAmounts($paymentMethodId, array $paymentMethod): void
    {
        if (!is_null($paymentMethod['allowed_amount']['max']) || !empty($paymentMethod['allowed_amount']['min'])) {
            $attributes = $this->container->get('shopware_attribute.data_loader')->load('s_core_paymentmeans_attributes', $paymentMethodId);

            if ($paymentMethod['type'] === 'coupon') {
                $attributes['msp_max_amount'] = $attributes['msp_min_amount'] = 0.0;
            } else {
                // The maximum amount may be null at the source
                $attributes['msp_max_amount'] = !empty($paymentMethod['allowed_amount']['max']) ? (float)$paymentMethod['allowed_amount']['max'] / 100 : 0.0;
                $attributes['msp_min_amount'] = !empty($paymentMethod['allowed_amount']['min']) ? (float)$paymentMethod['allowed_amount']['min'] / 100 : 0.0;
            }

            $this->container
                ->get('shopware_attribute.data_persister')
                ->persist($attributes, 's_core_paymentmeans_attributes', $paymentMethodId);
        }
    }

    /**
     * Install payment methods in Shopware
     *
     * @param array $paymentMethods
     * @return void
     */
    private function installPaymentMethodsInShopware(array $paymentMethods): void
    {
        /** @var PaymentInstaller $installer */
        $installer = $this->container->get('shopware.plugin_payment_installer');
        foreach ($paymentMethods as $paymentMethod) {
            $template = '';
            if ($paymentMethod['id'] === 'APPLEPAY') {
                $template = 'multisafepay_applepay.tpl';
            }

            $payment = $installer->createOrUpdate(self::NAME, [
                'name' => 'multisafepay_' . $paymentMethod['id'],
                'description' => $paymentMethod['name'],
                'action' => 'MultiSafepayPayment',
                'active' => 1,
                'position' => 0,
                'additionalDescription' => '',
                'template' => $template
            ]);
            if (!is_null($payment)) {
                $this->setMinAndMaxAmounts($payment->getId(), $paymentMethod);
                $allowedCountries = $this->paymentMethods->processAllowedCountries($paymentMethod);
                if (!empty($allowedCountries)) {
                    $countryIds = $this->paymentMethods->getCountryIdsForPaymentMethod($allowedCountries);
                    $this->paymentMethods->addCountriesForPaymentMethod($payment, $countryIds);
                }
            }
        }
    }

    /**
     * Unset the active flag for a payment method
     *
     * @param $payment
     */
    private function unsetActiveFlag($payment): void
    {
        $em = $this->container->get('models');
        if (!is_null($em)) {
            $payment->setActive(false);
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
}
