<?php
// src/view/GaleryBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class GaleryBuilder extends AbstractBuilder
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

            // ajouter un article
            $new_article = '';
            if($_SESSION['admin'])
            {
                $id = 'n' . $this->id_node;
                $js = 'onclick="openEditor(\'' . $id . '\')"';

                $share_button = '<p class="share hidden"><img class="action_icon" src="assets/share.svg"></p>';
                $html = '';

                $new_button = '<p id="new-' . $id . '">' . "\n" . 
                '<button ' . $js . '><img class="action_icon" src="assets/edit.svg">Nouvel article</button></p>';

                $modify_js = 'onclick="openEditor(\'' . $id . '\')"';
                $modify_article = '<p id="edit-' . $id . '" class="hidden"><img class="action_icon" src="assets/edit.svg" ' . $modify_js . '></p>' . "\n";

                $up_js = 'onclick="switchPositions(\'' . $id . '\', \'up\')"';
                $up_button = '<p id="position_up-' . $id . '" class="hidden"><img class="action_icon" src="assets/arrow-up.svg" ' . $up_js . '></p>' . "\n";
                
                $down_js = 'onclick="switchPositions(\'' . $id . '\', \'down\')"';
                $down_button = '<p id="position_down-' . $id . '" class="hidden"><img class="action_icon" src="assets/arrow-down.svg" ' . $down_js . '></p>' . "\n";
                
                $delete_js = 'onclick="deleteArticle(\'' . $id . '\')"';
                $delete_article = '<p id="delete-' . $id . '" class="hidden"><img class="action_icon" src="assets/delete-bin.svg" ' . $delete_js . '></p>' . "\n";

                $close_js = 'onclick="closeEditor(\'' . $id . '\')"';
                $close_editor = '<p id="cancel-' . $id . '" class="hidden"><button ' . $close_js . '>Annuler</button></p>';
                
                $submit_js = 'onclick="submitArticle(\'' . $id . '\', \'\', clone' . $this->id_node . ')"';
                $submit_article = '<p id="submit-' . $id . '" class="hidden"><button ' . $submit_js . '>Valider</button></p>';
                
                $admin_buttons = $new_button . $modify_article . $up_button . $down_button . $delete_article . $close_editor . $submit_article;

                // squelette d'un nouvel article
                ob_start();
                require self::VIEWS_PATH . 'article.php';
                $new_article = ob_get_clean();
            }

            // articles existants
            $this->useChildrenBuilder($node);
            $content = $this->html;

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
        }
    }
}