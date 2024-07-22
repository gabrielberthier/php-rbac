<?php

namespace Core\Http\Factories;

use Core\Builder\ProvidersCollector;
use Core\Providers\AppProviderInterface;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

class ContainerFactory
{
    private ContainerBuilder $containerBuilder;
    private ProvidersCollector $providersCollector;

    public function __construct(
        private bool $enableCompilation = false,
    ) {
        $this->containerBuilder = new ContainerBuilder();
        $this->providersCollector = new ProvidersCollector();

        // if ($this->enableCompilation) { // Should be set to true in production
        //     $this->containerBuilder->enableCompilation('tmp/var/cache');
        // }
    }

    public function addProviders(AppProviderInterface|string ...$providers){
        foreach ($providers as $provider) {
            $this->providersCollector->addProvider($provider);
        }
    }

    public function get(): ContainerInterface
    {
        $this->providersCollector->execute($this->containerBuilder);

        return $this->containerBuilder->build();
    }
}
