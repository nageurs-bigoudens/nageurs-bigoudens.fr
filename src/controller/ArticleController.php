<?php
// src/controller/ArticleController.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Article;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController
{
	static public function fetch(EntityManager $entityManager, Request $request): void
	{
		if($request->query->has('id') && !empty($request->query->get('id')) && $request->query->has('last_article')){
			//var_dump($request->query->get('last_article'));
			$id = (int)$request->get('id'); // type et nettoie
			$director = new Director($entityManager);
			$director->findNodeById($id);
			$parent_block = $director->getNode();

			if(Blocks::hasPresentation($parent_block->getName())){
				$get_articles_return = $director->getNextArticles($parent_block, $request);
				$bulk_data = $get_articles_return[0];

				if($parent_block->getName() === 'post_block'){
					$builder_name = 'PostBuilder';
				}
				elseif($parent_block->getName() === 'news_block'){
					$builder_name = 'NewBuilder';
				}

				$html = '';
				foreach($bulk_data as $article){
					$builder = new $builder_name($article);
					$html .= $builder->render();
				}

				echo json_encode(['success' => true, 'html' => $html, 'truncated' => $get_articles_return[1]]);
				die;
			}
			else{
				echo json_encode(['success' => false, 'error' => 'mauvais type de bloc']);
				die;
			}
		}
		else{
			echo json_encode(['success' => false, 'error' => 'la requête ne comporte pas les paramètres attendus']);
			die;
		}
	}

	static public function editorSubmit(EntityManager $entityManager, array $json): void
	{
		if(json_last_error() === JSON_ERROR_NONE)
	    {
	        $id = $json['id'];
	        if(in_array($id[0], ['t', 'p', 'i', 'd'])){
	        	$id = substr($id, 1);
	        }

	        $director = new Director($entityManager);
	        $content = $json['content'];

	        // nettoyage
	        if(is_array($content)){ // cas d'une nouvelle "news"
	        	foreach($content as $one_input){
	        		$one_input = Security::secureHTML($one_input);
	        	}
	        }
	        else{ // autres cas
	        	$content = Security::secureHTML($json['content']);
	        }

	        // nouvel article
	        if($json['id'][0] === 'n') // ici $id est un bloc
	        {
	        	$section_id = (int)substr($id, 1); // id du bloc <section>
	        	if(!$director->findNodeById($section_id)){ // erreur mauvais id
	        		echo json_encode(['success' => false, 'error' => 'article_not_saved, bad id']);
	        		die;
	        	}
	        	$director->makeSectionNode();
	        	$node = $director->getNode(); // = <section>
	        	
	        	if(is_array($content)){ // cas d'une nouvelle "news"
		        	if($node->getPage()->getEndOfPath() !== $json['from']){ // erreur mauvais from
		        		echo json_encode(['success' => false, 'error' => 'article_not_saved, bad from']);
		        		die;
		        	}

	                $date = new \DateTime($content['d'] . ':' . (new \DateTime)->format('s')); // l'input type="datetime-local" ne donne pas les secondes, on les ajoute: 'hh:mm' . ':ss'
	                $article = new Article($content['i'], $date, $content['t'], $content['p']);
	                $article_node = new Node('new', [], count($node->getChildren()) + 1, $node, $node->getPage(), $article);
	        	}
	        	else{ // autres cas
	        		$timestamp = time();
		        	$date = new \DateTime;
		        	$date->setTimestamp($timestamp);

		        	$article = new Article($content, $date); // le "current" timestamp est obtenu par la BDD
		        	$placement = $json['placement'] === 'first' ? 0 : count($node->getChildren()) + 1; // 
		        	$article_node = new Node('post', [], $placement, $node, $node->getPage(), $article);

		        	if($json['placement'] === 'first'){
		        		$node->addChild($article_node);
		        		$node->reindexPositions(); // régénère les positions (0 devient 1, 1 devient 2...)
		        	}
	        	}

	        	$entityManager->persist($article_node);
	        	$entityManager->flush();
	        	
	        	echo json_encode(['success' => true, 'article_id' => $article_node->getId()]);
	        	die;
	        }
	        // modification article
	        //else{}

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
					default: // modif article simple (id sans lettre devant)
						$node->getArticle()->setContent($content);
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
		if(!$director->makeArticleNode($json['id'], true)){
			return new Response(
	    		'{"success": false, "message": "Erreur: pas d\'article à supprimer"}',
                Response::HTTP_INTERNAL_SERVER_ERROR); // 500
		}
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
	    	if((string)$child->getId() === $json['id2']) // type string
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
		$id = substr($json['id'], 1);
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