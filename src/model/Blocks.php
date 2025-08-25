<?php
// src/Blocks.php

class Blocks{
    /*private array $types = ['blog', 'grid', 'calendar', 'galery', 'form'];*/
    static private array $types = ['post_block', 'news_block', 'calendar', 'galery', 'form'];

    /*private array $names = ['Blog', 'Grille', 'Calendrier', 'Galerie', 'Formulaire'];*/
    static private array $names = ['Articles libres', 'Actualités', 'Calendrier', 'Galerie', 'Formulaire'];

    static public function getNameList(): array
    {
        $blocks = [];
        foreach(self::$types as $type){
            $blocks[] = $type;
        }
        return $blocks;
    }

    static public function getTypeNamePairs(): array
    {
        $blocks = [];
        for($i = 0; $i < count(self::$types); $i++){
            $blocks[] = ['type' => self::$types[$i], 'name' => self::$names[$i]];
        }
        return $blocks;
    }

    static public function getNameFromType(string $type): string
    {
        for($i=0; $i < count(self::$types); $i++){ 
            if(self::$types[$i] === $type){
                return self::$names[$i];
            }
        }
        return 'server side error';
    }
}