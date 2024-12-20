<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
class UserDto
{
    public ?int $id;

    #[Assert\NotBlank(message: "Login cannot be blank")]
    #[Assert\Length(min: 2, max: 30)]
    #[Assert\Regex("/^[a-zA-Z0-9]+$/")]
    public string $login;

    #[Assert\NotBlank(message: "Email cannot be blank")]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank(message: "Password cannot be blank")]
    #[Assert\Length(min: 5, max: 100)]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9@#$%^&+=]*$/',
        message: "Password can only contain letters, numbers, and special characters @, #, $, %, ^, &, +, ="
    )]
    public string $password;

    #[Assert\NotBlank(message: "Surname cannot be blank")]
    #[Assert\Length(min: 3, max: 60)]
    #[Assert\Regex("/^[а-яА-Я]+$/")]
    public string $surname;

    #[Assert\NotBlank(message: "Name cannot be blank")]
    #[Assert\Length(min: 3, max: 40)]
    #[Assert\Regex("/^[а-яА-Я]+$/")]
    public string $name;

    public function __construct(
        string $login,
        string $email,
        string $password,
        string $surname,
        string $name,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->login = $login;
        $this->email = $email;
        $this->password = $password;
        $this->surname = $surname;
        $this->name = $name;
    }
}
