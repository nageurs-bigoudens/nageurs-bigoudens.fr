<?php
// src/view/MainBuilder.php

declare(strict_types=1);

use App\Entity\Article;
use App\Entity\Node;

class MainBuilder extends AbstractBuilder
{
    static bool $modif_mode = false;

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
            // si action = "modif_page", affiche des commandes pour modifier
            if($_SESSION['admin'] && self::$modif_mode){
                // ajouter un contrôle du champ in_menu
                $this->viewEditBlocks($node);
            }

            // cas normal
            $this->useChildrenBuilder($node);
        }

        $this->html .= "</main>\n";
    }

    // mode modification de page uniquement
    private function viewEditBlocks($node): void
    {
        $viewFile = self::VIEWS_PATH . $node->getName() . '.php'; // mode modification uniquement

        // blocs disponibles, même liste dans post.php
        $blocks = [ // créer une classe pour ça?
            ['type' => 'blog', 'name' => 'Blog'],
            ['type' => 'grid', 'name' => 'Grille'],
            ['type' => 'calendar', 'name' => 'Calendrier'],
            ['type' => 'galery', 'name' => 'Galerie'],
            ['type' => 'form', 'name' => 'Formulaire']];

        function getBlockName(array $blocks, string $type){ // créer une classe pour ça?
            for($i=0; $i < count($blocks); $i++){ 
                if($blocks[$i]['type'] === $type){
                    return $blocks[$i]['name'];
                }
            }
        }

        $options = '';
        for($i = 0; $i < count($blocks); $i++){
            $options .= '<option value= "' . $blocks[$i]['type'] . '">' . $blocks[$i]['name'] . "</option>\n";
        }

        // blabla
        /*$this->html .= '<aside class="modif_page_explanations">
            <p>Modification de la structure d\'une page:</p>
            <div>
                <p><img></p>
                <p><img></p>
            </div>
        </aside>' . "\n";*/

        //$page_id = Director::$page_path->getLast()->getId();
        $head_node = null;
        foreach(ViewBuilder::$root_node->getChildren() as $first_level_node){
            if($first_level_node->getName() === 'head'){
                $head_node = $first_level_node; // normallement c'est le 1er enfant
                break;
            }
        }
        
        $bloc_edit = '';
        foreach($node->getChildren() as $child_node){
            // renommage d'un bloc
            $bloc_edit .= '<div id="bloc_edit_' . $child_node->getId() . '">
                <p><label for="bloc_rename_' . $child_node->getId() . '"><b>' . getBlockName($blocks, $child_node->getName()) . '</b></label>
                <input type="text" id="bloc_rename_' . $child_node->getId() . '" name="bloc_rename_title" value="' . $child_node->getNodeData()->getdata()['title'] . '" required>
                <button onclick="renamePageBloc(' . $child_node->getId() . ')">Renommer</button>'. "\n";
            // déplacement d'un bloc
            $bloc_edit .= '<img class="action_icon" onclick="switchBlocsPositions(' . $child_node->getId() . ', \'up\', \'' . CURRENT_PAGE . '\')" src="assets/arrow-up.svg">
                <img class="action_icon" onclick="switchBlocsPositions(' . $child_node->getId() . ', \'down\', \'' . CURRENT_PAGE . '\')" src="assets/arrow-down.svg">' . "\n";
            // suppression d'un bloc
            $bloc_edit .= '<form method="post" action="' . new URL(['page' => CURRENT_PAGE]) . '">
                    <input type="hidden" name="delete_bloc_id" value="' . $child_node->getId() . '">
                    <input type="hidden" name="delete_bloc_hidden">
                    <input type="submit" value="Supprimer" onclick="return confirm(\'Voulez-vous vraiment supprimer cette page?\');"></p>
                </form>
            </div>'. "\n";
        }

        ob_start();
        require $viewFile;
        $this->html .= ob_get_clean();
    }
}