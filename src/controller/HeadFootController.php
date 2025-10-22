<?php
// src/controller/HeadFootController.php

declare(strict_types=1);

//use App\Entity\Node;
//use App\Entity\NodeData;
//use App\Entity\Image;
//use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class HeadFootController
{
	static public function setTextData(EntityManager $entityManager, array $request_params, array $json): void
	{
		if(count($request_params) !== 2){
			echo json_encode(['success' => false]);
			die;
		}

		$model = new Model($entityManager);
		if($model->findWhateverNode('name_node', $request_params[0])){
			$node_data = $model->getNode()->getNodeData();
			$node_data->updateData($request_params[1], htmlspecialchars($json['new_text'])); // $request_params[1] n'est pas contrôlé
			$entityManager->flush();
			echo json_encode(['success' => true]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
}