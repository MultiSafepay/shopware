<?php declare(strict_types=1);
namespace MltisafeMultiSafepayPayment\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use MltisafeMultiSafepayPayment\Components\Factory\Client;
use MultiSafepay\Api\Transactions\UpdateRequest;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class OrderHistorySubscriber implements EventSubscriber
{
    private $client;

    public function __construct()
    {
        $this->client = Shopware()->Container()->get('multi_safepay_payment.factory.client');
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [Events::preUpdate];
    }

    /**
     * If the order is getting updated. check if it is changed to Shipped. If so, update the status at MultiSafepay
     *
     * @param PreUpdateEventArgs $eventArgs
     * @throws \Exception
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $order = $eventArgs->getEntity();

        if (!($order instanceof Order) || !$eventArgs->hasChangedField('orderStatus')) {
            return;
        }

        /** @var Status $newStatus */
        $newStatus = $eventArgs->getNewValue('orderStatus');

        if (Status::ORDER_STATE_COMPLETELY_DELIVERED !== $newStatus->getId()) {
            return;
        }

        $pluginConfig = Shopware()->Container()->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $order->getShop());

        $this->client->getSdk($pluginConfig)->getTransactionManager()->update(
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
