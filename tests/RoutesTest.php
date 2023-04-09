<?php

namespace xmlshop\QueueMonitor\Tests;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route as RouteFacade;
use xmlshop\QueueMonitor\Controllers\ShowQueueMonitorController;

class RoutesTest extends TestCase
{
    public function testBasicRouteCreation()
    {
        RouteFacade::prefix('index')->group(function () {
            RouteFacade::monitor();
        });

        $this->assertInstanceOf(Route::class, $route = app(Router::class)->getRoutes()->getByAction(ShowQueueMonitorController::class));

        $this->assertEquals('index/jobs', $route->uri);
        $this->assertEquals('\xmlshop\QueueMonitor\Controllers\ShowQueueMonitorController', $route->getAction('controller'));
    }

    public function testRouteCreationInNamespace()
    {
        RouteFacade::namespace('App\Http')->prefix('index')->group(function () {
            RouteFacade::monitor();
        });

        $this->assertInstanceOf(Route::class, $route = app(Router::class)->getRoutes()->getByAction(ShowQueueMonitorController::class));

        $this->assertEquals('index/jobs', $route->uri);
        $this->assertEquals('\xmlshop\QueueMonitor\Controllers\ShowQueueMonitorController', $route->getAction('controller'));
    }
}
