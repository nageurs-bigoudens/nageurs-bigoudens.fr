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

        $old_version = getVersion($path . '/' . $key); // peut être null

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

        $new_version = getVersion($path . '/' . $key);

        if(!$old_version){
            echo 'public/js/fullcalendar/' . $key . ' installé ' . $new_version . "\n";
        }
        elseif($old_version === $new_version){
            echo 'public/js/fullcalendar/' . $key . ' réinstallé ' . $new_version . "\n";
        }
        else{
            echo 'public/js/fullcalendar/' . $key . ' mis à jour de ' . $old_version . ' vers ' . $new_version . "\n";
        }
    }
}

function getVersion(string $path): ?string
{
    if(!is_file($path)){
        return null;
    }

    $file = fopen($path, 'r');
    if(!$file){
        return null;
    }

    for($i = 0; $i < 5 && ($line = fgets($file)) !== false; $i++){
        if(preg_match('/FullCalendar .*? (v[0-9.]+)/', $line, $matches)){
            fclose($file);
            return $matches[1];
        }
    }

    fclose($file);
    return null;
}

installFullCalendar();
