<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\Mock\Call;
use Chubbyphp\Mock\MockByCallsTrait;
use Chubbyphp\ServiceProvider\Registry\DoctrineOrmManagerRegistry;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

/**
 * @covers \Chubbyphp\ServiceProvider\Registry\DoctrineOrmManagerRegistry
 */
class DoctrineOrmManagerRegistryTest extends TestCase
{
    use MockByCallsTrait;

    public function testGetDefaultConnectionName()
    {
        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn($this->getMockByCalls(Container::class)),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame('default', $registry->getDefaultConnectionName());
    }

    public function testGetConnection()
    {
        /** @var Connection $connection */
        $connection = $this->getMockByCalls(Connection::class);

        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($connection),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame($connection, $registry->getConnection());
    }

    public function testGetMissingConnection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing connection with name "default".');

        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(false),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);
        $registry->getConnection();
    }

    public function testGetConnections()
    {
        /** @var Connection $connection */
        $connection = $this->getMockByCalls(Connection::class);

        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                    Call::create('offsetGet')->with('default')->willReturn($connection),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        $connections = $registry->getConnections();

        self::assertInternalType('array', $connections);

        self::assertCount(1, $connections);

        self::assertSame($connection, $connections['default']);
    }

    public function testGetConnectionNames()
    {
        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.dbal.dbs')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.dbal.dbs.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame(['default'], $registry->getConnectionNames());
    }

    public function testGetDefaultManagerName()
    {
        /** @var EntityManager $manager */
        $manager = $this->getMockByCalls(EntityManager::class);

        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn($this->getMockByCalls(Container::class)),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame('default', $registry->getDefaultManagerName());
    }

    public function testGetManager()
    {
        /** @var EntityManager $manager */
        $manager = $this->getMockByCalls(EntityManager::class);

        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(true),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame($manager, $registry->getManager());
    }

    public function testGetMissingManager()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing manager with name "default".');

        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('offsetExists')->with('default')->willReturn(false),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);
        $registry->getManager();
    }

    public function testGetManagers()
    {
        /** @var EntityManager $manager */
        $manager = $this->getMockByCalls(EntityManager::class);

        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                    Call::create('offsetGet')->with('default')->willReturn($manager),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        $managers = $registry->getManagers();

        self::assertInternalType('array', $managers);

        self::assertCount(1, $managers);

        self::assertSame($manager, $managers['default']);
    }

    public function testGetManagerNames()
    {
        /** @var Container $container */
        $container = $this->getMockByCalls(Container::class, [
            Call::create('offsetGet')->with('doctrine.orm.ems')->willReturn(
                $this->getMockByCalls(Container::class, [
                    Call::create('keys')->with()->willReturn(['default']),
                ])
            ),
            Call::create('offsetGet')->with('doctrine.orm.ems.default')->willReturn('default'),
        ]);

        $registry = new DoctrineOrmManagerRegistry($container);

        self::assertSame(['default'], $registry->getManagerNames());
    }
}
