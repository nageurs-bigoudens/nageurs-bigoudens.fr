<?php
// src/view/MaintenanceBuilder.php

declare(strict_types=1);

use App\Entity\Node;

class MaintenanceBuilder extends AbstractBuilder
{
	public function __construct(Node $node){
		$viewFile = self::VIEWS_PATH . $node->getName() . '.php';

		// noter qu'un backup a été créé dans UserController::connect()
		try{
			$backup_array = Backup::getBackupList();
		}
		catch(RuntimeException $e){
			$backup_array = [];
			$_SESSION['flash_message'] = $e->getMessage(); // peut écraser une flash error déjà dans $_SESSION['flash_message']
		}
		
		$backup_options = '';
		for($i = count($backup_array) - 1; $i >= 0; $i--){
			$backup_options .= '<option value="' . $backup_array[$i] . '">' . $backup_array[$i] . '</option>';
		}

		if(file_exists($viewFile)){
			ob_start();
		    require $viewFile;
		    $this->html = ob_get_clean();
		}
	}
}