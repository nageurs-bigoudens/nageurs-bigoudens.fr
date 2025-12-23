<?php
// src/model/entities/Email.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "email")]
class Email
{
    // en mois
    const DEFAULT_RETENTION_PERIOD = 36; // 3 ans, justification = prospection, durée "glissante", date de suppression remise à jour à chaque nouvel e-mail
    const DEFAULT_RETENTION_PERIOD_SENSITIVE = 60; // 5 ans pour données sensibles ou litige, durée de preuve légale, durée non glissante

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_email;

    #[ORM\Column(type: "string", length: 255)]
    private string $sender_name;

    #[ORM\Column(type: "string", length: 320)]
    private string $sender_address;

    #[ORM\Column(type: "string", length: 320)]
    private string $recipient;

    // inutile, objet = 'Message envoyé par ' . $name . ' (' . $email . ') depuis le site web'
    /*#[ORM\Column(type: "text")]
    private string $subject;*/

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTime $date_time;

    #[ORM\Column(type: 'boolean')]
    private bool $is_sensitive; // "sensitive" tout court est un mot réservé

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTime $last_contact_date;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $is_sensitive_since;

    #[ORM\ManyToOne(targetEntity: NodeData::class)]
    #[ORM\JoinColumn(name: "node_data_id", referencedColumnName: "id_node_data", nullable: true)]
    private ?NodeData $node_data;

    public function __construct(string $sender_name, string $sender_address, string $recipient, string $content, NodeData $node_data, bool $sensitive = false){
        $this->sender_name = strtolower($sender_name);
        $this->sender_address = strtolower($sender_address);
        $this->recipient = strtolower($recipient);
        $this->content = $content;
        $this->date_time = new \DateTime;
        $this->last_contact_date = new \DateTime;
        $this->node_data = $node_data;
        $this->makeSensitive($sensitive);
    }

    public function getId(): int
    {
        return $this->id_email;
    }
    public function getSenderName(): string
    {
        return $this->sender_name;
    }
    public function getSenderAddress(): string
    {
        return $this->sender_address;
    }
    public function getRecipient(): string
    {
        return $this->recipient;
    }
    public function getContent(): string
    {
        return $this->content;
    }
    public function getDateTime(): \DateTime
    {
        return $this->date_time;
    }
    /*public function getLastContactDate(): \DateTime
    {
        return $this->last_contact_date;
    }*/
    public function isSensitive(): bool
    {
        return $this->is_sensitive;
    }
    public function isSensitiveSince(): ?\DateTime
    {
        return $this->is_sensitive_since;
    }

    public function makeSensitive(bool $sensitive = true): void
    {
        $this->is_sensitive = $sensitive;
        if($sensitive && $this->is_sensitive_since === null){
            $this->is_sensitive_since = new \DateTime();
        }
    }

    public function updateLastContactDate(): void
    {
        $this->last_contact_date = new \DateTime;
    }

    // la durée de conservation $period est propre au bloc formulaire (NodeData)
    // la date de dernier contact
    public function getDeletionDate(): \DateTime
    {
        // deux tests:
        // => e-mail associé à un formulaire?
        // => ce formulaire dispose d'une durée de stockage spécifique?
        $period = $this->node_data === null ? null : ($this->node_data->getData()['retention_period'] ?? null);

        $period = (int)$period;
        if($period === null || $period <= 0){
            $period = $this->is_sensitive ? self::DEFAULT_RETENTION_PERIOD_SENSITIVE : self::DEFAULT_RETENTION_PERIOD;
        }

        $date = $this->is_sensitive ? (clone $this->is_sensitive_since) : (clone $this->last_contact_date); // oui durée 5 ans, non durée 3 ans "glissante"
        // erreur si "sensible" mais sans date disponible (pas censé arriver)

        return $date->modify('+ ' . (string)$period . ' month');
    }
}