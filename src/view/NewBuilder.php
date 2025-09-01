<?php
// src/view/NewBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class NewBuilder extends AbstractBuilder
{
    static public bool $new_article_mode = false;

    public function __construct(Node $node, )
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
            if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
                $content = $node->getArticle()->getContent();
                $from_to_button = '<p><a class="link_to_article" href="' . new URL(isset($_GET['from']) ? ['page' => $_GET['from']] : []) . '"><button>Page<br>précédente</button></a></p>';
            }
            else{
                $from_to_button = '<p><a class="link_to_article" href="' . new URL(['page' => 'article', 'id' => $id, 'from' => CURRENT_PAGE]) . '"><button><img class="action_icon" src="assets/book-open.svg">Lire la suite</button></a></p>';
            }

            
            $date_object = $node->getArticle()->getDateTime(); // class DateTime
            $date = 'le ' . str_replace(':', 'h', $date_object->format('d-m-Y à H:i'));

            // partage
            $share_link = new URL(['page' => 'article', 'id' => $id]);
            $share_js = 'onclick="copyInClipBoard(\'' . $share_link . '\')"';
            if(isset($_GET['id']) && $_GET['id'][0] === 'n'){
                $class = 'class="share hidden"';
            }
            else{
                $class = 'class="share"';
            }
            $share_button = '<p ' . $class . ' ' . $share_js . '><img class="action_icon" src="assets/share.svg"></p>' . "\n";

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
                    $modify_title = '<p id="edit-' . $id_title . '"><button ' . $title_js . '><img class="action_icon" src="assets/edit.svg">Titre</button></p>' . "\n";
                    $close_js_title = 'onclick="closeEditor(\'' . $id_title . '\', \'article\', \'preview\')"';
                    $close_editor_title = '<p id="cancel-' . $id_title . '" class="hidden"><button ' . $close_js_title . '>Annuler</button></p>';
                    $submit_js_title = 'onclick="submitArticle(\'' . $id_title . '\', \'article\')"';
                    $submit_title = '<p id="submit-' . $id_title . '" class="hidden"><button ' . $submit_js_title . '>Valider</button></p>';
                    $title_buttons = '<div class="button_zone">' . $modify_title . $close_editor_title . $submit_title . '</div>';

                    $preview_js = 'onclick="openEditor(\'' . $id_preview . '\', \'article\')"';
                    $modify_preview = '<p id="edit-' . $id_preview . '"><button ' . $preview_js . '><img class="action_icon" src="assets/edit.svg">Aperçu</button></a></p>' . "\n";
                    $close_js_preview = 'onclick="closeEditor(\'' . $id_preview . '\', \'article\', \'preview\')"';
                    $close_editor_preview = '<p id="cancel-' . $id_preview . '" class="hidden"><button ' . $close_js_preview . '>Annuler</button></p>';
                    $submit_js_preview = 'onclick="submitArticle(\'' . $id_preview . '\', \'article\')"';
                    $submit_preview = '<p id="submit-' . $id_preview . '" class="hidden"><button ' . $submit_js_preview . '>Valider</button></p>';
                    $preview_buttons = '<div class="button_zone">' . $modify_preview . $close_editor_preview . $submit_preview . '</div>';

                    $article_js = 'onclick="openEditor(\'' . $id . '\', \'article\')"';
                    $modify_article = '<p id="edit-' . $id . '"><button ' . $article_js . '><img class="action_icon" src="assets/edit.svg">Article</button></p>' . "\n";
                    $close_js_article = 'onclick="closeEditor(\'' . $id . '\', \'article\')"';
                    $close_editor_article = '<p id="cancel-' . $id . '" class="hidden"><button ' . $close_js_article . '>Annuler</button></p>';
                    $submit_js_article = 'onclick="submitArticle(\'' . $id . '\', \'article\')"';
                    $submit_article = '<p id="submit-' . $id . '" class="hidden"><button ' . $submit_js_article . '>Valider</button></p>';
                    $article_buttons = '<div class="button_zone">' . $modify_article . $close_editor_article . $submit_article . '</div>';

                    $date_js = 'onclick="changeDate(\'' . $id_date . '\', \'article\');';
                    $modify_date = '<p id="edit-' . $id_date . '"><button ' . $date_js . '"><img class="action_icon" src="assets/edit.svg">Date</button></p>' . "\n";
                    $close_js_date = 'onclick="closeInput(\'' . $id_date . '\')"';
                    $close_editor_date = '<p id="cancel-' . $id_date . '" class="hidden"><button ' . $close_js_date . '>Annuler</button></p>';
                    $submit_js_date = 'onclick="submitDate(\'' . $id_date . '\')"';
                    $submit_date = '<p id="submit-' . $id_date . '" class="hidden"><button ' . $submit_js_date . '>Valider</button></p>';
                    $date_buttons = '<div class="button_zone">' . $modify_date . $close_editor_date . $submit_date . '</div>';
                    
                    // mode nouvel article
                    if(self::$new_article_mode){
                        $delete_article = '';
                        // valider la création d'un nouvel article
                        $submit_js = 'onclick="submitArticle(\'' . $_GET['id'] . '\', \'' . Director::$page_path->getLast()->getEndOfPath() . '\')"';
                        $submit_article = '<p id="save-' . $id . '"><button ' . $submit_js . '><img class="action_icon" src="assets/edit.svg"><span class="delete_button">Tout<br>enregistrer</span></button></p>' . "\n";
                    }
                    // mode article existant
                    else{
                        $url = new URL(['action' => 'delete_article', 'id' => $_GET['id'], 'from' => $_GET['from'] ?? '']);
                        $delete_article = '<form id="delete-' . $id . '" method="post" onsubmit="return confirm(\'Voulez-vous vraiment supprimer cet article ?\');" action="' . $url . '">
                            <p><button type="submit">
                                <img class="action_icon" src="assets/delete-bin.svg">
                                <span class="delete_button">Supprimer<br>cet article</span>
                            </button></p>
                        </form>' . "\n";
                        $submit_article = '';
                    }
                    
                    $admin_buttons = $delete_article . $from_to_button . $submit_article;
                }
                // autre page
                else{
                    $modify_article = '<p id="edit-' . $id . '"></p>' . "\n";

                    $up_js = 'onclick="switchPositions(\'' . $id . '\', \'up\')"';
                    $up_button = '<p id="position_up-' . $id . '"><img class="action_icon" src="assets/arrow-up.svg" ' . $up_js . '></p>' . "\n";
                    
                    $down_js = 'onclick="switchPositions(\'' . $id . '\', \'down\')"';
                    $down_button = '<p id="position_down-' . $id . '"><img class="action_icon" src="assets/arrow-down.svg" ' . $down_js . '></p>' . "\n";

                    $delete_js = 'onclick="deleteArticle(\'' . $id . '\')"';
                    $delete_article = '<p id="delete-' . $id . '"><img class="action_icon" src="assets/delete-bin.svg" ' . $delete_js . '></p>' . "\n";

                    $close_editor = '<p id="cancel-' . $id . '" class="hidden"></p>';
                    $submit_article = '<p id="submit-' . $id . '" class="hidden"></p>';

                    $submit_article = '<p id="submit-' . $id . '" class="hidden"></p>';

                    $admin_buttons = $from_to_button . $modify_article . $up_button . $down_button . $delete_article . $close_editor . $submit_article;
                }
            }
            else{
                $admin_buttons = $from_to_button;
            }

            ob_start();
            require($viewFile);
            $this->html .= ob_get_clean();
        }
    }
}
