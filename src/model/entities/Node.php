<?php
// src/model/entities/Node.php

declare(strict_types=1);

namespace App\Entity;

use Config;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "node")]
class Node
{
    use \Position;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_node;

    #[ORM\Column(type: "string", length: 255)]
    private string $name_node;

    #[ORM\Column(type: "string", length: 255, unique: true, nullable: true)]
    private ?string $article_timestamp;

    #[ORM\Column(type: "json", nullable: true)] // type: "json" crée un longtext avec mariadb
    private ?array $attributes = null;

    #[ORM\Column(type: "integer")]
    private int $position;

    #[ORM\ManyToOne(targetEntity: self::class)]
    //#[ORM\ManyToOne(targetEntity: self::class, fetch: 'EAGER')] // À TESTER
    #[ORM\JoinColumn(name: "parent_id", referencedColumnName: "id_node", onDelete: "SET NULL", nullable: true)]
    private ?self $parent = null;

    // un onDelete: "CASCADE" serait logique ici mais dangereux, on pourrait aussi faire en sorte que les noeuds soient récupérables si la page est brutalement supprimée
    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(name: "page_id", referencedColumnName: "id_page", nullable: true)]
    private ?Page $page;

    #[ORM\ManyToOne(targetEntity: Article::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: "article_id", referencedColumnName: "id_article", onDelete: "CASCADE", nullable: true)] // supprimer le contenu d'un article supprime les noeuds associés, faux dans l'autre sens
    private ?Article $article = null;

    // propriété non mappée dans la table "node", la jointure est décrite dans NodeData
    // elle sert à persister ou supprimer des données par cascade
    // "mappedBy" permet de cibler $node dans l'autre classe, qui elle possède un "inversedBy"
    #[ORM\OneToOne(targetEntity: NodeData::class, mappedBy: "node", cascade: ['persist', 'remove'])]
    private ?NodeData $node_data = null;


    // -- fin des attributs destinés à doctrine, début du code utilisateur --
    
    private array $children = []; // tableau de Node
    private ?self $adopted = null; // = "new" est un enfant de "main" lorsque la page est "article"
    static private array $default_attributes = ['css_array' => ['body', 'head', 'nav', 'foot', 'calendar'],'js_array' => ['main']];

    public function __construct(string $name = '', ?string $article_timestamp = null, array $attributes = [], int $position = 0, ?self $parent = null, ?Page $page = null, ?Article $article = null)
    {
        $this->name_node = $name;
        $this->article_timestamp = $article_timestamp;
        $this->attributes = $attributes;
        $this->position = $position;
        $this->parent = $parent;
        $this->page = $page;
        $this->article = $article;
    }

    // pfff...
    public function getId(): int
    {
        return $this->id_node;
    }
    public function getName(): string
    {
        return $this->name_node;
    }
    /*public function setName(string $name): void
    {
        $this->name_node = $name;
    }*/
    public function getArticleTimestamp(): string
    {
        return $this->article_timestamp;
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    public function setDefaultAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }
    public function useDefaultAttributes(): void
    {
        $this->attributes = self::$default_attributes;
    }
    public function addAttribute(string $key, string $value): void
    {
        if(!isset($this->attributes[$key])) { // sécurité $key inexistante
            $this->attributes[$key] = [];
        }
        if(!in_array($value, $this->attributes[$key])){
            $this->attributes[$key][] = $value;
        }
    }
    /*public function removeAttribute(string $key, string $value): void
    {
        if(isset($this->attributes[$key]))  // sécurité $key inexistante
        {
            // supprime et réindex avec un nouveau tableau
            $tmp_array = $this->attributes[$key];
            $this->attributes[$key] = [];
            foreach($tmp_array as $entry){
                if($entry !== $value){
                    $this->attributes[$key][] = $entry;
                }
            }
        }
    }*/
    public function getParent(): ?self
    {
        return $this->parent;
    }
    /*public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }*/
    public function getPosition(): int
    {
        return $this->position;
    }
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
    public function getPage(): Page
    {
        return $this->page;
    }
    /*public function setPage(Page $page): void
    {
        $this->page = $page;
    }*/
    public function getArticle(): Article
    {
        return $this->article;
    }
    /*public function setArticle(Article $article): void
    {
        $this->article = $article;
    }*/
    public function getNodeData(): ?NodeData
    {
        return $this->node_data;
    }
    public function getChildren(): array
    {
        return $this->children;
    }
    public function addChild(self $child): void
    {
        $this->children[] = $child;
        $this->sortChildren(false);
    }
    
    public function removeChild(self $child): void
    {
        foreach($this->children as $key => $object){
            if($object->getId() === $child->getId()){
                unset($this->children[$key]);
            }
            break;
        }
        $this->children = array_values($this->children); // réindexer pour supprimer la case vide
    }

    public function getAdoptedChild(): ?self // peut renvoyer null
    {
        return $this->adopted;
    }
    public function setAdoptedChild(self $child): void
    {
        $this->adopted = $child;
    }
}
