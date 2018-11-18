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
	$frontRouter[] = new Route('auth/skupina/<id>/studenti', 'Group:users');
	$frontRouter[] = new Route('auth/skupina/<id>/o-skupine', 'Group:about');
	$frontRouter[] = new Route('auth/skupina/<id>/nastaveni', 'Group:settings');
	$frontRouter[] = new Route('auth/skupina/<id>[/<action>]', 'Group:default');
        $frontRouter[] = new Route('auth/profil/<id>', 'Profile:default');
        $frontRouter[] = new Route('auth/zpravy', 'Profile:messages');
        $frontRouter[] = new Route('auth/zpravy/konverzace/<user>', 'Profile:conversation');
        $frontRouter[] = new Route('auth/nastaveni', 'Account:default');
        $frontRouter[] = new Route('auth/pripomenuti', 'Task:work');
        $frontRouter[] = new Route('auth/uloziste', 'Homepage:storage');
        $frontRouter[] = new Route('auth/klasifikace', 'Homepage:classification');
        $frontRouter[] = new Route('auth/nastaveni', 'Profile:settings');
	$frontRouter[] = new Route('auth/<presenter>/<action>[/<id>]', 'Homepage:default');
        
        $router[] = $publicRouter = new RouteList('Public');
        $publicRouter[] = new Route('a/<id>', 'Action:default');
	$publicRouter[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
        
        return $router;
    }
}
