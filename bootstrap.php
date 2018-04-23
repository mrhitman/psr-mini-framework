<?php

require_once "vendor/autoload.php";

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/entries"], $isDevMode);

$conn = [
    'driver' => 'pdo_mysql',
    'host' => 'db',
    'user' => 'root',
    'password' => '1',
    'dbname' => 'test',
];

// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);