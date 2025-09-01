<?php
// src/model/entities/Presentation.php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: TABLE_PREFIX . "presentation")]
class Presentation
{
    static public array $option_list = ['fullwidth' => 'Pleine largeur', 'grid' => 'Grille', 'mosaic' => 'Mosaïque', 'carousel' => 'Carrousel'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_presentation;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    public function __construct(string $name)
    {
        $this->name = array_keys(self::$option_list)[0]; // = fullwidth, sécurité option inconnue
        foreach(self::$option_list as $key => $value){
            if($name === $key){
                $this->name = $name;
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    static public function findPresentation(EntityManager $entityManager, string $name): ?self
    {
        return $entityManager
            ->createQuery('SELECT p FROM App\Entity\Presentation p WHERE p.name = :name')
            ->setParameter('name', $name)
            ->getOneOrNullResult();
    }
}