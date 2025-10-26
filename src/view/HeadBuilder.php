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
	        foreach($page->getCSS() as $name)
			{
				$css .= '<link rel="stylesheet" href="' . self::versionedFileURL('css', $name) . '">' . "\n";
			}
			
            $js = '';
	        foreach($page->getJS() as $name)
			{
				$js .= '<script src="' . self::versionedFileURL('js', $name) . '"></script>' . "\n";
			}

            if(MainBuilder::$modif_mode){
                $css .= '<link rel="stylesheet" href="' . self::versionedFileURL('css', 'modif_page') . '">' . "\n";
                $js .= '<script src="' . self::versionedFileURL('js', 'modif_page') . '"></script>' . "\n";
            }

            if($_SESSION['admin']){
                // édition éléments sur toutes les pages (header, footer et favicon)
                $js .= '<script src="' . self::versionedFileURL('js', 'Input') . '"></script>' . "\n";

                // tinymce, nécéssite un script de copie dans composer.json
                $css .= '<link rel="stylesheet" href="' . self::versionedFileURL('css', 'tinymce') . '">' . "\n";
                $js .= '<script src="' . self::versionedFileURL('js', 'tinymce/tinymce.min') . '"></script>' . "\n"; // pour js/tinymce/tinymce.min.js
                $js .= '<script src="' . self::versionedFileURL('js', 'tinymce') . '"></script>' . "\n";
            }

            $title = Model::$page_path->getLast()->getPageName();
            $description = Model::$page_path->getLast()->getDescription();

            // favicon
            // ?-> est l'opérateur de navigation sécurisée => LOVE!
            $favicon = Asset::USER_PATH . ($favicon_object = $node->getNodeData()->getAssetByRole('head_favicon'))?->getFileName() ?? '';
            $favicon_type = $favicon_object?->getMimeType() ?? '';

            ob_start();
            require $viewFile;
            $this->html .= ob_get_clean();
        }
    }

    static public function versionedFileURL(string $type, string $filename): string
    {
        $path = $type . '/' . $filename . '.' . $type;

        if(file_exists($path)){
            $version = substr(md5_file($path), 0, 8);
            return $path . '?v=' . $version;
        }
        return $path; // sécurité fichier absent
    }
}
