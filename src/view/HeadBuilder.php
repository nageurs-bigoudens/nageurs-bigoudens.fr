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
				$css .= '<link rel="stylesheet" href="css/' . $name . '.css">' . "\n";
			}
			$js = '';
	        foreach($js_array as $name)
			{
				$js .= '<script src="js/' . $name . '.js"></script>' . "\n";
			}

            // tinymce, nécéssite un script de copie dans composer.json
            if($_SESSION['admin']){
                $css .= '<link rel="stylesheet" href="css/tinymce.css">' . "\n";
                $js .= '<script src="js/tinymce/tinymce.min.js"></script>' . "\n";
                $js .= '<script src="js/tinymce.js"></script>' . "\n";
            }

            // titre et description
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
}
