<?php declare(strict_types=1);
namespace MltisafeMultiSafepayPayment\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class OrderHistorySubscriber implements EventSubscriber
{

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [Events::preUpdate];
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        // Class exist due to prevent errors. See PLGSHPS-249
    }
}
