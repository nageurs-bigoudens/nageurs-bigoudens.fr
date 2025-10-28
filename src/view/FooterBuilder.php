<?php
// src/view/FooterBuilder.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Asset;

class FooterBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            $node_data = $node->getNodeData();
            // nom du contact, adresse et e-mail
            if(!empty($node_data->getData()))
            {
                extract($node_data->getData());
            }

            $footer_logo = Asset::USER_PATH . $node_data->getAssetByRole('footer_logo')?->getFileName() ?? '';

            $this->useChildrenBuilder($node);
            $breadcrumb = $this->html;

            $empty_admin_zone = '';
            if($_SESSION['admin'])
            {
                // données du footer
                $admin_footer_name = '<input type="text" id="footer_name_input" class="hidden" value="' . htmlspecialchars($name ?? '') . '" placeholder="raison sociale" size="30">
                    <img id="footer_name_open" class="action_icon" src="assets/edit.svg" onclick="footer_name.open()">
                    <img id="footer_name_submit" class="action_icon hidden" src="assets/save.svg" onclick="footer_name.submit()">
                    <img id="footer_name_cancel" class="action_icon hidden" src="assets/close.svg" onclick="footer_name.cancel()">';
                $admin_footer_address = '<input type="text" id="footer_address_input" class="hidden" value="' . htmlspecialchars($address ?? '') . '" placeholder="adresse" size="30">
                    <img id="footer_address_open" class="action_icon" src="assets/edit.svg" onclick="footer_address.open()">
                    <img id="footer_address_submit" class="action_icon hidden" src="assets/save.svg" onclick="footer_address.submit()">
                    <img id="footer_address_cancel" class="action_icon hidden" src="assets/close.svg" onclick="footer_address.cancel()">';
                $admin_footer_email = '<input type="text" id="footer_email_input" class="hidden" value="' . htmlspecialchars($email ?? '') . '" placeholder="e-mail" size="30">
                    <img id="footer_email_open" class="action_icon" src="assets/edit.svg" onclick="footer_email.open()">
                    <img id="footer_email_submit" class="action_icon hidden" src="assets/save.svg" onclick="footer_email.submit()">
                    <img id="footer_email_cancel" class="action_icon hidden" src="assets/close.svg" onclick="footer_email.cancel()">';

                $admin_footer_logo = '<input type="file" id="footer_logo_input" class="hidden" accept="image/png, image/jpeg, image/gif, image/webp, image/tiff">
                    <img id="footer_logo_open" class="action_icon" src="assets/edit.svg" onclick="footer_logo.open()">
                    <img id="footer_logo_submit" class="action_icon hidden" src="assets/save.svg" onclick="footer_logo.submit()">
                    <img id="footer_logo_cancel" class="action_icon hidden" src="assets/close.svg" onclick="footer_logo.cancel()">';

                // zone admin
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
                if(Model::$page_path->getLast()->getEndOfPath() === 'article' && isset($_GET['id'])){
                    $url->addParams(['id' => $_GET['id']]);
                }
                $zone_admin = '<button><a href="' . $url . '">Mode admin</a></button>';

                $admin_footer_name = '';
                $admin_footer_address = '';
                $admin_footer_email = '';
                $admin_footer_logo = '';
            }

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean();
        }
    }

    private function makePageModifModeButton(): string
    {
        $link_edit_page = new URL(['page' => CURRENT_PAGE]);
        if(!in_array(CURRENT_PAGE, ['article', 'nouvelle_page', 'menu_chemins'])) // ajouter 'user_edit' et 'connection' le jour où ces pages auront un footer
        {
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