<?php
// src/view/FooterBuilder.php

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
                $div_admin = 'logged_in';
                $empty_admin_zone = 'empty_admin_zone';
                $link_edit_page = new URL(['page' => CURRENT_PAGE, 'action' => 'modif_page']);
                $link_new_page = new URL(['page' => 'nouvelle_page']);
                $link_change_paths = new URL(['page' => 'menu_chemins']);
                
                $link_change_password = new URL(['from' => CURRENT_PAGE, 'action' => 'modif_mdp']);
                isset($_GET['id']) ? $link_change_password->addParams(['id' => $_GET['id']]) : '';

                $link_logout = new URL(['page' => CURRENT_PAGE, 'action' => 'deconnexion']);
                isset($_GET['id']) ? $link_logout->addParams(['id' => $_GET['id']]) : '';

                $zone_admin = '<p>Vous êtes en mode administrateur.' . "\n" . 
                    '<a href="' . $link_edit_page . '"><button>Modifier la page</button></a>' . "\n" . 
                    '<a href="' . $link_new_page . '"><button>Nouvelle page</button></a>' . "\n" . 
                    '<a href="' . $link_change_paths . '"><button>Menu et chemins</button></a>' . "\n" . 
                    '<a href="' . $link_change_password . '"><button>Changer de mot de passe</button></a>' . "\n" . 
                    '<a href="' . $link_logout . '"><button>Déconnexion</button></a></p>' . "\n";
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