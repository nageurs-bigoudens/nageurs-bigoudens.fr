<?php
// src/controller/ImageUploadController.php

declare(strict_types=1);

class ImageUploadController
{
	static public function imagickCleanImage(string $image_data, string $local_path, string $format = 'jpeg'): bool // "string" parce que file_get_contents...
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
	static public function curlDownloadImage(string $url, $maxRetries = 3, $timeout = 10): string|false
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

	// téléchargement par le plugin (bouton "insérer une image")
	static public function imageUploadTinyMce(): void
	{
		if(isset($_FILES['file'])){
	        $file = $_FILES['file'];
	        $dest = 'user_data/images/';
	        $dest_mini = 'user_data/images-mini/';
	        
	        // Vérifier si les répertoires existent, sinon les créer
	        if(!is_dir($dest)){
	            mkdir($dest, 0777, true);
	        }
	        if(!is_dir($dest_mini)){
	            mkdir($dest_mini, 0777, true);
	        }
	        
	        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'tif'];
	        $name = Security::secureFileName(pathinfo($file['name'], PATHINFO_FILENAME));
	        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	        if(!in_array($extension, $allowed_extensions) || $extension === 'jpg'){
	            $extension = 'jpeg';
	        }
	        $file_path = uniqid($dest . $name . '_') . '.' . $extension;

	        // créer une miniature de l'image
	        //

	        if(self::imagickCleanImage(file_get_contents($file['tmp_name']), $file_path, $extension)){ // recréer l’image pour la nettoyer
	            echo json_encode(['location' => $file_path]); // renvoyer l'URL de l'image téléchargée
	        }
	        else{
	            http_response_code(500);
	            echo json_encode(['message' => 'Erreur image non valide', 'format' => $extension]);
	        }
	    }
	    else{
	        http_response_code(400);
	        echo json_encode(['message' => 'Erreur 400: Bad Request']);
	    }
	    die;
	}

	// collage de HTML => recherche de balises <img>, téléchargement côté serveur et renvoi de l'adresse sur le serveur 
	static public function uploadImageHtml(): void
	{
		$json = json_decode(file_get_contents('php://input'), true);
	    
	    if(isset($json['image_url'])){
	        $image_data = self::curlDownloadImage($json['image_url']); // téléchargement de l’image par le serveur avec cURL au lieu de file_get_contents
	        $dest = 'user_data/images/';
	        
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
	        $local_path = uniqid($dest . $name . '_') . '.' . $extension;
	        
	        if(self::imagickCleanImage($image_data, $local_path, $extension)){ // recréer l’image pour la nettoyer
	            echo json_encode(['location' => $local_path]); // nouvelle adresse
	        }
	        else{
	            http_response_code(500);
	            echo json_encode(['message' => 'Erreur image non valide', 'format' => $extension]);
	        }
	    }
	    else{
	        echo json_encode(['message' => 'Erreur 400: Bad Request']);
	    }
	    die;
	}

	// collage simple d'une image (base64 dans le presse-papier) non encapsulée dans du HTML
	static public function uploadImageBase64(): void
	{
		$json = json_decode(file_get_contents('php://input'), true);
	    $dest = 'user_data/images/';

	    if(!is_dir($dest)){
	        mkdir($dest, 0777, true);
	    }

	    // détection de data:image/ et de ;base64, et capture du format dans $type
	    if(!isset($json['image_base64']) || !preg_match('/^data:image\/(\w+);base64,/', $json['image_base64'], $type)){
	        http_response_code(400);
	        echo json_encode(['message' => 'Données image base64 manquantes ou invalides']);
	        die;
	    }

	    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'tiff', 'tif'];
	    $extension = strtolower($type[1]);
	    if(!in_array($extension, $allowed_extensions) || $extension === 'jpg'){
	        $extension = 'jpeg';
	    }

	    $image_data = base64_decode(substr($json['image_base64'], strpos($json['image_base64'], ',') + 1)); // découpe la chaine à la virgule puis convertit en binaire
	    if($image_data === false){
	        http_response_code(400);
	        echo json_encode(['message' => 'Décodage base64 invalide']);
	        die;
	    }
	    
	    $local_path = uniqid($dest . 'pasted_image_') . '.' . $extension;

	    if(self::imagickCleanImage($image_data, $local_path)){
	        echo json_encode(['location' => $local_path]);
	    }
	    else{
	        http_response_code(500);
	        echo json_encode(['message' => 'Erreur image non valide', 'format' => $extension]);
	    }
	    die;
	}
}