<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider
 */
class DoctrineDbalServiceProviderTest extends TestCase
{
    public function testRegisterWithDefaults()
    {
        $container = new Container();

        $serviceProvider = new DoctrineDbalServiceProvider();
        $serviceProvider->register($container);

        self::assertTrue($container->offsetExists('db.default_options'));
        self::assertTrue($container->offsetExists('dbs.options.initializer'));
        self::assertTrue($container->offsetExists('dbs'));
        self::assertTrue($container->offsetExists('dbs.config'));
        self::assertTrue($container->offsetExists('dbs.event_manager'));
        self::assertTrue($container->offsetExists('db'));
        self::assertTrue($container->offsetExists('db.config'));
        self::assertTrue($container->offsetExists('db.event_manager'));

        self::assertEquals([
            'driver' => 'pdo_mysql',
            'dbname' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
        ], $container['db.default_options']);

        self::assertInstanceOf(\Closure::class, $container['dbs.options.initializer']);

        // start: dbs
        self::assertInstanceOf(Container::class, $container['dbs']);

        /** @var Container $dbs */
        $dbs = $container['dbs'];

        self::assertTrue($dbs->offsetExists('default'));

        self::assertInstanceOf(Connection::class, $dbs['default']);

        // end: dbs

        // start: dbs.config
        self::assertInstanceOf(Container::class, $container['dbs.config']);

        /** @var Container $dbsConfig */
        $dbsConfig = $container['dbs.config'];

        self::assertTrue($dbsConfig->offsetExists('default'));

        self::assertInstanceOf(Configuration::class, $dbsConfig['default']);

        // end: dbs.config

        // start: dbs.event_manager
        self::assertInstanceOf(Container::class, $container['dbs.event_manager']);

        /** @var Container $dbsEventManager */
        $dbsEventManager = $container['dbs.event_manager'];

        self::assertTrue($dbsEventManager->offsetExists('default'));

        self::assertInstanceOf(EventManager::class, $dbsEventManager['default']);
        // end: dbs.event_manager

        self::assertInstanceOf(Connection::class, $container['db']);

        self::assertEquals([
            'driver' => 'pdo_mysql',
            'dbname' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
        ], $container['db']->getParams());

        self::assertSame($container['db'], $container['dbs']['default']);

        self::assertInstanceOf(Configuration::class, $container['db.config']);

        self::assertSame($container['db.config'], $container['dbs.config']['default']);

        self::assertInstanceOf(EventManager::class, $container['db.event_manager']);

        self::assertSame($container['db.event_manager'], $container['dbs.event_manager']['default']);
    }

    public function testRegisterWithOneConnetion()
    {
        $container = new Container();

        $container['logger'] = function () {
            return $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        };

        $container['db.options'] = [
            'driver' => 'pdo_sqlite',
            'path' => '/tmp/app.db',
        ];

        $serviceProvider = new DoctrineDbalServiceProvider();
        $serviceProvider->register($container);

        $db = $container['db'];

        self::assertEquals([
            'driver' => 'pdo_sqlite',
            'dbname' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
            'path' => '/tmp/app.db',
        ], $db->getParams());
    }

    public function testRegisterWithMultipleConnetions()
    {
        $container = new Container();

        $container['logger'] = function () {
            return $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        };

        $container['dbs.options'] = [
            'mysql_read' => [
                'driver' => 'pdo_mysql',
                'host' => 'mysql_read.someplace.tld',
                'dbname' => 'my_database',
                'user' => 'my_username',
                'password' => 'my_password',
                'charset' => 'utf8mb4',
            ],
            'mysql_write' => [
                'driver' => 'pdo_mysql',
                'host' => 'mysql_write.someplace.tld',
                'dbname' => 'my_database',
                'user' => 'my_username',
                'password' => 'my_password',
                'charset' => 'utf8mb4',
            ],
        ];

        $serviceProvider = new DoctrineDbalServiceProvider();
        $serviceProvider->register($container);

        self::assertFalse($container['dbs']->offsetExists('default'));
        self::assertTrue($container['dbs']->offsetExists('mysql_read'));
        self::assertTrue($container['dbs']->offsetExists('mysql_write'));

        /** @var Connection $dbRead */
        $dbRead = $container['dbs']['mysql_read'];

        /** @var Connection $dbWrite */
        $dbWrite = $container['dbs']['mysql_write'];

        self::assertEquals([
            'driver' => 'pdo_mysql',
            'host' => 'mysql_read.someplace.tld',
            'dbname' => 'my_database',
            'user' => 'my_username',
            'password' => 'my_password',
            'charset' => 'utf8mb4',
        ], $dbRead->getParams());

        self::assertEquals([
            'driver' => 'pdo_mysql',
            'host' => 'mysql_write.someplace.tld',
            'dbname' => 'my_database',
            'user' => 'my_username',
            'password' => 'my_password',
            'charset' => 'utf8mb4',
        ], $dbWrite->getParams());
    }
}
