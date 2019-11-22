<?php

require __DIR__ . '/../vendor/autoload.php';

$app = __DIR__ . '/../app';

Tester\Environment::setup();

date_default_timezone_set('Europe/Prague');

$configurator = new Nette\Configurator;
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setDebugMode(true);
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory($app)
	->register();

//$configurator->addConfig($app . '/config/services.neon');
//$configurator->addConfig($app . '/config/config.neon');
//if (is_readable($app . '/config/config.local.neon')) {
//    $configurator->addConfig($app . '/config/config.local.neon');
//}
//
//$container = $configurator->createContainer();
//$db = $container->getByType(Dibi\Connection::class);
