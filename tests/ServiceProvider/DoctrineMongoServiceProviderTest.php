<?php

namespace Chubbyphp\Tests\ServiceProvider\ServiceProvider;

use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\ServiceProvider\ServiceProvider\DoctrineMongoServiceProvider;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Configuration;
use Doctrine\MongoDB\Connection;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\ServiceProvider\ServiceProvider\DoctrineMongoServiceProvider
 */
class DoctrineMongoServiceProviderTest extends TestCase
{
    use MockByCallsTrait;

    public function testRegisterWithDefaults()
    {
        $container = new Container();

        $serviceProvider = new DoctrineMongoServiceProvider();
        $serviceProvider->register($container);

        self::assertTrue($container->offsetExists('doctrine.mongo.db.default_options'));
        self::assertTrue($container->offsetExists('doctrine.mongo.dbs.options.initializer'));
        self::assertTrue($container->offsetExists('doctrine.mongo.dbs'));
        self::assertTrue($container->offsetExists('doctrine.mongo.dbs.config'));
        self::assertTrue($container->offsetExists('doctrine.mongo.dbs.event_manager'));
        self::assertTrue($container->offsetExists('doctrine.mongo.db'));
        self::assertTrue($container->offsetExists('doctrine.mongo.db.config'));
        self::assertTrue($container->offsetExists('doctrine.mongo.db.event_manager'));
        self::assertTrue($container->offsetExists('doctrine.mongo.db.logger.batch_insert_threshold'));
        self::assertTrue($container->offsetExists('doctrine.mongo.db.logger.prefix'));

        self::assertEquals([
            'server' => 'mongodb://localhost:27017',
            'options' => [],
        ], $container['doctrine.mongo.db.default_options']);

        self::assertInstanceOf(\Closure::class, $container['doctrine.mongo.dbs.options.initializer']);

        // start: mongodbs
        self::assertInstanceOf(Container::class, $container['doctrine.mongo.dbs']);

        /** @var Container $mongodbs */
        $mongodbs = $container['doctrine.mongo.dbs'];

        self::assertTrue($mongodbs->offsetExists('default'));

        self::assertInstanceOf(Connection::class, $mongodbs['default']);

        // end: mongodbs

        // start: mongodbs.config
        self::assertInstanceOf(Container::class, $container['doctrine.mongo.dbs.config']);

        /** @var Container $mongodbsConfig */
        $mongodbsConfig = $container['doctrine.mongo.dbs.config'];

        self::assertTrue($mongodbsConfig->offsetExists('default'));

        self::assertInstanceOf(Configuration::class, $mongodbsConfig['default']);

        // end: mongodbs.config

        // start: mongodbs.event_manager
        self::assertInstanceOf(Container::class, $container['doctrine.mongo.dbs.event_manager']);

        /** @var Container $mongodbsEventManager */
        $mongodbsEventManager = $container['doctrine.mongo.dbs.event_manager'];

        self::assertTrue($mongodbsEventManager->offsetExists('default'));

        self::assertInstanceOf(EventManager::class, $mongodbsEventManager['default']);
        // end: mongodbs.event_manager

        self::assertInstanceOf(Connection::class, $container['doctrine.mongo.db']);

        self::assertSame('mongodb://localhost:27017', $container['doctrine.mongo.db']->getServer());

        self::assertSame($container['doctrine.mongo.db'], $container['doctrine.mongo.dbs']['default']);

        self::assertInstanceOf(Configuration::class, $container['doctrine.mongo.db.config']);

        self::assertSame($container['doctrine.mongo.db.config'], $container['doctrine.mongo.dbs.config']['default']);

        self::assertInstanceOf(EventManager::class, $container['doctrine.mongo.db.event_manager']);

        self::assertSame($container['doctrine.mongo.db.event_manager'], $container['doctrine.mongo.dbs.event_manager']['default']);

        self::assertSame(10, $container['doctrine.mongo.db.logger.batch_insert_threshold']);
        self::assertSame('MongoDB query: ', $container['doctrine.mongo.db.logger.prefix']);
    }

    public function testRegisterWithOneConnetion()
    {
        $container = new Container();

        $serviceProvider = new DoctrineMongoServiceProvider();
        $serviceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockByCalls(LoggerInterface::class);
        };

        $container['doctrine.mongo.db.options'] = [
            'server' => 'mongodb://localhost:27017',
            'options' => [
                'username' => 'root',
                'password' => 'root',
                'db' => 'admin',
            ],
        ];

        $mongodb = $container['doctrine.mongo.db'];

        self::assertSame('mongodb://localhost:27017', $mongodb->getServer());
    }

    public function testRegisterWithMultipleConnetions()
    {
        $container = new Container();

        $serviceProvider = new DoctrineMongoServiceProvider();
        $serviceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockByCalls(LoggerInterface::class);
        };

        $container['doctrine.mongo.dbs.options'] = [
            'doctrine.mongo.db_read' => [
                'server' => 'mongodb://localhost:27017',
                'options' => [
                    'username' => 'root',
                    'password' => 'root',
                    'db' => 'admin',
                ],
            ],
            'doctrine.mongo.db_write' => [
                'server' => 'mongodb://localhost:27018',
                'options' => [
                    'username' => 'root',
                    'password' => 'root',
                    'db' => 'admin',
                ],
            ],
        ];

        self::assertFalse($container['doctrine.mongo.dbs']->offsetExists('default'));
        self::assertTrue($container['doctrine.mongo.dbs']->offsetExists('doctrine.mongo.db_read'));
        self::assertTrue($container['doctrine.mongo.dbs']->offsetExists('doctrine.mongo.db_write'));

        /** @var Connection $mongodbRead */
        $mongodbRead = $container['doctrine.mongo.dbs']['doctrine.mongo.db_read'];

        /** @var Connection $mongodbWrite */
        $mongodbWrite = $container['doctrine.mongo.dbs']['doctrine.mongo.db_write'];

        self::assertSame('mongodb://localhost:27017', $mongodbRead->getServer());

        self::assertSame('mongodb://localhost:27018', $mongodbWrite->getServer());
    }
}
