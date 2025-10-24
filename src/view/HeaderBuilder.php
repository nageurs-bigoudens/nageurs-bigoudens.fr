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
            $header_logo = Asset::USER_PATH . $node_data->getAssetByRole('header_logo')?->getFileName() ?? '';
            $header_background = Asset::USER_PATH . $node_data->getAssetByRole('header_background')?->getFileName() ?? '';
            $keys = array_keys($social);
            $social_networks = '';

            // nécéssite des entrées dans la table node_asset
            /*foreach($node->getNodeData()->getAssets() as $asset)
            {
                for($i = 0; $i < count($keys); $i++)
                {
                    // réseaux sociaux
                    if(str_contains($asset->getFileName(), $keys[$i])){
                        $social_networks .= '<a href="' . $social[$keys[$i]] . '" target="_blank" rel="noopener noreferrer">
                        <img src="' . rtrim($asset->getFilePathMini(), '/') . '" alt="' . $keys[$i] . '_alt"></a>';
                        break;
                    }
                    // logo en-tête
                    //if(str_contains($asset->getFileName(), 'header_logo')){
                        //$header_logo = rtrim($asset->getFilePathMini(), '/');
                        //break;
                    //}
                    // image de fond
                    //if(str_contains($asset->getFileName(), 'header_background')){
                        //$header_background = rtrim($asset->getFilePath(), '/');
                        //break;
                    //}
                }
            }*/

            // réseaux sociaux, chemin du ficher dans node_data à déplacer dans asset
            foreach($keys as $one_key){
                $social_networks .= '<a href="' . $social[$one_key] . '" target="_blank" rel="noopener noreferrer">
                    <img src="assets/' . $one_key . '.svg" alt="' . $one_key . '_alt"></a>';
            }
            
            // boutons mode admin
            if($_SESSION['admin']){
                // assets dans classe editing_zone
                $editing_zone_margin = '5px';
                $buttons_favicon = '<button id="head_favicon_open" onclick="head_favicon.open()"><img id="head_favicon_img" class="action_icon"> Favicon</button>
                    <script>document.getElementById(\'head_favicon_img\').src = window.Config.favicon;</script>
                    <img id="head_favicon_submit" class="action_icon hidden" src="assets/save.svg" onclick="head_favicon.submit()">
                    <img id="head_favicon_cancel" class="action_icon hidden" src="assets/close.svg" onclick="head_favicon.cancel()">';
                $buttons_background = '<button id="header_background_open" onclick="header_background.open()"><img id="header_background_img" class="background_button" src="' . $header_background . '"> Image de fond</button>
                    <img id="header_background_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_background.submit()">
                    <img id="header_background_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_background.cancel()">';

                // asset dans classe header_content
                $buttons_header_logo = '<img id="header_logo_open" class="action_icon" src="assets/edit.svg" onclick="header_logo.open()">
                    <img id="header_logo_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_logo.submit()">
                    <img id="header_logo_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_logo.cancel()">';
                // texte dans classe header_content
                $buttons_header_title = '<img id="header_title_open" class="action_icon" src="assets/edit.svg" onclick="header_title.open()">
                    <img id="header_title_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_title.submit()">
                    <img id="header_title_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_title.cancel()">';
                $buttons_header_description = '<img id="header_description_open" class="action_icon" src="assets/edit.svg" onclick="header_description.open()">
                    <img id="header_description_submit" class="action_icon hidden" src="assets/save.svg" onclick="header_description.submit()">
                    <img id="header_description_cancel" class="action_icon hidden" src="assets/close.svg" onclick="header_description.cancel()">';

                //$buttons_social_networks = '<img class="action_icon" src="assets/edit.svg" onclick="editSocialNetworks()">';
                $buttons_social_networks = '';
            }
            else{
                $editing_zone_margin = '0';
                $buttons_favicon = '';
                $buttons_background = '';
                $buttons_header_logo = '';
                $buttons_header_title = '';
                $buttons_header_description = '';
                $buttons_social_networks = '';
            }
            
            ob_start();
            require $viewFile;
            $this->html .= ob_get_clean();
        }
    }
}