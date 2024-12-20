<?php
namespace App\MessageHandler;

use App\Entity\Message;
use App\Entity\User;
use App\Message\MessageNotReadEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class MessageReadEventHandler
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public function __invoke(MessageNotReadEvent $event): void
    {
        $repositoryUser = $this->entityManager->getRepository(User::class);
        $repositoryMessage = $this->entityManager->getRepository(Message::class);
        $message = $repositoryMessage->find($event->getEntityId());

        if (!$message) {
            return;
        }
        $userTo = $message->getUserTo();
        $userFrom = $message->getUserFrom();

        if (!$userTo || !$userFrom) {
            return;
        }

        if ($message->isReadIt() == false) {
            $email = (new Email())
                ->from($userFrom->getEmail())
                ->to($userTo->getEmail())
                ->subject('Unread Message Notification')
                ->text('У вас есть непрочитанное сообщение с ID: ' . $message->getId() . ' от ' . $userFrom->getLogin());

            $this->mailer->send($email);
        }
    }
}
