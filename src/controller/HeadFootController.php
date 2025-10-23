<?php
// src/controller/HeadFootController.php

declare(strict_types=1);

//use App\Entity\Node;
//use App\Entity\NodeData;
use App\Entity\Asset;
use Doctrine\Common\Collections\ArrayCollection;
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
			$node_data->updateData($params_array[1], $json['new_text']); // $params_array[1] n'est pas contrôlé
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
			$file = $_FILES['file'];

			if(!is_dir(Asset::USER_PATH)){
	            mkdir(Asset::USER_PATH, 0700, true);
	        }

	        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'tif', 'ico', 'bmp']; // pas de SVG
			$name = Security::secureFileName(pathinfo($file['name'], PATHINFO_FILENAME));
	        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	        if(!in_array($extension, $allowed_extensions) || $extension === 'jpg'){
	            $extension = 'jpeg';
	        }
			$file_path = uniqid($name . '_') . '.' . $extension;

			if(ImageUploadController::imagickCleanImage(file_get_contents($file['tmp_name']), Asset::USER_PATH . $file_path, $extension)){ // recréer l’image pour la nettoyer
				$params_array = explode('_', $request_params); // favicon, header_logo, header_background, footer_logo

				$model = new Model($entityManager);
				if($model->findWhateverNode('name_node', $params_array[0])){
					$node_data = $model->getNode()->getNodeData();
					$image = new Asset($name, $file_path, mime_content_type($file['tmp_name']), $request_params);
					$node_data->addAsset($image);

					$entityManager->persist($image);
					$entityManager->flush();
					echo json_encode(['success' => true, 'location' => Asset::USER_PATH . $file_path]);
				}
				else{
					echo json_encode(['success' => false, 'message' => 'Erreur noeud non trouvé.']);
				}
			}
			else{
				http_response_code(500);
	            echo json_encode(['success' => false, 'message' => 'Erreur image non valide.']);
			}
		}
		die;
	}

	//static public function uploadImage(EntityManager $entityManager, array $request_params): void
}