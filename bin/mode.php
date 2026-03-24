#!/usr/bin/env php
<?php
// bin/mode.php

declare(strict_types=1);

use App\Entity\AppMetadata;
use Doctrine\ORM\EntityManager;


chdir(dirname(__FILE__)); // pour être au même niveau que l'appli dans /public
require "../vendor/autoload.php";
Config::load('../config/config.ini'); // pour TABLE_PREFIX
require '../src/model/doctrine-bootstrap.php';

const ALLOWED_MODES = ['run', 'maintenance'];

// aide
$aide = "Usage : php bin/mode.php <mode>\nModes disponibles : " . implode(', ', ALLOWED_MODES) . "\n";
// version avec deux paramètres proposée par claude
/*$aide = "Usage : php bin/mode.php <mode> [--by <auteur>]
    Modes disponibles : " . implode(', ', ALLOWED_MODES) . "
    Exemple : php bin/mode.php maintenance --by alice\n";*/
if($argc < 2 || in_array($argv[1], ['--help', '-h'])){
    echo $aide;
    exit(0);
}

// validation du mode
if(!in_array($argv[1], ALLOWED_MODES)){
    echo "Erreur : mode '$argv[1]' invalide.\n";
    echo "Modes disponibles : " . implode(', ', ALLOWED_MODES) . "\n";
    exit(1);
}

// paramètre --by
/*$by = 'cli';
for ($i = 2; $i < $argc; $i++) {
    if ($argv[$i] === '--by' && isset($argv[$i + 1])) {
        $by = $argv[$i + 1];
        break;
    }
}*/

// changement BDD
try{
    AppMode::load($entityManager);
    $current = AppMode::get();

    if($current === $argv[1]){
        echo "Le mode est déjà '$argv[1]', aucun changement.\n";
        exit(0);
    }

    AppMode::set($entityManager, $argv[1]);
    echo "Mode changé : '$current' => '$argv[1]'\n";

    // le mode deux paramètres permettra d'indiquer son nom et automatiquement de d'enregistrer la date du changement
    //AppMode::set($entityManager, $argv[1], 'system');
    //echo "Mode changé : '$current' => '$argv[1]' (par $by)\n";
}
catch(LogicException $e){
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}