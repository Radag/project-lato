<?php

require __DIR__ . '/../fileServerApp/bootstrap.php';

/*
require __DIR__ . '/../vendor/autoload.php';

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . '/../app');
$loader->setTempDirectory(__DIR__ . '/../temp');
$loader->register();


$url = explode('/', $_SERVER['REQUEST_URI']);

$lastIndex = mb_strrpos($_SERVER['REQUEST_URI'], '/');
$fileName = mb_substr($_SERVER['REQUEST_URI'], $lastIndex + 1);
$fullPath = mb_substr($_SERVER['REQUEST_URI'], 0, $lastIndex);

$db = new \Dibi\Connection([
    'driver'   => 'mysqli',
    'host'     => '185.8.166.158',
    'username' => 'lato',
    'password' => 'vuHeDo8i1itimohi1etItITI3owuSe!',
    'database' => 'lato',
]);

$userManager = new App\Model\Manager\UserManager($db);
$storeage = new Nette\Http\UserStorage(new Nette\Http\Session());
$user = new Nette\Security\User($userManager);

$fileManager = new \App\Model\Manager\FileManager($user, $db, null);
 * 
 */