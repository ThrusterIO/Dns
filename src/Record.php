<?php

namespace Thruster\Component\Dns;

/**
 * Class Record
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Record
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
    private $ttl;

    /**
     * @var mixed
     */
    private $data;

    public function __construct(string $name, int $type, int $class, int $ttl = 0, $data = null)
    {
        $this->name     = $name;
        $this->type     = $type;
        $this->class    = $class;
        $this->ttl      = $ttl;
        $this->data     = $data;
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
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
