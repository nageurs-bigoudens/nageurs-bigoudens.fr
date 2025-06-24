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

    public function __construct(array $json){
        $this->securedUpdateFromJSON($json);
    }

    public function securedUpdateFromJSON(array $json): void
    {
        $this->title = htmlspecialchars($json['title']);
        $this->description = htmlspecialchars($json['description']);
        try{
            $this->start = new \Datetime($json['start']);
            $this->end = new \Datetime($json['end']);
        }
        catch(\Exception $e){
            throw new \InvalidArgumentException('Bad date input');
        }
        $all_day = filter_var($json['allDay'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if(!is_bool($all_day)){
            throw new \InvalidArgumentException('Bad checkbox input');
        }
        $this->all_day = $all_day;
        $this->color = isset($json['color']) ? htmlspecialchars($json['color']) : null;
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
