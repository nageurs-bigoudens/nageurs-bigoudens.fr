<?php
// src/view/MenuBuilder.php
//
// page Menu et chemins en mode admin, fonctionne avec menu.js

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Page;

class MenuBuilder extends AbstractBuilder
{
    //private int $margin_left_multiplier = 29;
    private string $options = '';

    public function __construct(?Node $node, bool $template = true)
    {
        // impossible de me rappeler pourquoi j'ai écrit ce test sur $node, pourquoi $node serait null?
        $viewFile = $node === null ? self::VIEWS_PATH . 'menu.php' : self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            if($_SESSION['admin']){
                $this->unfoldMenu(Model::$menu);
                
                if($template){
                    $this->unfoldOptions(Model::$menu);
                }
            }
            else{
                header('Location: ' . new URL);
                die;
            }

            // si faux, n'utilise pas le template
            if($template){
                ob_start();
                require $viewFile; // insertion de $this->html généré par unfoldMenu
                $this->html = ob_get_clean(); // pas de concaténation .= cette fois on écrase
            }
        }
    }

    private function unfoldMenu(Page $page): void
    {
        $this->html .= '<div class="level">' . "\n";

        foreach($page->getChildren() as $entry)
        {
            $checked = $entry->isHidden() ? '' : 'checked';
            $this->html .= '<div id="' . $entry->getId() . '" class="menu_edit_entry">
                <img class="move_entry_icon" onclick="moveOneLevelUp(' . $entry->getId() . ')" src="assets/arrow-left.svg">
                <img class="move_entry_icon" onclick="moveOneLevelDown(' . $entry->getId() . ')" src="assets/arrow-right.svg">
                <img class="move_entry_icon" onclick="switchMenuPositions(' . $entry->getId() . ', \'up\')" src="assets/arrow-up.svg">
                <img class="move_entry_icon" onclick="switchMenuPositions(' . $entry->getId() . ', \'down\')" src="assets/arrow-down.svg">
                <span class="menu_entry_checkbox">
                    <input type="checkbox" ' . $checked . ' onclick="checkMenuEntry(' . $entry->getId() . ')">
                </span>';

            // entrées URL: bouton poubelle + nom du lien + adresse
            if(str_starts_with($entry->getEndOfPath(), 'http')){
                $this->html .= '<form style="display: inline;" id="delete-i' . $entry->getId() . '" method="post" action="' . new URL(['from' => 'menu_chemins']) . '">
                        <input type="hidden" name="delete" value="' . $entry->getId() . '">
                        <input type="image" class="move_entry_icon" src="assets/delete-bin.svg" alt="delete link button" onclick="return confirm(\'Voulez-vous vraiment supprimer cette entrée?\');">
                    </form>
                    <span class="url_name">
                        <button class="url_name"><input type="text" value="' . htmlspecialchars($entry->getPageName()) . '"></button>
                        <img class="move_entry_icon" src="assets/save.svg" onclick="editUrl(' . $entry->getId() . ', \'url_name\')">
                    </span>
                    <span class="url_content">
                        <input type="url" value="' . htmlspecialchars($entry->getEndOfPath()) . '" placeholder="https://..." onchange="controlURL(this)">
                        <img class="move_entry_icon" src="assets/save.svg" onclick="editUrl(' . $entry->getId() . ', \'url_content\')">
                    </span>';
            }
            else{
                $this->html .= '<button>' . $entry->getPageName() . '</button>
                    <i class="path">' . $entry->getPagePath() . '</i>';
            }
            
            if(count($entry->getChildren()) > 0){
                $this->unfoldMenu($entry);
            }
            $this->html .= '</div>' . "\n";
        }
        $this->html .= "</div>\n";
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