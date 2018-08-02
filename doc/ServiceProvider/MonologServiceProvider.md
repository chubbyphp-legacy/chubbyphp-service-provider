# MonologServiceProvider

The *MonologServiceProvider* provides a default logging mechanism through
Jordi Boggiano's [Monolog][1] library.

It will log requests and errors and allow you to add logging to your
application. This allows you to debug and monitor the behaviour,
even in production.

## Install

```sh
composer require "monolog/monolog": "^1.4.1"
```

## Parameters

* **monolog.logfile**: File where logs are written to.
* **monolog.level** (optional): Level of logging, defaults to `DEBUG`.
  PSR-3 log levels from `\Psr\Log\LogLevel::` constants are also supported.

* **monolog.name** (optional): Name of the monolog channel,
  defaults to `myapp`.

* **monolog.bubble** (optional): Whether the messages that are handled can bubble up the stack or not.
* **monolog.permission** (optional): File permissions default (null), nothing change.

## Services

* **monolog**: The monolog logger, instance of `Monolog\Logger`.

## Registering

```php
$container['monolog.logfile'] = __DIR__.'/development.log';

$container->register(new Chubbyphp\ServiceProvider\ServiceProvider\MonologServiceProvider());
```

## Usage

The MonologServiceProvider provides a `monolog` service. You can use it to
add log entries for any logging level through `debug()`, `info()`,
`warning()` and `error()`::

```php
$container['monolog']->info(sprintf("User '%s' registered.", $username));
```

(c) Fabien Potencier <fabien@symfony.com> (https://github.com/silexphp/Silex-Providers)

[1]: https://github.com/Seldaek/monolog
