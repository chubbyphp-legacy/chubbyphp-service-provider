<?php

declare(strict_types=1);

namespace Chubbyphp\ServiceProvider;

use Chubbyphp\ServiceProvider\Logger\DoctrineMongoDbLogger;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Configuration;
use Doctrine\MongoDB\Connection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

final class DoctrineMongoDbServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['mongodb.default_options'] = $this->getMongoDbDefaultOptions();
        $container['mongodbs.options.initializer'] = $this->getMongoDbsOptionsInitializerDefinition($container);
        $container['mongodbs'] = $this->getMongoDbsDefinition($container);
        $container['mongodbs.config'] = $this->getMongoDbsConfigDefinition($container);
        $container['mongodbs.event_manager'] = $this->getMongoDbsEventManagerDefinition($container);
        $container['mongodb'] = $this->getMongoDbDefinition($container);
        $container['mongodb.config'] = $this->getMongoDbConfigDefinition($container);
        $container['mongodb.event_manager'] = $this->getMongoDbEventManagerDefinition($container);
        $container['mongodb.logger.batch_insert_threshold'] = 10;
        $container['mongodb.logger.prefix'] = 'MongoDB query: ';
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

            if (!isset($container['mongodbs.options'])) {
                $container['mongodbs.options'] = [
                    'default' => isset($container['mongodb.options']) ? $container['mongodb.options'] : [],
                ];
            }

            $tmp = $container['mongodbs.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace_recursive($container['mongodb.default_options'], $options);

                if (!isset($container['mongodbs.default'])) {
                    $container['mongodbs.default'] = $name;
                }
            }

            $container['mongodbs.options'] = $tmp;
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
            $container['mongodbs.options.initializer']();

            $mongodbs = new Container();
            foreach ($container['mongodbs.options'] as $name => $options) {
                if ($container['mongodbs.default'] === $name) {
                    // we use shortcuts here in case the default has been overridden
                    $config = $container['mongodb.config'];
                    $manager = $container['mongodb.event_manager'];
                } else {
                    $config = $container['mongodbs.config'][$name];
                    $manager = $container['mongodbs.event_manager'][$name];
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
            $container['mongodbs.options.initializer']();

            $configs = new Container();

            $addLogger = isset($container['logger']) && null !== $container['logger'];
            foreach ($container['mongodbs.options'] as $name => $options) {
                $configs[$name] = function () use ($addLogger, $container) {
                    $config = new Configuration();
                    if ($addLogger) {
                        $logger = new DoctrineMongoDbLogger(
                            $container['logger'],
                            $container['mongodb.logger.batch_insert_threshold'],
                            $container['mongodb.logger.prefix']
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
            $container['mongodbs.options.initializer']();

            $managers = new Container();
            foreach ($container['mongodbs.options'] as $name => $options) {
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
            $dbs = $container['mongodbs'];

            return $dbs[$container['mongodbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return callable
     */
    private function getMongoDbConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['mongodbs.config'];

            return $dbs[$container['mongodbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return callable
     */
    private function getMongoDbEventManagerDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['mongodbs.event_manager'];

            return $dbs[$container['mongodbs.default']];
        };
    }
}
