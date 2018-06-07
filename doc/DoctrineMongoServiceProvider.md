# DoctrineMongoServiceProvider

The *DoctrineMongoServiceProvider* provides integration with the [Doctrine MongoDb][1]
for easy database access
(Doctrine ODM integration is **not** supplied).

## Install

```sh
composer require alcaeus/mongo-php-adapter "^1.1.5"
composer require doctrine/mongodb "^1.1"
```

## Parameters

* **doctrine.mongo.db.options**: Array of Doctrine DBAL options.

  These options are available:

  * **server**: The server url to use, defaults to `mongodb://localhost:27017`.

  * **options**: Other connections options supported by the native driver.

  These options are described in detail in [PHP MongoClient documentation][2].

## Services

* **doctrine.mongo.db**: The database connection, instance of
  `Doctrine\MongoDB\Connection`.

* **doctrine.mongo.db.config**: Configuration object for Doctrine. Defaults to
  an empty `Doctrine\MongoDB\Configuration`.

* **doctrine.mongo.db.event_manager**: Event Manager for Doctrine.

## Registering

### Single connection

```php
$container = new Container():

$container->register(new Chubbyphp\ServiceProvider\DoctrineMongoServiceProvider()));

$container['doctrine.mongo.db.options'] = [
    'server' => 'mongodb://localhost:27017',
    'options' => [
        'username' => 'root',
        'password' => 'root',
        'db' => 'admin',
    ],
];
```

### Multiple connections

```php
$container = new Container():

$container->register(new Chubbyphp\ServiceProvider\DoctrineMongoServiceProvider());

$container['doctrine.mongo.dbs.options'] = [
    'doctrine.mongo.db_read' => [
        'server' => 'mongodb://localhost:27017',
        'options' => [
            'username' => 'root',
            'password' => 'root',
            'db' => 'admin',
        ],
    ],
    'doctrine.mongo.db_write' => [
        'server' => 'mongodb://localhost:27018',
        'options' => [
            'username' => 'root',
            'password' => 'root',
            'db' => 'admin',
        ],
    ],
];
```

## Usage

### Single connection

```php
$container['doctrine.mongo.db']
    ->selectCollection('users')
    ->findOne(['username' => 'john.doe@domain.com']);
```

### Multiple connections

```php
$container['doctrine.mongo.dbs']['name']
    ->selectCollection('users')
    ->findOne(['username' => 'john.doe@domain.com']);
```

[1]: https://www.doctrine-project.org/projects/mongodb.html
[2]: http://php.net/manual/de/mongo.connecting.php
