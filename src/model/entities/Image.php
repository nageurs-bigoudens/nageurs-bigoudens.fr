<?php
// src/model/entities/Image.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "image")]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_image;

    #[ORM\Column(type: "string", length: 255, unique: true)] // nom d'image UNIQUE
    private string $file_name;

    // choisir un répertoire du genre /var/www/html/uploads/ de préférence hors de /src
    #[ORM\Column(type: "string", length: 255, unique: true, nullable: true)]
    private ?string $file_path;

    #[ORM\Column(type: "string", length: 255, unique: true, nullable: true)]
    private ?string $file_path_mini;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private string $mime_type; // image/jpeg, image/png, etc

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private string $alt; // texte alternatif

    // autre champs optionnels: file_size, date (default current timestamp)

    /* étapes au téléchargement:
    => Validation du type de fichier : On vérifie que le fichier est bien une image en utilisant le type MIME. On peut aussi vérifier la taille du fichier.
    => Création d'un répertoire structuré : On génère un chemin dynamique basé sur la date (uploads/2024/12/24/) pour organiser les images.
    => Génération d'un nom de fichier unique : On utilise uniqid() pour générer un nom unique et éviter les conflits de nom.
    => Déplacement du fichier sur le serveur : Le fichier est déplacé depuis son emplacement temporaire vers le répertoire uploads/.
    => Enregistrement dans la base de données : On enregistre les informations de l'image dans la base de données. */

    #[ORM\ManyToMany(targetEntity: NodeData::class, mappedBy: "images")]
    private $node_data;

    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: "images")]
    private $article;

    public function __construct(string $name, ?string $path, ?string $path_mini, string $mime_type, string $alt)
    {
        $this->file_name = $name;
        $this->file_path = $path;
        $this->file_path_mini = $path_mini;
        $this->mime_type = $mime_type;
        $this->alt = $alt;
    }

    public function getFileName(): string
    {
        return $this->file_name;
    }
    public function getFilePath(): string
    {
        return $this->file_path;
    }
    public function getFilePathMini(): string
    {
        return $this->file_path_mini;
    }
    public function getAlt(): string
    {
        return $this->alt;
    }


    // pour ViewBuilder?
    /*public function displayImage($imageId): void
    {
        //$imageId = 1; // Exemple d'ID d'image
        $stmt = $pdo->prepare("SELECT file_path FROM images WHERE id = ?");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch();

        if ($image) {
            echo "<img src='" . $image['file_path'] . "' alt='Image'>";
        } else {
            echo "Image non trouvée.";
        }
    }*/
}
