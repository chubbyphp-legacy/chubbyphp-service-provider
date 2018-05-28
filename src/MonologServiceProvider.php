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
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['logger'] = $this->getLoggerServiceDefinition($container);
        $container['monolog'] = $this->getMonologServiceDefinition($container);
        $container['monolog.formatter'] = $this->getMonologFormatterServiceDefinition($container);
        $container['monolog.default_handler'] = $this->getMonologDefaultHandlerServiceDefinition($container);
        $container['monolog.handlers'] = $this->getMonologHandlersServiceDefinition($container);
        $container['monolog.level'] = Logger::DEBUG;
        $container['monolog.name'] = 'app';
        $container['monolog.bubble'] = true;
        $container['monolog.permission'] = null;
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getLoggerServiceDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            return $container['monolog'];
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getMonologServiceDefinition(Container $container): \Closure
    {
        return function ($container) {
            $log = new Logger($container['monolog.name']);
            $log->pushHandler(new GroupHandler($container['monolog.handlers']));

            return $log;
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getMonologFormatterServiceDefinition(Container $container): \Closure
    {
        return function () {
            return new LineFormatter();
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getMonologHandlersServiceDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            return [$container['monolog.default_handler']];
        };
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getMonologDefaultHandlerServiceDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $handler = new StreamHandler(
                $container['monolog.logfile'],
                MonologServiceProvider::translateLevel($container['monolog.level']),
                $container['monolog.bubble'],
                $container['monolog.permission']
            );

            $handler->setFormatter($container['monolog.formatter']);

            return $handler;
        };
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
