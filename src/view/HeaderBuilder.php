<?php
// src/view/HeaderBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class HeaderBuilder extends AbstractBuilder
{
    private ?Node $nav = null;
    private ?Node $breadcrumb = null;

    public function __construct(Node $node)
    {
        // pas de useChildrenBuilder, il faudrait peut-être
        $children = $node->getChildren();
        foreach($children as $child)
        {
            if($child->getName() === 'nav'){
                $this->nav = $child;
                // actuellement le noeud nav ne contient aucune info utile et l'envoyer à NavBuilder est inutile
                $nav_builder = new NavBuilder($this->nav);
                
                $nav = $nav_builder->render();
            }
            elseif($child->getName() === 'breadcrumb'){
                $this->breadcrumb = $child;
                $breadcrumb_builder = new BreadcrumbBuilder($this->breadcrumb);
                $breadcrumb = $breadcrumb_builder->render();
            }
        }
    	
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            // titre et description
            // => retourne $titre, $description et le tableau associatif: $social
            if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }

            // attributs, aucun pour l'instant
            if(!empty($node->getAttributes()))
            {
                extract($node->getAttributes());
            }

            // réseaux sociaux + logo dans l'entête
            $keys = array_keys($social);
            $social_networks = '';
            $head_logo = '';
            foreach($node->getNodeData()->getImages() as $image)
            {
                for($i = 0; $i < count($keys); $i++)
                {
                    // réseaux sociaux
                    if(str_contains($image->getFileName(), $keys[$i])){
                        $social_networks .= '<a href="' . $social[$keys[$i]] . '" target="_blank" rel="noopener noreferrer">
                        <img src="' . rtrim($image->getFilePathMini(), '/') . '" alt="' . $keys[$i] . '_alt"></a>';
                        break;
                    }
                    // logo en-tête
                    /*if(str_contains($image->getFileName(), 'header_logo')){
                        $header_logo = rtrim($image->getFilePathMini(), '/');
                        break;
                    }*/
                    // image de fond
                    /*if(str_contains($image->getFileName(), 'header_background')){
                        $header_background = rtrim($image->getFilePath(), '/');
                        break;
                    }*/
                }
            }

            ob_start();
            require $viewFile;
            $this->html .= ob_get_clean();
        }
    }
}