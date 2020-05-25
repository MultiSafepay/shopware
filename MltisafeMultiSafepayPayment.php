<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      MultiSafepay <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2018 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MltisafeMultiSafepayPayment;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Payment\Payment;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use MltisafeMultiSafepayPayment\Components\Gateways;

class MltisafeMultiSafepayPayment extends Plugin
{

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onOrderPostDispatch(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Action $controller */
        $controller = $args->getSubject();
        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir(__DIR__ . '/Resources/views');
        if ($request->getActionName() == 'load') {
            $view->extendsTemplate('backend/order/view/list/multisafepay_list.js');
            $view->extendsTemplate('backend/order/controller/multisafepay_list.js');
        }
    }

    /**
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        $this->installGateways($context);
        $this->installAttributes();
        $this->installMultiSafepayQuoteNumber();
    }

    /**
     * @param InstallContext $context
     */
    private function installGateways(InstallContext $context)
    {
        $installer = $this->container->get('shopware.plugin_payment_installer');

        foreach (Gateways::GATEWAYS as $gateway) {
            $options = [
                'name' => 'multisafepay_' . $gateway['code'],
                'description' => $gateway['name'],
                'action' => 'MultiSafepayPayment',
                'active' => 0,
                'position' => 0,
                'additionalDescription' => '',
                'template' => Gateways::getGatewayTemplate($gateway['code']),
            ];
            $installer->createOrUpdate($context->getPlugin(), $options);
        }
    }

    /**
     * @return void
     */
    private function installAttributes()
    {
        $attributeCrudService = $this->container->get('shopware_attribute.crud_service');

        $attributeCrudService->update(
            's_core_paymentmeans_attributes',
            'msp_min_amount',
            TypeMapping::TYPE_FLOAT,
            [
                'position' => -100,
                'label' => 'MultiSafepay Minimum Order Total',
                'supportText' => 'Only applicable to MultiSafepay payment methods',
                'helpText' => 'Payment method will be hidden when cart is less than amount',
                'displayInBackend' => true,
            ]
        );

        $attributeCrudService->update(
            's_core_paymentmeans_attributes',
            'msp_max_amount',
            TypeMapping::TYPE_FLOAT,
            [
                'position' => -99,
                'label' => 'MultiSafepay Maximum Order Total',
                'supportText' => 'Only applicable to MultiSafepay payment methods',
                'helpText' => 'Payment method will be hidden when cart exceeds amount',
                'displayInBackend' => true,
            ]
        );
    }

    /**
     * @return void
     */
    private function installMultiSafepayQuoteNumber()
    {
        $db = $this->container->get('dbal_connection');
        $sql = "
           INSERT IGNORE INTO `s_order_number` (`number`, `name`, `desc`) VALUES
           (0, 'msp_quote_number', 'MultiSafepay Quote Number');
       ";
        $db->executeUpdate($sql);
    }


    /**
     * @param UpdateContext $context
     */
    public function update(UpdateContext $context)
    {
        $this->updateGateways($context);
        parent::update($context);
    }

    /**
     * @param UpdateContext $context
     */
    private function updateGateways(UpdateContext $context)
    {
        $installer = $this->container->get('shopware.plugin_payment_installer');

        foreach (Gateways::GATEWAYS as $gateway) {
            $options = [
                'name' => 'multisafepay_' . $gateway['code'],
                'description' => $gateway['name'],
            ];
            $installer->createOrUpdate($context->getPlugin(), $options);
        }
    }

    /**
     * @param UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);

        $context->scheduleClearCache(UninstallContext::CACHE_LIST_ALL);
    }

    /**
     * @param DeactivateContext $context
     */
    public function deactivate(DeactivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), false);

        $context->scheduleClearCache(ActivateContext::CACHE_LIST_ALL);
    }

    /**
     * @param ActivateContext $context
     */
    public function activate(ActivateContext $context)
    {
        $this->setActiveFlag($context->getPlugin()->getPayments(), true);

        $context->scheduleClearCache(ActivateContext::CACHE_LIST_ALL);
    }

    /**
     * @param Payment[] $payments
     * @param $active bool
     */
    private function setActiveFlag($payments, $active)
    {
        $em = $this->container->get('models');

        foreach ($payments as $payment) {
            $payment->setActive($active);
        }
        $em->flush();
    }
}
