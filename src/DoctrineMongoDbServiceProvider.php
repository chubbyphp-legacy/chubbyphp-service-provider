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
        $container['mongodbs.options.initializer'] = $this->getMongoDbsOptionsInitializerServiceDefinition($container);
        $container['mongodbs'] = $this->getMongoDbsServiceDefinition($container);
        $container['mongodbs.config'] = $this->getMongoDbsConfigServiceDefinition($container);
        $container['mongodbs.event_manager'] = $this->getMongoDbsEventManagerServiceDefinition($container);
        $container['mongodb'] = $this->getMongoDbServiceDefinition($container);
        $container['mongodb.config'] = $this->getMongoDbConfigServiceDefinition($container);
        $container['mongodb.event_manager'] = $this->getMongoDbEventManagerServiceDefinition($container);
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
     * @return \Closure
     */
    private function getMongoDbsOptionsInitializerServiceDefinition(Container $container): \Closure
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
     * @return \Closure
     */
    private function getMongoDbsServiceDefinition(Container $container): \Closure
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
     * @return \Closure
     */
    private function getMongoDbsConfigServiceDefinition(Container $container): \Closure
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
     * @return \Closure
     */
    private function getMongoDbsEventManagerServiceDefinition(Container $container): \Closure
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
     * @return \Closure
     */
    private function getMongoDbServiceDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $dbs = $container['mongodbs'];

            return $dbs[$container['mongodbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return \Closure
     */
    private function getMongoDbConfigServiceDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $dbs = $container['mongodbs.config'];

            return $dbs[$container['mongodbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return \Closure
     */
    private function getMongoDbEventManagerServiceDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $dbs = $container['mongodbs.event_manager'];

            return $dbs[$container['mongodbs.default']];
        };
    }
}
