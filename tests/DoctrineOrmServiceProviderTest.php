<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider;
use Chubbyphp\ServiceProvider\DoctrineOrmServiceProvider;
use Chubbyphp\Tests\ServiceProvider\Resources\Annotation\Entity\Annotation;
use Chubbyphp\Tests\ServiceProvider\Resources\SimpleXml\Entity\SimpleXml;
use Chubbyphp\Tests\ServiceProvider\Resources\SimpleYaml\Entity\SimpleYaml;
use Chubbyphp\Tests\ServiceProvider\Resources\StaticPhp\Entity\StaticPhp;
use Chubbyphp\Tests\ServiceProvider\Resources\Xml\Entity\Xml;
use Chubbyphp\Tests\ServiceProvider\Resources\Yaml\Entity\Yaml;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\Cache\DefaultCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\LoggerInterface;

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

        self::assertArrayHasKey('doctrine.orm.em', $container);
        self::assertArrayHasKey('doctrine.orm.em.cache_factory.apcu', $container);
        self::assertArrayHasKey('doctrine.orm.em.cache_factory.array', $container);
        self::assertArrayHasKey('doctrine.orm.em.config', $container);
        self::assertArrayHasKey('doctrine.orm.em.default_options', $container);
        self::assertArrayHasKey('doctrine.orm.ems', $container);
        self::assertArrayHasKey('doctrine.orm.ems.config', $container);
        self::assertArrayHasKey('doctrine.orm.ems.options.initializer', $container);
        self::assertArrayHasKey('doctrine.orm.entity.listener_resolver.default', $container);
        self::assertArrayHasKey('doctrine.orm.manager_registry', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.annotation', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.static_php', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.simple_xml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.simple_yaml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.xml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver.factory.yaml', $container);
        self::assertArrayHasKey('doctrine.orm.mapping_driver_chain', $container);
        self::assertArrayHasKey('doctrine.orm.repository.factory.default', $container);
        self::assertArrayHasKey('doctrine.orm.strategy.naming.default', $container);
        self::assertArrayHasKey('doctrine.orm.strategy.quote.default', $container);

        // start: doctrine.orm.em
        self::assertSame($container['doctrine.orm.em'], $container['doctrine.orm.ems']['default']);

        /** @var EntityManager $em */
        $em = $container['doctrine.orm.em'];

        self::assertInstanceOf(EntityManager::class, $em);
        self::assertSame($container['doctrine.dbal.db'], $em->getConnection());
        self::assertInstanceOf(ClassMetadataFactory::class, $em->getMetadataFactory());
        self::assertNull($em->getCache());

        try {
            $hasMappingException = false;
            $em->getClassMetadata(\stdClass::class);
        } catch (MappingException $mappingException) {
            $hasMappingException = true;
        }

        self::assertTrue($hasMappingException);

        try {
            $hasMappingException = false;
            $em->getRepository(\stdClass::class);
        } catch (MappingException $mappingException) {
            $hasMappingException = true;
        }

        self::assertTrue($hasMappingException);

        self::assertSame($container['doctrine.dbal.db.event_manager'], $em->getEventManager());
        self::assertSame($container['doctrine.orm.em.config'], $em->getConfiguration());
        self::assertInstanceOf(ProxyFactory::class, $em->getProxyFactory());
        // end: doctrine.orm.em

        self::assertInstanceOf(ApcuCache::class, $container['doctrine.orm.em.cache_factory.apcu']);
        self::assertInstanceOf(ArrayCache::class, $container['doctrine.orm.em.cache_factory.array']);

        // start: doctrine.orm.em.config
        self::assertSame($container['doctrine.orm.em.config'], $container['doctrine.orm.ems.config']['default']);

        /** @var Configuration $config */
        $config = $container['doctrine.orm.em.config'];

        self::assertSame(sys_get_temp_dir().'/doctrine/orm/proxies', $config->getProxyDir());
        self::assertSame(1, $config->getAutoGenerateProxyClasses());
        self::assertSame('DoctrineProxy', $config->getProxyNamespace());
        self::assertInstanceOf(MappingDriverChain::class, $config->getMetadataDriverImpl());
        self::assertInstanceOf(ArrayCache::class, $config->getQueryCacheImpl());
        self::assertInstanceOf(ArrayCache::class, $config->getHydrationCacheImpl());
        self::assertInstanceOf(ArrayCache::class, $config->getMetadataCacheImpl());

        self::assertNotSame($config->getQueryCacheImpl(), $config->getHydrationCacheImpl());
        self::assertNotSame($config->getQueryCacheImpl(), $config->getMetadataCacheImpl());
        self::assertNotSame($config->getQueryCacheImpl(), $config->getResultCacheImpl());
        self::assertNotSame($config->getHydrationCacheImpl(), $config->getMetadataCacheImpl());
        self::assertNotSame($config->getHydrationCacheImpl(), $config->getResultCacheImpl());
        self::assertNotSame($config->getMetadataCacheImpl(), $config->getResultCacheImpl());

        self::assertSame(ClassMetadataFactory::class, $config->getClassMetadataFactoryName());
        self::assertSame(EntityRepository::class, $config->getDefaultRepositoryClassName());
        self::assertInstanceOf(DefaultNamingStrategy::class, $config->getNamingStrategy());
        self::assertInstanceOf(DefaultQuoteStrategy::class, $config->getQuoteStrategy());
        self::assertInstanceOf(DefaultEntityListenerResolver::class, $config->getEntityListenerResolver());
        self::assertInstanceOf(DefaultRepositoryFactory::class, $config->getRepositoryFactory());
        self::assertFalse($config->isSecondLevelCacheEnabled());
        self::assertNull($config->getSecondLevelCacheConfiguration());
        self::assertSame([], $config->getDefaultQueryHints());
        self::assertNull($config->getSQLLogger());
        self::assertSame($container['doctrine.dbal.db.config']->getResultCacheImpl(), $config->getResultCacheImpl());
        self::assertNull($config->getFilterSchemaAssetsExpression());
        self::assertTrue($config->getAutoCommit());
        // end: doctrine.orm.em.config

        // start: doctrine.orm.em.default_options
        self::assertEquals([
            'cache.hydration' => 'array',
            'cache.metadata' => 'array',
            'cache.query' => 'array',
            'class_metadata.factory.name' => ClassMetadataFactory::class,
            'connection' => 'default',
            'custom.datetime.functions' => [],
            'custom.hydration_modes' => [],
            'custom.numeric.functions' => [],
            'custom.string.functions' => [],
            'entity.listener_resolver' => 'default',
            'mappings' => [],
            'proxies.auto_generate' => true,
            'proxies.dir' => sys_get_temp_dir().'/doctrine/orm/proxies',
            'proxies.namespace' => 'DoctrineProxy',
            'query_hints' => [],
            'repository.default.class' => EntityRepository::class,
            'repository.factory' => 'default',
            'second_level_cache.enabled' => false,
            'second_level_cache.type' => 'array',
            'strategy.naming' => 'default',
            'strategy.quote' => 'default',
        ], $container['doctrine.orm.em.default_options']);
        // end: doctrine.orm.em.default_options

        // start: doctrine.orm.ems
        self::assertInstanceOf(Container::class, $container['doctrine.orm.ems']);
        // end: doctrine.orm.ems

        // start: doctrine.orm.ems.config
        self::assertInstanceOf(Container::class, $container['doctrine.orm.ems.config']);
        // end: doctrine.orm.ems.config

        // start: doctrine.orm.ems.options.initializer
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.ems.options.initializer']);
        // end: doctrine.orm.ems.options.initializer

        // start: doctrine.orm.manager_registry
        self::assertInstanceOf(ManagerRegistry::class, $container['doctrine.orm.manager_registry']);

        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $container['doctrine.orm.manager_registry'];

        self::assertSame('default', $managerRegistry->getDefaultConnectionName());
        self::assertSame($container['doctrine.dbal.db'], $managerRegistry->getConnection());
        self::assertSame($container['doctrine.dbal.db'], $managerRegistry->getConnections()['default']);
        self::assertSame(['default'], $managerRegistry->getConnectionNames());

        self::assertSame('default', $managerRegistry->getDefaultManagerName());
        self::assertSame($container['doctrine.orm.em'], $managerRegistry->getManager());
        self::assertSame($container['doctrine.orm.em'], $managerRegistry->getManagers()['default']);
        self::assertSame(['default'], $managerRegistry->getManagerNames());
        // end: doctrine.orm.manager_registry

        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.annotation']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.static_php']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.simple_xml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.simple_yaml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.xml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver.factory.yaml']);
        self::assertInstanceOf(\Closure::class, $container['doctrine.orm.mapping_driver_chain']);

        self::assertSame($config->getRepositoryFactory(), $container['doctrine.orm.repository.factory.default']);
        self::assertSame($config->getNamingStrategy(), $container['doctrine.orm.strategy.naming.default']);
        self::assertSame($config->getQuoteStrategy(), $container['doctrine.orm.strategy.quote.default']);
    }

    public function testRegisterWithOneManager()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        };

        $container['doctrine.orm.em.options'] = [
            'second_level_cache.enabled' => true,
            'mappings' => [
                [
                    'type' => 'annotation',
                    'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Annotation\Entity',
                    'path' => __DIR__.'/Resources/Annotation/Entity',
                ],
            ],
        ];

        /** @var EntityManager $em */
        $em = $container['doctrine.orm.em'];

        self::assertInstanceOf(EntityRepository::class, $em->getRepository(Annotation::class));
        self::assertInstanceOf(DefaultCache::class, $em->getCache());
    }

    public function testRegisterWithMultipleManager()
    {
        $container = new Container();

        $dbalServiceProvider = new DoctrineDbalServiceProvider();
        $dbalServiceProvider->register($container);

        $ormServiceProvider = new DoctrineOrmServiceProvider();
        $ormServiceProvider->register($container);

        $container['logger'] = function () {
            return $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();
        };

        $container['doctrine.dbal.dbs.options'] = [
            'annotation' => [],
            'simpleYaml' => [],
            'simpleXml' => [],
            'yaml' => [],
            'xml' => [],
            'staticPhp' => [],
        ];

        $container['doctrine.orm.ems.options'] = [
            'annotation' => [
                'connection' => 'annotation',
                'mappings' => [
                    [
                        'type' => 'annotation',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Annotation\Entity',
                        'alias' => 'Entity\Annotation',
                        'path' => __DIR__.'/Resources/Annotation/Entity',
                    ],
                ],
            ],
            'simpleYaml' => [
                'connection' => 'simpleYaml',
                'mappings' => [
                    [
                        'type' => 'simple_yaml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\SimpleYaml\Entity',
                        'alias' => 'Entity\SimpleYaml',
                        'path' => __DIR__.'/Resources/SimpleYaml/config',
                    ],
                ],
            ],
            'simpleXml' => [
                'connection' => 'simpleXml',
                'mappings' => [
                    [
                        'type' => 'simple_xml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\SimpleXml\Entity',
                        'alias' => 'Entity\SimpleXml',
                        'path' => __DIR__.'/Resources/SimpleXml/config',
                    ],
                ],
            ],
            'yaml' => [
                'connection' => 'yaml',
                'mappings' => [
                    [
                        'type' => 'yaml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Yaml\Entity',
                        'alias' => 'Entity\Yaml',
                        'path' => __DIR__.'/Resources/Yaml/config',
                    ],
                ],
            ],
            'xml' => [
                'connection' => 'xml',
                'mappings' => [
                    [
                        'type' => 'xml',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\Xml\Entity',
                        'alias' => 'Entity\Xml',
                        'path' => __DIR__.'/Resources/Xml/config',
                    ],
                ],
            ],
            'staticPhp' => [
                'connection' => 'staticPhp',
                'mappings' => [
                    [
                        'type' => 'static_php',
                        'namespace' => 'Chubbyphp\Tests\ServiceProvider\Resources\StaticPhp\Entity',
                        'alias' => 'Entity\StaticPhp',
                        'path' => __DIR__.'/Resources/StaticPhp/Entity',
                    ],
                ],
            ],
        ];

        /** @var EntityManager $annotationEm */
        $annotationEm = $container['doctrine.orm.ems']['annotation'];

        self::assertInstanceOf(EntityRepository::class, $annotationEm->getRepository(Annotation::class));

        /** @var EntityManager $simpleYamlEm */
        $simpleYamlEm = $container['doctrine.orm.ems']['simpleYaml'];

        self::assertInstanceOf(EntityRepository::class, $simpleYamlEm->getRepository(SimpleYaml::class));

        /** @var EntityManager $simpleXmlEm */
        $simpleXmlEm = $container['doctrine.orm.ems']['simpleXml'];

        self::assertInstanceOf(EntityRepository::class, $simpleXmlEm->getRepository(SimpleXml::class));

        /** @var EntityManager $yamlEm */
        $yamlEm = $container['doctrine.orm.ems']['yaml'];

        self::assertInstanceOf(EntityRepository::class, $yamlEm->getRepository(Yaml::class));

        /** @var EntityManager $xmlEm */
        $xmlEm = $container['doctrine.orm.ems']['xml'];

        self::assertInstanceOf(EntityRepository::class, $xmlEm->getRepository(Xml::class));

        /** @var EntityManager $staticPhp */
        $staticPhp = $container['doctrine.orm.ems']['staticPhp'];

        self::assertInstanceOf(EntityRepository::class, $staticPhp->getRepository(StaticPhp::class));
    }
}
