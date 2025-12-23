<?php
// bin/install_fullcalendar.php
function installFullCalendar(): void
{
	$path = 'public/js/fullcalendar';
    $links = [
        'index.global.min.js' => "https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js",
        'fr.global.min.js' => "https://cdn.jsdelivr.net/npm/@fullcalendar/core/locales/fr.global.min.js"
    ];

    foreach($links as $key => $link){
        $curl = curl_init($link);
        if(!$curl){ // lien non valide
            echo "Erreur : Impossible d'initialiser cURL.\n";
            return;
        }

        if(!is_dir($path)){
            mkdir($path, 0755, true);
        }
        
        $file = @fopen($path . '/' . $key, 'w+'); // @masque l'erreur pour la traiter soi-même
        if(!$file){ // erreur écriture fichier
            echo "Erreur : Impossible d'ouvrir le fichier $path pour l'écriture.\n";
            echo "Détails de l'erreur : " . error_get_last()['message'] . "\n";
            return;
        }

        curl_setopt($curl, CURLOPT_FILE, $file);
        curl_setopt($curl, CURLOPT_HEADER, 0);

        $response = curl_exec($curl);
        if(!$response){ // erreur téléchargement
            echo "Erreur : Le téléchargement a échoué. cURL Error: " . curl_error($curl) . "\n";
        }

        fclose($file);
        curl_close($curl);
    }	
}
installFullCalendar();
