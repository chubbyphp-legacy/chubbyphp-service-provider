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
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

final class DoctrineOrmServiceProvider implements ServiceProviderInterface
{
    /**
     * Register ORM service.
     *
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['orm.em.default_options'] = $this->getOrmEmDefaultOptions();
        $container['orm.ems.options.initializer'] = $this->getOrmEmsOptionsInitializerDefinition($container);
        $container['orm.ems'] = $this->getOrmEmsDefinition($container);
        $container['orm.ems.config'] = $this->getOrmEmsConfigServiceProvider($container);
        $container['orm.cache.configurer'] = $this->getOrmCacheConfigurerDefinition($container);
        $container['orm.cache.locator'] = $this->getOrmCacheLocatorDefinition($container);
        $container['orm.cache.factory'] = $this->getOrmCacheFactoryDefinition($container);
        $container['orm.cache.factory.apcu'] = $this->getOrmCacheFactoryApcuDefinition($container);
        $container['orm.cache.factory.array'] = $this->getOrmCacheFactoryArrayDefinition($container);
        $container['orm.cache.factory.filesystem'] = $this->getOrmCacheFactoryFilesystemDefinition($container);
        $container['orm.cache.factory.memcache'] = $this->getOrmCacheFactoryMemcacheDefinition($container);
        $container['orm.cache.factory.memcached'] = $this->getOrmCacheFactoryMemcachedDefinition($container);
        $container['orm.cache.factory.redis'] = $this->getOrmCacheFactoryRedisDefinition($container);
        $container['orm.cache.factory.xcache'] = $this->getOrmCacheFactoryXCacheDefinition($container);
        $container['orm.default_cache'] = ['driver' => 'array'];
        $container['orm.proxies_dir'] = sys_get_temp_dir();
        $container['orm.proxies_namespace'] = 'DoctrineProxy';
        $container['orm.auto_generate_proxies'] = true;
        $container['orm.custom.functions.string'] = [];
        $container['orm.custom.functions.numeric'] = [];
        $container['orm.custom.functions.datetime'] = [];
        $container['orm.custom.hydration_modes'] = [];
        $container['orm.class_metadata_factory_name'] = ClassMetadataFactory::class;
        $container['orm.default_repository_class'] = EntityRepository::class;
        $container['orm.entity_listener_resolver'] = $this->getOrmEntityListenerResolverDefinition($container);
        $container['orm.repository_factory'] = $this->getOrmRepositoryFactoryDefinition($container);
        $container['orm.strategy.naming'] = $this->getOrmNamingStrategyDefinition($container);
        $container['orm.strategy.quote'] = $this->getOrmQuoteStrategyDefinition($container);
        $container['orm.mapping_driver_chain.locator'] = $this->getOrmMappingDriverChainLocatorDefinition($container);
        $container['orm.mapping_driver_chain.factory'] = $this->getOrmMappingDriverChainFactoryDefinition($container);
        $container['orm.em'] = $this->getOrmEmDefinition($container);
        $container['orm.em.config'] = $this->getOrmEmConfigDefinition($container);
    }

    /**
     * @return array
     */
    private function getOrmEmDefaultOptions(): array
    {
        return [
            'connection' => 'default',
            'mappings' => [],
            'types' => [],
        ];
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmEmsOptionsInitializerDefinition(Container $container): \Closure
    {
        return $container->protect(function () use ($container) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($container['orm.ems.options'])) {
                $container['orm.ems.options'] = [
                    'default' => isset($container['orm.em.options']) ? $container['orm.em.options'] : [],
                ];
            }

            $tmp = $container['orm.ems.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace($container['orm.em.default_options'], $options);

                if (!isset($container['orm.ems.default'])) {
                    $container['orm.ems.default'] = $name;
                }
            }

            $container['orm.ems.options'] = $tmp;
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmEmsDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $container['orm.ems.options.initializer']();

            $ems = new Container();
            foreach ($container['orm.ems.options'] as $name => $options) {
                if ($container['orm.ems.default'] === $name) {
                    // we use shortcuts here in case the default has been overridden
                    $config = $container['orm.em.config'];
                } else {
                    $config = $container['orm.ems.config'][$name];
                }

                $ems[$name] = function () use ($container, $options, $config) {
                    return EntityManager::create(
                        $container['dbs'][$options['connection']],
                        $config,
                        $container['dbs.event_manager'][$options['connection']]
                    );
                };
            }

            return $ems;
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmEmsConfigServiceProvider(Container $container): \Closure
    {
        return function () use ($container) {
            $container['orm.ems.options.initializer']();

            $configs = new Container();
            foreach ($container['orm.ems.options'] as $name => $options) {
                $configs[$name] = $this->getOrmEmConfigByNameAndOptionsDefinition($container, $name, $options);
            }

            return $configs;
        };
    }

    /**
     * @param Container $container
     * @param string    $name
     * @param array     $options
     *
     * @return \Closure
     */
    private function getOrmEmConfigByNameAndOptionsDefinition(
        Container $container,
        string $name,
        array $options
    ): \Closure {
        return function () use ($container, $name, $options) {
            $config = new Configuration();

            $container['orm.cache.configurer']($name, $config, $options);

            $config->setProxyDir($container['orm.proxies_dir']);
            $config->setProxyNamespace($container['orm.proxies_namespace']);
            $config->setAutoGenerateProxyClasses($container['orm.auto_generate_proxies']);

            $config->setCustomStringFunctions($container['orm.custom.functions.string']);
            $config->setCustomNumericFunctions($container['orm.custom.functions.numeric']);
            $config->setCustomDatetimeFunctions($container['orm.custom.functions.datetime']);
            $config->setCustomHydrationModes($container['orm.custom.hydration_modes']);

            $config->setClassMetadataFactoryName($container['orm.class_metadata_factory_name']);
            $config->setDefaultRepositoryClassName($container['orm.default_repository_class']);

            $config->setEntityListenerResolver($container['orm.entity_listener_resolver']);
            $config->setRepositoryFactory($container['orm.repository_factory']);

            $config->setNamingStrategy($container['orm.strategy.naming']);
            $config->setQuoteStrategy($container['orm.strategy.quote']);

            /** @var MappingDriverChain $chain */
            $chain = $container['orm.mapping_driver_chain.locator']($name);

            foreach ((array) $options['mappings'] as $entity) {
                if (!is_array($entity)) {
                    throw new \InvalidArgumentException(
                        "The 'orm.em.options' option 'mappings' should be an array of arrays."
                    );
                }

                if (isset($entity['alias'])) {
                    $config->addEntityNamespace($entity['alias'], $entity['namespace']);
                }

                switch ($entity['type']) {
                    case 'annotation':
                        $useSimpleAnnotationReader = $entity['use_simple_annotation_reader'] ?? true;
                        $driver = $config->newDefaultAnnotationDriver(
                            (array) $entity['path'],
                            $useSimpleAnnotationReader
                        );
                        $chain->addDriver($driver, $entity['namespace']);

                        break;
                    case 'yml':
                        $driver = new YamlDriver($entity['path']);
                        $chain->addDriver($driver, $entity['namespace']);

                        break;
                    case 'simple_yml':
                        $driver = new SimplifiedYamlDriver([$entity['path'] => $entity['namespace']]);
                        $chain->addDriver($driver, $entity['namespace']);

                        break;
                    case 'xml':
                        $driver = new XmlDriver($entity['path']);
                        $chain->addDriver($driver, $entity['namespace']);

                        break;
                    case 'simple_xml':
                        $driver = new SimplifiedXmlDriver([$entity['path'] => $entity['namespace']]);
                        $chain->addDriver($driver, $entity['namespace']);

                        break;
                    case 'php':
                        $driver = new StaticPHPDriver($entity['path']);
                        $chain->addDriver($driver, $entity['namespace']);

                        break;
                    default:
                        throw new \InvalidArgumentException(
                            sprintf('"%s" is not a recognized driver', $entity['type'])
                        );
                }
            }

            $config->setMetadataDriverImpl($chain);

            foreach ((array) $options['types'] as $typeName => $typeClass) {
                if (Type::hasType($typeName)) {
                    Type::overrideType($typeName, $typeClass);
                } else {
                    Type::addType($typeName, $typeClass);
                }
            }

            return $config;
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheConfigurerDefinition(Container $container): \Closure
    {
        return $container->protect(function ($name, Configuration $config, $options) use ($container) {
            $config->setMetadataCacheImpl($container['orm.cache.locator']($name, 'metadata', $options));
            $config->setQueryCacheImpl($container['orm.cache.locator']($name, 'query', $options));
            $config->setResultCacheImpl($container['orm.cache.locator']($name, 'result', $options));
            $config->setHydrationCacheImpl($container['orm.cache.locator']($name, 'hydration', $options));
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheLocatorDefinition(Container $container): \Closure
    {
        return $container->protect(function ($name, $cacheName, $options) use ($container) {
            $cacheNameKey = $cacheName.'_cache';

            if (!isset($options[$cacheNameKey])) {
                $options[$cacheNameKey] = $container['orm.default_cache'];
            }

            if (isset($options[$cacheNameKey]) && !is_array($options[$cacheNameKey])) {
                $options[$cacheNameKey] = [
                    'driver' => $options[$cacheNameKey],
                ];
            }

            if (!isset($options[$cacheNameKey]['driver'])) {
                throw new \RuntimeException("No driver specified for '$cacheName'");
            }

            $driver = $options[$cacheNameKey]['driver'];

            $cacheInstanceKey = 'orm.cache.instances.'.$name.'.'.$cacheName;
            if (isset($container[$cacheInstanceKey])) {
                return $container[$cacheInstanceKey];
            }

            $cache = $container['orm.cache.factory']($driver, $options[$cacheNameKey]);

            if (isset($options['cache_namespace']) && $cache instanceof CacheProvider) {
                $cache->setNamespace($options['cache_namespace']);
            }

            return $container[$cacheInstanceKey] = $cache;
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheFactoryApcuDefinition(Container $container): \Closure
    {
        return $container->protect(function () use ($container) {
            return new ApcuCache();
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheFactoryArrayDefinition(Container $container): \Closure
    {
        return $container->protect(function () use ($container) {
            return new ArrayCache();
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheFactoryFilesystemDefinition(Container $container): \Closure
    {
        return $container->protect(function ($cacheOptions) {
            if (empty($cacheOptions['path'])) {
                throw new \RuntimeException('FilesystemCache path not defined');
            }

            $cacheOptions += [
                'extension' => FilesystemCache::EXTENSION,
                'umask' => 0002,
            ];

            return new FilesystemCache($cacheOptions['path'], $cacheOptions['extension'], $cacheOptions['umask']);
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheFactoryMemcacheDefinition(Container $container): \Closure
    {
        return $container->protect(function ($cacheOptions) use ($container) {
            if (empty($cacheOptions['host']) || empty($cacheOptions['port'])) {
                throw new \RuntimeException('Host and port options need to be specified for memcache cache');
            }

            $memcache = new \Memcache();
            $memcache->connect($cacheOptions['host'], $cacheOptions['port']);

            $cache = new MemcacheCache();
            $cache->setMemcache($memcache);

            return $cache;
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheFactoryMemcachedDefinition(Container $container): \Closure
    {
        return $container->protect(function ($cacheOptions) use ($container) {
            if (empty($cacheOptions['host']) || empty($cacheOptions['port'])) {
                throw new \RuntimeException('Host and port options need to be specified for memcached cache');
            }

            $memcached = new \Memcached();
            $memcached->addServer($cacheOptions['host'], $cacheOptions['port']);

            $cache = new MemcachedCache();
            $cache->setMemcached($memcached);

            return $cache;
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheFactoryRedisDefinition(Container $container): \Closure
    {
        return $container->protect(function ($cacheOptions) use ($container) {
            if (empty($cacheOptions['host']) || empty($cacheOptions['port'])) {
                throw new \RuntimeException('Host and port options need to be specified for redis cache');
            }

            $redis = new \Redis();
            $redis->connect($cacheOptions['host'], $cacheOptions['port']);

            if (isset($cacheOptions['password'])) {
                $redis->auth($cacheOptions['password']);
            }

            $cache = new RedisCache();
            $cache->setRedis($redis);

            return $cache;
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheFactoryXCacheDefinition(Container $container): \Closure
    {
        return $container->protect(function () use ($container) {
            return new XcacheCache();
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmCacheFactoryDefinition(Container $container): \Closure
    {
        return $container->protect(function ($driver, $cacheOptions) use ($container) {
            $cacheFactoryKey = 'orm.cache.factory.'.$driver;
            if (!isset($container[$cacheFactoryKey])) {
                throw new \RuntimeException(
                    sprintf('Factory "%s" for cache type "%s" not defined', $cacheFactoryKey, $driver)
                );
            }

            return $container[$cacheFactoryKey]($cacheOptions);
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmMappingDriverChainLocatorDefinition(Container $container): \Closure
    {
        return $container->protect(function ($name = null) use ($container) {
            $container['orm.ems.options.initializer']();

            if (null === $name) {
                $name = $container['orm.ems.default'];
            }

            $cacheInstanceKey = 'orm.mapping_driver_chain.instances.'.$name;
            if (isset($container[$cacheInstanceKey])) {
                return $container[$cacheInstanceKey];
            }

            return $container[$cacheInstanceKey] = $container['orm.mapping_driver_chain.factory']($name);
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmMappingDriverChainFactoryDefinition(Container $container): \Closure
    {
        return $container->protect(function () use ($container) {
            return new MappingDriverChain();
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmNamingStrategyDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            return new DefaultNamingStrategy();
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmQuoteStrategyDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            return new DefaultQuoteStrategy();
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmEntityListenerResolverDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            return new DefaultEntityListenerResolver();
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmRepositoryFactoryDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            return new DefaultRepositoryFactory();
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmEmDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $ems = $container['orm.ems'];

            return $ems[$container['orm.ems.default']];
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getOrmEmConfigDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $configs = $container['orm.ems.config'];

            return $configs[$container['orm.ems.default']];
        };
    }
}
