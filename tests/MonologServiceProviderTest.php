<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\MonologServiceProvider;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @covers \Chubbyphp\ServiceProvider\MonologServiceProvider
 */
class MonologServiceProviderTest extends TestCase
{
    public function testRegister()
    {
        $container = new Container();

        $container['monolog.logfile'] = sys_get_temp_dir().'/chubbyphp.serviceprovider.monolog.log';

        $serviceProvider = new MonologServiceProvider();
        $serviceProvider->register($container);

        self::assertTrue($container->offsetExists('logger'));
        self::assertTrue($container->offsetExists('monolog'));
        self::assertTrue($container->offsetExists('monolog.formatter'));
        self::assertTrue($container->offsetExists('monolog.default_handler'));
        self::assertTrue($container->offsetExists('monolog.handlers'));
        self::assertTrue($container->offsetExists('monolog.level'));
        self::assertTrue($container->offsetExists('monolog.name'));
        self::assertTrue($container->offsetExists('monolog.bubble'));
        self::assertTrue($container->offsetExists('monolog.permission'));
        self::assertTrue($container->offsetExists('monolog.logfile'));

        self::assertInstanceOf(LoggerInterface::class, $container['logger']);
        self::assertInstanceOf(Logger::class, $container['monolog']);
        self::assertInstanceOf(LineFormatter::class, $container['monolog.formatter']);
        self::assertInstanceOf(StreamHandler::class, $container['monolog.default_handler']);
        self::assertInstanceOf(StreamHandler::class, $container['monolog.handlers'][0]);
        self::assertInternalType('array', $container['monolog.handlers']);
        self::assertSame(LogLevel::DEBUG, $container['monolog.level']);
        self::assertSame('app', $container['monolog.name']);
        self::assertTrue($container['monolog.bubble']);
        self::assertNull($container['monolog.permission']);

        self::assertSame($container['monolog.default_handler'], $container['monolog.handlers'][0]);
    }
}
