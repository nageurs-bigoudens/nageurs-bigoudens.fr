<?php
// bin/install_tinymce_lang.php
function installTinymceLang(string $lang = 'fr_FR'): void
{
	$dest = 'public/js/tinymce-langs/' . $lang . '.js';
	$link = "https://cdn.jsdelivr.net/npm/tinymce-lang/langs/" . $lang . ".min.js";
	
	$curl = curl_init($link);
	if(!$curl){ // lien non valide
        echo "Erreur : Impossible d'initialiser cURL.\n";
        return;
    }
	
	$file = @fopen($dest, 'w+'); // @masque l'erreur pour la traiter soi-même
	if(!$file){ // erreur écriture fichier
        echo "Erreur : Impossible d'ouvrir le fichier $dest pour l'écriture.\n";
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
installTinymceLang($argv[1]);