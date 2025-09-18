<?php
// src/view/NewBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class NewBuilder extends AbstractBuilder
{
    static public bool $new_article_mode = false;

    public function __construct(Node $node)
    {
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';

        if(file_exists($viewFile))
        {
            if(self::$new_article_mode){
                $id = $_GET['id']; // ici l'id est le nom du block news_block parent
                $title = '';
                $preview = '';

                // lettre au début de l'id: t = title, p = preview, i = article, d = date
                $id_title = $id;
                $id_title[0] = 't';
                $id_preview = $id;
                $id_preview[0] = 'p';
                $id_content = 'i' . $id;
                $id_content[0] = 'i';
                $id_date = $id;
                $id_date[0] = 'd';
            }
            else{
                $id = (string)$node->getId();

                // id (timestamp)
                if(!empty($node->getAttributes()))
                {
                    extract($node->getAttributes());
                }

                // html, date
                $title = $node->getArticle()->getTitle();
                $preview = $node->getArticle()->getPreview();

                // lettre au début de l'id: t = title, p = preview, i = article, d = date
                $id_title = 't' . $id;
                $id_preview = 'p' . $id;
                $id_content = 'i' . $id;
                $id_date = 'd' . $id;
            }
            
            $content = '';

            // page article unique
            if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
                $content = $node->getArticle()->getContent();
                $from_to_button = '<p><a class="link_to_article" href="' . new URL(isset($_GET['from']) ? ['page' => $_GET['from']] : []) . '"><button>Retour</button></a></p>';
            }
            else{
                $from_to_button = '<p><a class="link_to_article" href="' . new URL(['page' => 'article', 'id' => $id, 'from' => CURRENT_PAGE]) . '"><button><img class="action_icon" src="assets/book-open.svg">Lire la suite</button></a></p>';
            }

            $date = $node->getArticle()->getDateTime()->format('Y-m-d\TH:i:s.v\Z'); // format: 2025-07-17T13:54:00.000Z
            // format(\DateTime::ATOM) produit le format: 2025-10-10T12:17:00+00:00, c'est aussi de la norme ISO, mais à éviter pour être compatible avec date.toISOString en JS

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
            if($_SESSION['admin']){
                if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
                    $title_js = 'onclick="openEditor(\'' . $id_title . '\')"';
                    $modify_title = '<p id="edit-' . $id_title . '"><button ' . $title_js . '><img class="action_icon" src="assets/edit.svg">Titre</button></p>' . "\n";
                    $close_js_title = 'onclick="closeEditor(\'' . $id_title . '\')"';
                    $close_editor_title = '<p id="cancel-' . $id_title . '" class="hidden"><button ' . $close_js_title . '>Annuler</button></p>';
                    $submit_js_title = 'onclick="submitArticle(\'' . $id_title . '\')"';
                    $submit_title = '<p id="submit-' . $id_title . '" class="hidden"><button ' . $submit_js_title . '>Valider</button></p>';
                    $title_buttons = '<div class="button_zone">' . $modify_title . $close_editor_title . $submit_title . '</div>';

                    $preview_js = 'onclick="openEditor(\'' . $id_preview . '\')"';
                    $modify_preview = '<p id="edit-' . $id_preview . '"><button ' . $preview_js . '><img class="action_icon" src="assets/edit.svg">Aperçu</button></a></p>' . "\n";
                    $close_js_preview = 'onclick="closeEditor(\'' . $id_preview . '\')"';
                    $close_editor_preview = '<p id="cancel-' . $id_preview . '" class="hidden"><button ' . $close_js_preview . '>Annuler</button></p>';
                    $submit_js_preview = 'onclick="submitArticle(\'' . $id_preview . '\')"';
                    $submit_preview = '<p id="submit-' . $id_preview . '" class="hidden"><button ' . $submit_js_preview . '>Valider</button></p>';
                    $preview_buttons = '<div class="button_zone">' . $modify_preview . $close_editor_preview . $submit_preview . '</div>';

                    $article_js = 'onclick="openEditor(\'' . $id_content . '\')"';
                    $modify_article = '<p id="edit-' . $id_content . '"><button ' . $article_js . '><img class="action_icon" src="assets/edit.svg">Article</button></p>' . "\n";
                    $close_js_article = 'onclick="closeEditor(\'' . $id_content . '\')"';
                    $close_editor_article = '<p id="cancel-' . $id_content . '" class="hidden"><button ' . $close_js_article . '>Annuler</button></p>';
                    $submit_js_article = 'onclick="submitArticle(\'' . $id_content . '\')"';
                    $submit_article = '<p id="submit-' . $id_content . '" class="hidden"><button ' . $submit_js_article . '>Valider</button></p>';
                    $article_buttons = '<div class="button_zone">' . $modify_article . $close_editor_article . $submit_article . '</div>';

                    $date_js = 'onclick="openDatetimeLocalInput(\'' . $id_date . '\', \'article\');';
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
                        $submit_js = 'onclick="submitArticle(\'' . $_GET['id'] . '\')"';
                        $submit_article = '<p id="save-' . $id . '"><img class="action_icon delete_button" src="assets/save.svg" ' . $submit_js . '></p>' . "\n";
                    }
                    // mode article existant
                    else{
                        $url = new URL(['action' => 'delete_article', 'id' => $_GET['id'], 'from' => $_GET['from'] ?? '']);
                        $delete_article = '<form id="delete-' . $id . '" method="post" action="' . $url . '">
                            <p>
                                <img src="assets/delete-bin.svg" alt="Supprimer l\'article" class="action_icon" style="cursor: pointer;" onclick="if(confirm(\'Voulez-vous vraiment supprimer cet article ?\')) { this.closest(\'form\').submit(); }"
                            </p>
                        </form>' . "\n"; // this.closest('form').submit() = submit du formulaire avec javascript
                        $submit_article = '';
                    }
                    
                    $admin_buttons = $share_button . $delete_article . $submit_article . $from_to_button;
                }
                // autre page
                else{
                    $delete_js = 'onclick="deleteArticle(\'' . $id . '\')"';
                    $delete_article = '<p id="delete-' . $id . '"><img class="action_icon" src="assets/delete-bin.svg" ' . $delete_js . '></p>' . "\n";

                    $close_editor = '<p id="cancel-' . $id . '" class="hidden"></p>';
                    $submit_article = '<p id="submit-' . $id . '" class="hidden"></p>';

                    $admin_buttons = $from_to_button . $share_button . $delete_article . $close_editor . $submit_article;
                }
            }
            else{
                $admin_buttons = $share_button . $from_to_button;
            }

            ob_start();
            require($viewFile);
            $this->html .= ob_get_clean();
        }
    }
}
