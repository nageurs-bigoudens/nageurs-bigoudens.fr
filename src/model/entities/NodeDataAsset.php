<?php
// src/model/entities/NodeDataAsset.php
//
// entité intermédiaire avec 3 colonnes

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . 'nodedata_asset')]
#[ORM\UniqueConstraint(name: 'unique_role_per_node', columns: ['node_data_id', 'role'])] // un rôle UNIQUE pour chaque node_data_id, excellent!
class NodeDataAsset
{
    // clé primaire double
    // inconvénient: impossible d'utiliser deux fois la même paire node_data/asset, même pour des rôles différents
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: NodeData::class, inversedBy: 'nda_collection')]
    #[ORM\JoinColumn(name: 'node_data_id', referencedColumnName: 'id_node_data', onDelete: 'CASCADE')]
    private NodeData $node_data;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id_asset', onDelete: 'CASCADE')]
    private Asset $asset;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role;

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
