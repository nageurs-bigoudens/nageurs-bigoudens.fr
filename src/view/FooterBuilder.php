<?php
// src/view/FooterBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class FooterBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            // $adresses postale et e-mail
            if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }

            $this->useChildrenBuilder($node);
            $breadcrumb = $this->html;

            // zone admin
            $empty_admin_zone = '';
            if($_SESSION['admin'])
            {
                $empty_admin_zone = 'empty_admin_zone';
                if(MainBuilder::$modif_mode){
                    $mode = 'modification de page';
                    $div_admin = 'logged_in modif_mode';
                }
                else{
                    $mode = 'administrateur';
                    $div_admin = 'logged_in';
                }
                $link_new_page = new URL(['page' => 'nouvelle_page']);
                $link_change_paths = new URL(['page' => 'menu_chemins']);
                
                $link_change_password = new URL(['page' => 'user_edit', 'from' => CURRENT_PAGE]);
                isset($_GET['id']) ? $link_change_password->addParams(['id' => $_GET['id']]) : '';

                $link_logout = new URL(['action' => 'deconnection', 'from' => CURRENT_PAGE]);
                isset($_GET['id']) ? $link_logout->addParams(['id' => $_GET['id']]) : '';

                $zone_admin = '<div class="admin_buttons_zone">
                    <p>Vous êtes en mode ' . $mode . ".</p>\n" . 
                    '<div><a href="' . $link_new_page . '"><button>Nouvelle page</button></a></div>' . "\n";
                $zone_admin .= $this->makePageModifModeButton();
                $zone_admin .= '<div><a href="' . $link_change_paths . '"><button>Menu et chemins</button></a></div>' . "\n" . 
                    '<div><a href="' . $link_change_password . '"><button>Mon compte</button></a></div>' . "\n" . 
                    '<div><a href="' . $link_logout . '"><button>Déconnexion</button></a></div>' . "\n" . 
                '</div>' . "\n";
            }
            else
            {
                $div_admin = 'logged_out';
                $url = new URL(['page' => 'connection', 'from' => CURRENT_PAGE]);
                if(Director::$page_path->getLast()->getEndOfPath() === 'article' && isset($_GET['id'])){
                    $url->addParams(['id' => $_GET['id']]);
                }
                $zone_admin = '<button><a href="' . $url . '">Mode admin</a></button>';
            }

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean();
        }
    }

    private function makePageModifModeButton(): string
    {
        $link_edit_page = new URL(['page' => CURRENT_PAGE]);
        if(CURRENT_PAGE !== 'article'){
            if(MainBuilder::$modif_mode){
                $link_edit_label = 'Sortir du mode modification';
            }
            else{
                $link_edit_page->addParams(['mode' => 'page_modif']);
                $link_edit_label = 'Modifier la page';
            }
            return '<div><a href="' . $link_edit_page . '"><button>' . $link_edit_label . '</button></a></div>' . "\n";
        }
        else{
            return '';
        }
    }
}