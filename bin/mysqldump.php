#!/usr/bin/env php
<?php
// bin/mysqldump.php

declare(strict_types=1);

use App\Service\Config;
use App\Service\Backup;

chdir(dirname(__FILE__));
require "../vendor/autoload.php";
Config::load('../config/config.ini');
require '../src/Model/doctrine-bootstrap.php';

$file_name = Backup::mySQLdump($entityManager, 'console'); // créer un nouveau backup
echo "Un backup a été créé:\n" . realpath($file_name) . "\n";