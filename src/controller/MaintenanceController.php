<?php
// src/controller/MaintenanceController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\log;

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
}