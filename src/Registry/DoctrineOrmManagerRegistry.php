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

        $name = $this->validateName(
            $this->connections,
            $name,
            $this->getDefaultConnectionName())
        ;

        return $this->connections[$name];
    }

    /**
     * @return Connection[]
     */
    public function getConnections(): array
    {
        $this->loadConnections();

        if ($this->connections instanceof Container) {
            $connections = array();
            foreach ($this->getConnectionNames() as $name) {
                $connections[$name] = $this->connections[$name];
            }
            $this->connections = $connections;
        }

        return $this->connections;
    }

    /**
     * @return string[]
     */
    public function getConnectionNames(): array
    {
        $this->loadConnections();

        if ($this->connections instanceof Container) {
            return $this->connections->keys();
        } else {
            return array_keys($this->connections);
        }
    }

    private function loadConnections()
    {
        if (is_null($this->connections)) {
            $this->connections = $this->container['doctrine.dbal.dbs'];
            $this->defaultConnectionName = $this->container['doctrine.dbal.dbs.default'];
        }
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
        $name = $this->validateManagerName($name);

        return isset($this->resetManagers[$name]) ? $this->resetManagers[$name] : $this->originalManagers[$name];
    }

    /**
     * @param string|null $name
     *
     * @return string
     */
    private function validateManagerName($name): string
    {
        return $this->validateName(
            $this->originalManagers,
            $name,
            $this->getDefaultManagerName())
        ;
    }

    /**
     * @param array       $data
     * @param string|null $name
     * @param string      $default
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function validateName($data, $name, $default): string
    {
        if (null === $name) {
            $name = $default;
        }

        if (!isset($data[$name])) {
            throw new \InvalidArgumentException(sprintf('Element named "%s" does not exist.', $name));
        }

        return $name;
    }

    /**
     * @return EntityManager[]|ObjectManager[]
     */
    public function getManagers(): array
    {
        $this->loadManagers();

        if ($this->originalManagers instanceof Container) {
            $managers = array();
            foreach ($this->getManagerNames() as $name) {
                $managers[$name] = $this->originalManagers[$name];
            }
            $this->originalManagers = $managers;
        }

        return array_replace($this->originalManagers, $this->resetManagers);
    }

    /**
     * @return array
     */
    public function getManagerNames(): array
    {
        $this->loadManagers();

        if ($this->originalManagers instanceof Container) {
            return $this->originalManagers->keys();
        } else {
            return array_keys($this->originalManagers);
        }
    }

    /**
     * @param string|null $name
     *
     * @return EntityManager|ObjectManager
     */
    public function resetManager($name = null)
    {
        $this->loadManagers();
        $name = $this->validateManagerName($name);

        $this->resetManagers[$name] = $this->container['doctrine.orm.ems.factory'][$name]();

        return $this->resetManagers[$name];
    }

    private function loadManagers()
    {
        if (is_null($this->originalManagers)) {
            $this->originalManagers = $this->container['doctrine.orm.ems'];
            $this->defaultManagerName = $this->container['doctrine.orm.ems.default'];
        }
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
}
