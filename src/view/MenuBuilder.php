<?php
// src/view/MenuBuilder.php
//
// page Menu et chemins en mode admin

use App\Entity\Node;
use App\Entity\Page;

class MenuBuilder extends AbstractBuilder
{
    private int $margin_left_multiplier = 29;

    public function __construct(Node $node)
    {
        parent::__construct($node);
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php';
        
        if(file_exists($viewFile))
        {
            /*if(!empty($node->getNodeData()->getData()))
            {
                extract($node->getNodeData()->getData());
            }*/

            if($_SESSION['admin'])
            {
                $this->unfoldMenu(Director::$menu_data, 0 - $this->margin_left_multiplier);
            }
            else{
                header('Location: ' . new URL);
                die;
            }

            ob_start();
            require $viewFile;
            $this->html = ob_get_clean(); // pas de concaténation ici, on écrase
        }
    }

    private function unfoldMenu(Page $menu, int $margin_left): void
    {
        $margin_left += $this->margin_left_multiplier;
        $this->html .= '<div class="level">' . "\n";

        foreach($menu->getChildren() as $entry)
        {
            $div_style = 'margin-left: ' . $margin_left . 'px;';
            $checked = $entry->isHidden() ? '' : 'checked';
            $this->html .= '<div id="' . $entry->getId() . '" style="' . $div_style . '">
                <img class="move_entry_icon" onclick="" src="assets/arrow-left.svg">
                <img class="move_entry_icon" onclick="" src="assets/arrow-right.svg">
                <img class="move_entry_icon" onclick="switchMenuPositions(' . $entry->getId() . ', \'up\')" src="assets/arrow-up.svg">
                <img class="move_entry_icon" onclick="switchMenuPositions(' . $entry->getId() . ', \'down\')" src="assets/arrow-down.svg">
                <span class="menu_entry_checkbox">
                    <input type="checkbox" ' . $checked . ' onclick="checkMenuEntry(' . $entry->getId() . ')">
                </span>
                <button>' . $entry->getPageName() . '</button>';

            if(str_starts_with($entry->getEndOfPath(), 'http')){
                $this->html .= '<span id="edit-i..."><img class="move_entry_icon" src="assets/edit.svg" onclick="openEditor(\'i...\')"></span>
                    <i>' . $entry->getEndOfPath() . '</i>
                    <span id="delete-i..."><img class="move_entry_icon" src="assets/delete-bin.svg" onclick="delete(\'i...\')"></span>';
            }
            else{
                $this->html .= '<i>' . $entry->getPagePath() . '</i>';
            }
            
            /*
            => flèche gauche: position = position du parent + 1, parent = grand-parent, recalculer les positions
            => flèche droite: position = nombre d'éléments de la fraterie + 1, l'élément précédent devient le parent
            */
            
            if(count($entry->getChildren()) > 0){
                $this->unfoldMenu($entry, $margin_left);
            }
            $this->html .= '</div>' . "\n";
        }
        $this->html .= "</div>\n";
        $margin_left -= $this->margin_left_multiplier;
    }
}