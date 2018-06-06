<?php

namespace Chubbyphp\Tests\ServiceProvider\Resources\Six\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;

class Model
{
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
        $metadata->setPrimaryTable(['name' => 'model']);

        $metadata->mapField(array(
            'fieldName' => 'string',
            'type' => 'string',
            'nullable' => true,
        ));
    }
}
