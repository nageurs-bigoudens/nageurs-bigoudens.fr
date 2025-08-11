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
            //$zone_admin = '';
            if($_SESSION['admin'])
            {
                $empty_admin_zone = 'empty_admin_zone';
                $link_edit_page = CURRENT_PAGE === 'article' ? new URL(['page' => 'accueil']) : new URL(['page' => CURRENT_PAGE]);
                if(MainBuilder::$modif_mode){
                    $mode = 'modification de page';
                    $div_admin = 'logged_in modif_mode';
                    $link_edit_label = 'Sortir du mode modification';
                }
                else{
                    $mode = 'administrateur';
                    $div_admin = 'logged_in';
                    $link_edit_page->addParams(['action' => 'modif_page']);
                    $link_edit_label = 'Modifier la page';
                }
                $link_new_page = new URL(['page' => 'nouvelle_page']);
                $link_change_paths = new URL(['page' => 'menu_chemins']);
                
                $link_change_password = new URL(['page' => 'user_edit', 'from' => CURRENT_PAGE]);
                isset($_GET['id']) ? $link_change_password->addParams(['id' => $_GET['id']]) : '';

                $link_logout = new URL(['action' => 'deconnection', 'from' => CURRENT_PAGE]);
                isset($_GET['id']) ? $link_logout->addParams(['id' => $_GET['id']]) : '';

                $zone_admin = '<div class="admin_buttons_zone">
                    <p>Vous êtes en mode ' . $mode . ".</p>\n" . 
                    '<div><a href="' . $link_new_page . '"><button>Nouvelle page</button></a></div>' . "\n" . 
                    '<div><a href="' . $link_edit_page . '"><button>' . $link_edit_label . '</button></a></div>' . "\n" . 
                    '<div><a href="' . $link_change_paths . '"><button>Menu et chemins</button></a></div>' . "\n" . 
                    '<div><a href="' . $link_change_password . '"><button>Mon compte</button></a></div>' . "\n" . 
                    '<div><a href="' . $link_logout . '"><button>Déconnexion</button></a></div>' . "\n" . 
                '</div>' . "\n";
            }
            else
            {
                $div_admin = 'logged_out';
                $zone_admin = '';
                if(Director::$page_path->getLast()->getEndOfPath() === 'article' && isset($_GET['id'])){
                    $zone_admin = '<button><a href="' . new URL(['page' => 'connexion', 'from' => CURRENT_PAGE, 'id' => $_GET['id']]) . '">Mode admin</a></button>';
                }
                else{
                    $zone_admin = '<button><a href="' . new URL(['page' => 'connexion', 'from' => CURRENT_PAGE]) . '">Mode admin</a></button>';
                }
            }

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean();
        }
    }
}