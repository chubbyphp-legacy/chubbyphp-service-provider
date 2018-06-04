# DoctrineDbalServiceProvider

The *DoctrineDbalServiceProvider* provides integration with the [Doctrine Dbal][1].

## Install

```sh
composer require doctrine/cache "^1.6"
composer require doctrine/dbal "^2.5"
```

## Parameters

* **doctrine.dbal.db.options**: Array of Doctrine DBAL options.

  These options are available:
  
  * **connection**:
  
    * **driver**: The database driver to use, defaults to ``pdo_mysql``.
      Can be any of: ``pdo_mysql``, ``pdo_sqlite``, ``pdo_pgsql``,
      ``pdo_oci``, ``oci8``, ``ibm_db2``, ``pdo_ibm``, ``pdo_sqlsrv``.
    * **dbname**: The name of the database to connect to.
    * **host**: The host of the database to connect to. Defaults to localhost.
    * **user**: The user of the database to connect to. Defaults to root.
    * **password**: The password of the database to connect to.
    * **charset**: Only relevant for ``pdo_mysql``, and ``pdo_oci/oci8``,
      specifies the charset used when connecting to the database.
    * **path**: Only relevant for ``pdo_sqlite``, specifies the path to
      the SQLite database.
    * **port**: Only relevant for ``pdo_mysql``, ``pdo_pgsql``, and ``pdo_oci/oci8``,
      specifies the port of the database to connect to.

  * **configuration**

    * **result_cache**: String or array describing result cache implementation.
    * **filter_schema_assets_expression**: An expression to filter for schema (tables)
    * **auto_commit**: Auto commit. Defaults to `true`

  These and additional options are described in detail in [Doctrine Dbal Configuration][2].

## Services

* **doctrine.dbal.db**: The database connection, instance of
  ``Doctrine\DBAL\Connection``.

* **doctrine.dbal.db.config**: The doctrine configuration, instance of
  ``Doctrine\DBAL\Configuration``.

* **doctrine.dbal.db.event_manager**: The doctrine event manager, instance of
  ``Doctrine\Common\EventManager``.

## Registering

### Single connection

```php
$container = new Container();

$container->register(new Chubbyphp\ServiceProvider\DoctrineCacheServiceProvider()));
$container->register(new Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider()));

$container['doctrine.dbal.db.options'] = [
    'connection' => [
        'driver'    => 'pdo_mysql',
        'host'      => 'mysql.someplace.tld',
        'dbname'    => 'my_database',
        'user'      => 'my_username',
        'password'  => 'my_password',
        'charset'   => 'utf8mb4',
    ],
];
```

### Multiple connections

```php
$container = new Container();

$container->register(new Chubbyphp\ServiceProvider\DoctrineCacheServiceProvider()));
$container->register(new Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider());

$container['doctrine.dbal.dbs.options'] = [
    'mysql_read' => [
        'connection' => [
            'driver'    => 'pdo_mysql',
            'host'      => 'mysql_read.someplace.tld',
            'dbname'    => 'my_database',
            'user'      => 'my_username',
            'password'  => 'my_password',
            'charset'   => 'utf8mb4',
        ],
    ],
    'mysql_write' => [
        'connection' => [
            'driver'    => 'pdo_mysql',
            'host'      => 'mysql_write.someplace.tld',
            'dbname'    => 'my_database',
            'user'      => 'my_username',
            'password'  => 'my_password',
            'charset'   => 'utf8mb4',
        ],
    ],
];
```

## Usage

### Single connection

```php
$container['doctrine.dbal.db']
    ->createQueryBuilder()
    ->select('u')
    ->from('users', 'u')
    ->where($qb->expr()->eq('u.username', ':username'))
    ->setParameter('username', 'john.doe@domain.com')
    ->execute()
    ->fetch(\PDO::FETCH_ASSOC);
```

### Multiple connections

```php
$container['doctrine.dbal.dbs']['name']
    ->createQueryBuilder()
    ->select('u')
    ->from('users', 'u')
    ->where($qb->expr()->eq('u.username', ':username'))
    ->setParameter('username', 'john.doe@domain.com')
    ->execute()
    ->fetch(\PDO::FETCH_ASSOC);
```

(c) Fabien Potencier <fabien@symfony.com> (https://github.com/silexphp/Silex-Providers)

[1]: https://www.doctrine-project.org/projects/dbal
[2]: https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
