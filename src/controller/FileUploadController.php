<?php
// src/controller/FileUploadController.php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\JsonResponse;

class FileUploadController
{
	static private function checkFileDownload(array $file): bool
	{
		$extensions_white_list = ['pdf', 'rtf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp']; // = extensions_white_list côté javascript
		$mime_type_white_list = ['application/pdf', 'application/rtf', 'text/rtf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.presentation'];
		
		// 1/ extension
		$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if(!in_array($extension, $extensions_white_list, true)){
			return false;
	    }
		
		// 2/ fichier obtenu par HTTP POST (théoriquement inutile si le routeur est solide, mais ça ne mange pas de pain)
		if(!is_uploaded_file($file['tmp_name'])){
			return false;
		}

		// 3/ objet $finfo valide (dépend du paramètre FILEINFO_MIME_TYPE)
	    $finfo = new finfo(FILEINFO_MIME_TYPE);
		if($finfo === false){
	        return false;
	    }
		
		// 4/ contrôle du "vrai" type mime (finfo_file lit les 1ers octets des fichiers pour y trouver des "signatures", très fiable sauf avec les conteneurs: doc, zip...)
	    $real_type = finfo_file($finfo, $file['tmp_name']);
	    return in_array($real_type, $mime_type_white_list, true);
	}

	static public function fileUploadTinyMce(): JsonResponse
	{
		if(isset($_FILES['file'])){
	        $dest = 'user_data/media/';
	        if(!is_dir($dest)){ // Vérifier si le répertoire existe, sinon le créer
	            mkdir($dest, 0755, true);
	        }
	        
	        $name = Security::secureFileName(pathinfo($_FILES['file']['name'], PATHINFO_FILENAME)); // retirer caractères spéciaux et changer espaces en underscores
	        $extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION)); 
	        $file_path = $dest . $name . '_' . uniqid() . '.' . $extension; // nom unique
	        
	        if(self::checkFileDownload($_FILES['file'])){
	        	if(move_uploaded_file($_FILES['file']['tmp_name'], $file_path)){
					return new JsonResponse(['location' => $file_path]);
				}
				else{
					return new JsonResponse(['message' => 'Erreur enregistrement du fichier.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // code 500
				}
	        }
	        else{
				return new JsonResponse(['message' => 'Erreur 400: fichier non valide.'], JsonResponse::HTTP_BAD_REQUEST); // code 400
			}
	    }
	    else{
	        return new JsonResponse(['message' => 'Erreur 400: Bad Request'], JsonResponse::HTTP_BAD_REQUEST); // code 400
	    }
	}
}