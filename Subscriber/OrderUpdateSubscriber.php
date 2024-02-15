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

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MltisafeMultiSafepayPayment\Service\CachedConfigService;
use MltisafeMultiSafepayPayment\Service\LoggerService;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\Exception\ApiException;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

/**
 * Class OrderUpdateSubscriber
 *
 * @package MltisafeMultiSafepayPayment\Subscriber
 */
class OrderUpdateSubscriber implements SubscriberInterface
{
    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware\Models\Order\Order::postUpdate' => 'onOrderUpdate'
        ];
    }

    /**
     * If the order is getting updated, check if it is changed to ship.
     * If so, update the status at MultiSafepay
     *
     * @param Enlight_Event_EventArgs $eventArgs
     * @return void
     */
    public function onOrderUpdate(Enlight_Event_EventArgs $eventArgs): void
    {
        $container = Shopware()->Container();

        /** @var Order $order */
        $order = $eventArgs->get('entity');
        if (is_null($order)) {
            return;
        }

        // Check what has been changed
        $entityManager = $eventArgs->get('entityManager');
        if (is_null($entityManager)) {
            return;
        }
        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($order);

        // Check if there are changes in the orderStatus
        if (!isset($changeSet['orderStatus'])) {
            return;
        }

        if (Status::ORDER_STATE_COMPLETELY_DELIVERED !== $order->getOrderStatus()->getId()) {
            return;
        }

        // Retrieve the factory.client component
        $client = $container->get('multisafepay.factory.client');
        if (is_null($client)) {
            return;
        }

        // Retrieve the cached_config_reader component
        [$cachedConfigReader, $shop] = (new CachedConfigService($container))->selectConfigReader();
        if (is_null($cachedConfigReader)) {
            (new LoggerService($container))->addLog(
                LoggerService::WARNING,
                'Could not load plugin configuration',
                [
                    'TransactionId' => $order->getTransactionId(),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Action' => 'postUpdate'
                ]
            );
            return;
        }
        $pluginConfig = $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop);

        try {
            $clientSdk = $client->getSdk($pluginConfig);
            $transactionManager = $clientSdk->getTransactionManager();

            $updateData = [
                'tracktrace_code' => $order->getTrackingCode() ?? '',
                'carrier' => '',
                'ship_date' => date('Y-m-d H:i:s'),
                'reason' => 'Shipped'
            ];
            $updateRequest = (new UpdateRequest())->addStatus('shipped')->addData($updateData);
            $transactionManager->update(
                $order->getTransactionId(),
                $updateRequest
            );
        } catch (ApiException $apiException) {
            (new LoggerService($container))->addLog(
                LoggerService::ERROR,
                'Error while trying to send shipping request to MultiSafepay',
                [
                    'TransactionId' => $order->getTransactionId(),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Exception' => $apiException->getMessage()
                ]
            );
        }
    }
}
