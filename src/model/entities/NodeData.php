<?php
// src/model/entities/NodeData.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection; // classe
use Doctrine\Common\Collections\Collection; // interface

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "node_data")]
class NodeData
{
    static array $social_networks = ['globe', 'facebook', 'instagram', 'whatsapp', 'snapchat', 'tiktok', 'linkedin', 'github']; // à completer
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_node_data;

    // onDelete: "CASCADE" supprime les données si le noeud est supprimé
    // inverseBy fait le lien avec $node_data dans Node (qui a "mappedBy")
    #[ORM\OneToOne(targetEntity: Node::class, inversedBy: "node_data")]
    #[ORM\JoinColumn(name: "node_id", referencedColumnName: "id_node", onDelete: "CASCADE")]
    private Node $node;

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

    #[ORM\OneToMany(mappedBy: 'node_data', targetEntity: AssetEmployment::class, cascade: ['persist', 'remove'])]
    private Collection $asset_employment;

    /*#[ORM\OneToMany(mappedBy: 'node_data', targetEntity: Email::class, cascade: ['persist', 'remove'])] // => noeud "form", inutilisé et conflit avec le tableau $emails
    private Collection $emails;*/

    private int $nb_pages = 1;
    private array $emails = []; // => noeud "show_emails"

    public function __construct(array $data, Node $node, Collection $asset_employment = new ArrayCollection, ?string $presentation = null, ?bool $chrono_order = null)
    {
        $this->data = $data;
        $this->node = $node;
        $this->asset_employment = $asset_employment;
        if(!empty($presentation) && $presentation === 'grid'){
            $this->grid_cols_min_width = 250;
        }
        $this->chrono_order = $chrono_order ?? null;
    }

    public function getId(): int
    {
        return $this->id_node_data;
    }
    public function getData(): array
    {
        return $this->data;
    }
    /*public function setData(array $data): void // entrée = tableau associatif
    {
        $this->data = $data;
    }*/
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
    
    /*public function setNode(Node $node): void
    {
        $this->node = $node;
    }*/


    public function getNodeDataAssets(): Collection
    {
        return $this->asset_employment;
    }
    public function getAssetEmploymentByRole(string $role): ?AssetEmployment
    {
        foreach($this->asset_employment as $nda){
            if($nda->getRole() === $role){
                return $nda;
            }
        }
        return null;
    }
    public function getAssetByRole(string $role): ?Asset
    {
        $nda = $this->getAssetEmploymentByRole($role);
        if($nda === null){
            return null;
        }
        return $nda->getAsset() ?? null;
    }

    public function getEmails(): array // appelée dans ShowEmailsBuilder
    {
        return $this->emails;
    }
    public function setEmails(array $emails): void // appelée dans Model
    {
        $this->emails = $emails;
    }
}