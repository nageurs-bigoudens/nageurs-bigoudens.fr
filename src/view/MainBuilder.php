<?php
// src/view/MainBuilder.php

declare(strict_types=1);

use App\Entity\Article;
use App\Entity\Node;
use App\Entity\Presentation;

class MainBuilder extends AbstractBuilder
{
    static public bool $modif_mode = false;

    public function __construct(Node $node)
    {
        $this->html .= "<main>\n";

        // page article: cas particulier où l'article est greffé sur main
        if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
            // pas censé arriver
            if(!isset($_GET['id'])){
                header('Location: ' . new URL);
                die;
            }

            if($node->getAdoptedChild() == null){
                $date = new \DateTime;
                $article = new Article('', $date);
                $new = new Node('new', 'i' . (string)$date->getTimestamp(), [], 0, null, null, $article);
            }
            else{
                $new = $node->getAdoptedChild();
            }
            //$builder_name = $this->snakeToPascalCase($new->getName()) . 'Builder';
            $builder_name = 'NewBuilder';
            $builder = new $builder_name($new);
            $this->html .= $builder->render();
        }
        else{
            // si action = "modif_page", affiche des commandes supplémentaires
            if($_SESSION['admin'] && self::$modif_mode){
                // ajouter un contrôle du champ in_menu
                $this->viewEditBlocks($node);
            }

            // dans tous les cas
            $this->useChildrenBuilder($node);
        }

        $this->html .= "</main>\n";
    }

    // mode modification de page uniquement
    private function viewEditBlocks($node): void
    {
        //$viewFile = self::VIEWS_PATH . $node->getName() . '.php'; // mode modification uniquement
        $viewFile = self::VIEWS_PATH . 'modify_page.php'; // mode modification uniquement
        $blocks = Blocks::getTypeNamePairs();

        $options = '';
        for($i = 0; $i < count($blocks); $i++){
            $options .= '<option value= "' . $blocks[$i]['type'] . '">' . $blocks[$i]['name'] . "</option>\n";
        }

        //$page_id = Director::$page_path->getLast()->getId();
        $head_node = null;
        foreach(ViewController::$root_node->getChildren() as $first_level_node){
            if($first_level_node->getName() === 'head'){
                $head_node = $first_level_node; // normalement c'est le 1er enfant
                break;
            }
        }
        
        $bloc_edit = '';
        foreach($node->getChildren() as $child_node){
            // mettre tout ça dans une vue modify_block.php
            // => pourrait être déplacé au niveau des articles

            // renommage d'un bloc
            $bloc_edit .= '<div class="modify_one_block" id="bloc_edit_' . $child_node->getId() . '">
                <div class="block_options">
                    <label for="bloc_rename_' . $child_node->getId() . '">Type <b>' . Blocks::getNameFromType($child_node->getName()) . '</b></label>
                    <p>
                        <input type="text" id="bloc_rename_' . $child_node->getId() . '" name="bloc_rename_title" value="' . $child_node->getNodeData()->getdata()['title'] . '" required>
                        <button onclick="renamePageBloc(' . $child_node->getId() . ')">Renommer</button>
                    </p>'. "\n";
            // déplacement d'un bloc
            $bloc_edit .= '<div>
                    <p>
                        <img class="action_icon" onclick="switchBlocsPositions(' . $child_node->getId() . ', \'up\')" src="assets/arrow-up.svg">
                        <img class="action_icon" onclick="switchBlocsPositions(' . $child_node->getId() . ', \'down\')" src="assets/arrow-down.svg">
                    </p>' . "\n";
            // suppression d'un bloc
            $bloc_edit .= '<form method="post" action="' . new URL(['page' => CURRENT_PAGE]) . '">
                        <input type="hidden" name="delete_bloc_id" value="' . $child_node->getId() . '">
                        <input type="hidden" name="delete_bloc_hidden">
                        <input type="submit" value="Supprimer" onclick="return confirm(\'Voulez-vous vraiment supprimer ce bloc?\');">
                    </form>
                </div>
            </div>'. "\n";
            if($child_node->getNodeData()->getPresentation() !== null){
                // select mode de présentation
                $bloc_edit .= '<div class="grid_options"><p>
                    <label for="presentation_select_' . $child_node->getId() . '">Présentation</label>
                    <select id="presentation_select_' . $child_node->getId() . '" onchange="changePresentation(' . $child_node->getId() . ')">';
                $bloc_edit .= $this->makePresentationOptions($child_node->getNodeData()->getPresentation()->getName());
                $bloc_edit .= '</select>';
                // select largeur minimale colonnes mode grid
                $bloc_edit .= '<div id="cols_min_width_edit_' . $child_node->getId() . '" class="' . ($child_node->getNodeData()->getPresentation()->getName() === 'grid' ? '' : 'hidden') . '">
                    <label for="cols_min_width_select_' . $child_node->getId() . '">Largeur minimum </label>';
                $bloc_edit .= '<input type="number" id="cols_min_width_select_' . $child_node->getId() . '" onchange="changeColsMinWidth(' . $child_node->getId() . ')" min="150" max="400" value="' . $child_node->getNodeData()->getColsMinWidth() . '">';
                /*$bloc_edit .= '<select id="cols_min_width_select_' . $child_node->getId() . '" onchange="changeColsMinWidth(' . $child_node->getId() . ')">'
                    . $this->makeColsMinWidthOptions($child_node->getNodeData()->getColsMinWidth())
                    . '</select>';*/
                $bloc_edit .= ' pixels</div>
                    </div>';
            }
            $bloc_edit .= "</div>\n";
        }

        ob_start();
        require $viewFile;
        $this->html .= ob_get_clean();
    }

    private function makePresentationOptions(string $presentation): string
    {
        $options = '';
        foreach(Presentation::$option_list as $key => $value){
            $options .= '<option value="' . $key . '" ' . ($presentation === $key ? 'selected' : '') . '>' . $value . '</option>';
        }
        return $options;
    }
}