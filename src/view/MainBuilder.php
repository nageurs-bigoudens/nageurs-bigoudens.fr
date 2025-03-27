<?php
// src/view/MainBuilder.php

use App\Entity\Node;

class MainBuilder extends AbstractBuilder
{
    public function __construct(Node $node)
    {
        $this->html .= "<main>\n";

        if(Director::$page_path->getLast()->getEndOfPath() === 'article'){
            if($node->getTempChild() == null){
                $new = new Node;
            }
            else{
                $new = $node->getTempChild();
            }
            //$builder_name = $this->snakeToPascalCase($new->getName()) . 'Builder';
            $builder_name = 'NewBuilder';
            $builder = new $builder_name($new);
            $this->html .= $builder->render();
        }
        else{
            $this->useChildrenBuilder($node);
        }

        $this->html .= "</main>\n";
    }
}
