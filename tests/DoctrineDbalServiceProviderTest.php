<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider;
use Chubbyphp\ServiceProvider\Logger\DoctrineDbalLogger;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
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

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        self::assertTrue($container->offsetExists('doctrine.dbal.db'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db.config'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db.default_options'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db.event_manager'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.config'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.event_manager'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.options.initializer'));

        // start: doctrine.dbal.db
        self::assertInstanceOf(Connection::class, $container['doctrine.dbal.db']);

        self::assertSame($container['doctrine.dbal.db'], $container['doctrine.dbal.dbs']['default']);

        /** @var Connection $connection */
        $connection = $container['doctrine.dbal.db'];

        self::assertEquals([
            'charset' => 'utf8mb4',
            'dbname' => null,
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'password' => null,
            'path' => null,
            'port' => 3306,
            'user' => 'root',
        ], $connection->getParams());
        // end: doctrine.dbal.db

        // start: doctrine.dbal.db.config
        self::assertInstanceOf(Configuration::class, $container['doctrine.dbal.db.config']);

        self::assertSame($container['doctrine.dbal.db.config'], $container['doctrine.dbal.dbs.config']['default']);

        /** @var Configuration $configuration */
        $configuration = $container['doctrine.dbal.db.config'];

        self::assertNull($configuration->getSQLLogger());
        self::assertInstanceOf(ArrayCache::class, $configuration->getResultCacheImpl());
        self::assertNull($configuration->getFilterSchemaAssetsExpression());
        self::assertTrue($configuration->getAutoCommit());
        // end: doctrine.dbal.db.config

        // start: doctrine.dbal.db.default_options
        self::assertEquals([
            'configuration' => [
                'auto_commit' => true,
                'cache.result' => 'array',
                'filter_schema_assets_expression' => null,
            ],
            'connection' => [
                'charset' => 'utf8mb4',
                'dbname' => null,
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'password' => null,
                'path' => null,
                'port' => 3306,
                'user' => 'root',
            ],
        ], $container['doctrine.dbal.db.default_options']);
        // end: doctrine.dbal.db.default_options

        // start: doctrine.dbal.db.event_manager
        self::assertInstanceOf(EventManager::class, $container['doctrine.dbal.db.event_manager']);

        self::assertSame(
            $container['doctrine.dbal.db.event_manager'],
            $container['doctrine.dbal.dbs.event_manager']['default']
        );
        // end: doctrine.dbal.db.event_manager

        // start: doctrine.dbal.dbs
        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs']);
        // end: doctrine.dbal.dbs

        // start: doctrine.dbal.dbs.config
        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs.config']);
        // end: doctrine.dbal.dbs.config

        // start: doctrine.dbal.dbs.event_manager
        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs.event_manager']);
        // end: doctrine.dbal.dbs.event_manager

        // start: doctrine.dbal.dbs.options.initializer
        self::assertInstanceOf(\Closure::class, $container['doctrine.dbal.dbs.options.initializer']);
        // end: doctrine.dbal.dbs.options.initializer
    }

    public function testRegisterWithOneConnetion()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        };

        $container['doctrine.dbal.db.options'] = [
            'connection' => [
                'dbname' => 'my_database',
                'host' => 'mysql.someplace.tld',
                'password' => 'my_password',
                'user' => 'my_username',
            ],
        ];

        /** @var Connection $db */
        $db = $container['doctrine.dbal.db'];

        self::assertEquals([
            'charset' => 'utf8mb4',
            'dbname' => 'my_database',
            'driver' => 'pdo_mysql',
            'host' => 'mysql.someplace.tld',
            'password' => 'my_password',
            'path' => null,
            'port' => 3306,
            'user' => 'my_username',
        ], $db->getParams());
    }

    public function testRegisterWithMultipleConnetions()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        };

        $container['doctrine.dbal.dbs.options'] = [
            'mysql_read' => [
                'configuration' => [
                    'auto_commit' => false,
                    'cache.result' => 'apcu',
                    'filter_schema_assets_expression' => 'expression',
                ],
                'connection' => [
                    'dbname' => 'my_database',
                    'host' => 'mysql_read.someplace.tld',
                    'password' => 'my_password',
                    'user' => 'my_username',
                ],
            ],
            'mysql_write' => [
                'configuration' => [
                    'cache.result' => 'apcu',
                ],
                'connection' => [
                    'dbname' => 'my_database',
                    'host' => 'mysql_write.someplace.tld',
                    'password' => 'my_password',
                    'user' => 'my_username',
                ],
            ],
        ];

        self::assertFalse($container['doctrine.dbal.dbs']->offsetExists('default'));
        self::assertTrue($container['doctrine.dbal.dbs']->offsetExists('mysql_read'));
        self::assertTrue($container['doctrine.dbal.dbs']->offsetExists('mysql_write'));

        /** @var Connection $dbRead */
        $dbRead = $container['doctrine.dbal.dbs']['mysql_read'];

        self::assertEquals([
            'charset' => 'utf8mb4',
            'dbname' => 'my_database',
            'driver' => 'pdo_mysql',
            'host' => 'mysql_read.someplace.tld',
            'password' => 'my_password',
            'path' => null,
            'port' => 3306,
            'user' => 'my_username',
        ], $dbRead->getParams());

        /** @var Configuration $configuration */
        $configuration = $container['doctrine.dbal.dbs.config']['mysql_read'];

        self::assertInstanceOf(DoctrineDbalLogger::class, $configuration->getSQLLogger());
        self::assertInstanceOf(ApcuCache::class, $configuration->getResultCacheImpl());
        self::assertSame('expression', $configuration->getFilterSchemaAssetsExpression());
        self::assertFalse($configuration->getAutoCommit());

        /** @var Connection $dbWrite */
        $dbWrite = $container['doctrine.dbal.dbs']['mysql_write'];

        self::assertEquals([
            'charset' => 'utf8mb4',
            'dbname' => 'my_database',
            'driver' => 'pdo_mysql',
            'host' => 'mysql_write.someplace.tld',
            'password' => 'my_password',
            'path' => null,
            'port' => 3306,
            'user' => 'my_username',
        ], $dbWrite->getParams());
    }
}
