<?php
// src/controller/ArticleController.php

declare(strict_types=1);

use App\Entity\Node;
use App\Entity\Article;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ArticleController
{
	static public function fetch(EntityManager $entityManager, Request $request): JsonResponse
	{
		if($request->query->has('id') && !empty($request->query->get('id')) && $request->query->has('last_article')){
			$id = (int)$request->get('id'); // type et nettoie
			$model = new Model($entityManager);
			$model->findNodeById($id);
			$parent_block = $model->getNode();

			if(Blocks::hasPresentation($parent_block->getName())){
				$get_articles_return = $model->getNextArticles($parent_block, $request);
				$bulk_data = $get_articles_return[0];

				if($parent_block->getName() === 'post_block'){
					$builder_name = 'PostBuilder';
				}
				elseif($parent_block->getName() === 'news_block'){
					$builder_name = 'NewBuilder';
				}
				else{
					return new JsonResponse(['success' => false, 'error' => 'server side error'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
				}

				$html = '';
				foreach($bulk_data as $article){
					$builder = new $builder_name($article);
					$html .= $builder->render();
				}

				return new JsonResponse(['success' => true, 'html' => $html, 'truncated' => $get_articles_return[1]]);
			}
			else{
				return new JsonResponse(['success' => false, 'error' => 'server side error']);
			}
		}
		else{
			return new JsonResponse(['success' => false, 'error' => 'bad parameters']);
		}
	}

	static public function editorSubmit(EntityManager $entityManager, array $json): JsonResponse
	{
		if(json_last_error() === JSON_ERROR_NONE){
	        $id = $json['id'];
	        if(in_array($id[0], ['t', 'p', 'i', 'd'])){
	        	$id = substr($id, 1);
	        }

	        $model = new Model($entityManager);
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
	        if($json['id'][0] === 'n'){ // ici $id est un bloc
	        	$section_id = (int)substr($id, 1); // id du bloc <section>
	        	if(!$model->findNodeById($section_id)){ // erreur mauvais id
	        		return new JsonResponse(['success' => false, 'error' => 'article_not_saved, bad id']);
	        	}
	        	$model->makeSectionNode();
	        	$section = $model->getNode();
	        	
	        	// ajout d'une news
	        	if(is_array($content)){
		        	if($section->getPage()->getEndOfPath() !== $json['from']){ // erreur mauvais from
		        		return new JsonResponse(['success' => false, 'error' => 'article_not_saved, bad from']);
		        	}

	                $date = new \DateTime($content['d'] . ':' . (new \DateTime)->format('s')); // l'input type="datetime-local" ne donne pas les secondes, on les ajoute: 'hh:mm' . ':ss'
	                $article = new Article($content['i'], $date, $content['t'], $content['p']);
	                $article_node = new Node('new', count($section->getChildren()) + 1, $section, $section->getPage(), $article);
	        	}
	        	// ajout d'un post
	        	else{
	        		$timestamp = time();
		        	$date = new \DateTime;
		        	$date->setTimestamp($timestamp);

		        	$article = new Article($content, $date);
		        	$placement = $json['placement'] === 'first' ? 0 : count($section->getChildren()) + 1; // 
		        	$article_node = new Node('post', $placement, $section, $section->getPage(), $article);

		        	if($json['placement'] === 'first'){
		        		$section->addChild($article_node);
		        		$section->sortChildren(true); // mettre $article_node au début du tableau puis régénérer les positions (0 devient 1, 1 devient 2...)
		        	}
	        	}

	        	$entityManager->persist($article_node);
	        	$entityManager->flush();
	        	
	        	return new JsonResponse(['success' => true, 'article_id' => $article_node->getId()]);
	        }
	        // modification article
	        //else{}

	        if($model->makeArticleNode($id)){ // une entrée est trouvée
	        	$node = $model->getArticleNode();
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
						return new JsonResponse(['success' => false, 'message' => 'l\'action editor_submit ne supporte pas les dates, utiliser date_submit.']);
					default: // modif article simple (id sans lettre devant)
						$node->getArticle()->setContent($content);
				}
		        $entityManager->flush();
		        return new JsonResponse(['success' => true]);
	        }
	        else{
	        	return new JsonResponse(['success' => false, 'message' => 'article non identifié']);
	        }
	    }
	    else{
	        return new JsonResponse(['success' => false, 'message' => 'Erreur de décodage JSON']);
	    }
	}

	static public function deleteArticle(EntityManager $entityManager, Request $request): Response
	{
		$model = new Model($entityManager);

		if($request->headers->get('Content-Type') === 'application/json'){
			$id = json_decode($request->getContent(), true)['id'];
		}
		elseif($request->headers->get('Content-Type') === 'application/x-www-form-urlencoded'){
			$id = $request->query->get('id');
		}
		// ni JSON ni form, c'est quoi? un POST vide?
		else{
			return new Response('la méthode deleteArticle ne peut être appelée de cette manière');
		}

		if(!$model->makeArticleNode($id, true)){
			$params = ['false', "Erreur 500 pas d\'article à supprimer"];
		}
		else{
			$article = $model->getArticleNode();
			$section = $model->getNode();

		    $entityManager->remove($article);
		    $section->removeChild($article);
		    $section->sortChildren(true); // régénère les positions

		    try{
		    	$entityManager->flush();
		    	$params = ['true', 'Article supprimé avec succès'];
		    }
		    catch(Exception $e){
		    	$params = ['false', 'Erreur 500 ' . $e->getMessage()];
		    }
		}

		if($request->headers->get('Content-Type') === 'application/json'){
	    	return new JsonResponse(
	    		['success' => $params[0], 'message' => $params[1]],
	    		$params[0] ? JsonResponse::HTTP_OK : JsonResponse::HTTP_INTERNAL_SERVER_ERROR
	    	);
	    }
	    elseif($request->headers->get('Content-Type') === 'application/x-www-form-urlencoded'){
	    	$url = new URL(['page' => $request->query->get('from') ?? '', 'success' => $params[0], 'message' => $params[1]]);
			return new RedirectResponse((string)$url);
		}
		else{
			// cas inaccesible
			throw new Exception('la méthode deleteArticle ne peut être appelée de cette manière');
		}
	}

	static public function switchPositions(EntityManager $entityManager, array $json): JsonResponse
	{
		$model = new Model($entityManager);
		$model->makeArticleNode($json['id1'], true);
	    $article1 = $model->getArticleNode();
		$section = $model->getNode();

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

		return new JsonResponse(['success' => true]);
	}

	static public function dateSubmit(EntityManager $entityManager, array $json): JsonResponse
	{
		$id = substr($json['id'], 1);
		$date = new DateTime($json['date']);

		$model = new Model($entityManager);
		$model->makeArticleNode($id);
	    $node = $model->getArticleNode();
		$node->getArticle()->setDateTime($date);
		$entityManager->flush();
		
		return new JsonResponse(['success' => true]);
	}
}