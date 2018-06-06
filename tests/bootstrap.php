<?php

namespace Chubbyphp\Tests\ServiceProvider;

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../vendor/autoload.php';
$loader->setPsr4('Chubbyphp\Tests\ServiceProvider\\', __DIR__);

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
