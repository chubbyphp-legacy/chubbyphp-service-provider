# MonologServiceProvider

The *MonologServiceProvider* provides a default logging mechanism through
Jordi Boggiano's [Monolog][1] library.

It will log requests and errors and allow you to add logging to your
application. This allows you to debug and monitor the behaviour,
even in production.

## Install

```sh
composer require "monolog/monolog": "~1.0"
```

## Parameters

* **monolog.logfile**: File where logs are written to.
* **monolog.bubble** (optional): Whether the messages that are handled can bubble up the stack or not.
* **monolog.permission** (optional): File permissions default (null), nothing change.

* **monolog.level** (optional): Level of logging, defaults
  to ``DEBUG``. Must be one of ``Logger::DEBUG``, ``Logger::INFO``,
  ``Logger::WARNING``, ``Logger::ERROR``. ``DEBUG`` will log
  everything, ``INFO`` will log everything except ``DEBUG``,
  etc.

  In addition to the ``Logger::`` constants, it is also possible to supply the
  level in string form, for example: ``"DEBUG"``, ``"INFO"``, ``"WARNING"``,
  ``"ERROR"``.

  PSR-3 log levels from ``\Psr\Log\LogLevel::`` constants are also supported.

* **monolog.name** (optional): Name of the monolog channel,
  defaults to ``myapp``.

* **monolog.exception.logger_filter** (optional): An anonymous function that
  returns an error level for on uncaught exception that should be logged.

* **monolog.use_error_handler** (optional): Whether errors and uncaught exceptions
  should be handled by the Monolog ``ErrorHandler`` class and added to the log.
  By default the error handler is enabled unless the application ``debug`` parameter
  is set to true.

  Please note that enabling the error handler may silence some errors,
  ignoring the PHP ``display_errors`` configuration setting.

## Services

* **monolog**: The monolog logger instance.

  Example usage::

    $app['monolog']->debug('Testing the Monolog logging.');

* **monolog.listener**: An event listener to log requests, responses and errors.

## Registering

```php
$container->register(new Chubbyphp\ServiceProvider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/development.log',
));
```

## Usage

The MonologServiceProvider provides a ``monolog`` service. You can use it to
add log entries for any logging level through ``debug()``, ``info()``,
``warning()`` and ``error()``::

```php
$container['monolog']->info(sprintf("User '%s' registered.", $username));
```

(c) Fabien Potencier <fabien@symfony.com> (https://github.com/silexphp/Silex-Providers)

[1]: https://github.com/Seldaek/monolog
