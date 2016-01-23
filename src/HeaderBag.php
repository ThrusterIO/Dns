<?php

namespace Thruster\Component\Dns;

/**
 * Class HeaderBag
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class HeaderBag
{
    /**
     * @var string
     */
    private $data;

    /**
     * @var array
     */
    private $attributes;

    public function __construct()
    {
        $this->data = '';
        $this->attributes = [
            'qdCount'   => 0,
            'anCount'   => 0,
            'nsCount'   => 0,
            'arCount'   => 0,
            'qr'        => 0,
            'opcode'    => Message::OPCODE_QUERY,
            'aa'        => 0,
            'tc'        => 0,
            'rd'        => 0,
            'ra'        => 0,
            'z'         => 0,
            'rcode'     => Message::RCODE_OK,
        ];
    }

    public function get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    public function set($name, $value) : self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function isQuery() : bool
    {
        return 0 === $this->attributes['qr'];
    }

    public function isResponse() : bool
    {
        return 1 === $this->attributes['qr'];
    }

    public function isTruncated() : bool
    {
        return 1 === $this->attributes['tc'];
    }

    public function populateCounts(Message $message)
    {
        $this->attributes['qdCount'] = count($message->questions);
        $this->attributes['anCount'] = count($message->answers);
        $this->attributes['nsCount'] = count($message->authority);
        $this->attributes['arCount'] = count($message->additional);
    }
}
