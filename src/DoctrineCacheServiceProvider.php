<?php

declare(strict_types=1);

/*
 * (c) Beau Simensen <beau@dflydev.com> (https://github.com/dflydev/dflydev-doctrine-orm-service-provider)
 */

namespace Chubbyphp\ServiceProvider;

use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\Cache\RedisCache;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

final class DoctrineCacheServiceProvider implements ServiceProviderInterface
{
    /**
     * Register ORM service.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['doctrine.cache.locator'] = $this->getOrmCacheLocatorDefinition($container);
        $container['doctrine.cache.factory'] = $this->getOrmCacheFactoryDefinition($container);
        $container['doctrine.cache.factory.apcu'] = $this->getOrmCacheFactoryApcuDefinition($container);
        $container['doctrine.cache.factory.array'] = $this->getOrmCacheFactoryArrayDefinition($container);
        $container['doctrine.cache.factory.filesystem'] = $this->getOrmCacheFactoryFilesystemDefinition($container);
        $container['doctrine.cache.factory.memcached'] = $this->getOrmCacheFactoryMemcachedDefinition($container);
        $container['doctrine.cache.factory.redis'] = $this->getOrmCacheFactoryRedisDefinition($container);
        $container['doctrine.cache.factory.xcache'] = $this->getOrmCacheFactoryXCacheDefinition($container);
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheLocatorDefinition(Container $container): callable
    {
        return $container->protect(function (string $key, array $options) use ($container) {
            $cacheInstanceKey = 'orm.cache.instances.'.$key;
            if (isset($container[$cacheInstanceKey])) {
                return $container[$cacheInstanceKey];
            }

            if (!isset($options['driver'])) {
                throw new \RuntimeException("No driver specified for '$key'");
            }

            $driver = $options['driver'];

            /** @var CacheProvider $cache */
            $cache = $container['doctrine.cache.factory']($driver, $options);

            if (isset($options['cache_namespace'])) {
                $cache->setNamespace($options['cache_namespace']);
            }

            return $container[$cacheInstanceKey] = $cache;
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheFactoryDefinition(Container $container): callable
    {
        return $container->protect(function (string $driver, array $options) use ($container) {
            $cacheFactoryKey = 'doctrine.cache.factory.'.$driver;
            if (!isset($container[$cacheFactoryKey])) {
                throw new \RuntimeException(
                    sprintf('Factory "%s" for cache type "%s" not defined', $cacheFactoryKey, $driver)
                );
            }

            return $container[$cacheFactoryKey]($options);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheFactoryApcuDefinition(Container $container): callable
    {
        return $container->protect(function () use ($container) {
            return new ApcuCache();
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheFactoryArrayDefinition(Container $container): callable
    {
        return $container->protect(function () use ($container) {
            return new ArrayCache();
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheFactoryFilesystemDefinition(Container $container): callable
    {
        return $container->protect(function (array $options) {
            if (empty($options['path'])) {
                throw new \RuntimeException('FilesystemCache path not defined');
            }

            $options += [
                'extension' => FilesystemCache::EXTENSION,
                'umask' => 0002,
            ];

            return new FilesystemCache($options['path'], $options['extension'], $options['umask']);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheFactoryMemcachedDefinition(Container $container): callable
    {
        return $container->protect(function (array $options) use ($container) {
            if (empty($options['host']) || empty($options['port'])) {
                throw new \RuntimeException('Host and port options need to be specified for memcached cache');
            }

            $memcached = new \Memcached();
            $memcached->addServer($options['host'], $options['port']);

            $cache = new MemcachedCache();
            $cache->setMemcached($memcached);

            return $cache;
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheFactoryRedisDefinition(Container $container): callable
    {
        return $container->protect(function (array $options) use ($container) {
            if (empty($options['host']) || empty($options['port'])) {
                throw new \RuntimeException('Host and port options need to be specified for redis cache');
            }

            $redis = new \Redis();
            $redis->connect($options['host'], $options['port']);

            if (isset($options['password'])) {
                $redis->auth($options['password']);
            }

            $cache = new RedisCache();
            $cache->setRedis($redis);

            return $cache;
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheFactoryXCacheDefinition(Container $container): callable
    {
        return $container->protect(function () use ($container) {
            return new XcacheCache();
        });
    }
}
