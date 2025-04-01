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
            
            // lettre au début de l'id: i = article, p = preview, t = title, d = date
            $id = $node->getArticleTimestamp();
            $id_title = $id;
            $id_title[0] = 't';
            $id_preview = $id;
            $id_preview[0] = 'p';
            $id_date = $id;
            $id_date[0] = 'd';

            $content = '';

            // page article unique
            if(Director::$page_path->getLast()->getEndOfPath() === 'article')
            {
                $content = $node->getArticle()->getContent();
                $from_to_button = '<p><a class="link_to_article" href="' . new URL(['page' => 'accueil']) . '"><button>Page<br>d\'accueil</button></a></p>';
            }
            // page d'accueil (avec des news)
            else
            {
                $from_to_button = '<p><a class="link_to_article" href="' . new URL(['page' => 'article', 'id' => $id]) . '"><button><img class="action_icon" src="assets/book-open.svg">Lire la suite</button></a></p>';
            }

            
            $date_object = $node->getArticle()->getDateTime(); // class DateTime
            $date = 'le ' . str_replace(':', 'h', $date_object->format('d-m-Y à H:i'));
            //$date = str_replace(':', 'h', $date_object->format('d-m-Y à H:i'));

            // partage
            $share_link = new URL(['page' => CURRENT_PAGE], $id);
            isset($_GET['id']) ? $share_link->addParams(['id' => $_GET['id']]) : '';
            $share_js = 'onclick="copyInClipBoard(\'' . $share_link . '\')"';
            $share_button = '<a class="share" href="' . $share_link . '" ' . $share_js . '><img class="action_icon" src="assets/share.svg"></a>' . "\n";

            // modifier un article
            $title_buttons = '';
            $preview_buttons = '';
            $article_buttons = '';
            $date_buttons = '';
            $admin_buttons = '';
            if($_SESSION['admin'])
            {
                if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
                    $title_js = 'onclick="openEditor(\'' . $id_title . '\', \'article\')"';
                    $modify_title = '<p id="edit-' . $id_title . '"><a href="#"><button ' . $title_js . '><img class="action_icon" src="assets/edit.svg">Titre</button></a></p>' . "\n";
                    $close_js_title = 'onclick="closeEditor(\'' . $id_title . '\', \'article\', \'preview\')"';
                    $close_editor_title = '<p id="cancel-' . $id_title . '" class="hidden"><a href="#"><button ' . $close_js_title . '>Annuler</button></a></p>';
                    $submit_js_title = 'onclick="submitArticle(\'' . $id_title . '\', \'article\')"';
                    $submit_title = '<p id="submit-' . $id_title . '" class="hidden"><a href="#"><button ' . $submit_js_title . '>Valider</button></a></p>';
                    $title_buttons = '<div class="button_zone">' . $modify_title . $close_editor_title . $submit_title . '</div>';

                    $preview_js = 'onclick="openEditor(\'' . $id_preview . '\', \'article\')"';
                    $modify_preview = '<p id="edit-' . $id_preview . '"><a href="#"><button ' . $preview_js . '><img class="action_icon" src="assets/edit.svg">Aperçu</button></a></p>' . "\n";
                    $close_js_preview = 'onclick="closeEditor(\'' . $id_preview . '\', \'article\', \'preview\')"';
                    $close_editor_preview = '<p id="cancel-' . $id_preview . '" class="hidden"><a href="#"><button ' . $close_js_preview . '>Annuler</button></a></p>';
                    $submit_js_preview = 'onclick="submitArticle(\'' . $id_preview . '\', \'article\')"';
                    $submit_preview = '<p id="submit-' . $id_preview . '" class="hidden"><a href="#"><button ' . $submit_js_preview . '>Valider</button></a></p>';
                    $preview_buttons = '<div class="button_zone">' . $modify_preview . $close_editor_preview . $submit_preview . '</div>';

                    $article_js = 'onclick="openEditor(\'' . $id . '\', \'article\')"';
                    $modify_article = '<p id="edit-' . $id . '"><a href="#"><button ' . $article_js . '><img class="action_icon" src="assets/edit.svg">Article</button></a></p>' . "\n";
                    $close_js_article = 'onclick="closeEditor(\'' . $id . '\', \'article\')"';
                    $close_editor_article = '<p id="cancel-' . $id . '" class="hidden"><a href="#"><button ' . $close_js_article . '>Annuler</button></a></p>';
                    $submit_js_article = 'onclick="submitArticle(\'' . $id . '\', \'article\')"';
                    $submit_article = '<p id="submit-' . $id . '" class="hidden"><a href="#"><button ' . $submit_js_article . '>Valider</button></a></p>';
                    $article_buttons = '<div class="button_zone">' . $modify_article . $close_editor_article . $submit_article . '</div>';

                    $date_js = 'onclick="changeDate(\'' . $id_date . '\', \'article\');';
                    $modify_date = '<p id="edit-' . $id_date . '"><a href="#"><button ' . $date_js . '"><img class="action_icon" src="assets/edit.svg">Date</button></a></p>' . "\n";
                    $close_js_date = 'onclick="closeInput(\'' . $id_date . '\')"';
                    $close_editor_date = '<p id="cancel-' . $id_date . '" class="hidden"><a href="#"><button ' . $close_js_date . '>Annuler</button></a></p>';
                    $submit_js_date = 'onclick="submitDate(\'' . $id_date . '\')"';
                    $submit_date = '<p id="submit-' . $id_date . '" class="hidden"><a href="#"><button ' . $submit_js_date . '>Valider</button></a></p>';
                    $date_buttons = '<div class="button_zone">' . $modify_date . $close_editor_date . $submit_date . '</div>';

                    $delete_js = 'onclick="deleteArticle(\'' . $id . '\', \'' . CURRENT_PAGE . '\')"';
                    $delete_article = '<p id="delete-' . $id . '"><a href="#"><button ' . $delete_js . '"><img class="action_icon" src="assets/delete-bin.svg" ' . $delete_js . '>Retirer<br>la publication</button></a></p>' . "\n";

                    $admin_buttons = $delete_article;
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

                    $admin_buttons = $modify_article . $up_button . $down_button . $delete_article . $close_editor . $submit_article;
                }
                
            }

            ob_start();
            require($viewFile);
            $this->html .= ob_get_clean();
        }
    }
}
