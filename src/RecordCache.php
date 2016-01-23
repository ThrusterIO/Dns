<?php

namespace Thruster\Component\Dns;

use Thruster\Component\Promise\FulfilledPromise;
use Thruster\Component\Promise\RejectedPromise;

/**
 * Class RecordCache
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class RecordCache
{
    /**
     * @var RecordBag[]
     */
    private $records;

    /**
     * @var int
     */
    private $expiredAt;

    public function __construct()
    {
        $this->records     = [];
        $this->expirations = [];
    }

    public function lookup(Query $query)
    {
        $id = $this->serializeQueryToIdentity($query);

        $expiredAt = $this->expiredAt;

        if (false === isset($this->records[$id])) {
            return new RejectedPromise();
        }

        $recordBag = $this->records[$id];

        if (null !== $expiredAt && $expiredAt <= $query->getCurrentTime()) {
            return new RejectedPromise();
        }

        return new FulfilledPromise($recordBag->all());
    }

    public function storeResponseMessage($currentTime, Message $message)
    {
        foreach ($message->answers as $record) {
            $this->storeRecord($currentTime, $record);
        }
    }

    public function expire($currentTime)
    {
        $this->expiredAt = $currentTime;
    }

    private function storeRecord($currentTime, Record $record)
    {
        $id = $this->serializeRecordToIdentity($record);

        if (isset($this->records[$id])) {
            $recordBag = $this->records[$id];
        } else {
            $recordBag = new RecordBag();
        }

        $recordBag->set($currentTime, $record);

        $this->records[$id] = $recordBag;
    }

    private function serializeQueryToIdentity(Query $query)
    {
        return $query->getName() . ':' . $query->getType() . ':' . $query->getClass();
    }

    private function serializeRecordToIdentity(Record $record)
    {
        return $record->getName() . ':' . $record->getType() . ':' . $record->getClass();
    }
}
