<?php
// src/controller/ajax.php

declare(strict_types=1);

use App\Entity\Article;
use App\Entity\Node;

// détection des requêtes de tinymce ou touchant aux articles
if($_SERVER['CONTENT_TYPE'] === 'application/json' && isset($_GET['action']))
{
	// récupération des données
	$data = file_get_contents('php://input');
	$json = json_decode($data, true);

	if($_GET['action'] === 'editor_submit' && isset($json['id']) && isset($json['content']))
	{
	    if(json_last_error() === JSON_ERROR_NONE)
	    {
	        $id = $json['id'];
	        $director = new Director($entityManager);

	        // cas d'une nouvelle "news"
	        if(is_array($json['content'])){
	        	foreach($json['content'] as $one_input){
	        		$one_input = Security::secureString($one_input);
	        	}
	        	$content = $json['content'];
	        }
	        else{
	        	$content = Security::secureString($json['content']);
	        }

	        // nouvel article
	        if($id[0] === 'n')
	        {
	        	$section_id = (int)substr($id, 1); // id du bloc <section>
	        	$director->makeSectionNode($section_id);
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
		        	$article_node = new Node('article', 'i' . (string)$timestamp, [], count($node->getChildren()) + 1, $node, $node->getPage(), $article);	
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
	elseif($_GET['action'] === 'delete_article' && isset($json['id']))
	{
        $director = new Director($entityManager);
		$director->makeArticleNode($json['id'], true);
        $article = $director->getArticleNode();
		$section = $director->getNode();

        $entityManager->remove($article);
        $section->removeChild($article);
        $section->sortChildren(true); // régénère les positions
        $entityManager->flush();

        // test avec une nouvelle requête qui ne devrait rien trouver
        if(!$director->makeArticleNode($json['id']))
        {
        	echo json_encode(['success' => true]);

        	// on pourrait afficher une notification "toast"
        }
        else{
        	http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de l\'article.']);
        }
		die;
	}
	// inversion de la position de deux noeuds
	elseif($_GET['action'] === 'switch_positions' && isset($json['id1']) && isset($json['id2']))
	{
		$director = new Director($entityManager);
		$director->makeArticleNode($json['id1'], true);
        $article1 = $director->getArticleNode();
		$section = $director->getNode();

        $section->sortChildren(true); // régénère les positions avant inversion

        $article2;
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
	elseif($_GET['action'] === 'date_submit' && isset($json['id']) && isset($json['date']))
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

// détection des requêtes d'upload d'image de tinymce
if(strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false && isset($_GET['action']) && $_GET['action'] === 'upload_image'){
	if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        $dest = 'images/';
        $dest_mini = 'images-mini/';
        
        // Vérifier si les répertoires existent, sinon les créer
        if(!is_dir($dest)) {
            mkdir($dest, 0700, true);
        }
        if(!is_dir($dest_mini)) {
            mkdir($dest_mini, 0700, true);
        }
        
        $filePath = $dest . basename($file['name']);

        // créer une miniature de l'image

        if(move_uploaded_file($file['tmp_name'], $filePath)) {
            $image_url = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
            echo json_encode(['location' => $image_url . $filePath]); // renvoyer l'URL de l'image téléchargée
        }
        else{
            http_response_code(500);
            echo json_encode(['message' => 'Erreur 500: Internal Server Error']);
        }
    }
    else{
        http_response_code(400);
        echo json_encode(['message' => 'Erreur 400: Bad Request']);
    }
    die;
}

if($_SERVER['CONTENT_TYPE'] === 'application/json' && isset($_GET['menu_edit']))
{
	// récupération des données
	$data = file_get_contents('php://input');
	$json = json_decode($data, true);
	Director::$menu_data = new Menu($entityManager);

	if($_GET['menu_edit'] === 'switch_positions' && isset($json['id1']) && isset($json['id2']))
	{
        $id1 = $json['id1'];
        $id2 = $json['id2'];

        // vérifier qu'ils ont le même parent
        $page1 = Director::$menu_data->findPageById((int)$id1);
        $page2 = Director::$menu_data->findPageById((int)$id2);

        // double le contrôle fait en JS
        if($page1->getParent() === $page2->getParent()) // comparaison stricte d'objet (même instance du parent?)
        {
        	// inversion
	        $tmp = $page1->getPosition();
	        $page1->setPosition($page2->getPosition());
	        $page2->setPosition($tmp);
	        Director::$menu_data->sortChildren(true); // modifie tableau children 
	        $entityManager->flush();
	        
	        // nouveau menu
	        $nav_builder = new NavBuilder();
	        echo json_encode(['success' => true, 'nav' => $nav_builder->render()]);
        }
        else{
        	echo json_encode(['success' => false]);
        }
	    
	    die;
	}

	if($_GET['menu_edit'] === 'displayInMenu' && isset($json['id']) && isset($json['checked']))
	{
		$id = $json['id'];
		$checked = $json['checked'];

		$page = Director::$menu_data->findPageById((int)$id);
		if($page->isHidden() === $checked){
			$page->setHidden(!$checked);
			$entityManager->flush();

			// nouveau menu
			$nav_builder = new NavBuilder();
			echo json_encode(['success' => true, 'nav' => $nav_builder->render()]);
		}
		else{
			echo json_encode(['success' => false]);
		}
		die;
	}
}

// détection des requêtes de type XHR?, pas d'utilité pour l'instant
/*elseif(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
	echo "requête XHR reçue par le serveur";
	die;
}*/


