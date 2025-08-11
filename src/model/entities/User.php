<?php
// src/model/entities/User.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "user")]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_user;

    /*#[ORM\Column(type: "string", length: 255)]
    private string $name;*/

    #[ORM\Column(type: "string", length: 255, unique: true)] // risque de modifier son mot de passe sans s'apercevoir qu'il fonctionne encore sur un autre compte
    private string $login;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    public function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    public function getLogin(): string
    {
        return $this->login;
    }
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}