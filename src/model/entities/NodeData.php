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
    private ?Node $node;

    #[ORM\Column(type: "json")]
    private array $data;

    #[ORM\OneToMany(mappedBy: 'node_data', targetEntity: AssetEmployment::class, cascade: ['persist', 'remove'])]
    private Collection $asset_employment;

    private array $emails = []; // => noeud "show_emails"

    public function __construct(array $data, Node $node, Collection $asset_employment = new ArrayCollection){
        $this->data = $data;
        $this->node = $node;
        $this->asset_employment = $asset_employment;
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
    
    public function setNode(?Node $node): void
    {
        $this->node = $node;
    }

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

    // pour affichage page Courriels
    public function getEmails(): array // appelée dans ShowEmailsBuilder
    {
        return $this->emails;
    }
    public function setEmails(array $emails): void // appelée dans Model
    {
        $this->emails = $emails;
    }
}