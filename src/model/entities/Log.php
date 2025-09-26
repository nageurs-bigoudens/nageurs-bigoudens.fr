<?php
// src/model/entities/Log.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "log")]
class Log
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_log;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])] // CURRENT_TIMESTAMP "inutile", date générée par PHP
    private \DateTime $date_time; // type datetime de doctrine <=> type \DateTime de PHP

    #[ORM\Column(type: "boolean")]
    private bool $success;

    public function __construct(bool $success){
        $this->date_time = new \DateTime;
        $this->success = $success;
    }
}
