<?php

namespace App\Controller;

use App\DTO\MessageDto;
use App\DTO\TokenDto;
use App\DTO\UserDto;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ChatController extends AbstractController
{
    private SerializerInterface $serializer;
    private ChatService $chatService;

    public function __construct(
        SerializerInterface $serializer,
        ChatService $chatService
    ) {
        $this->serializer = $serializer;
        $this->chatService = $chatService;
    }

    #[Route('/api/user', name: 'create_user', methods: "POST")]
    public function createUser(Request $request): JsonResponse
    {
        try {
            $data = $request->getContent();
            $userDto = $this->serializer->deserialize($data, UserDto::class, 'json');
            $token = $this->chatService->createUser($userDto);

            return $this->json([
                'id' => $token->id,
                'token' => $token->token,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/api/message/send/{user_to}', name: 'app_send_message', methods: "POST", requirements: ['user_to' => '\d+'])]
    public function sendMessage(Request $request, int $user_to): JsonResponse
    {
        try {
            $userId = $request->headers->get('x-user-id');
            $userToken = $request->headers->get('x-user-token');
            $this->chatService->checkAuthorization($userId, $userToken);

            $content = $request->getContent();
            $data = json_decode($content, true);
            $text = $data['text'] ?? null;
            $arrMessage = [
                'id' => null,
                'userFrom' => $userId,
                'userTo' => $user_to,
                'text' => $text,
                'readIt' => false,
                'isReceiving' => false
            ];
            $messageDto = new MessageDto(...$arrMessage);
            $messageDto->userFrom = $userId;
            $messageDto->userTo = $user_to;
            $idMessage = $this->chatService->sendMessage($messageDto);

            return $this->json([
                'id_message' => $idMessage,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/api/message/receiving', name: 'app_receiving_message', methods: "GET")]
    public function receivingMessage(Request $request): JsonResponse
    {
        try {
            $userId = $request->headers->get('x-user-id');
            $userToken = $request->headers->get('x-user-token');
            $this->chatService->checkAuthorization($userId, $userToken);
            $messagesDto = $this->chatService->receivingMessage($userId);
            $normalizedMessages = [];
            foreach ($messagesDto as $dto) {
                $normalizedMessages[] = $this->serializer->normalize($dto);
            }

            return $this->json($normalizedMessages);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/api/message/read', name: 'app_read_message', methods: "POST")]
    public function readMessage(Request $request): JsonResponse
    {
        try {
            $userId = $request->headers->get('x-user-id');
            $userToken = $request->headers->get('x-user-token');
            $this->chatService->checkAuthorization($userId, $userToken);
            $content = $request->getContent();
            $data = json_decode($content, true);
            $idsMessages = $data['ids'] ?? [];
            $this->chatService->readMessage($userId, $idsMessages);

            return $this->json([
                'read' => true
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ]);
        }
    }
}
