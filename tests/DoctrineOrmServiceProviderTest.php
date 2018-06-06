<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\DoctrineCacheServiceProvider;
use Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider;
use Chubbyphp\ServiceProvider\DoctrineOrmServiceProvider;
use Chubbyphp\ServiceProvider\Registry\DoctrineOrmManagerRegistry;
use Chubbyphp\Tests\ServiceProvider\Resources\One\Entity\Model as OneModel;
use Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity\Model as TwoModel;
use Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity\Model as ThreeModel;
use Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity\Model as FourModel;
use Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity\Model as FiveModel;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Repository\RepositoryFactory;
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
        self::assertArrayHasKey('doctrine.orm.default_cache.provider', $container);
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
        self::assertEquals(['driver' => 'array'], $container['doctrine.orm.default_cache.provider']);
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
        self::assertInstanceOf(MappingDriverChain::class, $configuration->getMetadataDriverImpl());
        self::assertInstanceOf(ArrayCache::class, $configuration->getQueryCacheImpl());
        self::assertInstanceOf(ArrayCache::class, $configuration->getHydrationCacheImpl());
        self::assertInstanceOf(ArrayCache::class, $configuration->getMetadataCacheImpl());
        self::assertSame(ClassMetadataFactory::class, $configuration->getClassMetadataFactoryName());
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
                    'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\One\Entity',
                    'alias' => 'Alias\Entity',
                    'path' => __DIR__.'/Resources/One/Entity',
                    'use_simple_annotation_reader' => false,
                ],
                [
                    'type' => 'yml',
                    'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity',
                    'path' => __DIR__.'/Resources/Two/config/yml',
                ],
                [
                    'type' => 'simple_yml',
                    'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity',
                    'path' => __DIR__.'/Resources/Three/config/simple_yml',
                ],
                [
                    'type' => 'xml',
                    'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity',
                    'path' => __DIR__.'/Resources/Four/config/xml',
                ],
                [
                    'type' => 'simple_xml',
                    'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity',
                    'path' => __DIR__.'/Resources/Five/config/simple_xml',
                ],
                [
                    'type' => 'php',
                    'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Six\Entity',
                    'path' => __DIR__.'/Resources/Six/Entity',
                ],
            ],
            'types' => [
                Type::STRING => \stdClass::class,
                'anotherType' => \stdClass::class,
            ],
        ];

        $container['doctrine.orm.proxies_dir'] = '/another/proxy/dir';
        $container['doctrine.orm.auto_generate_proxies'] = false;
        $container['doctrine.orm.proxies_namespace'] = 'AnotherNamespace';
        $container['doctrine.orm.custom.functions.string'] = ['string' => \stdClass::class];
        $container['doctrine.orm.custom.functions.numeric'] = ['numeric' => \stdClass::class];
        $container['doctrine.orm.custom.functions.datetime'] = ['datetime' => \stdClass::class];
        $container['doctrine.orm.custom.hydration_modes'] = ['mode' => \stdClass::class];
        $container['doctrine.orm.class_metadata_factory_name'] = function () {
            return $this->getMockBuilder(ClassMetadataFactory::class)
                ->disableOriginalConstructor()
                ->getMock();
        };
        $container['doctrine.orm.default_repository_class'] = function () {
            return $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        };
        $container['doctrine.orm.strategy.naming'] = function () {
            return $this->getMockBuilder(NamingStrategy::class)->getMockForAbstractClass();
        };
        $container['doctrine.orm.strategy.quote'] = function () {
            return $this->getMockBuilder(QuoteStrategy::class)->getMockForAbstractClass();
        };
        $container['doctrine.orm.entity_listener_resolver'] = function () {
            return $this->getMockBuilder(EntityListenerResolver::class)->getMockForAbstractClass();
        };
        $container['doctrine.orm.repository_factory'] = function () {
            return $this->getMockBuilder(RepositoryFactory::class)->getMockForAbstractClass();
        };
        $container['doctrine.orm.second_level_cache.enabled'] = true;
        $container['doctrine.orm.default.query_hints'] = ['name' => \stdClass::class];

        /** @var EntityManager $entityManager */
        $entityManager = $container['doctrine.orm.em'];

        /** @var Configuration $configuration */
        $configuration = $container['doctrine.orm.em.config'];

        self::assertSame($configuration, $entityManager->getConfiguration());

        self::assertNull($configuration->getSQLLogger());
        self::assertInstanceOf(MemcachedCache::class, $configuration->getResultCacheImpl());
        self::assertTrue($configuration->getAutoCommit());
        self::assertSame('/another/proxy/dir', $configuration->getProxyDir());
        self::assertSame(0, $configuration->getAutoGenerateProxyClasses());
        self::assertSame('AnotherNamespace', $configuration->getProxyNamespace());
        self::assertSame('Chubbyphp\Tests\ServiceProvider\Resources\One\Entity', $configuration->getEntityNamespace('Alias\Entity'));
        self::assertSame(['Alias\Entity' => 'Chubbyphp\Tests\ServiceProvider\Resources\One\Entity'], $configuration->getEntityNamespaces());
        self::assertInstanceOf(MappingDriverChain::class, $configuration->getMetadataDriverImpl());
        self::assertInstanceOf(ApcuCache::class, $configuration->getQueryCacheImpl());
        self::assertInstanceOf(RedisCache::class, $configuration->getHydrationCacheImpl());
        self::assertInstanceOf(FilesystemCache::class, $configuration->getMetadataCacheImpl());
        self::assertSame(\stdClass::class, $configuration->getCustomStringFunction('string'));
        self::assertSame(\stdClass::class, $configuration->getCustomNumericFunction('numeric'));
        self::assertSame(\stdClass::class, $configuration->getCustomDatetimeFunction('datetime'));
        self::assertSame(\stdClass::class, $configuration->getCustomHydrationMode('mode'));
        self::assertInstanceOf(ClassMetadataFactory::class, $configuration->getClassMetadataFactoryName());
        self::assertInstanceOf(EntityRepository::class, $configuration->getDefaultRepositoryClassName());
        self::assertInstanceOf(NamingStrategy::class, $configuration->getNamingStrategy());
        self::assertInstanceOf(QuoteStrategy::class, $configuration->getQuoteStrategy());
        self::assertInstanceOf(EntityListenerResolver::class, $configuration->getEntityListenerResolver());
        self::assertInstanceOf(RepositoryFactory::class, $configuration->getRepositoryFactory());
        self::assertTrue($configuration->isSecondLevelCacheEnabled());
        self::assertInstanceOf(CacheConfiguration::class, $configuration->getSecondLevelCacheConfiguration());
        self::assertSame($container['doctrine.orm.class_metadata_factory_name'], $configuration->getClassMetadataFactoryName());
        self::assertSame($container['doctrine.orm.default_repository_class'], $configuration->getDefaultRepositoryClassName());
        self::assertSame($container['doctrine.orm.strategy.naming'], $configuration->getNamingStrategy());
        self::assertSame($container['doctrine.orm.strategy.quote'], $configuration->getQuoteStrategy());
        self::assertSame($container['doctrine.orm.entity_listener_resolver'], $configuration->getEntityListenerResolver());
        self::assertSame($container['doctrine.orm.repository_factory'], $configuration->getRepositoryFactory());
        self::assertSame($container['doctrine.orm.second_level_cache.configuration'], $configuration->getSecondLevelCacheConfiguration());

        /** @var MappingDriverChain $metadataDriver */
        $metadataDriver = $configuration->getMetadataDriverImpl();

        $drivers = $metadataDriver->getDrivers();

        self::assertCount(6, $drivers);

        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\One\Entity', $drivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity', $drivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity', $drivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity', $drivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity', $drivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Six\Entity', $drivers);

        self::assertInstanceOf(AnnotationDriver::class, $drivers['Chubbyphp\Tests\ServiceProvider\Resources\One\Entity']);
        self::assertInstanceOf(YamlDriver::class, $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity']);
        self::assertInstanceOf(SimplifiedYamlDriver::class, $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity']);
        self::assertInstanceOf(XmlDriver::class, $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity']);
        self::assertInstanceOf(SimplifiedXmlDriver::class, $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity']);
        self::assertInstanceOf(StaticPHPDriver::class, $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Six\Entity']);

        /** @var AnnotationDriver $oneDriver */
        $oneDriver = $drivers['Chubbyphp\Tests\ServiceProvider\Resources\One\Entity'];

        self::assertSame([__DIR__.'/Resources/One/Entity'], $oneDriver->getPaths());
        self::assertSame('.php', $oneDriver->getFileExtension());
        self::assertSame([OneModel::class], $oneDriver->getAllClassNames());

        /** @var YamlDriver $twoDriver */
        $twoDriver = $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity'];

        self::assertSame([TwoModel::class], $twoDriver->getAllClassNames());

        /** @var SimplifiedYamlDriver $threeDriver */
        $threeDriver = $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity'];

        self::assertSame([ThreeModel::class], $threeDriver->getAllClassNames());

        /** @var XmlDriver $fourDriver */
        $fourDriver = $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity'];

        self::assertSame([FourModel::class], $fourDriver->getAllClassNames());

        /** @var SimplifiedXmlDriver $fiveDriver */
        $fiveDriver = $drivers['Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity'];

        self::assertSame([FiveModel::class], $fiveDriver->getAllClassNames());
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
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\One\Entity',
                        'alias' => 'Alias\Entity',
                        'path' => __DIR__.'/Resources/One/Entity',
                        'use_simple_annotation_reader' => false,
                    ],
                    [
                        'type' => 'yml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity',
                        'path' => __DIR__.'/Resources/Two/config/yml',
                    ],
                    [
                        'type' => 'simple_yml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity',
                        'path' => __DIR__.'/Resources/Three/config/simple_yml',
                    ],
                    [
                        'type' => 'xml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity',
                        'path' => __DIR__.'/Resources/Four/config/xml',
                    ],
                    [
                        'type' => 'simple_xml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity',
                        'path' => __DIR__.'/Resources/Five/config/simple_xml',
                    ],
                    [
                        'type' => 'php',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Six\Entity',
                        'path' => __DIR__.'/Resources/Six/Entity',
                    ],
                ],
            ],
            'mysql_write' => [
                'connection' => 'mysql_read',
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\One\Entity',
                        'alias' => 'Alias\Entity',
                        'path' => __DIR__.'/Resources/One/Entity',
                        'use_simple_annotation_reader' => false,
                    ],
                    [
                        'type' => 'yml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity',
                        'path' => __DIR__.'/Resources/Two/config/yml',
                    ],
                    [
                        'type' => 'simple_yml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity',
                        'path' => __DIR__.'/Resources/Three/config/simple_yml',
                    ],
                    [
                        'type' => 'xml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity',
                        'path' => __DIR__.'/Resources/Four/config/xml',
                    ],
                    [
                        'type' => 'simple_xml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity',
                        'path' => __DIR__.'/Resources/Five/config/simple_xml',
                    ],
                ],
            ],
        ];

        /** @var EntityManager $readEntityManager */
        $readEntityManager = $container['doctrine.orm.ems']['mysql_read'];

        /** @var Configuration $readConfiguration */
        $readConfiguration = $container['doctrine.orm.ems.config']['mysql_read'];

        self::assertSame($readConfiguration, $readEntityManager->getConfiguration());

        /** @var MappingDriverChain $readMetadataDriver */
        $readMetadataDriver = $readConfiguration->getMetadataDriverImpl();

        $readDrivers = $readMetadataDriver->getDrivers();

        self::assertCount(6, $readDrivers);

        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\One\Entity', $readDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity', $readDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity', $readDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity', $readDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity', $readDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Six\Entity', $readDrivers);

        self::assertInstanceOf(AnnotationDriver::class, $readDrivers['Chubbyphp\Tests\ServiceProvider\Resources\One\Entity']);
        self::assertInstanceOf(YamlDriver::class, $readDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity']);
        self::assertInstanceOf(SimplifiedYamlDriver::class, $readDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity']);
        self::assertInstanceOf(XmlDriver::class, $readDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity']);
        self::assertInstanceOf(SimplifiedXmlDriver::class, $readDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity']);
        self::assertInstanceOf(StaticPHPDriver::class, $readDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Six\Entity']);

        /** @var EntityManager $writeEntityManager */
        $writeEntityManager = $container['doctrine.orm.ems']['mysql_write'];

        /** @var Configuration $writeConfiguration */
        $writeConfiguration = $container['doctrine.orm.ems.config']['mysql_write'];

        self::assertSame($writeConfiguration, $writeEntityManager->getConfiguration());

        /** @var MappingDriverChain $writeMetadataDriver */
        $writeMetadataDriver = $writeConfiguration->getMetadataDriverImpl();

        $writeDrivers = $writeMetadataDriver->getDrivers();

        self::assertCount(5, $writeDrivers);

        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\One\Entity', $writeDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity', $writeDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity', $writeDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity', $writeDrivers);
        self::assertArrayHasKey('Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity', $writeDrivers);

        self::assertInstanceOf(AnnotationDriver::class, $writeDrivers['Chubbyphp\Tests\ServiceProvider\Resources\One\Entity']);
        self::assertInstanceOf(YamlDriver::class, $writeDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Two\Entity']);
        self::assertInstanceOf(SimplifiedYamlDriver::class, $writeDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Three\Entity']);
        self::assertInstanceOf(XmlDriver::class, $writeDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Four\Entity']);
        self::assertInstanceOf(SimplifiedXmlDriver::class, $writeDrivers['Chubbyphp\Tests\ServiceProvider\Resources\Five\Entity']);
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
