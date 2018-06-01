<?php

return [
    "target_php_version" => null,
    'directory_list' => [
        'src',
        'vendor/alcaeus/mongo-php-adapter',
        'vendor/doctrine',
        'vendor/monolog/monolog',
        'vendor/pimple/pimple',
        'vendor/psr/container',
        'vendor/psr/log',
        'vendor/symfony/doctrine-bridge'
    ],
    "exclude_analysis_directory_list" => [
        'vendor/'
    ],
    'plugins' => [
        'AlwaysReturnPlugin',
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
    ],
];
