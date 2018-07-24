# DoctrineOrmServiceProvider

The *DoctrineOrmServiceProvider* provides integration with the [Doctrine ORM][1].

## Install

```sh
composer require doctrine/cache "^1.6"
composer require doctrine/orm "^2.5"
```

## Parameters

* **doctrine.orm.proxies_dir**: The directory where generated proxies get saved. Example: `var/cache/doctrine/orm/proxies`
* **doctrine.orm.auto_generate_proxies**: Enable or disable the auto generation of proxies. Defaults to `true`
* **doctrine.orm.em.options**: Array of Doctrine ORM options.
* **doctrine.orm.proxies_namespace**: The namespace of generated proxies. Defaults to `DoctrineProxy`

    These options are available:

    * **connection**: The connection name of the Doctrine DBAL configuration. Defaults to `default`
    * **mappings**: Array of Mappings.
        * **type**: The mapping driver to use. Can be any of: `annotation`, `yml`, `simple_yml`, `xml`, `simple_xml`,  or `php`
        * **namespace**: The entity namespace. Example: `One\Entity`
        * **path**: The path to the entities. Example: `/path/to/project/One/Entity`
        * **alias**: The entity alias to the namespace. Example: `Alias\Entity`
        * **extension**: The file extension to search for mappings. Example: `.dcm.xml`
        * **use_simple_annotation_reader**: Use simple annotation, supported: `@Entity`. Defaults to `true`
        If you wanna use `@ORM\Enity` set it to `false` and add the following code after require the composer autoloader.

            ```php
            use \Doctrine\Common\Annotations\AnnotationRegistry;

            $loader = require __DIR__.'/../vendor/autoload.php';
            AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
            ```
    * **query_cache**: String with the cache type, defaults to `null`.
    * **metadata_cache**: String with the cache type, defaults to `null`.
    * **result_cache**: String with the cache type, defaults to `null`.
    * **hydration_cache**: String with the cache type, defaults to `null`.
    * **second_level_cache**: String with the cache type, defaults to `null`.
    Can be any of: `apcu`, `array`
    Define your own cache adapters by adding `doctrine.orm.em.cache_factory.%s` to the container
* **doctrine.orm.custom.functions.string**: Add [dql user defined functions][2] for string
* **doctrine.orm.custom.functions.numeric**: Add [dql user defined functions][2] for numeric
* **doctrine.orm.custom.functions.datetime**: Add [dql user defined functions][2] for datetime
* **doctrine.orm.custom.hydration_modes**: Add [custom hydration modes][3]

## Services

* **doctrine.orm.em**: The entity manager, instance of `Doctrine\ORM\EntityManager`.
* **doctrine.orm.em.config**: Configuration object for Doctrine. Defaults to an empty `Doctrine\ORM\Configuration`.
* **doctrine.orm.manager_registry**: The manager registry, instance of `Doctrine\Common\Persistence\ManagerRegistry`.

## Registering

### Single connection

```php
$container = new Container();

$container->register(new Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider()));
$container->register(new Chubbyphp\ServiceProvider\DoctrineOrmServiceProvider()));

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

$container['doctrine.orm.em.options'] = [
    'mappings' => [
        [
            'type' => 'annotation',
            'namespace' => 'One\Entity',
            'path' => __DIR__.'/src/One/Entity',
            'use_simple_annotation_reader' => false
        ]
    ]
];
```

### Multiple connections

```php
$container = new Container();

$container->register(new Chubbyphp\ServiceProvider\DoctrineDbalServiceProvider()));
$container->register(new Chubbyphp\ServiceProvider\DoctrineOrmServiceProvider()));

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

$container['doctrine.orm.ems.options'] = [
    'mysql_read' => [
        'connection' => 'mysql_read',
        'mappings' => [
            [
                'type' => 'annotation',
                'namespace' => 'One\Entity',
                'alias' => 'One',
                'path' => __DIR__.'/src/One/Entity',
                'use_simple_annotation_reader' => false,
            ],
        ],
    ],
    'mysql_write' => [
        'connection' => 'mysql_write',
        'mappings' => [
            [
                'type' => 'annotation',
                'namespace' => 'One\Entity',
                'path' => __DIR__.'/src/One/Entity',
                'use_simple_annotation_reader' => false,
            ],
        ],
    ],
];
```

## Usage

### Single connection

```php
$container['doctrine.orm.em']
    ->getRepository(User::class)
    ->findOneBy(['username' => 'john.doe@domain.com']);
```

### Multiple connections

```php
$container['doctrine.orm.ems']['name']
    ->getRepository(User::class)
    ->findOneBy(['username' => 'john.doe@domain.com']);
```

(c) Beau Simensen <beau@dflydev.com> (https://github.com/dflydev/dflydev-doctrine-orm-service-provider)

[1]: https://www.doctrine-project.org/projects/orm
[2]: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/dql-user-defined-functions.html
[3]: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#custom-hydration-modes
[4]: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html#query-hints

