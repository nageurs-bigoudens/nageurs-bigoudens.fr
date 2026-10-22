#!/usr/bin/env php
<?php
// bin/cron.php

declare(strict_types=1);

use App\Service\Config;
use App\Service\EmailService;

chdir(dirname(__FILE__)); // = /chemin/du/site/bin = même niveau que public/
require('../src/Service/Config.php');
Config::load('../config/config.ini');
require '../src/Model/doctrine-bootstrap.php';

EmailService::cleanEmails($entityManager);