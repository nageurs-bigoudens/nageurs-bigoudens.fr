<?php
// src/view/NavBuilder.php
//
// menu principal

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Page;

class NavBuilder extends AbstractBuilder
{
    public function __construct(Node $node = null)
    {
        $this->html .= '<nav class="nav_main"><ul>';
        $this->html .= $this->navMainHTML(
            Model::$menu_data,
            // param nullable, ça retire une dépendance stricte entre NavBuilder et Model
            Model::$page_path != null ? Model::$page_path->getArray() : []);
        $this->html .= '</ul></nav>';
    }

    private function navMainHTML(Page $nav_data, array $current): string
    {
        $nav_html = '';
        static $level = 0;

        foreach($nav_data->getChildren() as $data)
        {
            if(!$data->isHidden()){
                $li_class = '';
                if(isset($current[$level]) && $data->getEndOfPath() === $current[$level]->getEndOfPath()){
                    $li_class = 'current ';
                }

                $link = '';
                if($data->isReachable()) // titre de catégorie du menu non clicable
                {
                    if(str_starts_with($data->getEndOfPath(), 'http')) // lien vers autre site
                    {
                        $link .= '<a href="' . htmlspecialchars($data->getEndOfPath()) . '" target="_blank">';
                    }
                    elseif($data->getEndOfPath() != '') // lien relatif
                    {
                        $link .= '<a href="' . new URL(['page' => $data->getPagePath()]) . '">';
                    }
                }
                else{
                    $link .= '<a>';
                }
                
                if(count($data->getChildren()) > 0) // titre de catégorie
                {
                    $li_class .= $data->getParent() == null ? 'drop-down' : 'drop-right';
                    
                    $nav_html .= '<li class="'. $li_class . '">' . $link . '<p id="m_' . $data->getId() . '">' . $data->getPageName() . '</p></a>
                            <button class="sub-menu-toggle" aria-label="Ouvrir le sous-menu">▼</button>
                        <ul class="sub-menu">' . "\n";
                    $level++;
                    $nav_html .= $this->navMainHTML($data, $current);
                    $level--;
                    $nav_html .= '</ul></li>' . "\n";
                }
                else
                {
                    $nav_html .= '<li class="'. $li_class . '">' . $link . '<p id="m_' . $data->getId() . '">' . $data->getPageName() . '</p></a></li>' . "\n";
                }
            }
        }
        return $nav_html;
    }
}