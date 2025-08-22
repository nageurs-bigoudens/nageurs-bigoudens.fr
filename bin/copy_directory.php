<?php
// bin/copy_directory.php
//
// usage : php bin/copy_directory.php source destination
// appel dans le composer.json à l'intallation et lors des MAJ

function copyDirectory(string $source, string $destination): void
{
    if (!is_dir($source)) {
        fwrite(STDERR, "Erreur rencontrée dans bin/copy_directory.php: le dossier source '$source' n'est pas un répertoire valide.\n");
        exit(1);
    }

    if(!is_dir($destination)){
        mkdir($destination, 0755, true);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach($iterator as $item){
        $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if($item->isDir()){
            if(!is_dir($targetPath)){
                mkdir($targetPath, 0755);
            }
        }
        else{
            copy($item, $targetPath); // copy() écrase la cible, c'est ce qu'on veut
        }
    }
}

if ($argc != 3){ // nombre de paramètres
    fwrite(STDERR, "Erreur rencontrée dans bin/copy_directory.php. Usage:\nphp bin/copy_directory.php <source> <destination>\n");
    exit(1);
}
copyDirectory($argv[1], $argv[2]);
