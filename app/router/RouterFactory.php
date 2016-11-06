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
	$frontRouter[] = new Route('auth/group/<id>[/<action>]', 'Group:default');
        $frontRouter[] = new Route('auth/profile/<id>', 'Profile:default');
        $frontRouter[] = new Route('auth/messages', 'Profile:messages');
        $frontRouter[] = new Route('auth/settings', 'Profile:settings');
	$frontRouter[] = new Route('auth/<presenter>/<action>[/<id>]', 'Homepage:default');
        
        $router[] = $publicRouter = new RouteList('Public');
        $publicRouter[] = new Route('a/<id>', 'Action:default');
	$publicRouter[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');
        
        
        return $router;
    }
    
    /**
     * @return Nette\Application\IRouter
     */
    public static function createSecuredRouter()
    {
        $router = new RouteList;
        
        $router[] = $adminRouter = new RouteList('Admin');
	$adminRouter[] = new Route('admin/<presenter>/<action>', 'Homepage:default', Route::SECURED);

	$router[] = $frontRouter = new RouteList('Front');
        $frontRouter[] = new Route('auth', 'Homepage:noticeboard', Route::SECURED);
	$frontRouter[] = new Route('auth/group/<id>[/<action>]', 'Group:default', Route::SECURED);
        $frontRouter[] = new Route('auth/profile/<id>', 'Profile:default', Route::SECURED);
        $frontRouter[] = new Route('auth/messages', 'Profile:messages', Route::SECURED);
        $frontRouter[] = new Route('auth/settings', 'Profile:settings', Route::SECURED);
	$frontRouter[] = new Route('auth/<presenter>/<action>[/<id>]', 'Homepage:default', Route::SECURED);
        
        $router[] = $publicRouter = new RouteList('Public');
        $publicRouter[] = new Route('a/<id>', 'Action:default', Route::SECURED);
	$publicRouter[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default', Route::SECURED);
        
        
        return $router;
    }

}
