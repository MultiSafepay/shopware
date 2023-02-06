<?php declare(strict_types=1);
namespace MltisafeMultiSafepayPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MultiSafepay\Api\Transactions\UpdateRequest;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class OrderUpdateSubscriber implements SubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return ['Shopware\Models\Order\Order::postUpdate' => 'onOrderUpdate'];
    }

    /**
     * If the order is getting updated. check if it is changed to Shipped. If so, update the status at MultiSafepay
     */
    public function onOrderUpdate(Enlight_Event_EventArgs $eventArgs)
    {
        /** @var Order $order */
        $order = $eventArgs->get('entity');

        if ($order === null) {
            return;
        }

        $client = Shopware()->Container()->get('multi_safepay_payment.factory.client');

        // Check what has been changed
        $changeSet = $eventArgs->get('entityManager')->getUnitOfWork()->getEntityChangeSet($order);

        // Check if there are changes in the orderStatus
        if (!isset($changeSet['orderStatus'])) {
            return;
        }

        if (Status::ORDER_STATE_COMPLETELY_DELIVERED !== $order->getOrderStatus()->getId()) {
            return;
        }

        $pluginConfig = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $order->getShop());
        $client->getSdk($pluginConfig)->getTransactionManager()->update(
            $order->getTransactionId(),
            (new UpdateRequest())->addStatus('shipped')->addData([
                "tracktrace_code" => $order->getTrackingCode(),
                "carrier" => "",
                "ship_date" => date('Y-m-d H:i:s'),
                "reason" => 'Shipped'
            ])
        );
    }
}
