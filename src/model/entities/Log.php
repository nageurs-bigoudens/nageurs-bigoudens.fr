<?php
// src/model/entities/Log.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "log")]
class Log
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_log;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    //#[ORM\Column(type: 'datetime', columnDefinition: "TIMESTAMP DEFAULT CURRENT_TIMESTAMP")]
    private ?\DateTime $date_time ; // le type datetime de doctrine convertit en type \DateTime de PHP

    #[ORM\Column(type: "boolean")]
    private bool $success;

    public function __construct(bool $success){
        $this->date_time = new \DateTime();
        $this->success = $success;
    }
}
