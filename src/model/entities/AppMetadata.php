<?php
// src/model/entities/AppMetadata.php

// comme dans AppMode, prévoir d'ajouter des champs "since" et "by" (qui a changé quoi quel jour?)

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "app_metadata")]
class AppMetadata
{
    #[ORM\Id]
    #[ORM\Column(type: "string", length: 100)]
    private string $key_name;

    #[ORM\Column(type: "string")]
    private string $value;

    public function __construct(string $key, string $value)
    {
        $this->key_name = $key;
        $this->value = $value;
    }

    public function getKey(): string
    {
    	return $this->key_name;
    }
    public function getValue(): string
    {
    	return $this->value;
    }

    public function setKey(string $key): void
    {
        $this->key_name = $key;
    }
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}