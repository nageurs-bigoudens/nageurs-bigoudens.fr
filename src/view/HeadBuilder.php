<?php
// src/view/HeadBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class HeadBuilder extends AbstractBuilder
{
    private bool $stop = false;

    public function __construct(Node $node)
    {
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            // css et js
            if(!empty($node->getAttributes()))
            {
                extract($node->getAttributes());
            }

            // pages spéciales où on n'assemble pas tout
            $this->stop = isset($stop) ? $stop : false;
            $css = '';
	        foreach($css_array as $name)
			{
				$css .= '<link rel="stylesheet" href="' . self::versionedFileURL('css', $name) . '">' . "\n";
			}
			
            $js = '';
	        foreach($js_array as $name)
			{
				$js .= '<script src="' . self::versionedFileURL('js', $name) . '"></script>' . "\n";
			}

            if(MainBuilder::$modif_mode){
                $css .= '<link rel="stylesheet" href="' . self::versionedFileURL('css', 'modif_page') . '">' . "\n";
                $js .= '<script src="' . self::versionedFileURL('js', 'modif_page') . '"></script>' . "\n";
            }

            // tinymce, nécéssite un script de copie dans composer.json
            if($_SESSION['admin']){
                $css .= '<link rel="stylesheet" href="' . self::versionedFileURL('css', 'tinymce') . '">' . "\n";
                $js .= '<script src="' . self::versionedFileURL('js', 'tinymce/tinymce.min') . '"></script>' . "\n"; // pour js/tinymce/tinymce.min.js
                $js .= '<script src="' . self::versionedFileURL('js', 'tinymce') . '"></script>' . "\n";
            }

            // titre
            $title = Director::$page_path->getLast()->getPageName();
            
            // description
            if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }

            // favicon
            foreach($node->getNodeData()->getImages() as $image)
            {
                if(str_contains($image->getFileName(), 'favicon'))
                {
                    $favicon = rtrim($image->getFilePathMini(), '/');
                    $alt = $image->getAlt();
                }
            }

            ob_start();
            require $viewFile;
            $this->html .= ob_get_clean();
        }
    }

    public function getStop(): bool
    {
        return $this->stop;
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
