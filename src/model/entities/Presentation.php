<?php
// src/model/entities/Presentation.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "presentation")]
class Presentation{
	#[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_presentation;

    // inverseBy fait le lien avec $presentation dans Node (qui a "mappedBy")
    #[ORM\OneToOne(targetEntity: Node::class, inversedBy: "presentation")]
    #[ORM\JoinColumn(name: "node_id", referencedColumnName: "id_node")]
    private ?Node $node;

    #[ORM\Column(type: "json")]
    private array $data;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $presentation;

    #[ORM\Column(type: "boolean", length: 255, nullable: true)]
    private ?bool $chrono_order = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $grid_cols_min_width = null; // pour le mode grille

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $pagination_limit = null; // pour les post_block et news_block

    private int $nb_pages = 1;

    public function __construct(array $data, Node $node
        //, ?string $presentation = null, ?bool $chrono_order = null
    ){
        $this->data = $data;
        $this->node = $node;
        /*if(!empty($presentation) && $presentation === 'grid'){
            $this->grid_cols_min_width = 250;
        }
        $this->chrono_order = $chrono_order ?? null;*/
    }

    /*public function getId(): int
    {
        return $this->id_presentation;
    }*/

    public function setNode(?Node $node): void
    {
    	$this->node = $node;
    }

    // un trait pour ça
    public function getData(): array
    {
        return $this->data;
    }
    public function updateData(string $key, string|int|bool|array $value = ''): void
    {
        if($value !== ''){
            $this->data[$key] = $value;
        }
        // si $value est vide, supprime la clé
        elseif(isset($this->data[$key])){
            unset($this->data[$key]);
        }
    }

    // spécifique aux blocs contenant des articles
    public function getPresentation(): ?string
    {
        return $this->presentation;
    }
    public function setPresentation(string $presentation): void
    {
        $this->presentation = $presentation;
    }
    public function getColsMinWidth(): int
    {
        $default = 320; // pixels
        return $this->grid_cols_min_width === null ? $default : $this->grid_cols_min_width;
    }
    public function setColsMinWidth(int $columns): void
    {
        $this->grid_cols_min_width = $columns;
    }
    public function getChronoOrder(): bool
    {
        return $this->chrono_order ?? false;
    }
    public function setChronoOrder(bool $reverse_order): void
    {
        $this->chrono_order = $reverse_order;
    }

    public function getPaginationLimit(): ?int
    {
        $default = 12; // si 0 pas de pagination, 12 rend bien avec des grilles de 2, 3 ou 4 colonnes
        return $this->pagination_limit === null ? $default : $this->pagination_limit;
    }
    public function setPaginationLimit(int $pagination_limit): void
    {
        $this->pagination_limit = $pagination_limit;
    }
    public function getNumberOfPages(): int
    {
        return $this->nb_pages;
    }
    public function setNumberOfPages(int $nb_pages): void
    {
        $this->nb_pages = $nb_pages;
    }


    /* -- blocs et présentations disponibles dans l'application -- */
    const BLOCKS = ['post_block' => 'Articles libres', 'news_block' => 'Actualités',
        //'galery' => 'Galerie',
        'calendar' => 'Calendrier', 'form' => 'Formulaire'
    ];
    const PRESENTATIONS = ['fullwidth' => 'Pleine largeur', 'grid' => 'Grille', 'mosaic' => 'Mosaïque'
        //, 'carousel' => 'Carrousel'
    ];
    static public function hasPresentation(string $block): bool
    {
        return in_array($block, ['post_block', 'news_block']) ? true : false;
    }
}