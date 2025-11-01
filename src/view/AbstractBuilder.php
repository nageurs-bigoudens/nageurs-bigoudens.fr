<?php
// src/view/AbstractBuilder.php

declare(strict_types=1);

use App\Entity\Node;

abstract class AbstractBuilder
{
	const VIEWS_PATH = '../src/view/templates/';
	protected string $html = '';
    protected int $id_node;

    protected function __construct(Node $node)
    {
        $this->id_node = $node->getId();
    }

    protected function useChildrenBuilder(Node $node): void
    {
    	foreach($node->getChildren() as $child_node)
        {
            $builder_name = $this->snakeToPascalCase($child_node->getName()) . 'Builder';
            $builder = new $builder_name($child_node);
            $this->html .= $builder->render();

            // pages spéciales où on n'assemble pas tout
            if($builder_name === 'HeadBuilder' && in_array(Model::$page_path->getString(), ['connection', 'user_edit']))
            {
                foreach($node->getChildren() as $target_node){
                    if($target_node->getName() === 'main'){
                        $main_node = $target_node;
                        break;
                    }
                }
                // on construit <main> et on s'arrête! les autres noeuds sont ignorés
                $builder_name = $this->snakeToPascalCase($main_node->getName()) . 'Builder';
                $builder = new $builder_name($main_node);
                $this->html .= "<body>\n";
                $this->html .= $builder->render() . "\n";
                $this->html .= "</body>\n</html>";
                break;
            }
        }
    }

    private function snakeToPascalCase(string $input): string
    {
    	return str_replace('_', '', ucwords($input, '_'));
    }

    public function render(): string // = getHTML(), nécéssite d'être public
    {
        return $this->html;
    }
    protected function addHTML(string $html): void
    {
        $this->html .= $html;
    }

    protected function insertSVG(string $path, array $attributes = []): string
    {
        $svg = file_get_contents($path);

        // modification des attributs
        if(!empty($attributes)){
            $dom = new DOMDocument();
            $dom->loadXML($svg);
            $svg_elem = $dom->documentElement;
            foreach($attributes as $key => $value){
                $svg_elem->setAttribute($key, $value);
            }
            $svg = $dom->saveXML($svg_elem);
        }
        return $svg;
    }
}