<?php

declare(strict_types=1);

namespace Chubbyphp\ServiceProvider\Registry;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Proxy\Proxy;
use Pimple\Container;

final class DoctrineOrmManagerRegistry implements ManagerRegistry
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Container|Connection[]
     */
    private $connections;

    /**
     * @var string
     */
    private $defaultConnectionName;

    /**
     * @var Container|EntityManager[]
     */
    private $originalManagers;

    /**
     * @var EntityManager[]
     */
    private $resetManagers = [];

    /**
     * @var string
     */
    private $defaultManagerName;

    /**
     * @var string
     */
    private $proxyInterfaceName;

    /**
     * @param Container $container
     * @param string    $proxyInterfaceName
     */
    public function __construct(Container $container, $proxyInterfaceName = Proxy::class)
    {
        $this->container = $container;
        $this->proxyInterfaceName = $proxyInterfaceName;
    }

    /**
     * @return string
     */
    public function getDefaultConnectionName(): string
    {
        $this->loadConnections();

        return $this->defaultConnectionName;
    }

    /**
     * @param string|null $name
     *
     * @return Connection
     *
     * @throws \InvalidArgumentException
     */
    public function getConnection($name = null): Connection
    {
        $this->loadConnections();

        $name = $name ?? $this->getDefaultConnectionName();

        if (!isset($this->connections[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing connection with name "%s".', $name));
        }

        return $this->connections[$name];
    }

    /**
     * @return Connection[]
     */
    public function getConnections(): array
    {
        $this->loadConnections();

        $connections = array();
        foreach ($this->connections->keys() as $connectionName) {
            $connections[$connectionName] = $this->connections[$connectionName];
        }

        return $connections;
    }

    /**
     * @return string[]
     */
    public function getConnectionNames(): array
    {
        $this->loadConnections();

        return $this->connections->keys();
    }

    /**
     * @return string
     */
    public function getDefaultManagerName(): string
    {
        $this->loadManagers();

        return $this->defaultManagerName;
    }

    /**
     * @param string|null $name
     *
     * @return EntityManager|ObjectManager
     */
    public function getManager($name = null): ObjectManager
    {
        $this->loadManagers();

        $name = $name ?? $this->getDefaultManagerName();

        if (!isset($this->originalManagers[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing manager with name "%s".', $name));
        }

        return $this->resetManagers[$name] ?? $this->originalManagers[$name];
    }

    /**
     * @return EntityManager[]|ObjectManager[]
     */
    public function getManagers(): array
    {
        $this->loadManagers();

        $managers = array();
        foreach ($this->originalManagers->keys() as $managerName) {
            $managers[$managerName] = $this->resetManagers[$managerName] ?? $this->originalManagers[$managerName];
        }

        return $managers;
    }

    /**
     * @return array
     */
    public function getManagerNames(): array
    {
        $this->loadManagers();

        return $this->originalManagers->keys();
    }

    /**
     * @param string|null $name
     *
     * @return EntityManager|ObjectManager
     */
    public function resetManager($name = null)
    {
        $this->loadManagers();

        $name = $name ?? $this->getDefaultManagerName();

        if (!isset($this->originalManagers[$name])) {
            throw new \InvalidArgumentException(sprintf('Missing manager with name "%s".', $name));
        }

        $originalManager = $this->originalManagers[$name];

        $this->resetManagers[$name] = EntityManager::create(
            $originalManager->getConnection(),
            $originalManager->getConfiguration(),
            $originalManager->getEventManager()
        );

        return $this->resetManagers[$name];
    }

    /**
     * @param string $alias
     *
     * @return string
     *
     * @throws ORMException
     */
    public function getAliasNamespace($alias): string
    {
        foreach ($this->getManagerNames() as $name) {
            try {
                return $this->getManager($name)->getConfiguration()->getEntityNamespace($alias);
            } catch (ORMException $e) {
                // throw the exception only if no manager can solve it
            }
        }
        throw ORMException::unknownEntityNamespace($alias);
    }

    /**
     * @param string $persistentObject
     * @param null   $persistentManagerName
     *
     * @return EntityRepository|ObjectRepository
     */
    public function getRepository($persistentObject, $persistentManagerName = null): ObjectRepository
    {
        return $this->getManager($persistentManagerName)->getRepository($persistentObject);
    }

    /**
     * @param string $class
     *
     * @return EntityManager|ObjectManager|null
     */
    public function getManagerForClass($class)
    {
        $proxyClass = new \ReflectionClass($class);
        if ($proxyClass->implementsInterface($this->proxyInterfaceName)) {
            $class = $proxyClass->getParentClass()->getName();
        }

        foreach ($this->getManagerNames() as $managerName) {
            if (!$this->getManager($managerName)->getMetadataFactory()->isTransient($class)) {
                return $this->getManager($managerName);
            }
        }
    }

    private function loadConnections()
    {
        if (null === $this->connections) {
            $this->connections = $this->container['doctrine.dbal.dbs'];
            $this->defaultConnectionName = $this->container['doctrine.dbal.dbs.default'];
        }
    }

    private function loadManagers()
    {
        if (null === $this->originalManagers) {
            $this->originalManagers = $this->container['doctrine.orm.ems'];
            $this->defaultManagerName = $this->container['doctrine.orm.ems.default'];
        }
    }
}
