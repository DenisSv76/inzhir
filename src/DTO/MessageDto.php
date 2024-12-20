<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
class MessageDto
{
    public ?int $id;

    #[Assert\NotBlank(message: "IdFrom cannot be blank")]
    #[Assert\Positive(message: "IdFrom must be a positive number")]
    public int $userFrom;

    #[Assert\NotBlank(message: "IdTo cannot be blank")]
    #[Assert\Positive(message: "IdTo must be a positive number")]
    public int $userTo;

    #[Assert\NotBlank(message: "Message cannot be blank")]
    #[Assert\Length(min: 1, max: 250)]
    public string $text;
    public bool $readIt = false;
    public bool $isReceiving = false;

    public function __construct(
        int $userFrom,
        int $userTo,
        string $text,
        bool $readIt = false,
        bool $isReceiving = false,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->userFrom = $userFrom;
        $this->userTo = $userTo;
        $this->text = $text;
        $this->readIt = $readIt;
        $this->isReceiving = $isReceiving;
    }
}
