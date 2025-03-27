<?php
// src/view/BlogBuilder.php

use App\Entity\Node;

class BlogBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }

            // ajouter un article
            $new_article = '';
            $new_article_admin_buttons  = '';
            if($_SESSION['admin'])
            {
                $id = 'new';

                //$link = new URL(['page' => CURRENT_PAGE, 'action' => 'open_editor']);
                $js = 'onclick="openEditor(\'' . $id . '\')"';
                //$new_article = '<article><a href="' . $link . '"><button>Nouvel article</button></a></article>';
                $new_article = '<article><p id="new"></p>' . "\n" . 
                    '<p id="new-' . $id . '"><a href="#"><button ' . $js . '><img class="action_icon" src="assets/edit.svg">Nouvel article</button></a></p>';

                $close_js = 'onclick="closeEditor(\'' . $id . '\')"';
                $close_editor = '<div class="article_admin_zone"><p id="cancel-' . $id . '" class="hidden"><a href="#"><button ' . $close_js . '>Annuler</button></a></p>';
                
                $submit_js = 'onclick="submitArticle(\'' . $id . '\')"';
                $submit_article = '<p id="submit-' . $id . '" class="hidden"><a href="#"><button ' . $submit_js . '>Valider</button></a></p></div></article>';
                
                $new_article_admin_buttons = $close_editor . $submit_article;
            }

            $this->useChildrenBuilder($node);
            $content = $this->html;

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
        }
    }
}