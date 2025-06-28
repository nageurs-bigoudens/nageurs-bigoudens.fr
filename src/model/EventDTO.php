<?php
// src/model/EventDTO.php
//
// classe de données JSONifiable compatible avec fullcalendar
// servira aussi pour l'import/export de fichiers .ics

declare(strict_types=1);

use App\Entity\Event;

class EventDTO
{
    public int $id;
    public string $title;
    public string $start;
    public string $end;
    public bool $allDay;
    public ?string $color;
    public string $description;

    public function __construct(Event $event)
    {
        $this->id = $event->getId();
        $this->title = $event->getTitle();
        $this->description = $event->getDescription() ?? ''; // renvoie $event->getDescription() si existe et ne vaut pas "null"
        $this->allDay = $event->isAllDay();
        $this->color = $event->getColor();

        if($this->allDay){
            $this->start = $event->getStart()->format('Y-m-d');
            $this->end = $event->getEnd()->format('Y-m-d');
        }
        else{
            $this->start = $event->getStart()->format('Y-m-d\TH:i:s\Z');
            $this->end = $event->getEnd()->format('Y-m-d\TH:i:s\Z');
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start' => $this->start,
            'end' => $this->end,
            'allDay' => $this->allDay,
            'color' => $this->color,
        ];
    }
}
