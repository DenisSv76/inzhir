<?php

namespace App\EventSubscriber;

use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Entity\Message;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\MessageNotReadEvent;
class CheckReadMessageSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [ Events::postPersist ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Message) {
            $this->messageBus->dispatch(
                (new MessageNotReadEvent($entity->getId())),
                [new \Symfony\Component\Messenger\Stamp\DelayStamp(60000)]
            );
        }
    }
}
