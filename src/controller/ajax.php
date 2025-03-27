<?php
// src/controller/ajax.php

declare(strict_types=1);

// détection des requêtes de tinymce
if($_SERVER['CONTENT_TYPE'] === 'application/json' && isset($_GET['action']))
{
	// récupération des données
	$data = file_get_contents('php://input');
	$json = json_decode($data, true);

	if($_GET['action'] === 'editor_submit' && isset($json['id']) && isset($json['content']))
	{
	    if(json_last_error() === JSON_ERROR_NONE)
	    {
	        $articleId = $json['id'];
	        $content = Security::secureString($json['content']);

	        $director = new Director($entityManager);
	        if($director->makeArticleNode($articleId)) // une entrée est trouvée
	        {
	        	$node = $director->getRootNode();
		        $node->getArticle()->setContent($content);
		        $entityManager->flush();

		        echo json_encode(['success' => true]);
	        }
	        else{
	        	echo json_encode(['success' => false, 'message' => 'Aucune entrée trouvée en BDD']);
	        }
	    }
	    else{
	        echo json_encode(['success' => false, 'message' => 'Erreur de décodage JSON']);
	    }
	    die;
	}
	elseif($_GET['action'] === 'delete_article' && isset($json['id']))
	{
        $articleId = $json['id'];

        $director = new Director($entityManager);
        $director->makeArticleNode($articleId);
        $node = $director->getRootNode();
        $entityManager->remove($node);
        $entityManager->flush();

        // test avec une nouvelle requête qui ne devrait rien trouver
        if(!$director->makeArticleNode($articleId))
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

// détection des requêtes de type XHR, pas d'utilité pour l'instant
/*elseif(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
	echo "requête XHR reçue par le serveur";
	die;
}*/


