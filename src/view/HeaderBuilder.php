<?php
// src/view/HeaderBuilder.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Asset;

class HeaderBuilder extends AbstractBuilder
{
    private ?Node $nav = null;
    private ?Node $breadcrumb = null;

    public function __construct(Node $node)
    {
        // pas de useChildrenBuilder, il faudrait peut-être
        $children = $node->getChildren();
        foreach($children as $child)
        {
            if($child->getName() === 'nav'){
                $this->nav = $child;
                // actuellement le noeud nav ne contient aucune info utile et l'envoyer à NavBuilder est inutile
                $nav_builder = new NavBuilder($this->nav);
                
                $nav = $nav_builder->render();
            }
            elseif($child->getName() === 'breadcrumb'){
                $this->breadcrumb = $child;
                $breadcrumb_builder = new BreadcrumbBuilder($this->breadcrumb);
                $breadcrumb = $breadcrumb_builder->render();
            }
        }
    	
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            $node_data = $node->getNodeData();
            // titre et description
            if(!empty($node_data->getData()))
            {
                extract($node_data->getData());
            }

            // réseaux sociaux + logo dans l'entête
            // ?-> est l'opérateur de chainage optionnel
            $header_logo = Asset::USER_PATH . $node_data->getAssetByRole('header_logo')?->getFileName() ?? '';
            $header_background_name = $node_data->getAssetByRole('header_background')?->getFileName();
            $header_background = $header_background_name ? Asset::USER_PATH . $header_background_name : '';
            
            // boutons mode admin
            if($_SESSION['admin']){
                // assets dans classe header_additional_inputs
                $admin_favicon = '<input type="file" id="head_favicon_input" class="hidden" accept="image/png, image/jpeg, image/gif, image/webp, image/tiff, image/x-icon, image/bmp">
                    <button id="head_favicon_open" onclick="head_favicon.open()"><img id="head_favicon_content" class="action_icon"> Favicon</button>
                    <script>document.getElementById("head_favicon_content").src = window.Config.favicon;</script>
                    <img id="head_favicon_submit" class="action_icon hidden" src="assets/save.svg" onclick="head_favicon.submit()">
                    <img id="head_favicon_cancel" class="action_icon hidden" src="assets/close.svg" onclick="head_favicon.cancel()">';
                $admin_background = '<input type="file" id="header_background_input" class="hidden" accept="image/png, image/jpeg, image/gif, image/webp, image/tiff">
                    <button id="header_background_open" onclick="header_background.open()"><img id="header_background_content" class="background_button" src="' . $header_background . '"> Image de fond</button>
                    <img id="header_background_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_background.submit()">
                    <img id="header_background_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_background.cancel()">';

                // asset dans classe header_content
                $admin_header_logo = '<input type="file" id="header_logo_input" class="hidden" accept="image/png, image/jpeg, image/gif, image/webp, image/tiff">
                    <img id="header_logo_open" class="action_icon" src="assets/edit.svg" onclick="header_logo.open()">
                    <img id="header_logo_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_logo.submit()">
                    <img id="header_logo_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_logo.cancel()">';
                // texte dans classe header_content
                $admin_header_title = '<input type="text" id="header_title_input" class="hidden" value="' . htmlspecialchars($title ?? '') . '" placeholder="nom du site web" size="30">
                    <img id="header_title_open" class="action_icon" src="assets/edit.svg" onclick="header_title.open()">
                    <img id="header_title_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_title.submit()">
                    <img id="header_title_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_title.cancel()">';
                $admin_header_description = '<input type="text" id="header_description_input" class="hidden" value="' . htmlspecialchars($description ?? '') . '" placeholder="sous-titre ou description" size="30">
                    <img id="header_description_open" class="action_icon" src="assets/edit.svg" onclick="header_description.open()">
                    <img id="header_description_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_description.submit()">
                    <img id="header_description_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_description.cancel()">';

                // icônes réseaux sociaux
                $header_social_flex_direction = 'column';
                $admin_social_networks = [];
                foreach(array_keys($social) as $one_key){
                    //<input type="file" id="header_' . $one_key . '_input" class="hidden" accept="image/svg+xml, image/png, image/jpeg, image/gif, image/webp, image/tiff">
                    // sinon plutôt qu'on bouton nouveau réseau, utiliser le foreach avec HeadFootController::$social_networks pour tous les parcourir et placer des cases à cocher
                    // les icones seront ajoutées par mes soins
                    $admin_social_networks[$one_key] = '<input type="text" id="header_' . $one_key . '_input" class="hidden" value="' . $social[$one_key] . '" placeholder="nom du réseau social" size="30">
                        <img id="header_' . $one_key . '_open" class="action_icon" src="assets/edit.svg" onclick="header_' . $one_key . '.open()">
                        <img id="header_' . $one_key . '_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_' . $one_key . '.submit()">
                        <img id="header_' . $one_key . '_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_' . $one_key . '.cancel()">
                        <script>let header_' . $one_key . ' = new InputTextSocialNetwork(\'header_' . $one_key . '\');</script>';
                }
                //$admin_social_new_network = '<div>nouveau réseau</div>';
                $admin_social_new_network = '';

                /*$social_networks_inputs = '<div id="header_social_input" class="hidden">';
                foreach(array_keys($social) as $one_key){
                    $social_networks_inputs .= '<div>
                        <input type="text" placeholder="nom du réseau social">
                        <input type="text" placeholder="lien https://...">
                        <input type="file">
                    </div>';
                }
                $social_networks_inputs .= '</div>';*/
            }
            else{
                $admin_favicon = '';
                $admin_background = '';
                $admin_header_logo = '';
                $admin_header_title = '';
                $admin_header_description = '';
                $header_social_flex_direction = 'row';
                $admin_social_networks = [];
                foreach(array_keys($social) as $one_key){
                    $admin_social_networks[$one_key] = '';
                }
                $admin_social_new_network = '';
            }
            
            ob_start();
            require $viewFile;
            $this->html .= ob_get_clean();
        }
    }
}