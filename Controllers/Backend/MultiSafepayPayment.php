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
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Status;

class Shopware_Controllers_Backend_MultiSafepayPayment extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        return ['refundOrder'];
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function refundOrderAction()
    {
        $request = $this->Request();
        $orderNumber = $request->getParam('orderNumber');
        $transactionId = $request->getParam('transactionId');

        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['number' => $orderNumber]);

        $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $order->getShop());
        $msp = new MspClient();
        $msp->setApiKey($pluginConfig['msp_api_key']);
        if (!$pluginConfig['msp_environment']) {
            $msp->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $msp->setApiUrl('https://api.multisafepay.com/v1/json/');
        }

        $endpoint = 'orders/' . $transactionId . '/refunds';
        $refundData = array(
            "amount" => $order->getInvoiceAmount() * 100,
            "currency" => $order->getCurrency(),
            "description" => "Refund: " . $transactionId,
        );
        $msp->orders->post($refundData, $endpoint);

        if (!empty($msp->orders->result->error_code)) {
            return $this->view->assign([
                'success' =>  false,
                'message' => "{$msp->orders->result->error_code} - {$msp->orders->result->error_info}",
            ]);
        }

        //Update status
        $em = Shopware()->Models();
        if ($pluginConfig['msp_update_refund_active'] &&
            is_int($pluginConfig['msp_update_refund']) &&
            $pluginConfig['msp_update_refund'] > 0
        ) {
            $orderStatusRefund = $em->getReference(Status::class, $pluginConfig['msp_update_refund']);
            $order->setPaymentStatus($orderStatusRefund);
        }
        $em->persist($order);
        $em->flush($order);

        return $this->view->assign([
            'success' => true,
            'message' => "Order has been fully refunded at MultiSafepay",
        ]);
    }
}
