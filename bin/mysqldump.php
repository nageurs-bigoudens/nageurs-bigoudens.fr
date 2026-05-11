#!/usr/bin/env php
<?php
// bin/mysqldump.php

declare(strict_types=1);

chdir(dirname(__FILE__));
require "../vendor/autoload.php";
Config::load('../config/config.ini');
require '../src/model/doctrine-bootstrap.php';

$file_name = Backup::mySQLdump($entityManager, 'console'); // créer un nouveau backup
echo realpath($file_name) . "\n";