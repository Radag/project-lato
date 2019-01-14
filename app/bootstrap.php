<?php

require __DIR__ . '/../vendor/autoload.php';
define('APP_DIR', __DIR__ );
$configurator = new Nette\Configurator;

//$configurator->setDebugMode('2a00:1028:83a2:2252:8c36:b0b8:ec2d:238d'); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();


$configurator->addConfig(__DIR__ . '/config/services.neon');
$configurator->addConfig(__DIR__ . '/config/config.neon');
if (is_readable(__DIR__ . '/config/config.local.neon')) {
    $configurator->addConfig(__DIR__ . '/config/config.local.neon');
}

$container = $configurator->createContainer();

return $container;
