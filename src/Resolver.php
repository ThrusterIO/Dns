<?php

namespace Thruster\Component\Dns;

use Thruster\Component\Dns\Exception\RecordNotFoundException;
use Thruster\Component\Promise\PromiseInterface;

class Resolver implements ResolverInterface
{
    /**
     * @var string
     */
    private $nameServer;

    /**
     * @var ExecutorInterface
     */
    private $executor;

    public function __construct(string $nameServer, ExecutorInterface $executor)
    {
        $this->nameServer = $nameServer;
        $this->executor = $executor;
    }

    public function resolve(string $domain) : PromiseInterface
    {
        $query = new Query($domain, Message::TYPE_A, Message::CLASS_IN, time());

        return $this->resolveInternal($this->nameServer, $query);
    }

    protected function resolveInternal(string $nameServer, Query $query)
    {
        return $this->executor
            ->query($nameServer, $query)
            ->then(
                function (Message $response) use ($query) {
                    return $this->extractAddress($query, $response);
                }
            );
    }

    private function extractAddress(Query $query, Message $response)
    {
        $answers = $response->answers;

        $addresses = $this->resolveAliases($answers, $query->getName());

        if (0 === count($addresses)) {
            throw new RecordNotFoundException('DNS Request did not return valid answer.');
        }

        $address = $addresses[array_rand($addresses)];

        return $address;
    }

    private function resolveAliases(array $answers, $name)
    {
        $named = $this->filterByName($answers, $name);
        $aRecords = $this->filterByType($named, Message::TYPE_A);
        $cnameRecords = $this->filterByType($named, Message::TYPE_CNAME);

        if ($aRecords) {
            return $this->mapRecordData($aRecords);
        }

        if ($cnameRecords) {
            $aRecords = array();

            $cnames = $this->mapRecordData($cnameRecords);
            foreach ($cnames as $cname) {
                $targets = $this->filterByName($answers, $cname);
                $aRecords = array_merge(
                    $aRecords,
                    $this->resolveAliases($answers, $cname)
                );
            }

            return $aRecords;
        }

        return array();
    }

    private function filterByName(array $answers, $name)
    {
        return $this->filterByField($answers, 'name', $name);
    }

    private function filterByType(array $answers, $type)
    {
        return $this->filterByField($answers, 'type', $type);
    }

    private function filterByField(array $answers, $field, $value)
    {
        return array_filter($answers, function ($answer) use ($field, $value) {
            $getter = 'get' . ucfirst($field);
            return $value === $answer->$getter();
        });
    }

    private function mapRecordData(array $records)
    {
        return array_map(function ($record) {
            return $record->getData();
        }, $records);
    }
}
