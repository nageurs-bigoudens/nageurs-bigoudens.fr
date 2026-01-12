<?php
// src/controller/HeadFootController.php

declare(strict_types=1);

use App\Entity\NodeData;
use App\Entity\Asset;
use App\Entity\AssetEmployment;
use Doctrine\ORM\EntityManager;

class HeadFootController
{
	static public function setTextData(EntityManager $entityManager, string $request_params, array $json): void
	{
		$params_array = explode('_', $request_params); // header_title, header_description, footer_name, footer_address, footer_email
		if(count($params_array) !== 2){
			echo json_encode(['success' => false]);
			die;
		}

		$model = new Model($entityManager);
		if($model->findWhateverNode('name_node', $params_array[0])){
			$node_data = $model->getNode()->getNodeData();

			// liens réseaux sociaux
			if(in_array($params_array[1], NodeData::$social_networks)){
				$social = $node_data->getData()['social'] ?? [];
				$social[$params_array[1]] = $json['new_text'];
				$node_data->updateData('social', $social);
			}
			// autres textes
			else{
				$node_data->updateData($params_array[1], $json['new_text']); // $params_array[1] n'est pas contrôlé
			}

			$entityManager->flush();
			echo json_encode(['success' => true]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
	static public function uploadAsset(EntityManager $entityManager, string $request_params): void
	{
		if(empty($_FILES)){
			http_response_code(400);
			echo json_encode(['success' => false]);
		}
		else{
			if(!is_dir(Asset::USER_PATH)){
	            mkdir(Asset::USER_PATH, 0777, true);
	        }

	        /* -- téléchargement -- */
	        $file = $_FILES['file'];
	        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'tif', 'ico', 'bmp']; // pas de SVG
	        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	        if(!in_array($extension, $allowed_extensions) || $extension === 'jpg'){
	            $extension = 'jpeg';
	        }
			$mime_type = mime_content_type($file['tmp_name']);
			$hash = hash_file('sha256', $file['tmp_name']);

			/* -- instance d'Asset -- */
			$model = new Model($entityManager);
			$result = $model->getWhatever('App\Entity\Asset', 'hash', $hash);

			if(count($result) > 0){ // asset existant trouvé
				$asset = $result[0];

				// correction des informations
				$name = $asset->getFileName(); // permet à priori de réécrire par dessus le précédent fichier
				//$asset->setFileName($name);
				$asset->setMimeType($mime_type);
			}
			else{
				$name = Security::secureFileName(pathinfo($file['name'], PATHINFO_FILENAME));
				$name = uniqid($name . '_') . '.' . $extension;
				$asset = new Asset($name, $mime_type, $hash);
			}

			/* -- écriture du fichier sur le disque -- */
			if(!ImageUploadController::imagickCleanAndWriteImage(file_get_contents($file['tmp_name']), Asset::USER_PATH . $name, $extension)){ // recréer l’image pour la nettoyer
				http_response_code(500);
	            echo json_encode(['success' => false, 'message' => 'Erreur image non valide.', 'format' => $extension]);
			}
			else{
				$params_array = explode('_', $request_params); // head_favicon, header_logo, header_background, footer_logo

				/* -- table intermédiaire node_data/asset-- */
				if($model->findWhateverNode('name_node', $params_array[0])){ // noeud (head, header ou footer)
					$node_data = $model->getNode()->getNodeData();
					
					// recherche à l'aide du rôle
					$old_nda = null;
					foreach($node_data->getNodeDataAssets() as $nda){
						if($nda->getRole() === $request_params){
							$old_nda = $nda;
							$old_nda->setAsset($asset);
			                break;
			            }
					}
					// entrée pas trouvée
					if($old_nda === null){
						$new_nda = new AssetEmployment($node_data, $asset, $request_params); // $request_params sera le rôle de l'asset
						$entityManager->persist($new_nda);
					}
					
					if(count($result) === 0){
						$entityManager->persist($asset);
					}
					$entityManager->flush();
					echo json_encode(['success' => true, 'location' => Asset::USER_PATH . $name, 'mime_type' => $mime_type]);
				}
				else{
					http_response_code(500);
					echo json_encode(['success' => false, 'message' => "Erreur noeud non trouvé, c'est pas du tout normal!"]);
				}
			}
		}
		die;
	}

	static public function displaySocialNetwork(EntityManager $entityManager, string $request_params, array $json): void
	{
		$params_array = explode('_', $request_params);
		if(count($params_array) !== 2){
			echo json_encode(['success' => false]);
			die;
		}

		$model = new Model($entityManager);
		if(in_array($params_array[1], NodeData::$social_networks) && $model->findWhateverNode('name_node', $params_array[0])){
			$node_data = $model->getNode()->getNodeData();
			$social_show = $node_data->getData()['social_show'] ?? [];
			$social_show[$params_array[1]] = $json['checked'];
			$node_data->updateData('social_show', $social_show);

			$entityManager->flush();
			echo json_encode(['success' => true, 'checked' => $json['checked']]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
}