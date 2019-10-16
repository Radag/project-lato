<?php

require __DIR__ . '/../vendor/autoload.php';
define('APP_DIR', __DIR__ );
define('TEMP_DIR', __DIR__ . '/../temp');
$configurator = new Nette\Configurator;

$configurator->setDebugMode(true); // enable for your remote IP
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(TEMP_DIR);

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
