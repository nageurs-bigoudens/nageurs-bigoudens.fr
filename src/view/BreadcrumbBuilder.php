<?php
// src/view/BreadcrumbBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class BreadcrumbBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $this->html = $this->breadcrumbHTML();
    }

    private function breadcrumbHTML(): string
    {
        $asset = 'assets/home.svg'; // => BDD?
        $breadcrumb_array = Model::$page_path->getArray(); // tableau de Page
        $html = '';
        $nb_of_entries = count($breadcrumb_array);
        
        if($nb_of_entries > 1)
        {
            // petite maison et flèche
            $html .= '<nav class="breadcrumb" aria-label="Breadcrumb">' . "\n";
            $html .= '<a href="' . new URL . '"><img src="' . $asset . '"></a><span class="arrow"> →</span>' . "\n";

            // partie intermédiaire (pas de lien sur le dernier élément)
            for($i = 0; $i < ($nb_of_entries - 1); $i++)
            {
                // liens optionnels
                if($breadcrumb_array[$i]->isReachable())
                {
                    $html .= '<a href="' . new URL(['page' => $breadcrumb_array[$i]->getPagePath()]) . '">';
                }
                $html .= '<span>' . $breadcrumb_array[$i]->getPageName() . '</span>';
                if($breadcrumb_array[$i]->isReachable())
                {
                    $html .= '</a>';
                }
                $html .= '<span class="arrow"> →</span>' . "\n";
            }

            // fin du chemin (=> Thésée)
            $html .= '<span id="thesee" aria-current="page">' . $breadcrumb_array[$nb_of_entries - 1]->getPageName() . "</span>\n";
            $html .= "</nav>\n";
        }
        return $html;
    }
}