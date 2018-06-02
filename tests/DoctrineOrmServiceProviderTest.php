<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\DoctrineCacheServiceProvider;
use Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider;
use Chubbyphp\ServiceProvider\DoctrineOrmServiceProvider;
use Doctrine\DBAL\Types\Type;
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

        $cacheServiceProvider = new DoctrineCacheServiceProvider();
        $cacheServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        self::assertInstanceOf(EntityManager::class, $container['doctrine.orm.em']);
    }

    public function testRegisterWithOneConnection()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $cacheServiceProvider = new DoctrineCacheServiceProvider();
        $cacheServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        $container['doctrine.dbal.db.options'] = [
            'driver' => 'pdo_sqlite',
            'path' => '/tmp/app.db',
        ];

        $container['doctrine.orm.em.options'] = [
            'query_cache' => 'apcu',
            'metadata_cache' => [
                'driver' => 'filesystem',
                'path' => sys_get_temp_dir(),
            ],
            'result_cache' => [
                'driver' => 'memcached',
                'host' => '127.0.0.1',
                'port' => 11211,
            ],
            'hydration_cache' => [
                'driver' => 'redis',
                'host' => '127.0.0.1',
                'port' => 6379,
                'password' => 'password',
            ],
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
            'types' => [
                Type::STRING => \stdClass::class,
                'aotherTyoe' => \stdClass::class,
            ],
        ];

        self::assertInstanceOf(EntityManager::class, $container['doctrine.orm.em']);
    }

    public function testRegisterWithMultipleConnections()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $cacheServiceProvider = new DoctrineCacheServiceProvider();
        $cacheServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        $container['doctrine.dbal.dbs.options'] = [
            'sqlite_read' => [
                'driver' => 'pdo_sqlite',
                'path' => '/tmp/app_read.db',
            ],
            'sqlite_write' => [
                'driver' => 'pdo_sqlite',
                'path' => '/tmp/app_write.db',
            ],
        ];

        $container['doctrine.orm.ems.options'] = [
            'sqlite_read' => [
                'connection' => 'sqlite_read',
                'query_cache' => 'xcache',
                'cache_namespace' => 'prefix-',
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'One\Entities',
                        'alias' => 'One',
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

        self::assertInstanceOf(EntityManager::class, $container['doctrine.orm.em']);
    }
}
