<?php

declare(strict_types=1);

/*
 * (c) Fabien Potencier <fabien@symfony.com> (https://github.com/silexphp/Silex-Providers)
 */

namespace Chubbyphp\ServiceProvider;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;

final class MonologServiceProvider
{
    public function register(Container $container)
    {
        $container['logger'] = function () use ($container) {
            return $container['monolog'];
        };

        $container['monolog'] = function ($container) {
            $log = new Logger($container['monolog.name']);
            $log->pushHandler(new GroupHandler($container['monolog.handlers']));

            return $log;
        };

        $container['monolog.formatter'] = function () {
            return new LineFormatter();
        };

        $container['monolog.handler'] = $defaultHandler = function () use ($container) {
            $level = MonologServiceProvider::translateLevel($container['monolog.level']);

            $handler = new StreamHandler(
                $container['monolog.logfile'],
                $level,
                $container['monolog.bubble'],
                $container['monolog.permission']
            );

            $handler->setFormatter($container['monolog.formatter']);

            return $handler;
        };

        $container['monolog.handlers'] = function () use ($container, $defaultHandler) {
            $handlers = [];

            // enables the default handler if a logfile was set or the monolog.handler service was redefined
            if ($container['monolog.logfile'] || $defaultHandler !== $container->raw('monolog.handler')) {
                $handlers[] = $container['monolog.handler'];
            }

            return $handlers;
        };

        $container['monolog.level'] = function () {
            return Logger::DEBUG;
        };

        $container['monolog.name'] = 'app';
        $container['monolog.bubble'] = true;
        $container['monolog.permission'] = null;
    }

    /**
     * @param int|string $name
     *
     * @return int
     */
    public static function translateLevel($name): int
    {
        // level is already translated to logger constant, return as-is
        if (is_int($name)) {
            return $name;
        }

        $psrLevel = Logger::toMonologLevel($name);

        if (is_int($psrLevel)) {
            return $psrLevel;
        }

        $levels = Logger::getLevels();
        $upper = strtoupper($name);

        if (!isset($levels[$upper])) {
            throw new \InvalidArgumentException(
                "Provided logging level '$name' does not exist. Must be a valid monolog logging level."
            );
        }

        return $levels[$upper];
    }
}
