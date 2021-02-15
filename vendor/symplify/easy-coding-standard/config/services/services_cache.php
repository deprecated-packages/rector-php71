<?php

declare(strict_types=1);

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->set(Psr16Cache::class);

    $services->alias(CacheInterface::class, Psr16Cache::class);

    $services->set(FilesystemAdapter::class)
        ->args([
            '$namespace' => '%cache_namespace%',
            '$defaultLifetime' => 0,
            '$directory' => '%cache_directory%',
        ]);

    $services->alias(CacheItemPoolInterface::class, FilesystemAdapter::class);

    $services->alias(TagAwareAdapterInterface::class, TagAwareAdapter::class);
};
