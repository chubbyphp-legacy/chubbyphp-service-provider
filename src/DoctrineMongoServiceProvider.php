<?php

declare(strict_types=1);

namespace Chubbyphp\ServiceProvider;

use Chubbyphp\ServiceProvider\Logger\DoctrineMongoLogger;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Configuration;
use Doctrine\MongoDB\Connection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

final class DoctrineMongoServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['doctrine.mongo.db.default_options'] = $this->getMongoDbDefaultOptions();
        $container['doctrine.mongo.dbs.options.initializer'] = $this->getMongoDbsOptionsInitializerDefinition($container);
        $container['doctrine.mongo.dbs'] = $this->getMongoDbsDefinition($container);
        $container['doctrine.mongo.dbs.config'] = $this->getMongoDbsConfigDefinition($container);
        $container['doctrine.mongo.dbs.event_manager'] = $this->getMongoDbsEventManagerDefinition($container);
        $container['doctrine.mongo.db'] = $this->getMongoDbDefinition($container);
        $container['doctrine.mongo.db.config'] = $this->getMongoDbConfigDefinition($container);
        $container['doctrine.mongo.db.event_manager'] = $this->getMongoDbEventManagerDefinition($container);
        $container['doctrine.mongo.db.logger.batch_insert_threshold'] = 10;
        $container['doctrine.mongo.db.logger.prefix'] = 'MongoDB query: ';
    }

    /**
     * @return array
     */
    private function getMongoDbDefaultOptions(): array
    {
        return [
            'server' => 'mongodb://localhost:27017',
            'options' => [],
            /* @link http://www.php.net/manual/en/mongoclient.construct.php */
        ];
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getMongoDbsOptionsInitializerDefinition(Container $container): callable
    {
        return $container->protect(function () use ($container) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($container['doctrine.mongo.dbs.options'])) {
                $container['doctrine.mongo.dbs.options'] = [
                    'default' => isset($container['doctrine.mongo.db.options']) ? $container['doctrine.mongo.db.options'] : [],
                ];
            }

            $tmp = $container['doctrine.mongo.dbs.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace_recursive($container['doctrine.mongo.db.default_options'], $options);

                if (!isset($container['doctrine.mongo.dbs.default'])) {
                    $container['doctrine.mongo.dbs.default'] = $name;
                }
            }

            $container['doctrine.mongo.dbs.options'] = $tmp;
        });
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getMongoDbsDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.mongo.dbs.options.initializer']();

            $mongodbs = new Container();
            foreach ($container['doctrine.mongo.dbs.options'] as $name => $options) {
                if ($container['doctrine.mongo.dbs.default'] === $name) {
                    // we use shortcuts here in case the default has been overridden
                    $config = $container['doctrine.mongo.db.config'];
                    $manager = $container['doctrine.mongo.db.event_manager'];
                } else {
                    $config = $container['doctrine.mongo.dbs.config'][$name];
                    $manager = $container['doctrine.mongo.dbs.event_manager'][$name];
                }

                $mongodbs[$name] = function () use ($options, $config, $manager) {
                    return new Connection($options['server'], $options['options'], $config, $manager);
                };
            }

            return $mongodbs;
        };
    }

    /**
     * @param Container $container
     *
     * @return callable
     */
    private function getMongoDbsConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.mongo.dbs.options.initializer']();

            $addLogger = $container['logger'] ?? false;

            $configs = new Container();
            foreach ($container['doctrine.mongo.dbs.options'] as $name => $options) {
                $configs[$name] = function () use ($addLogger, $container) {
                    $config = new Configuration();
                    if ($addLogger) {
                        $logger = new DoctrineMongoLogger(
                            $container['logger'],
                            $container['doctrine.mongo.db.logger.batch_insert_threshold'],
                            $container['doctrine.mongo.db.logger.prefix']
                        );
                        $config->setLoggerCallable([$logger, 'logQuery']);
                    }

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
    private function getMongoDbsEventManagerDefinition(Container $container): callable
    {
        return function () use ($container) {
            $container['doctrine.mongo.dbs.options.initializer']();

            $managers = new Container();
            foreach ($container['doctrine.mongo.dbs.options'] as $name => $options) {
                $managers[$name] = function () {
                    return new EventManager();
                };
            }

            return $managers;
        };
    }

    /***
     * @param Container $container
     * @return callable
     */
    private function getMongoDbDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.mongo.dbs'];

            return $dbs[$container['doctrine.mongo.dbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return callable
     */
    private function getMongoDbConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.mongo.dbs.config'];

            return $dbs[$container['doctrine.mongo.dbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return callable
     */
    private function getMongoDbEventManagerDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.mongo.dbs.event_manager'];

            return $dbs[$container['doctrine.mongo.dbs.default']];
        };
    }
}
