<?php

declare(strict_types=1);

/*
 * (c) Fabien Potencier <fabien@symfony.com> (https://github.com/silexphp/Silex-Providers)
 */

namespace Chubbyphp\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;
use Symfony\Bridge\Doctrine\Logger\DbalLogger;

final class DoctrineDbalServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['db.default_options'] = $this->getDbDefaultOptions();
        $container['dbs.options.initializer'] = $this->getDbsOptionsInitializerDefinition($container);
        $container['dbs'] = $this->getDbsDefinition($container);
        $container['dbs.config'] = $this->getDbsConfigDefinition($container);
        $container['dbs.event_manager'] = $this->getDbsEventManagerDefinition($container);
        $container['db'] = $this->getDbDefinition($container);
        $container['db.config'] = $this->getDbConfigDefinition($container);
        $container['db.event_manager'] = $this->getDbEventManagerDefinition($container);
    }

    /**
     * @return array
     */
    private function getDbDefaultOptions(): array
    {
        return [
            'driver' => 'pdo_mysql',
            'dbname' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
        ];
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getDbsOptionsInitializerDefinition(Container $container): \Closure
    {
        return $container->protect(function () use ($container) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            if (!isset($container['dbs.options'])) {
                $container['dbs.options'] = [
                    'default' => isset($container['db.options']) ? $container['db.options'] : [],
                ];
            }

            $tmp = $container['dbs.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace($container['db.default_options'], $options);

                if (!isset($container['dbs.default'])) {
                    $container['dbs.default'] = $name;
                }
            }

            $container['dbs.options'] = $tmp;
        });
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getDbsDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $container['dbs.options.initializer']();

            $dbs = new Container();
            foreach ($container['dbs.options'] as $name => $options) {
                if ($container['dbs.default'] === $name) {
                    // we use shortcuts here in case the default has been overridden
                    $config = $container['db.config'];
                    $manager = $container['db.event_manager'];
                } else {
                    $config = $container['dbs.config'][$name];
                    $manager = $container['dbs.event_manager'][$name];
                }

                $dbs[$name] = function () use ($options, $config, $manager) {
                    return DriverManager::getConnection($options, $config, $manager);
                };
            }

            return $dbs;
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getDbsConfigDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $container['dbs.options.initializer']();

            $addLogger = isset($container['logger']) && null !== $container['logger']
                && class_exists('Symfony\Bridge\Doctrine\Logger\DbalLogger');
            $stopwatch = $container['stopwatch'] ?? null;

            $configs = new Container();
            foreach ($container['dbs.options'] as $name => $options) {
                $configs[$name] = function () use ($addLogger, $container, $stopwatch) {
                    $config = new Configuration();
                    if ($addLogger) {
                        $config->setSQLLogger(
                            new DbalLogger($container['logger'], $stopwatch)
                        );
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
    private function getDbsEventManagerDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $container['dbs.options.initializer']();

            $managers = new Container();
            foreach ($container['dbs.options'] as $name => $options) {
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
    private function getDbDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $dbs = $container['dbs'];

            return $dbs[$container['dbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return \Closure
     */
    private function getDbConfigDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $dbs = $container['dbs.config'];

            return $dbs[$container['dbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return \Closure
     */
    private function getDbEventManagerDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $dbs = $container['dbs.event_manager'];

            return $dbs[$container['dbs.default']];
        };
    }
}
