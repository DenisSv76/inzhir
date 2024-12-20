<?php
namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;
#[AsMessage('async')]
class MessageNotReadEvent
{
    private int $entityId;

    public function __construct(int $entityId)
    {
        $this->entityId = $entityId;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }
}
