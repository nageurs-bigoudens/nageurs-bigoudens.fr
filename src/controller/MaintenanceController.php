<?php
// src/controller/MaintenanceController.php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use App\Entity\log;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MaintenanceController
{
	static public function getLogs(EntityManager $entityManager): JsonResponse
	{
		$data = $entityManager->getRepository(Log::class)->findAll();
		if(empty($data)){
			return new JsonResponse(['success' => false]);
		}
		else{
			$view = '<h4>Table ' . TABLE_PREFIX . 'log de la base de données</h4>
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
			return new JsonResponse(['success' => true, 'view' => $view]);
		}
	}
	static public function eraseLogs(EntityManager $entityManager): JsonResponse
	{
		try{
			$table = $entityManager->getClassMetadata(Log::class)->getTableName();
			$entityManager->getConnection()->executeStatement("TRUNCATE TABLE {$table}"); // SQL donné à DBAL
			return new JsonResponse(['success' => true]);
		}
		catch(Exception $e){
			return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
		}
	}

	static public function getLastDump(EntityManager $entityManager): BinaryFileResponse|RedirectResponse
	{
		try{
			$backup_list = Backup::getBackupList();
			$nb = count($backup_list);

			if($nb <= 0){ // se produit à la première connexion en mode admin pour une raison algorithimque
				Backup::mySQLdump($entityManager, 'auto');
				$backup_list = Backup::getBackupList();
				$nb = count($backup_list);
				if($nb <= 0){ // improbable, les dossiers devraient déjà avoir été créés
					throw new RuntimeException("Le serveur a rencontré une erreur: aucun backup n'est disponible et ce n'est pas normal.");
				}
			}

			$file_path = Backup::$backup_dir . '/' . $backup_list[$nb - 1];
			$response = new BinaryFileResponse($file_path);
			$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT); // ne pas essayer de l'afficher dans le navigateur
		}
		catch(RuntimeException $e){
			$_SESSION['flash_message'] = $e->getMessage();
			$response = new RedirectResponse((string) new URL(['page' => 'maintenance']));
		}
		return $response;
	}
	static public function getAllMedia(): BinaryFileResponse|RedirectResponse
	{
		try{
			$file_path = '../var/' . UserDataService::createZip('all_media.zip', ['user_data/assets', 'user_data/images', 'user_data/media']);
			$response = new BinaryFileResponse($file_path);
			$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT); // ne pas essayer de l'afficher dans le navigateur
		}
		catch(RuntimeException $e){
			$_SESSION['flash_message'] = $e->getMessage();
			$response = new RedirectResponse((string) new URL(['page' => 'maintenance']));
		}
		return $response;
	}

	// parce qu'il faut un contrôleur
	static public function handleBackupSelection(EntityManager $entityManager, Request $request): RedirectResponse
	{
		$selected_file = $request->request->get('selected_sql');
		$url = new URL;
        if($request->query->has('from')){
            $url->addParams(['page' => $request->query->get('from')]);
        }

        try{
			if(pathinfo($selected_file)['extension'] !== 'sql'){ // pas censé se produire en fait
	        	throw new Exception("charger un fichier au format SQL");
	        }
	        Backup::restoreDatabase($entityManager, $selected_file);

	        $_SESSION['flash_message'] = "La base de données a été restaurée avec succès !!";
	    }
	    catch(Exception $e){
	    	$_SESSION['flash_message'] = "Une erreur s'est produite: " . $e->getMessage();
	    }

	    return new RedirectResponse((string)$url);
	}

	static public function downloadSQL(EntityManager $entityManager, Request $request): RedirectResponse
	{
        $uploaded_file = $request->files->get('uploaded_sql');
        $date = new DateTime;
        $server_place = Config::$database . '_' . $date->format('Y-m-d') . '_uploaded.sql';
        $url = new URL;
        if($request->query->has('from')){
            $url->addParams(['page' => $request->query->get('from')]);
        }

        try{
	        if(pathinfo($uploaded_file->getClientOriginalName())['extension'] !== 'sql'){
	        	throw new Exception("Charger un fichier au format SQL");
	        }
	        //echo $uploaded_file->getSize(); // à garder de côté au cas où

        	// enregistrer le fichier
	        $uploaded_file->move(Backup::$backup_dir, $server_place);

	        // s'en servir
	        Backup::restoreDatabase($entityManager, $server_place);

	        $_SESSION['flash_message'] = "La base de données a été restaurée avec succès !!";
	    }
	    catch(Exception $e){
	    	$_SESSION['flash_message'] = "Une erreur s'est produite: " . $e->getMessage();
	    }

	    return new RedirectResponse((string)$url);
	}
}