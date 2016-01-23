<?php

namespace Thruster\Component\Dns;

/**
 * Class Query
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Query
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $type;

    /**
     * @var int
     */
    private $class;

    /**
     * @var int
     */
    private $currentTime;

    public function __construct(string $name, int $type, int $class, int $currentTime)
    {
        $this->name = $name;
        $this->type = $type;
        $this->class = $class;
        $this->currentTime = $currentTime;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return int
     */
    public function getCurrentTime()
    {
        return $this->currentTime;
    }
}
