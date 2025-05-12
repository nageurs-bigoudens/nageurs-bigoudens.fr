<?php
// src/model/entities/Article.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "article")]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_article;

    // datetime_immutable permet à la base de toujours gérer cette clé primaire correctement
    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'], unique: true)]
    private ?\DateTime $date_time; // le type datetime de doctrine convertit en type \DateTime de PHP

    #[ORM\Column(type: "string")]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $preview; // une simple textarea

    #[ORM\Column(type: "text")]
    private string $content; // de l'éditeur html

    // liaison avec table intermédiaire
    #[ORM\ManyToMany(targetEntity: Image::class, inversedBy: "article")]
    #[ORM\JoinTable(
        name: TABLE_PREFIX . "article_image",
        joinColumns: [new ORM\JoinColumn(name: "article_id", referencedColumnName: "id_article", onDelete: "CASCADE")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "image_id", referencedColumnName: "id_image", onDelete: "CASCADE")]
    )]
    private Collection $images;

    public function __construct(string $content, \DateTime $date_time = null, string $title = '', string $preview = '')
    {
        $this->date_time = $date_time;
        $this->title = $title;
        $this->preview = $preview;
        $this->content = $content;
        $this->images = new ArrayCollection(); // initialisation nécessaire
    }

    public function getDateTime(): \DateTime
    {
        return $this->date_time;
    }
    public function setDateTime(\DateTime $date_time): void
    {
        $this->date_time = $date_time;
    }
    public function getTimestamp(): int
    {
        return $this->date_time->getTimestamp();
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    public function getPreview(): string
    {
        return $this->preview;
    }
    public function setPreview(string $preview): void
    {
        $this->preview = $preview;
    }
    public function getContent(): string
    {
        return $this->content;
    }
    public function setContent(string $data): void
    {
        $this->content = $data;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }
}
