<?php
// src/view/CalendarBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class CalendarBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        parent::__construct($node);
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }

            
            if($_SESSION['admin'])
            {
                

                // squelette d'un nouvel article ()
                /*ob_start();
                require self::VIEWS_PATH . 'article.php';
                $new_article = ob_get_clean();*/
            }

            // articles existants
            /*$this->useChildrenBuilder($node);
            $content = $this->html;*/

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
        }
    }
}