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
	$frontRouter[] = new Route('auth/<presenter>/<action>[/<id>]', 'Homepage:default', Route::SECURED);
        
        $router[] = $publicRouter = new RouteList('Public');
        $publicRouter[] = new Route('a/<id>', 'Action:default', Route::SECURED);
	$publicRouter[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default', Route::SECURED);
        
        
        return $router;
    }

}
