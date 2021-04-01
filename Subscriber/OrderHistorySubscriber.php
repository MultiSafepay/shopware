<?php


namespace MltisafeMultiSafepayPayment\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use MltisafeMultiSafepayPayment\Components\API\MspClient;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class OrderHistorySubscriber implements EventSubscriber
{

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
        $msp = new MspClient();
        $msp->setApiKey($pluginConfig['msp_api_key']);
        if (!$pluginConfig['msp_environment']) {
            $msp->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $msp->setApiUrl('https://api.multisafepay.com/v1/json/');
        }

        $msp->orders->patch(
            array(
                "tracktrace_code" => $order->getTrackingCode(),
                "carrier" => "",
                "ship_date" => date('Y-m-d H:i:s'),
                "reason" => 'Shipped'
            ),
            'orders/' . $order->getTransactionId()
        );

        $msp->orders->getResult()->success;
    }
}
