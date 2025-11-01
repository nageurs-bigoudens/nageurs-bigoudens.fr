<?php
// src/view/HeadBuilder.php

declare(strict_types=1);

use App\Entity\Asset;
use App\Entity\Node;

class HeadBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            // css et js
            $page = Model::$page_path->getLast();

            $css = '';
	        foreach($page->getCSS() as $name){
                $css .= self::insertCSS($name);
			}
			
            $js = '';
	        foreach($page->getJS() as $name){
                $js .= self::insertJS($name);
			}

            if(MainBuilder::$modif_mode){
                $css .= self::insertCSS('modif_page');
                $js .= self::insertJS('modif_page');
            }

            if($_SESSION['admin']){
                // édition éléments sur toutes les pages (header, footer et favicon)
                $js .= self::insertJS('Input');

                // sert partout?
                $js .= self::insertJS('Fetcher');

                // tinymce, nécéssite un script post-install et post-update dans composer.json
                $css .= self::insertCSS('tinymce');
                $js .= self::insertJS('tinymce/tinymce.min');
                $js .= self::insertJS('tinymce');
            }

            $title = Model::$page_path->getLast()->getPageName();
            $description = Model::$page_path->getLast()->getDescription();

            // favicon
            // ?-> est l'opérateur de navigation sécurisée => LOVE!
            $favicon_name = ($favicon_object = $node->getNodeData()->getAssetByRole('head_favicon'))?->getFileName();
            $favicon = $favicon_name ? Asset::USER_PATH . $favicon_name : '';
            $favicon_type = $favicon_object?->getMimeType() ?? '';

            ob_start();
            require $viewFile;
            $this->html .= ob_get_clean();
        }
    }

    static function insertCSS(string $name): string
    {
        return '<link rel="stylesheet" href="' . self::versionedFileURL('css', $name) . '">' . "\n";
    }
    static function insertJS(string $name): string
    {
        return '<script src="' . self::versionedFileURL('js', $name) . '"></script>' . "\n";
    }

    static function versionedFileURL(string $type, string $filename): string
    {
        $path = $type . '/' . $filename . '.' . $type;

        if(file_exists($path)){
            $version = substr(md5_file($path), 0, 8);
            return $path . '?v=' . $version;
        }
        return $path; // sécurité fichier absent
    }
}
