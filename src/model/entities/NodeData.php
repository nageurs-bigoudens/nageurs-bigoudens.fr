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

    // liaison avec table intermédiaire
    #[ORM\ManyToMany(targetEntity: Image::class, inversedBy: "node_data")]
    #[ORM\JoinTable(
        name: TABLE_PREFIX . "node_image",
        joinColumns: [new ORM\JoinColumn(name: "node_data_id", referencedColumnName: "id_node_data", onDelete: "CASCADE")],
        inverseJoinColumns: [new ORM\JoinColumn(name: "image_id", referencedColumnName: "id_image", onDelete: "CASCADE")]
    )]
    private Collection $images;

    public function __construct(array $data, Node $node, Collection $images = new ArrayCollection)
    {
        $this->data = $data;
        $this->node = $node;
        $this->images = $images;
    }

    public function getId(): int
    {
        return $this->id_node_data;
    }
    public function getData(): array
    {
        return $this->data;
    }
    /*public function setData(array $data): void
    {
        $this->data = $data;
    }*/
    public function updateData(string $key, string $value = ''): void
    {
        if($value !== ''){
            $this->data[$key] = $value;
        }
        // si $value est vide, supprime la clé
        elseif(isset($this->data[$key])){
            unset($this->data[$key]);
        }
    }
    /*public function setNode(Node $node): void
    {
        $this->node = $node;
    }*/
    public function getImages(): Collection
    {
        return $this->images;
    }
    public function setImages(Collection $images): void
    {
        $this->images = $images;
    }
}
