<?php
// src/controller/ajax.php

declare(strict_types=1);

use App\Entity\Page;
use App\Entity\Node;
use App\Entity\Article;


// mettre ça ailleurs
function imagickCleanImage(string $image_data, string $local_path, string $format = 'jpeg'): bool // "string" parce que file_get_contents...
{
    try{
        $imagick = new Imagick();
        $imagick->readImageBlob($image_data);
        $imagick->stripImage(); // nettoyage métadonnées
        $imagick->setImageFormat($format);
        if($format === 'jpeg'){
            $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(85); // optionnel
        }
        $imagick->writeImage($local_path); // enregistrement
        $imagick->clear();
        $imagick->destroy();
        return true;
    }
    catch(Exception $e){
        return false;
    }
}
function curlDownloadImage(string $url, $maxRetries = 3, $timeout = 10): string|false
{
    $attempt = 0;
    $imageData = false;

    while($attempt < $maxRetries){
        $ch = curl_init($url); // instance de CurlHandle
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'TinyMCE-Image-Downloader');

        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //$curlError = curl_error($ch);

        curl_close($ch);

        if($imageData !== false && $httpCode >= 200 && $httpCode < 300){
            return $imageData;
        }

        $attempt++;
        sleep(1);
    }

    return false; // échec après trois tentatives
}


// détection des requêtes d'upload d'image de tinymce
if(strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false && isset($_GET['action']) && $_GET['action'] === 'upload_image')
{
	if(isset($_FILES['file'])){
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
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'tif'];
        $name = Security::secureFileName(pathinfo($file['name'], PATHINFO_FILENAME));
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(!in_array($extension, $allowed_extensions) || $extension === 'jpg'){
            $extension = 'jpeg';
        }
        $file_path = $dest . $name . '_' . uniqid() . '.' . $extension;

        // créer une miniature de l'image
        //

        if(imagickCleanImage(file_get_contents($file['tmp_name']), $file_path, $extension)){ // recréer l’image pour la nettoyer
            echo json_encode(['location' => $file_path]); // renvoyer l'URL de l'image téléchargée
        }
        else{
            http_response_code(500);
            echo json_encode(['message' => 'Erreur image non valide']);
        }
    }
    else{
        http_response_code(400);
        echo json_encode(['message' => 'Erreur 400: Bad Request']);
    }
    die;
}
// cas du collage d'un contenu HTML, réception d'une URL, téléchargement par le serveur et renvoie de l'adresse sur le serveur 
elseif(isset($_GET['action']) && $_GET['action'] == 'upload_image_url'){
    $json = json_decode(file_get_contents('php://input'), true);
    
    if(isset($json['image_url'])){
        $image_data = curlDownloadImage($json['image_url']); // téléchargement de l’image par le serveur avec cURL au lieu de file_get_contents
        $dest = 'images/';
        
        if(!is_dir($dest)) { // Vérifier si le répertoire existe, sinon le créer
            mkdir($dest, 0777, true);
        }

        if($image_data === false){
            http_response_code(400);
            echo json_encode(['message' => "Erreur, le serveur n'a pas réussi à télécharger l'image."]);
            die;
        }
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'tif'];
        $url_path = parse_url($json['image_url'], PHP_URL_PATH);
        $name = Security::secureFileName(pathinfo($url_path, PATHINFO_FILENAME));
        $extension = strtolower(pathinfo($url_path, PATHINFO_EXTENSION));
        if(!in_array($extension, $allowed_extensions) || $extension === 'jpg'){
            $extension = 'jpeg';
        }
        $local_path = $dest . $name . '_' . uniqid() . '.' . $extension;
        
        if(imagickCleanImage($image_data, $local_path, $extension)){ // recréer l’image pour la nettoyer
            echo json_encode(['location' => $local_path]); // nouvelle adresse
        }
        else{
            http_response_code(500);
            echo json_encode(['message' => 'Erreur image non valide']);
        }
    }
    else{
        echo json_encode(['message' => 'Erreur 400: Bad Request']);
    }
    die;
}


// détection des requêtes de type XHR, y en a pas à priori
/*elseif(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
	echo "requête XHR reçue par le serveur";
	die;
}*/


// détection des requêtes envoyées avec fetch (application/json) et récupération du JSON
if($_SERVER['CONTENT_TYPE'] === 'application/json'){
	$data = file_get_contents('php://input');
	$json = json_decode($data, true);

	// requêtes de tinymce ou touchant aux articles
	if(isset($_GET['action']))
	{
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


	/* -- page Menu et chemins -- */
	elseif(isset($_GET['menu_edit']))
	{
		// récupération des données
		$data = file_get_contents('php://input');
		$json = json_decode($data, true);
		Director::$menu_data = new Menu($entityManager);

		// flèche gauche <=: position = position du parent + 1, parent = grand-parent, recalculer les positions
		if($_GET['menu_edit'] === 'move_one_level_up' && isset($json['id'])){
			$id = $json['id'];
			$page = Director::$menu_data->findPageById((int)$id);

	        $parent = $page->getParent(); // peut être null
	        if($parent === null){
	            // 1er niveau: ne rien faire
	            echo json_encode(['success' => false]);
	            die;
	        }
	        // BDD
	        else{
	            $page->setPosition($parent->getPosition() + 1); // nouvelle position

	            // 2ème niveau: le parent devient $menu_data, puis null après tri
	            if($parent->getParent() === null){
	                // connexion dans les deux sens
	                $page->setParent(Director::$menu_data); // => pour la persistance
	                
	                //Director::$menu_data->addChild($page); // => pour sortChildren
	                $page->getParent()->addChild($page); // => pour sortChildren
	                //Director::$menu_data->sortChildren(true); // positions décaléees des nouveaux petits frères
	                $page->getParent()->sortChildren(true); // positions décaléees des nouveaux petits frères
	                $page->setParent(null);

	                // affichage
	                $page->setPagePath($page->getEndOfPath());
	                $page->fillChildrenPagePath();
	            }
	            // 3ème niveau et plus
	            else{
	                $page->setParent($parent->getParent()); // nouveau parent
	                $page->getParent()->addChild($page); // => pour sortChildren
	                $page->getParent()->sortChildren(true); // positions décaléees des nouveaux petits frères
	                $page->fillChildrenPagePath($page->getParent()->getPagePath());
	            }
	            //$parent->sortChildren(true); // positions des enfants restants, inutile si la fonction est récursive?
	            $entityManager->flush();
	            
	            // affichage
	            $parent->removeChild($page);
	            $nav_builder = new NavBuilder();
		        $menu_builder = new MenuBuilder(null, false);
				echo json_encode(['success' => true, 'nav' => $nav_builder->render(), 'menu_buttons' => $menu_builder->render()]);
				die;
	        }
		}

		// flèche droite =>: position = nombre d'éléments de la fraterie + 1, l'élément précédent devient le parent
		if($_GET['menu_edit'] === 'move_one_level_down' && isset($json['id'])){
			$id = $json['id'];
			$page = Director::$menu_data->findPageById((int)$id);

	        $parent = $page->getParent(); // peut être null
	        if($parent == null){
	            $parent = Director::$menu_data;
	        }

	        // BDD
	        $parent->sortChildren(true); // trie et réindexe par sécurité: 1, 2, 3...
	        if($page->getPosition() > 1){
	            foreach($parent->getChildren() as $child){
	                if($child->getPosition() === $page->getPosition() - 1){
	                    $page->setParent($child);
	                    break;
	                }
	            }
	            $page->setPosition(count($page->getParent()->getChildren()) + 1);
	        }
	        $entityManager->flush();

	        // affichage
			$parent->removeChild($page);
	        $page->getParent()->addChild($page);
	        $page->fillChildrenPagePath($page->getParent()->getPagePath()); // variable non mappée $page_path
	        $nav_builder = new NavBuilder();
			$menu_builder = new MenuBuilder(null, false);

			echo json_encode(['success' => true, 'nav' => $nav_builder->render(), 'menu_buttons' => $menu_builder->render()]);
			die;
		}

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


	/* -- mode Modification d'une page -- */

	// partie "page"
	elseif(isset($_GET['page_edit']))
	{
		// récupération des données
		$data = file_get_contents('php://input');
		$json = json_decode($data, true);

		// titre de la page
		if($_GET['page_edit'] === 'page_title'){
			$page = $entityManager->find('App\Entity\Page', $json['page_id']);
			$page->setPageName(htmlspecialchars($json['title']));
			$entityManager->flush();
			echo json_encode(['success' => true, 'title' => $page->getPageName()]);
		}
		// titre en snake_case pour le menu
		/*elseif($_GET['page_edit'] === 'page_menu_path'){
			$page = $entityManager->find('App\Entity\Page', $json['page_id']);
			$page->setEndOfPath(htmlspecialchars($json['page_menu_path']));
			$entityManager->flush();
			echo json_encode(['success' => true, 'page_name_path' => $page->getEndOfPath()]);
		}*/
		// description dans les métadonnées
		elseif($_GET['page_edit'] === 'page_description'){
			$node_data = $entityManager->find('App\Entity\NodeData', $json['node_data_id']);
			$node_data->updateData('description', htmlspecialchars($json['description']));
			$entityManager->flush();
			echo json_encode(['success' => true, 'description' => $node_data->getData()['description']]);
		}
		die;
	}

	// partie "blocs"
	elseif(isset($_GET['bloc_edit']))
	{
		// renommage d'un bloc
		if($_GET['bloc_edit'] === 'rename_page_bloc')
		{
	        if(isset($json['bloc_title']) && $json['bloc_title'] !== null && isset($json['bloc_id']) && is_int($json['bloc_id'])){
	            $director = new Director($entityManager);
	            $director->findNodeById($json['bloc_id']);

	            // le titre (du JSON en BDD) est récupéré sous forme de tableau, modifié et renvoyé
	            $data = $director->getNode()->getNodeData()->getData();
	            $data['title'] = htmlspecialchars($json['bloc_title']);
	            $director->getNode()->getNodeData()->updateData('title', htmlspecialchars($json['bloc_title']));

	            $entityManager->flush();
	            echo json_encode(['success' => true, 'title' => $data['title']]);
	        }
	        else{
				echo json_encode(['success' => false]);
			}
			die;
	    }
	    // inversion des positions de deux blocs
	    elseif($_GET['bloc_edit'] === 'switch_blocs_positions')
	    {
	    	if(isset($json['id1']) && is_int($json['id1']) && isset($json['id2']) && is_int($json['id2']) && isset($_GET['page'])){
	    		$director = new Director($entityManager, true);
	    		$director->findUniqueNodeByName('main');
	            $director->findItsChildren();
	            $main = $director->getNode();
	            $main->sortChildren(true); // régénère les positions avant inversion

	            $bloc1; $bloc2;
	            foreach($main->getChildren() as $child){
	            	if($child->getId() === $json['id1']){
	            		$bloc1 = $child;
	            		break;
	            	}
	            }
	            foreach($main->getChildren() as $child){
	            	if($child->getId() === $json['id2']){
	            		$bloc2 = $child;
	            		break;
	            	}
	            }

		        // inversion
		        $tmp = $bloc1->getPosition();
		        $bloc1->setPosition($bloc2->getPosition());
		        $bloc2->setPosition($tmp);

	    		$entityManager->flush();
	            echo json_encode(['success' => true]);
	    	}
	    	else{
				echo json_encode(['success' => false]);
			}
			die;
	    }
	}
}