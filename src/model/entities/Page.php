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

    private string $page_path = '';

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $css = null;

    #[ORM\Column(type: "json", nullable: true)]
    private ?array $js = null;

    static private array $default_css = ['body', 'head', 'nav', 'foot'];
    static private array $default_js = ['main'];

    #[ORM\Column(type: "boolean")]
    private bool $reachable;

    #[ORM\Column(type: "boolean")]
    private bool $in_menu;

    #[ORM\Column(type: "boolean")]
    private bool $hidden;

    #[ORM\Column(type: "integer", nullable: true)] // null si hors menu
    private ?int $position;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: "children")]
    #[ORM\JoinColumn(name: "parent_id", referencedColumnName: "id_page", onDelete: "SET NULL", nullable: true)]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    protected Collection $children;
    
    /*#[ORM\Column(type: "json", nullable: true)]
    private ?array $metadata = null;*/

    public function __construct(string $name, string $eop, string $description, bool $reachable, bool $in_menu, bool $hidden, ?int $position, ?Page $parent)
    {
        $this->name_page = $name;
        $this->end_of_path = $eop;
        $this->description = $description;
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
    public function setPageName(string $name): void
    {
        $this->name_page = $name;
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
    public function setEndOfPath(string $end_of_path):void
    {
        $this->end_of_path = $end_of_path;
    }
    public function getDescription(): string
    {
        return $this->description;
    }
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCSS(): array
    {
        return array_merge(self::$default_css, $this->css ?? []);
    }
    public function addCSS(string $css): void
    {
        if(!in_array($css, $this->css ?? [])){
            $this->css[] = $css;
        }
    }
    public function removeCSS(string $css): void
    {
        // array_diff renvoie une copie du 1er tableau alégée des élements existants aussi dans le 2è, array_values réindexe
        $this->css = array_values(array_diff($this->css, [$css]));
    }
    public function getJS(): array
    {
        return array_merge(self::$default_js, $this->js ?? []);
        //UPDATE `nb_page` SET `js` = NULL WHERE JSON_EQUALS(`js`, '["main"]');
    }
    public function addJS(string $js): void
    {
        if(!in_array($js, $this->js ?? [])){
            $this->js[] = $js;
        }
    }
    public function removeJS(string $js): void
    {
        $this->js = array_values(array_diff($this->js, [$js]));
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

    // utilisée par $menu_path
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
