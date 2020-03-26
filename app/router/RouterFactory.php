<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{

    /**
     * @return Nette\Application\IRouter
     */
    public static function createRouter()
    {
        $router = new RouteList;
        
        $router[] = $adminRouter = new RouteList('Admin');
	$adminRouter[] = new Route('admin/<presenter>/<action>', 'Homepage:default');

		$router[] = $frontRouter = new RouteList('Front');
        $frontRouter[] = new Route('auth', 'Homepage:noticeboard');
		$frontRouter[] = new Route('auth/skupina/<id>/studenti', 'Group:usersList');
		$frontRouter[] = new Route('auth/skupina/<id>/o-skupine', 'Group:about');
		$frontRouter[] = new Route('auth/skupina/<id>/nastaveni', 'Group:settings');
		$frontRouter[] = new Route('auth/skupina/<id>[/<action>]', 'Group:default');
        $frontRouter[] = new Route('auth/profil/<id>', 'Profile:default');
        $frontRouter[] = new Route('auth/nastaveni', 'Account:default');
        $frontRouter[] = new Route('auth/pripomenuti', 'Task:work');
        $frontRouter[] = new Route('auth/uloziste', 'Homepage:storage');
        $frontRouter[] = new Route('auth/klasifikace', 'Homepage:classification');
        $frontRouter[] = new Route('auth/nastaveni', 'Profile:settings');
        $frontRouter[] = new Route('auth/testy', 'Tests:list');
        $frontRouter[] = new Route('auth/testy/editor', 'Tests:editor');        
		$frontRouter[] = new Route('auth/zpravy', 'Conversation:list'); 
		$frontRouter[] = new Route('auth/zprava/<id>', 'Conversation:default');
		$frontRouter[] = new Route('auth/<presenter>/<action>[/<id>]', 'Homepage:default');
        
        $router[] = $publicRouter = new RouteList('Public');
        $publicRouter[] = new Route('a/<id>', 'Action:default');
        $publicRouter[] = new Route('podminky-pouzivani', 'Homepage:terms');
        $publicRouter[] = new Route('podminky-ochrany-osobnich-udaju', 'Homepage:gdpr');
		$publicRouter[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
        
        
        
        return $router;
    }
}
