<?php
// src/view/NewPageBuilder.php
//
// page Nouvelle page en mode admin, fonctionne avec new_page.js

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Page;

class NewPageBuilder extends AbstractBuilder
{
    //private int $margin_left_multiplier = 29;
    private string $options = '';

    public function __construct(Node $node = null)
    {
        //parent::__construct($node);
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(isset($_SESSION['admin']) && $_SESSION['admin'] && file_exists($viewFile))
        {
            /*if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }*/

            $this->unfoldOptions(Director::$menu_data);

            ob_start();
            require $viewFile; // insertion de $this->html généré par unfoldMenu
            $this->html = ob_get_clean(); // pas de concaténation .= cette fois on écrase
        }
        else{
            header('Location: ' . new URL);
            die;
        }
    }

    private function unfoldOptions(Page $page): void
    {
        foreach($page->getChildren() as $entry){
            $this->options .= '<option value="' . $entry->getId() . '">' . $entry->getPageName() . "</option>\n";
            if(count($entry->getChildren()) > 0){
                $this->unfoldOptions($entry);
            }
        }
    }
}