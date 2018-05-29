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
use Psr\Log\LogLevel;

final class MonologServiceProvider
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['logger'] = $this->getLoggerDefinition($container);
        $container['monolog'] = $this->getMonologDefinition($container);
        $container['monolog.formatter'] = $this->getMonologFormatterDefinition($container);
        $container['monolog.default_handler'] = $this->getMonologDefaultHandlerDefinition($container);
        $container['monolog.handlers'] = $this->getMonologHandlersDefinition($container);
        $container['monolog.level'] = LogLevel::DEBUG;
        $container['monolog.name'] = 'app';
        $container['monolog.bubble'] = true;
        $container['monolog.permission'] = null;
    }

    /**
     * @param Container $container
     *
     * @return \Closure
     */
    private function getLoggerDefinition(Container $container): \Closure
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
    private function getMonologDefinition(Container $container): \Closure
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
    private function getMonologFormatterDefinition(Container $container): \Closure
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
    private function getMonologHandlersDefinition(Container $container): \Closure
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
    private function getMonologDefaultHandlerDefinition(Container $container): \Closure
    {
        return function () use ($container) {
            $handler = new StreamHandler(
                $container['monolog.logfile'],
                $container['monolog.level'],
                $container['monolog.bubble'],
                $container['monolog.permission']
            );

            $handler->setFormatter($container['monolog.formatter']);

            return $handler;
        };
    }
}
