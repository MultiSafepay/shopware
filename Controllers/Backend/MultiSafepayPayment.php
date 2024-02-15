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

use MltisafeMultiSafepayPayment\Components\Factory\Client;
use MltisafeMultiSafepayPayment\Service\CachedConfigService;
use MltisafeMultiSafepayPayment\Service\LoggerService;
use MultiSafepay\Api\Transactions\RefundRequest;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\ValueObject\Money;
use Psr\Http\Client\ClientExceptionInterface;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

/**
 * Class Shopware_Controllers_Backend_MultiSafepayPayment
 *
 * @package MltisafeMultiSafepayPayment\Controllers\Backend
 */
class Shopware_Controllers_Backend_MultiSafepayPayment extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerService
     */
    private $logger;

    /**
     * Pre-dispatch method
     *
     * @return void
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->client = $this->get('multisafepay.factory.client');
        $this->logger = new LoggerService($this->container);
    }

    /**
     * Get CSRF whitelist actions
     *
     * @return array
     */
    public function getWhitelistedCSRFActions(): array
    {
        return [
            'refundOrder'
        ];
    }

    /**
     * Refund order action
     *
     * @return Enlight_View_Default
     */
    public function refundOrderAction(): Enlight_View_Default
    {
        $request = $this->Request();
        $orderNumber = $request->getParam('orderNumber');
        $transactionId = $request->getParam('transactionId');

        $order = Shopware()
            ->Models()
            ->getRepository(Order::class)
            ->findOneBy(
                ['number' => $orderNumber]
            );

        [$cachedConfigReader, $shop] = (new CachedConfigService($this->container, $order))->selectConfigReader();
        if (is_null($cachedConfigReader)) {
            $this->logger->addLog(
                LoggerService::INFO,
                'Could not load plugin configuration',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Action' => $this->Request()->getActionName()
                ]
            );
            return $this->view->assign([
                'success' => false,
                'message' => 'Could not load plugin configuration',
            ]);
        }
        $pluginConfig = $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop);

        try {
            $clientSdk = $this->client->getSdk($pluginConfig);
            $transactionManager = $clientSdk->getTransactionManager();
            $transactionData = $transactionManager->get($transactionId);
        } catch (ApiException | ClientExceptionInterface | Exception $exception) {
            return $this->view->assign([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        $refundRequest = (new RefundRequest())->addMoney(
            new Money(
                $order->getInvoiceAmount() * 100,
                $order->getCurrency()
            )
        );

        try {
            $transactionManager->refund($transactionData, $refundRequest);
        } catch (ApiException | ClientExceptionInterface | Exception $exception) {
            return $this->view->assign([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        // Update status
        $em = Shopware()->Models();
        if ($pluginConfig['msp_update_refund_active'] &&
            is_int($pluginConfig['msp_update_refund']) &&
            ($pluginConfig['msp_update_refund'] > 0)
        ) {
            try {
                $orderStatusRefund = $em->getReference(Status::class, $pluginConfig['msp_update_refund']);
                $order->setPaymentStatus($orderStatusRefund);
            } catch (Exception $exception) {
                $this->logger->addLog(
                    LoggerService::ERROR,
                    'Could not update the order status after refunding the order',
                    [
                        'TransactionId' => $this->Request()->getParam('transactionid'),
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Action' => $this->Request()->getActionName(),
                        'Exception' => $exception->getMessage()
                    ]
                );
            }
        }

        try {
            $em->persist($order);
            $em->flush($order);
        } catch (Exception $exception) {
            $this->logger->addLog(
                LoggerService::ERROR,
                'Could not save the order status after refunding the order',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Action' => $this->Request()->getActionName(),
                    'Exception' => $exception->getMessage()
                ]
            );
        }

        return $this->view->assign([
            'success' => true,
            'message' => 'Order has been fully refunded at MultiSafepay',
        ]);
    }
}
