<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\MonologServiceProvider;
use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * @covers \Chubbyphp\ServiceProvider\MonologServiceProvider
 */
class MonologServiceProviderTest extends TestCase
{
    public function testRegisterWithDefaults()
    {
        $container = new Container();

        $serviceProvider = new MonologServiceProvider();
        $serviceProvider->register($container);

        self::assertTrue($container->offsetExists('logger'));
        self::assertTrue($container->offsetExists('monolog'));
        self::assertTrue($container->offsetExists('monolog.formatter'));
        self::assertTrue($container->offsetExists('monolog.handler'));
        self::assertTrue($container->offsetExists('monolog.handlers'));
        self::assertTrue($container->offsetExists('monolog.level'));
        self::assertTrue($container->offsetExists('monolog.name'));
        self::assertTrue($container->offsetExists('monolog.bubble'));
        self::assertTrue($container->offsetExists('monolog.permission'));
        self::assertTrue($container->offsetExists('monolog.logfile'));
    }
}
