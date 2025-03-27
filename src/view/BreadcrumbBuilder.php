<?php
// src/view/BreadcrumbBuilder.php

use App\Entity\Node;

class BreadcrumbBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $this->html = $this->breadcrumbHTML(false);
    }

    private function breadcrumbHTML(bool $links = false): string
    {
        $asset = 'assets/home.svg'; // => BDD?
        $breadcrumb_array = Director::$page_path->getArray(); // tableau de Page
        $html = '';
        $nb_of_entries = count($breadcrumb_array);
        
        if($nb_of_entries > 1)
        {
            // petite maison et flèche
            $html .= '<nav class="breadcrumb" aria-label="Breadcrumb">' . "\n";
            $html .= '<a href="' . new URL . '"><img src="' . $asset . '"></a><span class="arrow"> →</span>' . "\n";

            // partie intermédiaire
            for($i = 0; $i < ($nb_of_entries - 1); $i++)
            {
                // liens optionnels
                if($links)
                {
                    $html .= '<a href="';
                    for($j = 1; $j < $i; $j++) // chemin sans la fin
                    {
                        $html .= new URL(['page' => $breadcrumb_array[$i]->getPagePath()]); 
                    }
                    $html .= '">';
                }
                $html .= '<span>' . $breadcrumb_array[$i]->getPageName() . '</span>';
                if($links)
                {
                    $html .= '</a>';
                }
                $html .= '<span class="arrow"> →</span>' . "\n";
            }

            // fin du chemin
            $html .= '<span aria-current="page">' . $breadcrumb_array[$nb_of_entries - 1]->getPageName() . "</span>\n";
            $html .= "</nav>\n";
        }
        return $html;
    }
}