<?php
// src/view/NavBuilder.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Page;

class NavBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $this->html .= '<nav class="nav_main"><ul>';
        $this->html .= $this->navMainHTML(Director::$menu_data, Director::$page_path->getArray());
        $this->html .= '</ul></nav>';
    }

    private function navMainHTML(Page $nav_data, array $current): string
    {
        $nav_html = '';
        static $level = 0;

        foreach($nav_data->getChildren() as $data)
        {
            $li_class = '';
            if(isset($current[$level]) && $data->getEndOfPath() === $current[$level]->getEndOfPath()){
                $li_class = 'current';
            }

            if($data->isReachable())
            {
                $target = '';
                if(str_starts_with($data->getEndOfPath(), 'http')) // lien vers autre site
                {
                    $link = $data->getEndOfPath(); // $link = chaine
                    $target = ' target="_blank"';
                }
                elseif($data->getEndOfPath() != '') // lien relatif
                {
                    $link = new URL(['page' => $data->getPagePath()]); // $link = objet
                }
                $nav_html .= '<a href="' . $link . '"' . $target . '>';
            }
            else{
                $nav_html .= '<a>';
            }
            
            if(count($data->getChildren()) > 0) // titre de catégorie
            {
                $li_class = $data->getParent() == null ? 'drop-down' : 'drop-right';
                
                $nav_html .= '<li class="'. $li_class . '"><p>' . $data->getPageName() . '</p><ul class="sub-menu">' . "\n";
                $level++;
                $nav_html .= $this->navMainHTML($data, $current);
                $level--;
                $nav_html .= '</ul></li>' . "\n";
            }
            else
            {
                $nav_html .= '<li class="'. $li_class . '"><p>' . $data->getPageName() . '</p></li>' . "\n";
            }
            
            $nav_html .= "</a>\n";
        }
        return $nav_html;
    }
}