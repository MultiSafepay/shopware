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

use MltisafeMultiSafepayPayment\Components\API\MspClient;
use Shopware\Models\Order\Status;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_MultiSafepayPayment extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'shipOrder',
            'refundOrder',
        ];
    }

    public function shipOrderAction()
    {
        $request = $this->Request();
        $orderNumber = $request->getParam('orderNumber');
        $transactionId = $request->getParam('transactionId');

        $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment');
        $msp = new MspClient();
        $msp->setApiKey($pluginConfig['msp_api_key']);
        if (!$pluginConfig['msp_environment']) {
            $msp->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $msp->setApiUrl('https://api.multisafepay.com/v1/json/');
        }

        $endpoint = 'orders/' . $transactionId;
        $msporder = $msp->orders->patch(
            array(
                "tracktrace_code" => "",
                "carrier" => "",
                "ship_date" => date('Y-m-d H:i:s'),
                "reason" => 'Shipped'
                    ), $endpoint);

        // Set order status to shipped within Shopware                   
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);
        $em = $this->container->get('models');
        $orderStatusShipped = $em->getReference(Status::class, Status::ORDER_STATE_READY_FOR_DELIVERY);
        $order->setOrderStatus($orderStatusShipped);
        $em->persist($order);
        $em->flush($order);

        $this->view->assign([
            'success', true,
            'message' => "Order has been set to shipped at MultiSafepay",
        ]);
    }

    public function refundOrderAction()
    {
        $request = $this->Request();
        $orderNumber = $request->getParam('orderNumber');
        $transactionId = $request->getParam('transactionId');

        $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment');
        $msp = new MspClient();
        $msp->setApiKey($pluginConfig['msp_api_key']);
        if (!$pluginConfig['msp_environment']) {
            $msp->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $msp->setApiUrl('https://api.multisafepay.com/v1/json/');
        }

        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);

        $endpoint = 'orders/' . $transactionId . '/refunds';
        $refundData = array(
            "amount" => $order->getInvoiceAmount() * 100,
            "currency" => $order->getCurrency(),
            "description" => "Refund: " . $transactionId,
        );
        $msporder = $msp->orders->post($refundData, $endpoint);        

        $this->view->assign([
            'success', true,
            'message' => "Order has been fully refunded at MultiSafepay",
        ]);
    }    
}
