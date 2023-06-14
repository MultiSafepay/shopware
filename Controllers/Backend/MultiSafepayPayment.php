<?php declare(strict_types=1);
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
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

use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\ValueObject\Money;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Status;

class Shopware_Controllers_Backend_MultiSafepayPayment extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    /** @var \MltisafeMultiSafepayPayment\Components\Factory\Client */
    private $client;
    public function preDispatch()
    {
        $this->client = $this->get('multi_safepay_payment.factory.client');
    }

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

        $transactionManager = $this->client->getSdk($pluginConfig)->getTransactionManager();
        $transactionData = $transactionManager->get($transactionId);

        $refundRequest = (new RefundRequest())->addMoney(
            new Money(
                $order->getInvoiceAmount() * 100,
                $order->getCurrency()
            )
        );

        try {
            $transactionManager->refund($transactionData, $refundRequest);
        } catch (Exception $exception) {
            return $this->view->assign([
                'success' =>  false,
                'message' => $exception->getMessage(),
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
