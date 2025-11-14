<?php
// src/model/doctrine-bootstrap.php

declare(strict_types=1);

use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;

require_once "../vendor/autoload.php";

// Create a simple "default" Doctrine ORM configuration for Attributes
$config = ORMSetup::createAttributeMetadataConfiguration(
    //paths: array(__DIR__.'/entities'),
    paths: array('../src/model/entities'),
    isDevMode: true,
    // true: cache en mémoire vive
    // false: utilisation de APCu ou redis ou memcache
);

// configuring the database connection
$connection = DriverManager::getConnection([
    'driver'   => Config::$db_driver,
    'user'     => Config::$user,
    'password' => Config::$password,
    'host'     => Config::$db_host,
    'dbname'   => Config::$database,
], $config);

// obtaining the entity manager
$entityManager = new EntityManager($connection, $config);

foreach($entityManager->getMetadataFactory()->getAllMetadata() as $class){}