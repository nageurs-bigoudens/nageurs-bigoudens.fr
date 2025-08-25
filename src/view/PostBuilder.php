<?php
// src/view/PostBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class PostBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';

        if(file_exists($viewFile))
        {
            // id (timestamp)
            if(!empty($node->getAttributes()))
            {
                extract($node->getAttributes());
            }

            // html
            $title = $node->getArticle()->getTitle();
            $html = $node->getArticle()->getContent();
            $id = $node->getArticleTimestamp();

            // partage
            $share_link = new URL(['page' => CURRENT_PAGE], $id);
            $share_js = 'onclick="copyInClipBoard(\'' . $share_link . '\')"';
            $share_button = '<p class="share" ' . $share_js . '><img class="action_icon" src="assets/share.svg"></p>' . "\n";

            // modifier un article
            $admin_buttons = '';
            if($_SESSION['admin'])
            {
                $modify_js = 'onclick="openEditor(\'' . $id . '\')"';
                $modify_article = '<p id="edit-' . $id . '"><img class="action_icon" src="assets/edit.svg" ' . $modify_js . '></p>' . "\n";

                $up_js = 'onclick="switchPositions(\'' . $id . '\', \'up\')"';
                $up_button = '<p id="position_up-' . $id . '"><img class="action_icon" src="assets/arrow-up.svg" ' . $up_js . '></p>' . "\n";
                
                $down_js = 'onclick="switchPositions(\'' . $id . '\', \'down\')"';
                $down_button = '<p id="position_down-' . $id . '"><img class="action_icon" src="assets/arrow-down.svg" ' . $down_js . '></p>' . "\n";
                
                $delete_js = 'onclick="deleteArticle(\'' . $id . '\')"';
                $delete_article = '<p id="delete-' . $id . '"><img class="action_icon" src="assets/delete-bin.svg" ' . $delete_js . '></p>' . "\n";
                
                $close_js = 'onclick="closeEditor(\'' . $id . '\')"';
                $close_editor = '<p id="cancel-' . $id . '" class="hidden"><button ' . $close_js . '>Annuler</button></p>';
                
                $submit_js = 'onclick="submitArticle(\'' . $id . '\')"';
                $submit_article = '<p id="submit-' . $id . '" class="hidden"><button ' . $submit_js . '>Valider</button></p>';
                
                $admin_buttons = $modify_article . $up_button . $down_button . $delete_article . $close_editor . $submit_article;
            }

            ob_start();
            require($viewFile);
            $this->html .= ob_get_clean();
        }
    }
}
