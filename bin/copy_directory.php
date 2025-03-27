<?php
// bin/copy_directory.php
//
// appel dans le composer.json

function copyDirectory($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        } else {
            copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        }
    }
}

copyDirectory($argv[1], $argv[2]);
