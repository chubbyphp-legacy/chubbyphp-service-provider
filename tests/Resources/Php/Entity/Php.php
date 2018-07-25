<?php

namespace Chubbyphp\Tests\ServiceProvider\Resources\Php\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;

class Php
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @param ClassMetadata $metadata
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->setPrimaryTable(['name' => 'php']);

        $metadata->mapField([
            'id' => true,
            'fieldName' => 'id',
            'type' => 'string',
        ]);

        $metadata->mapField([
            'fieldName' => 'string',
            'type' => 'string',
        ]);
    }
}
