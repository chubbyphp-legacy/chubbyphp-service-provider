# DoctrineMongoDbServiceProvider

The *DoctrineMongoDbServiceProvider* provides integration with the [Doctrine MongoDb][1]
for easy database access
(Doctrine ODM integration is **not** supplied).

## Install

```sh
composer require alcaeus/mongo-php-adapter "^1.1.5"
composer require doctrine/mongodb "^1.1"
```

## Parameters

* **mongodb.options**: Array of Doctrine DBAL options.

  These options are available:

  * **server**: The server url to use, defaults to ``mongodb://localhost:27017``.

  * **options**: Other connections options supported by the native driver.

  These options are described in detail in [PHP MongoClient documentation][2].

## Services

* **mongodb**: The database connection, instance of
  ``Doctrine\MongoDB\Connection``.

* **mongodb.config**: Configuration object for Doctrine. Defaults to
  an empty ``Doctrine\MongoDB\Configuration``.

* **mongodb.event_manager**: Event Manager for Doctrine.

## Registering

### Single connection

```php
$container['mongodb.options'] = [
    'server' => 'mongodb://localhost:27017',
    'options' => [
        'username' => 'root',
        'password' => 'root',
        'db' => 'admin',
    ],
];

$container->register(new Chubbyphp\ServiceProvider\DoctrineMongoDbServiceProvider()));
```

### Multiple connections

```php
$container['mongodbs.options'] = [
    'mongodb_read' => [
        'server' => 'mongodb://localhost:27017',
        'options' => [
            'username' => 'root',
            'password' => 'root',
            'db' => 'admin',
        ],
    ],
    'mongodb_write' => [
        'server' => 'mongodb://localhost:27018',
        'options' => [
            'username' => 'root',
            'password' => 'root',
            'db' => 'admin',
        ],
    ],
];

$container->register(new Chubbyphp\ServiceProvider\DoctrineMongoDbServiceProvider());
```

## Usage

### Single connection

```php
$container['mongodb']
    ->selectCollection('users')
    ->findOne(['username' => 'john.doe@domain.com']);
```

### Multiple connections

```php
$container['mongodbs']['name']
    ->selectCollection('users')
    ->findOne(['username' => 'john.doe@domain.com']);
```

[1]: https://www.doctrine-project.org/projects/mongodb.html
[2]: http://php.net/manual/de/mongo.connecting.php
