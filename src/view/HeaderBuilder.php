<?php
// src/view/HeaderBuilder.php

use App\Entity\Node;

class HeaderBuilder extends AbstractBuilder
{
    private ?Node $nav = null;

    public function __construct(Node $node)
    {
        // nav
        // n'utilise pas useChildrenBuilder, il faudrait peut-être
        $children = $node->getChildren();
        foreach($children as $child)
        {
            if($child->getName() === 'nav')
            {
                $this->nav = $child;
                $nav_builder = new NavBuilder($this->nav);
                $nav = $nav_builder->render();
            }
        }
    	
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            // titre et description
            if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }

            // attributs, aucun pour l'instant
            if(!empty($node->getAttributes()))
            {
                extract($node->getAttributes());
            }

            // header logo + réseaux sociaux
            $targets = ['logo', 'facebook', 'instagram', 'fond_piscine'];
            $i = 0;
            foreach($node->getNodeData()->getImages() as $image)
            {
                if(str_contains($image->getFileName(), $targets[$i]))
                {
                    $var = $targets[$i];
                    $$var = rtrim($image->getFilePathMini(), '/');
                    $var .= '_alt'; // ex: logo_alt
                    $$var = $image->getAlt();
                }
                $i++;
            }

            // générer HTML réseaux sociaux
            //

            ob_start();
            require $viewFile;
            $this->html .= ob_get_clean();
        }
    }
}