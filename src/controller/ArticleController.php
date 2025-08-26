<?php
// src/controller/ArticleController.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Article;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;

class ArticleController
{
	static public function editorSubmit(EntityManager $entityManager, array $json): void
	{
		if(json_last_error() === JSON_ERROR_NONE)
	    {
	        $id = $json['id'];
	        $director = new Director($entityManager);

	        // cas d'une nouvelle "news"
	        if(is_array($json['content'])){
	        	foreach($json['content'] as $one_input){
	        		$one_input = Security::secureHTML($one_input);
	        	}
	        	$content = $json['content'];
	        }
	        else{
	        	$content = Security::secureHTML($json['content']);
	        }

	        // nouvel article
	        if($id[0] === 'n')
	        {
	        	$section_id = (int)substr($id, 1); // id du bloc <section>
	        	$director->findNodeById($section_id);
	        	$director->makeSectionNode();
	        	$node = $director->getNode(); // = <section>

	        	if(is_array($content)){
	                $date = new \DateTime($content['d']);
	                $article = new Article($content['i'], $date, $content['t'], $content['p']);
	                $article_node = new Node('new', 'i' . (string)$date->getTimestamp(), [], count($node->getChildren()) + 1, $node, $node->getPage(), $article);

	        		// id_node tout juste généré
	        		//$article_node->getId();
	        	}
	        	else{
	        		$timestamp = time();
		        	$date = new \DateTime;
		        	$date->setTimestamp($timestamp);

		        	$article = new Article($content, $date); // le "current" timestamp est obtenu par la BDD
		        	$article_node = new Node('post', 'i' . (string)$timestamp, [], count($node->getChildren()) + 1, $node, $node->getPage(), $article);	
	        	}

	        	$entityManager->persist($article_node);
	        	$entityManager->flush();
	        	
	        	echo json_encode(['success' => true, 'article_id' => $article_node->getArticleTimestamp()]);
	        	die;
	        }
	        // modification article
	        else{
	        	$id[0] = 'i'; // id de l'article node
	        }

	        if($director->makeArticleNode($id)) // une entrée est trouvée
	        {
	        	$node = $director->getArticleNode(); // article
	        	switch($json['id'][0]){
					case 'i':
						$node->getArticle()->setContent($content);
						break;
					case 'p':
						$node->getArticle()->setPreview($content); // html de l'éditeur
						break;
					case 't':
						$node->getArticle()->setTitle($content); // html de l'éditeur
						break;
					case 'd':
						echo json_encode(['success' => false, 'message' => 'l\'action editor_submit ne supporte pas les dates, utiliser date_submit.']);
						die;
					default:
						echo json_encode(['success' => false, 'message' => 'identifiant non utilisable']);
						die;
				}
		        $entityManager->flush();
		        echo json_encode(['success' => true]);
	        }
	        else
	        {
	        	echo json_encode(['success' => false, 'message' => 'article non identifié']);
	        }
	    }
	    else{
	        echo json_encode(['success' => false, 'message' => 'Erreur de décodage JSON']);
	    }
	    die;
	}

	static public function deleteArticle(EntityManager $entityManager, array $json): Response
	{
		$director = new Director($entityManager);
		$director->makeArticleNode($json['id'], true);
	    $article = $director->getArticleNode();
		$section = $director->getNode();

	    $entityManager->remove($article);
	    $section->removeChild($article);
	    $section->sortChildren(true); // régénère les positions

	    try{
	    	$entityManager->flush();
	    	return new Response(
	    		'{"success": true, "message": "Article supprimé avec succès"}',
                Response::HTTP_OK); // 200
	    }
	    catch(Exception $e){
	    	return new Response(
	    		'{"success": false, "message": "Erreur: ' . $e->getMessage() . '"}',
                Response::HTTP_INTERNAL_SERVER_ERROR); // 500
	    }
	}

	static public function switchPositions(EntityManager $entityManager, array $json): void
	{
		$director = new Director($entityManager);
		$director->makeArticleNode($json['id1'], true);
	    $article1 = $director->getArticleNode();
		$section = $director->getNode();

	    $section->sortChildren(true); // régénère les positions avant inversion

	    $article2 = null;
	    foreach($section->getChildren() as $child){
	    	if($child->getArticleTimestamp() === $json['id2']) // type string
	    	{
	    		$article2 = $child;
	    		break;
	    	}
	    }

	    // inversion
	    $tmp = $article1->getPosition();
	    $article1->setPosition($article2->getPosition());
	    $article2->setPosition($tmp);
	    $entityManager->flush();

		echo json_encode(['success' => true]);
		die;
	}

	static public function dateSubmit(EntityManager $entityManager, array $json): void
	{
		$id = $json['id'];
		$id[0] = 'i';
		$date = new DateTime($json['date']);

		$director = new Director($entityManager);
		$director->makeArticleNode($id);
	    $node = $director->getArticleNode();
		$node->getArticle()->setDateTime($date);
		$entityManager->flush();
		
		echo json_encode(['success' => true]);
		die;
	}
}