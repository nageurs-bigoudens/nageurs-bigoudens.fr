<?php
// src/model/entities/Email.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "email")]
class Email
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_log;

    #[ORM\Column(type: "string", length: 320)]
    private string $sender;

    #[ORM\Column(type: "string", length: 320)]
    private string $recipient;

    // inutile, objet = 'Message envoyé par ' . $name . ' (' . $email . ') depuis le site web'
    /*#[ORM\Column(type: "text")]
    private string $subject;*/

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $date_time ; 

    public function __construct(string $sender, string $recipient, string $content){
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->content = $content;
        $this->date_time = new \DateTime();
    }
}
