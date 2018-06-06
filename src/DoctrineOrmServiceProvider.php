<?php

declare(strict_types=1);

/*
 * (c) Beau Simensen <beau@dflydev.com> (https://github.com/dflydev/dflydev-doctrine-orm-service-provider)
 */

namespace Chubbyphp\ServiceProvider;

use Chubbyphp\ServiceProvider\Registry\DoctrineOrmManagerRegistry;
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
        $container['doctrine.orm.manager_registry'] = $this->getOrmManagerRegistryDefintion($container);
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

            foreach (['query', 'hydration', 'metadata', 'result'] as $cacheType) {
                $setMethod = sprintf('set%sCacheImpl', ucfirst($cacheType));
                $cacheOptions = $options[sprintf('%s_cache', $cacheType)] ?? $container['doctrine.orm.default_cache'];
                if (is_string($cacheOptions)) {
                    $cacheOptions = ['driver' => $cacheOptions];
                }

                $config->$setMethod(
                    $container['doctrine.cache.locator'](sprintf('%s_%s', $name, $cacheType), $cacheOptions)
                );
            }

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
            foreach ($mappings as $mapping) {
                if (!is_array($mapping)) {
                    throw new \InvalidArgumentException(
                        'The "doctrine.orm.em.options" option "mappings" should be an array of arrays.'
                    );
                }

                if (isset($mapping['alias'])) {
                    $config->addEntityNamespace($mapping['alias'], $mapping['namespace']);
                }

                $factoryKey = sprintf('doctrine.orm.mapping_driver.factory.%s', $mapping['type']);
                if (!isset($container[$factoryKey])) {
                    throw new \InvalidArgumentException(
                        sprintf('There is no driver factory for type "%s"', $mapping['type'])
                    );
                }

                $chain->addDriver($container[$factoryKey]($mapping, $config), $mapping['namespace']);
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
        return $container->protect(function (array $mapping, Configuration $config) {
            $useSimpleAnnotationReader = $mapping['use_simple_annotation_reader'] ?? true;

            return $config->newDefaultAnnotationDriver(
                (array) $mapping['path'],
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
        return $container->protect(function (array $mapping, Configuration $config) {
            return new YamlDriver($mapping['path'], $mapping['extension'] ?? YamlDriver::DEFAULT_FILE_EXTENSION);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactorySimpleYaml(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new SimplifiedYamlDriver(
                [$mapping['path'] => $mapping['namespace']],
                $mapping['extension'] ?? SimplifiedYamlDriver::DEFAULT_FILE_EXTENSION
            );
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryXml(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new XmlDriver($mapping['path'], $mapping['extension'] ?? XmlDriver::DEFAULT_FILE_EXTENSION);
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactorySimpleXml(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new SimplifiedXmlDriver(
                [$mapping['path'] => $mapping['namespace']],
                $mapping['extension'] ?? SimplifiedXmlDriver::DEFAULT_FILE_EXTENSION
            );
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmMappingDriverFactoryPhp(Container $container): callable
    {
        return $container->protect(function (array $mapping, Configuration $config) {
            return new StaticPHPDriver($mapping['path']);
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

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getOrmManagerRegistryDefintion(Container $container): callable
    {
        return function ($container) {
            return new DoctrineOrmManagerRegistry($container);
        };
    }
}
