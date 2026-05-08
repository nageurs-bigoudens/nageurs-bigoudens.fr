<?php
// src/view/MaintenanceBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class MaintenanceBuilder extends AbstractBuilder
{
	public function __construct(Node $node){
		$viewFile = self::VIEWS_PATH . $node->getName() . '.php';

		if(file_exists($viewFile)){
			ob_start();
		    require $viewFile;
		    $this->html = ob_get_clean();
		}
	}
}