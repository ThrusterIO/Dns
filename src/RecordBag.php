<?php

namespace Thruster\Component\Dns;

/**
 * Class RecordBag
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class RecordBag
{
    /**
     * @var array
     */
    private $records;

    public function __construct()
    {
        $this->records = [];
    }

    public function set(int $currentTime, Record $record)
    {
        $this->records[$record->getData()] = [$currentTime + $record->getTtl(), $record];
    }

    public function all() : array
    {
        return array_values(
            array_map(
                function ($value) {
                    list($expiresAt, $record) = $value;

                    return $record;
                },
                $this->records
            )
        );
    }
}
