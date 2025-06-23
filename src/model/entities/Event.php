<?php
// src/model/entities/Event.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)] // type varchar(255)
    private string $title;
    // contrôle JS: if(title.length > 255)

    #[ORM\Column(type: 'text')]
    private string $description = '';
    // chatgpt: Dans un contexte API/Front comme FullCalendar,
    // préférer une chaîne vide à une varaible "null" peut être plus pratique,
    // car ça évite des contrôles côté JS.

    #[ORM\Column(type: 'datetime')] // chatgpt: Doctrine suppose UTC si pas de configuration spécifique
    private \DateTimeInterface $start; // typage possible avec une interface,
    //chatgpt: choix \DateTime par défaut, autorise \DateTimeImmutable

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $end;

    #[ORM\Column(type: 'boolean')]
    private bool $all_day;

    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    private ?string $color = null;

    public function __construct(string $title, string|\DateTimeInterface $start, string|\DateTimeInterface $end, bool $all_day, string $description = '', string $color = null){
        $this->title = $title;
        $this->description = $description;
        $this->start = gettype($start) === 'string' ? new \DateTime($start) : $start;
        $this->end = gettype($end) === 'string' ? new \DateTime($end) : $end;
        $this->all_day = $all_day;
        $this->color = $color;
    }

    public function updateFromJSON(array $json): void
    {
        $this->title = $json['title'];
        $this->description = $json['description'];
        $this->start = new \DateTime($json['start']);
        $this->end = new \DateTime($json['end']);
        $this->all_day = $json['allDay'];
        $this->color = $json['color'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
    /*public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }*/

    public function getDescription(): string
    {
        return $this->description;
    }
    /*public function setDescription(string $description = ''): self
    {
        $this->description = $description;
        return $this;
    }*/

    public function getStart(): \DateTimeInterface
    {
        return $this->start;
    }
    /*public function setStart(\DateTimeInterface $start): self
    {
        $this->start = $start;
        return $this;
    }*/

    public function getEnd(): \DateTimeInterface
    {
        return $this->end;
    }
    /*public function setEnd(\DateTimeInterface $end): self
    {
        $this->end = $end;
        return $this;
    }*/

    public function isAllDay(): bool
    {
        return $this->all_day;
    }
    /*public function setAllDay(bool $all_day): self
    {
        $this->all_day = $all_day;
        return $this;
    }*/

    public function getColor(): ?string
    {
        return $this->color;
    }
    /*public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }*/
}
