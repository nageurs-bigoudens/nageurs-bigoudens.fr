<?php
// src/model/entities/Asset.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "asset")]
class Asset
{
    const PATH = 'assets/';
    const USER_PATH = 'user_data/assets/';
    // choisir un répertoire du genre /var/www/html/uploads/? ou au moins hors de /src? j'en sais rien

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_asset;

    #[ORM\Column(type: "string", length: 255)] // nom de fichier modifié avec uniqid (fichiers différents de même nom)
    private string $file_name;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private string $mime_type; // image/jpeg, image/png, etc

    #[ORM\Column(type: "string", length: 64, unique: true)] // doctrine n'a pas d'équivalent au type CHAR des BDD (on voulait CHAR(64)), c'est pas grave!
    private string $hash; // pour détecter deux fichiers identiques, même si leurs noms et les métadonnées changent

    #[ORM\OneToMany(mappedBy: 'asset', targetEntity: NodeDataAsset::class)]
    private Collection $nda_collection;

    public function __construct(string $name, string $mime_type, string $hash)
    {
        $this->file_name = $name;
        $this->mime_type = $mime_type;
        $this->hash = $hash;
    }

    public function getFileName(): string
    {
        return $this->file_name;
    }
    public function setFileName(string $name): void
    {
        $this->file_name = $name;
    }
    public function getMimeType(): string
    {
        return $this->mime_type;
    }
    public function setMimeType(string $mime_type): void
    {
        $this->mime_type = $mime_type;
    }
    public function getHash(): string
    {
        return $this->hash;
    }

    public function getNodeDataAssets(): Collection
    {
        return $this->nda_collection;
    }
}
