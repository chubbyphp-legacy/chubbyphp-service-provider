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

        self::assertTrue($container->offsetExists('doctrine.dbal.db.default_options'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.options.initializer'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.config'));
        self::assertTrue($container->offsetExists('doctrine.dbal.dbs.event_manager'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db.config'));
        self::assertTrue($container->offsetExists('doctrine.dbal.db.event_manager'));

        self::assertEquals([
            'connection' => [
                'driver' => 'pdo_mysql',
                'dbname' => null,
                'host' => 'localhost',
                'user' => 'root',
                'password' => null,
            ],
            'configuration' => [
                'result_cache' => null,
                'filter_schema_assets_expression' => null,
                'auto_commit' => true,
            ],
        ], $container['doctrine.dbal.db.default_options']);

        self::assertInstanceOf(\Closure::class, $container['doctrine.dbal.dbs.options.initializer']);

        // start: dbs
        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs']);

        /** @var Container $dbs */
        $dbs = $container['doctrine.dbal.dbs'];

        self::assertTrue($dbs->offsetExists('default'));

        self::assertInstanceOf(Connection::class, $dbs['default']);

        // end: dbs

        // start: dbs.config
        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs.config']);

        /** @var Container $dbsConfig */
        $dbsConfig = $container['doctrine.dbal.dbs.config'];

        self::assertTrue($dbsConfig->offsetExists('default'));

        self::assertInstanceOf(Configuration::class, $dbsConfig['default']);

        // end: dbs.config

        // start: dbs.event_manager
        self::assertInstanceOf(Container::class, $container['doctrine.dbal.dbs.event_manager']);

        /** @var Container $dbsEventManager */
        $dbsEventManager = $container['doctrine.dbal.dbs.event_manager'];

        self::assertTrue($dbsEventManager->offsetExists('default'));

        self::assertInstanceOf(EventManager::class, $dbsEventManager['default']);
        // end: dbs.event_manager

        self::assertInstanceOf(Connection::class, $container['doctrine.dbal.db']);

        self::assertEquals([
            'driver' => 'pdo_mysql',
            'dbname' => null,
            'host' => 'localhost',
            'user' => 'root',
            'password' => null,
        ], $container['doctrine.dbal.db']->getParams());

        self::assertSame($container['doctrine.dbal.db'], $container['doctrine.dbal.dbs']['default']);

        self::assertInstanceOf(Configuration::class, $container['doctrine.dbal.db.config']);

        /** @var Configuration $configuration */
        $configuration = $container['doctrine.dbal.db.config'];

        self::assertNull($configuration->getSQLLogger());
        self::assertNull($configuration->getResultCacheImpl());
        self::assertNull($configuration->getFilterSchemaAssetsExpression());
        self::assertTrue($configuration->getAutoCommit());

        self::assertSame($container['doctrine.dbal.db.config'], $container['doctrine.dbal.dbs.config']['default']);

        self::assertInstanceOf(EventManager::class, $container['doctrine.dbal.db.event_manager']);

        self::assertSame($container['doctrine.dbal.db.event_manager'], $container['doctrine.dbal.dbs.event_manager']['default']);
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
                'driver' => 'pdo_mysql',
                'host' => 'mysql_read.someplace.tld',
                'dbname' => 'my_database',
                'user' => 'my_username',
                'password' => 'my_password',
                'charset' => 'utf8mb4',
            ],
            'configuration' => [
                'result_cache' => 'array',
            ],
        ];

        /** @var Connection $db */
        $db = $container['doctrine.dbal.db'];

        self::assertEquals([
            'driver' => 'pdo_mysql',
            'host' => 'mysql_read.someplace.tld',
            'dbname' => 'my_database',
            'user' => 'my_username',
            'password' => 'my_password',
            'charset' => 'utf8mb4',
        ], $db->getParams());

        /** @var Configuration $configuration */
        $configuration = $container['doctrine.dbal.db.config'];

        self::assertInstanceOf(DoctrineDbalLogger::class, $configuration->getSQLLogger());
        self::assertInstanceOf(ArrayCache::class, $configuration->getResultCacheImpl());
        self::assertNull($configuration->getFilterSchemaAssetsExpression());
        self::assertTrue($configuration->getAutoCommit());
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
                'connection' => [
                    'driver' => 'pdo_mysql',
                    'host' => 'mysql_read.someplace.tld',
                    'dbname' => 'my_database',
                    'user' => 'my_username',
                    'password' => 'my_password',
                    'charset' => 'utf8mb4',
                ],
                'configuration' => [
                    'result_cache' => 'apcu',
                    'filter_schema_assets_expression' => 'expression',
                    'auto_commit' => false,
                ],
            ],
            'mysql_write' => [
                'connection' => [
                    'driver' => 'pdo_mysql',
                    'host' => 'mysql_write.someplace.tld',
                    'dbname' => 'my_database',
                    'user' => 'my_username',
                    'password' => 'my_password',
                    'charset' => 'utf8mb4',
                ],
                'configuration' => [
                     'result_cache' => 'apcu',
                ],
            ],
        ];

        self::assertFalse($container['doctrine.dbal.dbs']->offsetExists('default'));
        self::assertTrue($container['doctrine.dbal.dbs']->offsetExists('mysql_read'));
        self::assertTrue($container['doctrine.dbal.dbs']->offsetExists('mysql_write'));

        /** @var Connection $dbRead */
        $dbRead = $container['doctrine.dbal.dbs']['mysql_read'];

        /** @var Connection $dbWrite */
        $dbWrite = $container['doctrine.dbal.dbs']['mysql_write'];

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

        /** @var Configuration $configuration */
        $configuration = $container['doctrine.dbal.dbs.config']['mysql_read'];

        self::assertInstanceOf(DoctrineDbalLogger::class, $configuration->getSQLLogger());
        self::assertInstanceOf(ApcuCache::class, $configuration->getResultCacheImpl());
        self::assertSame('expression', $configuration->getFilterSchemaAssetsExpression());
        self::assertFalse($configuration->getAutoCommit());
    }
}
