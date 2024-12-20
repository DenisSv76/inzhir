<?php
namespace App\Service;

use App\DTO\MessageDto;
use App\DTO\UserDto;
use App\DTO\TokenDto;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
class ChatService
{
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $passwordHasher;
    private RequestStack $requestStack;

    public function __construct(
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher,
        RequestStack $requestStack
    ) {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
        $this->requestStack = $requestStack;
    }

    /**
     * @throws \Exception
     */
    public function createUser(UserDto $userDto): ?TokenDto
    {
        try {
            $errors = $this->validator->validate($userDto);

            if (count($errors) > 0) {
                $errorsString = (string)$errors;
                throw new \Exception('There are errors ' . $errorsString . ' in the registration data');
            }

            $returnLogin = $this->entityManager->getRepository(User::class)->findBy(['login' => $userDto->login]);

            if (!empty($returnLogin)) {
                throw new \Exception('Login ' . $userDto->login . ' used');
            }

            $returnEmail = $this->entityManager->getRepository(User::class)->findBy(['email' => $userDto->email]);

            if (!empty($returnEmail)) {
                throw new \Exception('Email ' . $userDto->email . ' used');
            }

            $user = $this->serializer->denormalize($userDto, User::class);
            $hash = $this->passwordHasher->hashPassword(
                $user,
                $userDto->password
            );
            $user->setPassword($hash);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $randomBytes = random_bytes(32);
            $token = base64_encode($randomBytes);
            $token = str_replace(['+', '/', '='], ['-', '_', ''], $token);

            $session = $this->requestStack->getSession();
            $userAuth = $session->get('user_auth', []);
            $userAuth[$user->getId()] = $token;
            $session->set('user_auth', $userAuth);

            return (new TokenDto($user->getId(), $token));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function  checkAuthorization(int $idUser, string $token): ?bool
    {
        $session = $this->requestStack->getSession();
        $userAuth = $session->get('user_auth', []);

        if (!empty($userAuth[$idUser]) && $userAuth[$idUser] === $token) {
            return true;
        } else {
            throw new AccessDeniedHttpException('Access Denied.');
        }
    }

    /**
     * @throws \Exception
     */
    public function sendMessage(MessageDto $messageDto): ?int
    {
        try {
            $errors = $this->validator->validate($messageDto);

            if (count($errors) > 0) {
                $errorsString = (string)$errors;
                throw new \Exception('There are errors ' . $errorsString . ' when sending a message');
            }
            $userFrom = $this->entityManager->getRepository(User::class)->find($messageDto->userFrom);
            $userTo = $this->entityManager->getRepository(User::class)->find($messageDto->userTo);

            if (!$userTo) {
                throw new \Exception('There are not user id=' . $messageDto->userTo . ' to whom sending a message');
            }
            $message = new Message();
            $message->setUserFrom($userFrom);
            $message->setUserTo($userTo);
            $message->setText($messageDto->text);

            $this->entityManager->persist($message);
            $this->entityManager->flush();

        } catch (\Exception $e) {
            throw $e;
        }

        return $message->getId();
    }

    /**
     * @return MessageDto[]
     * @throws \Exception
     */
    public function receivingMessage(int $userTo): array
    {
        try {
            $messages = $this->entityManager->getRepository(Message::class)->findBy(['userTo' => $userTo, 'isReceiving' => false]);

            $messagesDto = [];
            foreach ($messages as $message) {
                $message->setReceiving(true);
                $messagesDto[] = new MessageDto(
                    $message->getId(),
                    ($message->getUserFrom())->getId(),
                    ($message->getUserTo())->getId(),
                    $message->getText(),
                    $message->isReadIt(),
                    $message->isReceiving(),
                );
            }
            $this->entityManager->flush();

            return $messagesDto;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /** *
     * @param int[] $idsMessages
     * @throws \Exception
     */
    public function readMessage(int $userTo, array $idsMessages): void
    {
        try {

            if (empty($idsMessages)) {
                throw new \Exception('Empty messages list');
            }

            $messages = $this->entityManager->getRepository(Message::class)->findBy(['id' => $idsMessages, 'userTo' => $userTo, 'readIt' => 0]);

            if (count($messages) != count($idsMessages)) {
                throw new \Exception('Not read messages ' . implode(',', $idsMessages) . ' not found for user');
            }
            foreach ($messages as $message) {
                $message->setReadIt(true);
            }
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
