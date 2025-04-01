<?php
// src/view/NewBuilder.php

use App\Entity\Node;

class NewBuilder extends AbstractBuilder
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

            // html, date
            $title = $node->getArticle()->getTitle();
            $preview = $node->getArticle()->getPreview();
            $id = $node->getArticleTimestamp();
            $content = '';

            // page article unique
            if(Director::$page_path->getLast()->getEndOfPath() === 'article')
            {
                $content = $node->getArticle()->getContent();
                $from_to_button = '<p><a class="link_to_article" href="' . new URL(['page' => 'accueil']) . '"><button>Retour page d\'accueil</button></a></p>';
            }
            // page d'accueil (avec des news)
            else
            {
                $from_to_button = '<p><a class="link_to_article" href="' . new URL(['page' => 'article', 'id' => $id]) . '"><button><img class="action_icon" src="assets/book-open.svg">Lire la suite</button></a></p>';
            }

            
            $date_object = $node->getArticle()->getDateTime(); // class DateTime
            $date = 'le ' . str_replace(':', 'h', $date_object->format('d-m-Y à H:i'));

            // partage
            $share_link = new URL(['page' => CURRENT_PAGE], $id);
            isset($_GET['id']) ? $share_link->addParams(['id' => $_GET['id']]) : '';
            $share_js = 'onclick="copyInClipBoard(\'' . $share_link . '\')"';
            $share_button = '<p><a href="' . $share_link . '" ' . $share_js . '><img class="action_icon" src="assets/share.svg"></a></p>' . "\n";

            // modifier un article
            $admin_buttons = '';
            if($_SESSION['admin'])
            {
                if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
                    $modify_js = 'onclick="openEditor(\'' . $id . '\', \'article\')"';
                    $modify_article = '<p id="edit-' . $id . '"><a href="#"><img class="action_icon" src="assets/edit.svg" ' . $modify_js . '></a></p>' . "\n";

                    $up_button = '';
                    $down_button = '';
                    
                    $delete_js = 'onclick="deleteArticle(\'' . $id . '\', \'' . CURRENT_PAGE . '\')"';
                    $delete_article = '<p id="delete-' . $id . '"><a href="#"><img class="action_icon" src="assets/delete-bin.svg" ' . $delete_js . '></a></p>' . "\n";
                    
                    $close_js = 'onclick="closeEditor(\'' . $id . '\', \'article\')"';
                    $close_editor = '<p id="cancel-' . $id . '" class="hidden"><a href="#"><button ' . $close_js . '>Annuler</button></a></p>';
                    
                    $submit_js = 'onclick="submitArticle(\'' . $id . '\', \'article\')"';
                    $submit_article = '<p id="submit-' . $id . '" class="hidden"><a href="#"><button ' . $submit_js . '>Valider</button></a></p>';
                }
                else{
                    $modify_article = '<p id="edit-' . $id . '"></p>' . "\n";

                    $up_js = 'onclick="switchPositions(\'' . $id . '\', \'up\')"';
                    $up_button = '<p id="position_up-' . $id . '"><a href="#"><img class="action_icon" src="assets/arrow-up.svg" ' . $up_js . '></a></p>' . "\n";
                    
                    $down_js = 'onclick="switchPositions(\'' . $id . '\', \'down\')"';
                    $down_button = '<p id="position_down-' . $id . '"><a href="#"><img class="action_icon" src="assets/arrow-down.svg" ' . $down_js . '></a></p>' . "\n";

                    $delete_js = 'onclick="deleteArticle(\'' . $id . '\')"';
                    $delete_article = '<p id="delete-' . $id . '"><a href="#"><img class="action_icon" src="assets/delete-bin.svg" ' . $delete_js . '></a></p>' . "\n";

                    $close_editor = '<p id="cancel-' . $id . '" class="hidden"></p>';
                    $submit_article = '<p id="submit-' . $id . '" class="hidden"></p>';

                    $submit_article = '<p id="submit-' . $id . '" class="hidden"></p>';
                }
                $admin_buttons = $modify_article . $up_button . $down_button . $delete_article . $close_editor . $submit_article;
            }

            ob_start();
            require($viewFile);
            $this->html .= ob_get_clean();
        }
    }
}
