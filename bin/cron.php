#!/usr/bin/env php
<?php
// bin/cron.php

declare(strict_types=1);

chdir(dirname(__FILE__)); // = /chemin/du/site/bin = même niveau que public/
require('../src/Config.php');
Config::load('../config/config.ini');
require '../src/model/doctrine-bootstrap.php';

EmailService::cleanEmails($entityManager);