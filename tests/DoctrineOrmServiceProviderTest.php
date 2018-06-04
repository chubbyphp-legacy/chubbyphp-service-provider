<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\DoctrineCacheServiceProvider;
use Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider;
use Chubbyphp\ServiceProvider\DoctrineOrmServiceProvider;
use Chubbyphp\ServiceProvider\Registry\DoctrineOrmManagerRegistry;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
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

        self::assertArrayHasKey('doctrine.orm.em.default_options', $container);
        self::assertArrayHasKey('doctrine.orm.ems.options.initializer', $container);
        self::assertArrayHasKey('doctrine.orm.ems', $container);
        self::assertArrayHasKey('doctrine.orm.ems.config', $container);
        self::assertArrayHasKey('doctrine.orm.proxies_dir', $container);
        self::assertArrayHasKey('doctrine.orm.auto_generate_proxies', $container);
        self::assertArrayHasKey('doctrine.orm.proxies_namespace', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver_chain', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver_chain.factory', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.annotation', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.yml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.simple_yml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.xml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.simple_xml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.php', $container);
        self::assertArrayHasKey('doctrine.orm.default_cache', $container);
        self::assertArrayHasKey('doctrine.orm.custom.functions.string', $container);
        self::assertArrayHasKey('doctrine.orm.custom.functions.numeric', $container);
        self::assertArrayHasKey('doctrine.orm.custom.functions.datetime', $container);
        self::assertArrayHasKey('doctrine.orm.custom.hydration_modes', $container);
        self::assertArrayHasKey('doctrine.orm.class_metadata_factory_name', $container);
        self::assertArrayHasKey('doctrine.orm.default_repository_class', $container);
        self::assertArrayHasKey('doctrine.orm.strategy.naming', $container);
        self::assertArrayHasKey('doctrine.orm.strategy.quote', $container);
        self::assertArrayHasKey('doctrine.orm.entity_listener_resolver', $container);
        self::assertArrayHasKey('doctrine.orm.repository_factory', $container);
        self::assertArrayHasKey('doctrine.orm.second_level_cache.enabled', $container);
        self::assertArrayHasKey('doctrine.orm.second_level_cache.configuration', $container);
        self::assertArrayHasKey('doctrine.orm.default.query_hints', $container);
        self::assertArrayHasKey('doctrine.orm.em', $container);
        self::assertArrayHasKey('doctrine.orm.em.config', $container);
        self::assertArrayHasKey('doctrine.orm.manager_registry', $container);

        self::assertEquals([
            'connection' => 'default',
            'mappings' => [],
            'types' => [],
        ], $container['doctrine.orm.em.default_options']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.ems.options.initializer']);
        self::assertInstanceOf(Container::class, $container['doctrine.orm.ems']);
        self::assertInstanceOf(Container::class, $container['doctrine.orm.ems.config']);
        self::assertSame(sys_get_temp_dir(), $container['doctrine.orm.proxies_dir']);
        self::assertTrue($container['doctrine.orm.auto_generate_proxies']);
        self::assertSame('DoctrineProxy', $container['doctrine.orm.proxies_namespace']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver_chain']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver_chain.factory']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.annotation']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.yml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.simple_yml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.xml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.simple_xml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.php']);
        self::assertEquals(['driver' => 'array'], $container['doctrine.orm.default_cache']);
        self::assertEquals([], $container['doctrine.orm.custom.functions.string']);
        self::assertEquals([], $container['doctrine.orm.custom.functions.numeric']);
        self::assertEquals([], $container['doctrine.orm.custom.functions.datetime']);
        self::assertEquals([], $container['doctrine.orm.custom.hydration_modes']);
        self::assertSame(ClassMetadataFactory::class, $container['doctrine.orm.class_metadata_factory_name']);
        self::assertSame(EntityRepository::class, $container['doctrine.orm.default_repository_class']);
        self::assertInstanceOf(DefaultNamingStrategy::class, $container['doctrine.orm.strategy.naming']);
        self::assertInstanceOf(DefaultQuoteStrategy::class, $container['doctrine.orm.strategy.quote']);
        self::assertInstanceOf(DefaultEntityListenerResolver::class, $container['doctrine.orm.entity_listener_resolver']);
        self::assertInstanceOf(DefaultRepositoryFactory::class, $container['doctrine.orm.repository_factory']);
        self::assertFalse($container['doctrine.orm.second_level_cache.enabled']);
        self::assertInstanceOf(CacheConfiguration::class, $container['doctrine.orm.second_level_cache.configuration']);
        self::assertEquals([], $container['doctrine.orm.default.query_hints']);
        self::assertInstanceOf(EntityManager::class, $container['doctrine.orm.em']);
        self::assertInstanceOf(Configuration::class, $container['doctrine.orm.em.config']);
        self::assertInstanceOf(DoctrineOrmManagerRegistry::class, $container['doctrine.orm.manager_registry']);

        /** @var EntityManager $entityManager */
        $entityManager = $container['doctrine.orm.em'];

        /** @var Configuration $configuration */
        $configuration = $container['doctrine.orm.em.config'];

        self::assertSame($configuration, $entityManager->getConfiguration());

        self::assertNull($configuration->getSQLLogger());
        self::assertInstanceOf(ArrayCache::class, $configuration->getResultCacheImpl());
        self::assertTrue($configuration->getAutoCommit());
        self::assertSame(sys_get_temp_dir(), $configuration->getProxyDir());
        self::assertSame(1, $configuration->getAutoGenerateProxyClasses());
        self::assertSame('DoctrineProxy', $configuration->getProxyNamespace());
        //self::assertSame('', $configuration->getEntityNamespace('alias'));
        //self::assertSame([], $configuration->getEntityNamespaces());
        self::assertInstanceOf(MappingDriverChain::class, $configuration->getMetadataDriverImpl());
        self::assertInstanceOf(ArrayCache::class, $configuration->getQueryCacheImpl());
        self::assertInstanceOf(ArrayCache::class, $configuration->getHydrationCacheImpl());
        self::assertInstanceOf(ArrayCache::class, $configuration->getMetadataCacheImpl());
        //self::assertSame('', $configuration->getNamedQuery('name'));
        //self::assertSame('', $configuration->getNamedNativeQuery('name'));
        //self::assertSame('', $configuration->getCustomStringFunction('name'));
        //self::assertSame('', $configuration->getCustomNumericFunction('name'));
        //self::assertSame('', $configuration->getCustomDatetimeFunction('name'));
        //self::assertSame('', $configuration->getCustomHydrationMode('name'));
        self::assertSame(ClassMetadataFactory::class, $configuration->getClassMetadataFactoryName());
        //self::assertSame('', $configuration->getFilterClassName('name'));
        self::assertSame(EntityRepository::class, $configuration->getDefaultRepositoryClassName());
        self::assertInstanceOf(DefaultNamingStrategy::class, $configuration->getNamingStrategy());
        self::assertInstanceOf(DefaultQuoteStrategy::class, $configuration->getQuoteStrategy());
        self::assertInstanceOf(DefaultEntityListenerResolver::class, $configuration->getEntityListenerResolver());
        self::assertInstanceOf(DefaultRepositoryFactory::class, $configuration->getRepositoryFactory());
        self::assertFalse($configuration->isSecondLevelCacheEnabled());
        self::assertInstanceOf(CacheConfiguration::class, $configuration->getSecondLevelCacheConfiguration());
        //self::assertSame('', $configuration->getDefaultQueryHint('name'));

        self::assertSame($container['doctrine.orm.class_metadata_factory_name'], $configuration->getClassMetadataFactoryName());
        self::assertSame($container['doctrine.orm.default_repository_class'], $configuration->getDefaultRepositoryClassName());
        self::assertSame($container['doctrine.orm.strategy.naming'], $configuration->getNamingStrategy());
        self::assertSame($container['doctrine.orm.strategy.quote'], $configuration->getQuoteStrategy());
        self::assertSame($container['doctrine.orm.entity_listener_resolver'], $configuration->getEntityListenerResolver());
        self::assertSame($container['doctrine.orm.repository_factory'], $configuration->getRepositoryFactory());
        self::assertSame($container['doctrine.orm.second_level_cache.configuration'], $configuration->getSecondLevelCacheConfiguration());
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
            'driver' => 'pdo_mysql',
            'host' => 'mysql_read.someplace.tld',
            'dbname' => 'my_database',
            'user' => 'my_username',
            'password' => 'my_password',
            'charset' => 'utf8mb4',
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

        $container['doctrine.orm.ems.options'] = [
            'mysql_read' => [
                'connection' => 'mysql_read',
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
            'mysql_write' => [
                'connection' => 'mysql_read',
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

    public function testRegisterWithInvalidMappingStructure()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The "doctrine.orm.em.options" option "mappings" should be an array of arrays.');

        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $cacheServiceProvider = new DoctrineCacheServiceProvider();
        $cacheServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        $container['doctrine.orm.em.options'] = [
            'mappings' => [
                'invalid_mapping',
            ],
        ];

        $container['doctrine.orm.em'];
    }

    public function testRegisterWithInvalidMappingType()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('There is no driver factory for type "unknown"');

        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $cacheServiceProvider = new DoctrineCacheServiceProvider();
        $cacheServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        $container['doctrine.orm.em.options'] = [
            'mappings' => [
                [
                    'type' => 'unknown',
                ],
            ],
        ];

        $container['doctrine.orm.em'];
    }
}
