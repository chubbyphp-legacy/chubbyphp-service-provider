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
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache\CacheConfiguration;
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
        $container['doctrine.orm.em.default_options'] = $this->getOrmEmDefaultOptions();
        $container['doctrine.orm.ems.options.initializer'] = $this->getOrmEmsOptionsInitializerDefinition($container);
        $container['doctrine.orm.ems'] = $this->getOrmEmsDefinition($container);
        $container['doctrine.orm.ems.config'] = $this->getOrmEmsConfigServiceProvider($container);
        $container['doctrine.orm.proxies_dir'] = sys_get_temp_dir();
        $container['doctrine.orm.auto_generate_proxies'] = true;
        $container['doctrine.orm.proxies_namespace'] = 'DoctrineProxy';
        $container['doctrine.orm.mapping_driver_chain'] = $this->getOrmMappingDriverChainDefinition($container);
        $container['doctrine.orm.mapping_driver_chain.factory'] = $this->getOrmMappingDriverChainFactoryDefinition($container);
        $container['doctrine.orm.mapping_driver.factory.annotation'] = $this->getOrmMappingDriverFactoryAnnotation($container);
        $container['doctrine.orm.mapping_driver.factory.yml'] = $this->getOrmMappingDriverFactoryYaml($container);
        $container['doctrine.orm.mapping_driver.factory.simple_yml'] = $this->getOrmMappingDriverFactorySimpleYaml($container);
        $container['doctrine.orm.mapping_driver.factory.xml'] = $this->getOrmMappingDriverFactoryXml($container);
        $container['doctrine.orm.mapping_driver.factory.simple_xml'] = $this->getOrmMappingDriverFactorySimpleXml($container);
        $container['doctrine.orm.mapping_driver.factory.php'] = $this->getOrmMappingDriverFactoryPhp($container);
        $container['doctrine.orm.cache.locator'] = $this->getOrmCacheLocatorDefinition($container);
        $container['doctrine.orm.cache.factory'] = $this->getOrmCacheFactoryDefinition($container);
        $container['doctrine.orm.cache.factory.apcu'] = $this->getOrmCacheFactoryApcuDefinition($container);
        $container['doctrine.orm.cache.factory.array'] = $this->getOrmCacheFactoryArrayDefinition($container);
        $container['doctrine.orm.cache.factory.filesystem'] = $this->getOrmCacheFactoryFilesystemDefinition($container);
        $container['doctrine.orm.cache.factory.memcached'] = $this->getOrmCacheFactoryMemcachedDefinition($container);
        $container['doctrine.orm.cache.factory.redis'] = $this->getOrmCacheFactoryRedisDefinition($container);
        $container['doctrine.orm.cache.factory.xcache'] = $this->getOrmCacheFactoryXCacheDefinition($container);
        $container['doctrine.orm.default_cache'] = ['driver' => 'array'];
        $container['doctrine.orm.custom.functions.string'] = [];
        $container['doctrine.orm.custom.functions.numeric'] = [];
        $container['doctrine.orm.custom.functions.datetime'] = [];
        $container['doctrine.orm.custom.hydration_modes'] = [];
        $container['doctrine.orm.class_metadata_factory_name'] = ClassMetadataFactory::class;
        $container['doctrine.orm.default_repository_class'] = EntityRepository::class;
        $container['doctrine.orm.strategy.naming'] = $this->getOrmNamingStrategyDefinition($container);
        $container['doctrine.orm.strategy.quote'] = $this->getOrmQuoteStrategyDefinition($container);
        $container['doctrine.orm.entity_listener_resolver'] = $this->getOrmEntityListenerResolverDefinition($container);
        $container['doctrine.orm.repository_factory'] = $this->getOrmRepositoryFactoryDefinition($container);
        $container['doctrine.orm.second_level_cache.enabled'] = false;
        $container['doctrine.orm.second_level_cache.configuration'] = $this->getOrmSecondLevelCacheConfigurationDefinition($container);
        $container['doctrine.orm.default.query_hints'] = [];
        $container['doctrine.orm.em'] = $this->getOrmEmDefinition($container);
        $container['doctrine.orm.em.config'] = $this->getOrmEmConfigDefinition($container);
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
     * @return callable
     */
    private function getOrmEmsOptionsInitializerDefinition(Container $container): callable
    {
        return $container->protect(function () use ($container) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($container['doctrine.orm.ems.options'])) {
                $container['doctrine.orm.ems.options'] = [
                    'default' => isset($container['doctrine.orm.em.options']) ? $container['doctrine.orm.em.options'] : [],
                ];
            }

            $tmp = $container['doctrine.orm.ems.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace($container['doctrine.orm.em.default_options'], $options);

                if (!isset($container['doctrine.orm.ems.default'])) {
                    $container['doctrine.orm.ems.default'] = $name;
                }
            }

            $container['doctrine.orm.ems.options'] = $tmp;
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmsDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.orm.ems.options.initializer']();

            $ems = new Container();
            foreach ($container['doctrine.orm.ems.options'] as $name => $options) {
                if ($container['doctrine.orm.ems.default'] === $name) {
                    // we use shortcuts here in case the default has been overridden
                    $config = $container['doctrine.orm.em.config'];
                } else {
                    $config = $container['doctrine.orm.ems.config'][$name];
                }

                $ems[$name] = function () use ($container, $options, $config) {
                    return EntityManager::create(
                        $container['doctrine.dbal.dbs'][$options['connection']],
                        $config,
                        $container['doctrine.dbal.dbs.event_manager'][$options['connection']]
                    );
                };
            }

            return $ems;
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmsConfigServiceProvider(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.orm.ems.options.initializer']();

            $configs = new Container();
            foreach ($container['doctrine.orm.ems.options'] as $name => $options) {
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
     * @return callable
     */
    private function getOrmEmConfigByNameAndOptionsDefinition(
        Container $container,
        string $name,
        array $options
    ): callable {
        return function () use ($container, $name, $options) {
            $config = new Configuration();

            $config->setProxyDir($container['doctrine.orm.proxies_dir']);
            $config->setAutoGenerateProxyClasses($container['doctrine.orm.auto_generate_proxies']);
            $config->setProxyNamespace($container['doctrine.orm.proxies_namespace']);
            $config->setMetadataDriverImpl(
                $container['doctrine.orm.mapping_driver_chain']($name, $config, (array) $options['mappings'])
            );
            $config->setQueryCacheImpl($container['doctrine.orm.cache.locator']($name, 'query', $options));
            $config->setHydrationCacheImpl($container['doctrine.orm.cache.locator']($name, 'hydration', $options));
            $config->setMetadataCacheImpl($container['doctrine.orm.cache.locator']($name, 'metadata', $options));
            $config->setResultCacheImpl($container['doctrine.orm.cache.locator']($name, 'result', $options));

            foreach ((array) $options['types'] as $typeName => $typeClass) {
                if (Type::hasType($typeName)) {
                    Type::overrideType($typeName, $typeClass);
                } else {
                    Type::addType($typeName, $typeClass);
                }
            }

            $config->setCustomStringFunctions($container['doctrine.orm.custom.functions.string']);
            $config->setCustomNumericFunctions($container['doctrine.orm.custom.functions.numeric']);
            $config->setCustomDatetimeFunctions($container['doctrine.orm.custom.functions.datetime']);
            $config->setCustomHydrationModes($container['doctrine.orm.custom.hydration_modes']);

            $config->setClassMetadataFactoryName($container['doctrine.orm.class_metadata_factory_name']);
            $config->setDefaultRepositoryClassName($container['doctrine.orm.default_repository_class']);

            $config->setNamingStrategy($container['doctrine.orm.strategy.naming']);
            $config->setQuoteStrategy($container['doctrine.orm.strategy.quote']);

            $config->setEntityListenerResolver($container['doctrine.orm.entity_listener_resolver']);
            $config->setRepositoryFactory($container['doctrine.orm.repository_factory']);

            $config->setSecondLevelCacheEnabled($container['doctrine.orm.second_level_cache.enabled']);
            $config->setSecondLevelCacheConfiguration($container['doctrine.orm.second_level_cache.configuration']);

            $config->setDefaultQueryHints($container['doctrine.orm.default.query_hints']);

            return $config;
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverChainDefinition(Container $container): callable
    {
        return $container->protect(function (string $name, Configuration $config, array $mappings) use ($container) {
            $container['doctrine.orm.ems.options.initializer']();

            /** @var MappingDriverChain $chain */
            $chain = $container['doctrine.orm.mapping_driver_chain.factory']();
            foreach ($mappings as $entity) {
                if (!is_array($entity)) {
                    throw new \InvalidArgumentException(
                        "The 'doctrine.orm.em.options' option 'mappings' should be an array of arrays."
                    );
                }

                if (isset($entity['alias'])) {
                    $config->addEntityNamespace($entity['alias'], $entity['namespace']);
                }

                $factoryKey = sprintf('doctrine.orm.mapping_driver.factory.%s', $entity['type']);
                if (!isset($container[$factoryKey])) {
                    throw new \InvalidArgumentException(
                        sprintf('There is no driver factory for type "%s"', $entity['type'])
                    );
                }

                $chain->addDriver($container[$factoryKey]($entity, $config), $entity['namespace']);
            }

            return $container['doctrine.orm.mapping_driver_chain.instances.'.$name] = $chain;
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverChainFactoryDefinition(Container $container): callable
    {
        return $container->protect(function () use ($container) {
            return new MappingDriverChain();
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryAnnotation(Container $container): callable
    {
        return $container->protect(function (array $entity, Configuration $config) {
            $useSimpleAnnotationReader = $entity['use_simple_annotation_reader'] ?? true;

            return $config->newDefaultAnnotationDriver(
                (array) $entity['path'],
                $useSimpleAnnotationReader
            );
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryYaml(Container $container): callable
    {
        return $container->protect(function (array $entity, Configuration $config) {
            return new YamlDriver($entity['path']);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactorySimpleYaml(Container $container): callable
    {
        return $container->protect(function (array $entity, Configuration $config) {
            return new SimplifiedYamlDriver([$entity['path'] => $entity['namespace']]);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryXml(Container $container): callable
    {
        return $container->protect(function (array $entity, Configuration $config) {
            return new XmlDriver($entity['path']);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactorySimpleXml(Container $container): callable
    {
        return $container->protect(function (array $entity, Configuration $config) {
            return new SimplifiedXmlDriver([$entity['path'] => $entity['namespace']]);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryPhp(Container $container): callable
    {
        return $container->protect(function (array $entity, Configuration $config) {
            return new StaticPHPDriver($entity['path']);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheLocatorDefinition(Container $container): callable
    {
        return $container->protect(function (string $name, string $cacheName, array $options) use ($container) {
            $cacheNameKey = $cacheName.'_cache';

            if (!isset($options[$cacheNameKey])) {
                $options[$cacheNameKey] = $container['doctrine.orm.default_cache'];
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

            $cache = $container['doctrine.orm.cache.factory']($driver, $options[$cacheNameKey]);

            if (isset($options['cache_namespace']) && $cache instanceof CacheProvider) {
                $cache->setNamespace($options['cache_namespace']);
            }

            return $container['doctrine.orm.cache.instances.'.$name.'.'.$cacheName] = $cache;
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmCacheFactoryDefinition(Container $container): callable
    {
        return $container->protect(function (string $driver, array $cacheOptions) use ($container) {
            $cacheFactoryKey = 'doctrine.orm.cache.factory.'.$driver;
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
     * @return callable
     */
    private function getOrmCacheFactoryApcuDefinition(Container $container): callable
    {
        return $container->protect(function (array $cacheOptions) use ($container) {
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
        return $container->protect(function (array $cacheOptions) use ($container) {
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
        return $container->protect(function (array $cacheOptions) {
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
     * @return callable
     */
    private function getOrmCacheFactoryMemcachedDefinition(Container $container): callable
    {
        return $container->protect(function (array $cacheOptions) use ($container) {
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
     * @return callable
     */
    private function getOrmCacheFactoryRedisDefinition(Container $container): callable
    {
        return $container->protect(function (array $cacheOptions) use ($container) {
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
     * @return callable
     */
    private function getOrmCacheFactoryXCacheDefinition(Container $container): callable
    {
        return $container->protect(function (array $cacheOptions) use ($container) {
            return new XcacheCache();
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmNamingStrategyDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new DefaultNamingStrategy();
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmQuoteStrategyDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new DefaultQuoteStrategy();
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEntityListenerResolverDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new DefaultEntityListenerResolver();
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmRepositoryFactoryDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new DefaultRepositoryFactory();
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmSecondLevelCacheConfigurationDefinition(Container $container): callable
    {
        return function () use ($container) {
            return new CacheConfiguration();
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmDefinition(Container $container): callable
    {
        return function () use ($container) {
            $ems = $container['doctrine.orm.ems'];

            return $ems[$container['doctrine.orm.ems.default']];
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmEmConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $configs = $container['doctrine.orm.ems.config'];

            return $configs[$container['doctrine.orm.ems.default']];
        };
    }
}
