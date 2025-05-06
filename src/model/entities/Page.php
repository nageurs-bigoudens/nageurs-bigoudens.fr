<?php
// src/model/entities/Page.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "page")]
class Page
{
    use \Position;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_page;
    
    #[ORM\Column(type: "string", length: 255)]
    private string $name_page;

    #[ORM\Column(type: "string", length: 255)]
    private string $end_of_path; // morceau d'URL plus exactement

    private string $page_path;

    #[ORM\Column(type: "boolean")]
    private bool $reachable;

    #[ORM\Column(type: "boolean")]
    private bool $in_menu;

    #[ORM\Column(type: "boolean")]
    private bool $hidden;

    #[ORM\Column(type: "integer", nullable: true)] // null si hors menu
    private ?int $position;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: "parent_id", referencedColumnName: "id_page", onDelete: "SET NULL", nullable: true)]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected Collection $children;
    
    /*#[ORM\Column(type: "json", nullable: true)]
    private ?array $metadata = null;*/

    public function __construct(string $name, string $eop, bool $reachable, bool $in_menu, bool $hidden, ?int $position, ?Page $parent)
    {
        $this->name_page = $name;
        $this->end_of_path = $eop;
        $this->reachable = $reachable;
        $this->in_menu = $in_menu;
        $this->hidden = $hidden;
        $this->position = $position;
        $this->parent = $parent;
        $this->children = new ArrayCollection();
    }
    
    // getters/setters
    public function getId(): int
    {
        return $this->id_page;
    }
    public function getPageName(): string
    {
        return $this->name_page;
    }
    public function getPagePath(): string
    {
        return $this->page_path;
    }
    public function setPagePath(string $path):void
    {
        $this->page_path = $path;
    }
    public function getEndOfPath(): string
    {
        return $this->end_of_path;
    }
    public function isReachable(): bool
    {
        return $this->reachable;
    }
    public function isInMenu(): bool
    {
        return $this->in_menu;
    }
    public function isHidden(): bool
    {
        return $this->hidden;
    }
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }
    public function getPosition(): ?int
    {
        return $this->position;
    }
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
    public function getParent(): ?Page
    {
        return $this->parent;
    }
    public function setParent(?Page $parent): void
    {
        $this->parent = $parent;
    }
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function fillChildrenPagePath(string $parent_path = ''): void
    {
        $this->page_path = $parent_path != '' ? $parent_path . '/' . $this->end_of_path : $this->end_of_path;
        foreach($this->getChildren() as $page){
            $page->fillChildrenPagePath($this->page_path);
        }
    }

    public function addChild(self $child): void
    {
        $this->children[] = $child;
        $this->sortChildren(false);
    }
    public function removeChild(self $child): void
    {
        $this->children->removeElement($child);
        $this->children = new ArrayCollection(array_values($this->children->toArray())); // réindexer en passant par un tableau
        $this->sortChildren(false);
    }

    public function findPageById(int $id): ?Page
    {
        $target = null;
        foreach($this->children as $page){
            if($page->getId() === $id){
                return $page;
            }
            if(count($page->getChildren()) > 0){
                $target = $page->findPageById($id);
                if($target !== null){
                    return $target;
                }
            }
        }
        return $target;
    }
}
