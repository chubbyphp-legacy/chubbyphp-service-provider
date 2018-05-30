<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider;
use Chubbyphp\ServiceProvider\DoctrineOrmServiceProvider;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * @covers \Chubbyphp\ServiceProvider\DoctrineOrmServiceProvider
 */
class DoctrineOrmServiceProviderTest extends TestCase
{
    public function testRegisterWithDefaults()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        self::assertInstanceOf(EntityManager::class, $container['orm.em']);
    }

    public function testRegisterWithOneConnection()
    {
        $container = new Container();

        $container['db.options'] = [
            'driver' => 'pdo_sqlite',
            'path' => '/tmp/app.db',
        ];

        $container['orm.em.options'] = [
            'mappings' => [
                [
                    'type' => 'annotation',
                    'namespace' => 'One\Entities',
                    'path' => __DIR__.'/src/One/Entities',
                ],
                [
                    'type' => 'yml',
                    'namespace' => 'Two\Entities',
                    'path' => __DIR__.'/src/Two/Resources/config/doctrine',
                ],
                [
                    'type' => 'simple_yml',
                    'namespace' => 'Three\Entities',
                    'path' => __DIR__.'/src/Three/Resources/config/doctrine',
                ],
                [
                    'type' => 'xml',
                    'namespace' => 'Four\Entities',
                    'path' => __DIR__.'/src/Four/Resources/config/doctrine',
                ],
                [
                    'type' => 'simple_xml',
                    'namespace' => 'Five\Entities',
                    'path' => __DIR__.'/src/Five/Resources/config/doctrine',
                ],
                [
                    'type' => 'php',
                    'namespace' => 'Six\Entities',
                    'path' => __DIR__.'/src/Six/Entities',
                ],
            ],
        ];

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        self::assertInstanceOf(EntityManager::class, $container['orm.em']);
    }

    public function testRegisterWithMultipleConnections()
    {
        $container = new Container();

        $container['dbs.options'] = [
            'sqlite_read' => [
                'driver' => 'pdo_sqlite',
                'path' => '/tmp/app_read.db',
            ],
            'sqlite_write' => [
                'driver' => 'pdo_sqlite',
                'path' => '/tmp/app_write.db',
            ],
        ];

        $container['orm.ems.options'] = [
            'sqlite_read' => [
                'connection' => 'sqlite_read',
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'One\Entities',
                        'path' => __DIR__.'/src/One/Entities',
                    ],
                    [
                        'type' => 'yml',
                        'namespace' => 'Two\Entities',
                        'path' => __DIR__.'/src/Two/Resources/config/doctrine',
                    ],
                    [
                        'type' => 'simple_yml',
                        'namespace' => 'Three\Entities',
                        'path' => __DIR__.'/src/Three/Resources/config/doctrine',
                    ],
                    [
                        'type' => 'xml',
                        'namespace' => 'Four\Entities',
                        'path' => __DIR__.'/src/Four/Resources/config/doctrine',
                    ],
                    [
                        'type' => 'simple_xml',
                        'namespace' => 'Five\Entities',
                        'path' => __DIR__.'/src/Five/Resources/config/doctrine',
                    ],
                    [
                        'type' => 'php',
                        'namespace' => 'Six\Entities',
                        'path' => __DIR__.'/src/Six/Entities',
                    ],
                ],
            ],
            'sqlite_write' => [
                'connection' => 'sqlite_read',
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'One\Entities',
                        'path' => __DIR__.'/src/One/Entities',
                    ],
                    [
                        'type' => 'yml',
                        'namespace' => 'Two\Entities',
                        'path' => __DIR__.'/src/Two/Resources/config/doctrine',
                    ],
                    [
                        'type' => 'simple_yml',
                        'namespace' => 'Three\Entities',
                        'path' => __DIR__.'/src/Three/Resources/config/doctrine',
                    ],
                    [
                        'type' => 'xml',
                        'namespace' => 'Four\Entities',
                        'path' => __DIR__.'/src/Four/Resources/config/doctrine',
                    ],
                    [
                        'type' => 'simple_xml',
                        'namespace' => 'Five\Entities',
                        'path' => __DIR__.'/src/Five/Resources/config/doctrine',
                    ],
                    [
                        'type' => 'php',
                        'namespace' => 'Six\Entities',
                        'path' => __DIR__.'/src/Six/Entities',
                    ],
                ],
            ],
        ];

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        self::assertInstanceOf(EntityManager::class, $container['orm.em']);
    }
}
