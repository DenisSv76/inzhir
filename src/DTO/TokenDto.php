<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
class TokenDto
{
    #[Assert\NotBlank(message: "Id cannot be blank")]
    public int $id;

    #[Assert\NotBlank(message: "Token cannot be blank")]
    public string $token;

    public function __construct(
        int $id,
        string $token
    ) {
        $this->id = $id;
        $this->token = $token;
    }
}
