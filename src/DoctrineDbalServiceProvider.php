<?php

declare(strict_types=1);

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chubbyphp\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;
use Symfony\Bridge\Doctrine\Logger\DbalLogger;

/**
 * Doctrine DBAL Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class DoctrineDbalServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['db.default_options'] = [
            'driver' => 'pdo_mysql',
            'dbname' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
        ];

        $container['dbs.options.initializer'] = $container->protect(function () use ($container) {
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

        $container['dbs'] = function ($container) {
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

                $dbs[$name] = function ($dbs) use ($options, $config, $manager) {
                    return DriverManager::getConnection($options, $config, $manager);
                };
            }

            return $dbs;
        };

        $container['dbs.config'] = function ($container) {
            $container['dbs.options.initializer']();

            $configs = new Container();
            $addLogger = isset($container['logger']) && null !== $container['logger']
                && class_exists('Symfony\Bridge\Doctrine\Logger\DbalLogger');
            foreach ($container['dbs.options'] as $name => $options) {
                $configs[$name] = new Configuration();
                if ($addLogger) {
                    $configs[$name]->setSQLLogger(
                        new DbalLogger(
                            $container['logger'],
                            isset($container['stopwatch']) ? $container['stopwatch'] : null
                        )
                    );
                }
            }

            return $configs;
        };

        $container['dbs.event_manager'] = function ($container) {
            $container['dbs.options.initializer']();

            $managers = new Container();
            foreach ($container['dbs.options'] as $name => $options) {
                $managers[$name] = new EventManager();
            }

            return $managers;
        };

        // shortcuts for the "first" DB
        $container['db'] = function ($container) {
            $dbs = $container['dbs'];

            return $dbs[$container['dbs.default']];
        };

        $container['db.config'] = function ($container) {
            $dbs = $container['dbs.config'];

            return $dbs[$container['dbs.default']];
        };

        $container['db.event_manager'] = function ($container) {
            $dbs = $container['dbs.event_manager'];

            return $dbs[$container['dbs.default']];
        };
    }
}
