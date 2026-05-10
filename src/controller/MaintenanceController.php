<?php
// src/controller/MaintenanceController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\log;
use Symfony\Component\Process\Exception\ProcessFailedException;

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

	static public function getLastDump(EntityManager $entityManager): void
	{
		try{
			$file_path = Backup::getLastBackupName();
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
}