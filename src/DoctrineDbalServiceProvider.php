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
        $container['doctrine.dbal.db.default_options'] = $this->getDbDefaultOptions();
        $container['doctrine.dbal.dbs.options.initializer'] = $this->getDbsOptionsInitializerDefinition($container);
        $container['doctrine.dbal.dbs'] = $this->getDbsDefinition($container);
        $container['doctrine.dbal.dbs.config'] = $this->getDbsConfigDefinition($container);
        $container['doctrine.dbal.dbs.event_manager'] = $this->getDbsEventManagerDefinition($container);
        $container['doctrine.dbal.db'] = $this->getDbDefinition($container);
        $container['doctrine.dbal.db.config'] = $this->getDbConfigDefinition($container);
        $container['doctrine.dbal.db.event_manager'] = $this->getDbEventManagerDefinition($container);
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
                    'default' => isset($container['doctrine.dbal.db.options']) ? $container['doctrine.dbal.db.options'] : [],
                ];
            }

            $tmp = $container['doctrine.dbal.dbs.options'];
            foreach ($tmp as $name => &$options) {
                $options = array_replace($container['doctrine.dbal.db.default_options'], $options);

                if (!isset($container['doctrine.dbal.dbs.default'])) {
                    $container['doctrine.dbal.dbs.default'] = $name;
                }
            }

            $container['doctrine.dbal.dbs.options'] = $tmp;
        });
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
                    return DriverManager::getConnection($options, $config, $manager);
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

            $addLogger = isset($container['logger']) && null !== $container['logger']
                && class_exists('Symfony\Bridge\Doctrine\Logger\DbalLogger');
            $stopwatch = $container['stopwatch'] ?? null;

            $configs = new Container();
            foreach ($container['doctrine.dbal.dbs.options'] as $name => $options) {
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
                };
            }

            return $managers;
        };
    }

    /***
     * @param Container $container
     * @return callable
     */
    private function getDbDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.dbal.dbs'];

            return $dbs[$container['doctrine.dbal.dbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return callable
     */
    private function getDbConfigDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.dbal.dbs.config'];

            return $dbs[$container['doctrine.dbal.dbs.default']];
        };
    }

    /***
     * @param Container $container
     * @return callable
     */
    private function getDbEventManagerDefinition(Container $container): callable
    {
        return function () use ($container) {
            $dbs = $container['doctrine.dbal.dbs.event_manager'];

            return $dbs[$container['doctrine.dbal.dbs.default']];
        };
    }
}
