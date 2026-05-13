<?php
// src/controller/MaintenanceController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MaintenanceController
{
	static public function getLogs(EntityManager $entityManager): void
	{
		$data = $entityManager->getRepository(Log::class)->findAll();
		if(empty($data)){
			echo json_encode(['success' => false]);
		}
		else{
			$view = '<h4>Table ' . TABLE_PREFIX . 'log de la BDD</h4>
				<table>
					<thead>
						<tr>
	                        <th>date et heure (Greenwich)</th>
	                        <th>connexion réussie</th>
	                    </tr>
	                </thead>
	                <tbody>';
            foreach($data as $entry){
            	$view .= '<tr>
            		<td>' . $entry->getFormatedDate() . '</td>
            		<td>' . ($entry->getSuccess() ? 'oui' : 'non') . '</td>
            	</tr>';
            }
            $view .= '</tbody></table>';
			echo json_encode(['success' => true, 'view' => $view]);
		}
		die;
	}
	static public function eraseLogs(EntityManager $entityManager): void
	{
		try{
			$table = $entityManager->getClassMetadata(Log::class)->getTableName();
			$entityManager->getConnection()->executeStatement("TRUNCATE TABLE {$table}"); // SQL donné à DBAL
			echo json_encode(['success' => true]);
		}
		catch(Exception $e){
			echo json_encode(['success' => false]);
		}
		die;
	}

	static public function getLastDump(): void
	{
		try{
			$file_path = Backup::$backup_dir . '/' . Backup::getLastBackupName();
			header('Content-Type: application/octet-stream'); // signifie fichier quelconque, du binaire quoi!
			header('Content-Disposition: attachment; filename="' . basename($file_path) . '"'); // pour provoquer un téléchargement et non pour afficher
			header('Content-Length: ' . filesize($file_path)); // peut servir côté client (barre de progression...)
			readfile($file_path);
			die;
		}
		// exeptions lancées dans Backup::mySQLdump
		catch(ProcessFailedException $e){ // pas d'info $e pour le client
			header('Location: ' . new URL(['page' => 'maintenance', 'error' => '500']));
			die;
		}
		die;
	}

	// parce qu'il faut un contrôleur
	static public function handleBackupSelection(EntityManager $entityManager, string $selected_file): void
	{
		if(pathinfo($selected_file)['extension'] !== 'sql'){ // pas censé se produire en fait
        	throw new Exception("charger un fichier au format SQL");
        }

        Backup::restoreDatabase($entityManager, $selected_file);
	}

	static public function downloadSQL(EntityManager $entityManager, UploadedFile $uploaded_file): void
	{
        if(pathinfo($uploaded_file->getClientOriginalName())['extension'] !== 'sql'){
        	throw new Exception("Charger un fichier au format SQL");
        }
        //echo $uploaded_file->getSize(); // à garder de côté au cas où

        $server_place = Config::$database . '_' . new DateTime()->format('Y-m-d') . '_uploaded.sql';

        try{
        	// enregistrer le fichier
	        $uploaded_file->move(Backup::$backup_dir, $server_place);

	        // s'en servir
	        Backup::restoreDatabase($entityManager, $server_place);
	    }
	    finally{}
	}
}