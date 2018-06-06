<?php

namespace Chubbyphp\Tests\ServiceProvider\Resources\One\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="model")
 */
class Model
{
    /**
     * @var string
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;
}
