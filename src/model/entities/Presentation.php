<?php
// src/model/entities/Presentation.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "presentation")]
class Presentation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_presentation;

    #[ORM\Column(type: "string", length: 255)]
    private string $name_presentation;

    public function __construct(string $name)
    {
        $this->name_presentation = $name;
    }

    public function getName(): string
    {
        return $this->name_presentation;
    }
}