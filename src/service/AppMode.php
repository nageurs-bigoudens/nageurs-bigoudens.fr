<?php
// src/service/AppMode.php

// comme dans AppMetadata, prévoir d'ajouter des champs "since" et "by" (qui a changé quoi quel jour?)

declare(strict_types=1);

use App\Entity\AppMetadata;
use Doctrine\ORM\EntityManager;

class AppMode
{
    private static string $mode;

    public static function load(EntityManager $entityManager): void
    {
        $metadata = $entityManager->getRepository(AppMetadata::class)->find('mode');
        if(!$metadata){
            self::$mode = 'maintenance';
        }
        else{
            self::$mode = $metadata->getValue();
        }
    }

    public static function is(string $mode): bool
    {
        return self::$mode === $mode;
    }

    public static function get(): string
    {
        return self::$mode;
    }

    public static function set(EntityManager $entityManager, string $mode): void
    {
        self::$mode = $mode;

        $metadata = $entityManager->find(AppMetadata::class, 'mode');
        if($metadata){
            $metadata->setValue($mode);
        }
        else{
            $metadata = new AppMetadata('mode', $mode);
            $entityManager->persist($metadata);
        }
        $entityManager->flush();

        /*self::$data = [
            'mode'  => $mode,
            'since' => (new DateTimeImmutable())->format('c'),
            'by'    => $by,
        ];*/
    }
}