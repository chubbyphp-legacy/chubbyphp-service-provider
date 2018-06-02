# DoctrineDbalServiceProvider

The *DoctrineDbalServiceProvider* provides integration with the [Doctrine Dbal][1]
for easy database access
(Doctrine ORM integration is **not** supplied).

## Install

```sh
composer require doctrine/dbal "^2.5"
```

## Parameters

* **doctrine.dbal.db.options**: Array of Doctrine DBAL options.

  These options are available:

  * **driver**: The database driver to use, defaults to ``pdo_mysql``.
    Can be any of: ``pdo_mysql``, ``pdo_sqlite``, ``pdo_pgsql``,
    ``pdo_oci``, ``oci8``, ``ibm_db2``, ``pdo_ibm``, ``pdo_sqlsrv``.

  * **dbname**: The name of the database to connect to.

  * **host**: The host of the database to connect to. Defaults to
    localhost.

  * **user**: The user of the database to connect to. Defaults to
    root.

  * **password**: The password of the database to connect to.

  * **charset**: Only relevant for ``pdo_mysql``, and ``pdo_oci/oci8``,
    specifies the charset used when connecting to the database.

  * **path**: Only relevant for ``pdo_sqlite``, specifies the path to
    the SQLite database.

  * **port**: Only relevant for ``pdo_mysql``, ``pdo_pgsql``, and ``pdo_oci/oci8``,
    specifies the port of the database to connect to.

  These and additional options are described in detail in [Doctrine Dbal Configuration][2].

## Services

* **doctrine.dbal.db**: The database connection, instance of
  ``Doctrine\DBAL\Connection``.

* **doctrine.dbal.db.config**: Configuration object for Doctrine. Defaults to
  an empty ``Doctrine\DBAL\Configuration``.

* **doctrine.dbal.db.event_manager**: Event Manager for Doctrine.

## Registering

### Single connection

```php
$container['doctrine.dbal.db.options'] = [
    'driver'   => 'pdo_sqlite',
    'path'     => __DIR__.'/app.db',
];

$container->register(new Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider()));
```

### Multiple connections

```php
$container['doctrine.dbal.dbs.options'] = [
    'mysql_read' => [
        'driver'    => 'pdo_mysql',
        'host'      => 'mysql_read.someplace.tld',
        'dbname'    => 'my_database',
        'user'      => 'my_username',
        'password'  => 'my_password',
        'charset'   => 'utf8mb4',
    ],
    'mysql_write' => [
        'driver'    => 'pdo_mysql',
        'host'      => 'mysql_write.someplace.tld',
        'dbname'    => 'my_database',
        'user'      => 'my_username',
        'password'  => 'my_password',
        'charset'   => 'utf8mb4',
    ],
];

$container->register(new Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider());
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
