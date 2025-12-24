<?php
// src/model/entities/NodeDataAsset.php
//
// entité intermédiaire 3 colonnes conçue selon le principe "slot <=> ressource unique" (paires node_data/role uniques)
// doctrine gère mal les clés primaires triples, j'ai donc choisi une clé primaire double node_data_id/role

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . 'asset_employment')]
class AssetEmployment
{
    // clé primaire double
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: NodeData::class, inversedBy: 'nda_collection')]
    #[ORM\JoinColumn(name: 'node_data_id', referencedColumnName: 'id_node_data', onDelete: 'CASCADE')]
    private NodeData $node_data;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $role;

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id_asset', onDelete: 'CASCADE')]
    private Asset $asset;

    public function __construct(NodeData $node_data, Asset $asset, string $role){
        $this->node_data = $node_data;
        $this->asset = $asset;
        $this->role = $role;
    }

    /*public function getNodeData(): NodeData
    {
        return $this->node_data;
    }*/
    public function getAsset(): Asset
    {
        return $this->asset;
    }
    public function setAsset(Asset $asset): self
    {
        $this->asset = $asset;
        return $this;
    }
    public function getRole(): string
    {
        return $this->role;
    }
}
