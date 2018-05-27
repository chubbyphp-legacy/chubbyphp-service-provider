<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\DoctrineMongoDbServiceProvider;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Configuration;
use Doctrine\MongoDB\Connection;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\ServiceProvider\DoctrineMongoDbServiceProvider
 */
class DoctrineMongoDbServiceProviderTest extends TestCase
{
    public function testRegisterWithDefaults()
    {
        $container = new Container();

        $serviceProvider = new DoctrineMongoDbServiceProvider();
        $serviceProvider->register($container);

        self::assertTrue($container->offsetExists('mongodb.default_options'));
        self::assertTrue($container->offsetExists('mongodbs.options.initializer'));
        self::assertTrue($container->offsetExists('mongodbs'));
        self::assertTrue($container->offsetExists('mongodbs.config'));
        self::assertTrue($container->offsetExists('mongodbs.event_manager'));
        self::assertTrue($container->offsetExists('mongodb'));
        self::assertTrue($container->offsetExists('mongodb.config'));
        self::assertTrue($container->offsetExists('mongodb.event_manager'));
        self::assertTrue($container->offsetExists('mongodb.logger.batch_insert_threshold'));
        self::assertTrue($container->offsetExists('mongodb.logger.prefix'));

        self::assertEquals([
            'server' => 'mongodb://localhost:27017',
            'options' => [],
        ], $container['mongodb.default_options']);

        self::assertInstanceOf(\Closure::class, $container['mongodbs.options.initializer']);

        // start: mongodbs
        self::assertInstanceOf(Container::class, $container['mongodbs']);

        /** @var Container $mongodbs */
        $mongodbs = $container['mongodbs'];

        self::assertTrue($mongodbs->offsetExists('default'));

        self::assertInstanceOf(Connection::class, $mongodbs['default']);

        // end: mongodbs

        // start: mongodbs.config
        self::assertInstanceOf(Container::class, $container['mongodbs.config']);

        /** @var Container $mongodbsConfig */
        $mongodbsConfig = $container['mongodbs.config'];

        self::assertTrue($mongodbsConfig->offsetExists('default'));

        self::assertInstanceOf(Configuration::class, $mongodbsConfig['default']);

        // end: mongodbs.config

        // start: mongodbs.event_manager
        self::assertInstanceOf(Container::class, $container['mongodbs.event_manager']);

        /** @var Container $mongodbsEventManager */
        $mongodbsEventManager = $container['mongodbs.event_manager'];

        self::assertTrue($mongodbsEventManager->offsetExists('default'));

        self::assertInstanceOf(EventManager::class, $mongodbsEventManager['default']);
        // end: mongodbs.event_manager

        self::assertInstanceOf(Connection::class, $container['mongodb']);

        self::assertSame('mongodb://localhost:27017', $container['mongodb']->getServer());

        self::assertSame($container['mongodb'], $container['mongodbs']['default']);

        self::assertInstanceOf(Configuration::class, $container['mongodb.config']);

        self::assertSame($container['mongodb.config'], $container['mongodbs.config']['default']);

        self::assertInstanceOf(EventManager::class, $container['mongodb.event_manager']);

        self::assertSame($container['mongodb.event_manager'], $container['mongodbs.event_manager']['default']);

        self::assertSame(10, $container['mongodb.logger.batch_insert_threshold']);
        self::assertSame('MongoDB query: ', $container['mongodb.logger.prefix']);
    }

    public function testRegisterWithOneConnetion()
    {
        $container = new Container();

        $container['logger'] = function () {
            return $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        };

        $container['mongodb.options'] = [
            'server' => 'mongodb://localhost:27017',
            'options' => [
                'username' => 'root',
                'password' => 'root',
                'db' => 'admin',
            ],
        ];

        $serviceProvider = new DoctrineMongoDbServiceProvider();
        $serviceProvider->register($container);

        $mongodb = $container['mongodb'];

        self::assertSame('mongodb://localhost:27017', $mongodb->getServer());
    }

    public function testRegisterWithMultipleConnetions()
    {
        $container = new Container();

        $container['logger'] = function () {
            return $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        };

        $container['mongodbs.options'] = [
            'mongodb_read' => [
                'server' => 'mongodb://localhost:27017',
                'options' => [
                    'username' => 'root',
                    'password' => 'root',
                    'db' => 'admin',
                ],
            ],
            'mongodb_write' => [
                'server' => 'mongodb://localhost:27018',
                'options' => [
                    'username' => 'root',
                    'password' => 'root',
                    'db' => 'admin',
                ],
            ],
        ];

        $serviceProvider = new DoctrineMongoDbServiceProvider();
        $serviceProvider->register($container);

        self::assertFalse($container['mongodbs']->offsetExists('default'));
        self::assertTrue($container['mongodbs']->offsetExists('mongodb_read'));
        self::assertTrue($container['mongodbs']->offsetExists('mongodb_write'));

        /** @var Connection $mongodbRead */
        $mongodbRead = $container['mongodbs']['mongodb_read'];

        /** @var Connection $mongodbWrite */
        $mongodbWrite = $container['mongodbs']['mongodb_write'];

        self::assertSame('mongodb://localhost:27017', $mongodbRead->getServer());

        self::assertSame('mongodb://localhost:27018', $mongodbWrite->getServer());
    }
}
