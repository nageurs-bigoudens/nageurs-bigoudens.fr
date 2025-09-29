<?php
// src/model/Blocks.php

class Blocks{
    static public array $blocks = ['post_block' => 'Articles libres', 'news_block' => 'Actualités',
        //'galery' => 'Galerie',
        'calendar' => 'Calendrier', 'form' => 'Formulaire'];

    static public array $presentations = ['fullwidth' => 'Pleine largeur', 'grid' => 'Grille', 'mosaic' => 'Mosaïque'
        //, 'carousel' => 'Carrousel'
    ];

    static public function hasPresentation(string $block): bool
    {
        return in_array($block, ['post_block', 'news_block']) ? true : false;
    }
}