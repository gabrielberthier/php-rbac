<?php

namespace Core\Http\Factories;

use Core\Http\Middlewares\Factories\ValidationMiddlewareFactory;
use Core\Http\Routing\Cache\GroupCacheResult;
use Core\Http\Routing\GroupCollector;
use Core\Http\Routing\RouteFactory;
use Core\Http\Routing\RouterCollector;
use Psr\Container\ContainerInterface;

class RouteCollectorFactory
{
    public function __construct(private ContainerInterface $containerInterface)
    {
    }

    public function getRouteCollector(): RouterCollector
    {
        $validationMiddlewareFactory = new ValidationMiddlewareFactory($this->containerInterface);
        $groupCollector = new GroupCollector();
        $caching = new GroupCacheResult();
        $routeFactory = new RouteFactory($groupCollector, $caching);
        return new RouterCollector(
            $routeFactory,
            $validationMiddlewareFactory
        );
    }
}


