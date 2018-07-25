<?php

declare(strict_types=1);

namespace Chubbyphp\ServiceProvider;

use Chubbyphp\ServiceProvider\Logger\DoctrineDbalLogger;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

final class DoctrineDbalServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['doctrine.dbal.db'] = $this->getDbDefinition($container);
        $container['doctrine.dbal.db.cache_factory.apcu'] = $this->getDbApcuCacheFactoryDefinition($container);
        $container['doctrine.dbal.db.cache_factory.array'] = $this->getDbArrayCacheFactoryDefinition($container);
        $container['doctrine.dbal.db.config'] = $this->getDbConfigDefinition($container);
        $container['doctrine.dbal.db.default_options'] = $this->getDbDefaultOptions();
        $container['doctrine.dbal.db.event_manager'] = $this->getDbEventManagerDefinition($container);
        $container['doctrine.dbal.dbs'] = $this->getDbsDefinition($container);
        $container['doctrine.dbal.dbs.config'] = $this->getDbsConfigDefinition($container);
        $container['doctrine.dbal.dbs.event_manager'] = $this->getDbsEventManagerDefinition($container);
        $container['doctrine.dbal.dbs.options.initializer'] = $this->getDbsOptionsInitializerDefinition($container);
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.dbal.dbs'];

            return $dbs[$container['doctrine.dbal.dbs.default']];
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbApcuCacheFactoryDefinition(Container $container): callable
    {
        return $container->factory(function () use ($container) {
            return new ApcuCache();
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbArrayCacheFactoryDefinition(Container $container): callable
    {
        return $container->factory(function () use ($container) {
            return new ArrayCache();
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.dbal.dbs.config'];

            return $dbs[$container['doctrine.dbal.dbs.default']];
        };
    }

    /**
     * @return array
     */
    private function getDbDefaultOptions(): array
    {
        return [
            'configuration' => [
                'auto_commit' => true,
                'cache.result' => 'array',
                'filter_schema_assets_expression' => null,
            ],
            'connection' => [
                'charset' => 'utf8mb4',
                'dbname' => null,
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'password' => null,
                'path' => null,
                'port' => 3306,
                'user' => 'root',
            ],
        ];
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbEventManagerDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.dbal.dbs.event_manager'];

            return $dbs[$container['doctrine.dbal.dbs.default']];
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbsDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.dbal.dbs.options.initializer']();

            $dbs = new Container();
            foreach ($container['doctrine.dbal.dbs.options'] as $name => $options) {
                if ($container['doctrine.dbal.dbs.default'] === $name) {
                    // we use shortcuts here in case the default has been overridden
                    $config = $container['doctrine.dbal.db.config'];
                    $manager = $container['doctrine.dbal.db.event_manager'];
                } else {
                    $config = $container['doctrine.dbal.dbs.config'][$name];
                    $manager = $container['doctrine.dbal.dbs.event_manager'][$name];
                }

                $dbs[$name] = function () use ($options, $config, $manager) {
                    return DriverManager::getConnection($options['connection'], $config, $manager);
                };
            }

            return $dbs;
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbsConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.dbal.dbs.options.initializer']();

            $addLogger = $container['logger'] ?? false;

            $configs = new Container();
            foreach ($container['doctrine.dbal.dbs.options'] as $name => $options) {
                $configs[$name] = function () use ($addLogger, $container, $name, $options) {
                    $configOptions = $options['configuration'];

                    $config = new Configuration();

                    if ($addLogger) {
                        $config->setSQLLogger(new DoctrineDbalLogger($container['logger']));
                    }

                    $cacheFactoryKey = sprintf('doctrine.dbal.db.cache_factory.%s', $configOptions['cache.result']);
                    $config->setResultCacheImpl($container[$cacheFactoryKey]);

                    $config->setFilterSchemaAssetsExpression($configOptions['filter_schema_assets_expression']);
                    $config->setAutoCommit($configOptions['auto_commit']);

                    return $config;
                };
            }

            return $configs;
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbsEventManagerDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.dbal.dbs.options.initializer']();

            $managers = new Container();
            foreach ($container['doctrine.dbal.dbs.options'] as $name => $options) {
                $managers[$name] = function () {
                    return new EventManager();
                    // todo: check for set/add methods to implement
                };
            }

            return $managers;
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getDbsOptionsInitializerDefinition(Container $container): callable
    {
        return $container->protect(function () use ($container) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($container['doctrine.dbal.dbs.options'])) {
                $container['doctrine.dbal.dbs.options'] = [
                    'default' => $container['doctrine.dbal.db.options'] ?? [],
                ];
            }

            $tmp = $container['doctrine.dbal.dbs.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace_recursive($container['doctrine.dbal.db.default_options'], $options);

                if (!isset($container['doctrine.dbal.dbs.default'])) {
                    $container['doctrine.dbal.dbs.default'] = $name;
                }
            }

            $container['doctrine.dbal.dbs.options'] = $tmp;
        });
    }
}
