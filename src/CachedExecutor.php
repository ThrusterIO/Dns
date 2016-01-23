<?php

namespace Thruster\Component\Dns;

use Thruster\Component\Promise\PromiseInterface;

/**
 * Class CachedExecutor
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class CachedExecutor implements ExecutorInterface
{
    /**
     * @var ExecutorInterface
     */
    private $executor;

    /**
     * @var RecordCache
     */
    private $cache;

    public function __construct(ExecutorInterface $executor, RecordCache $cache)
    {
        $this->executor = $executor;
        $this->cache = $cache;
    }

    public function query(string $nameServer, Query $query) : PromiseInterface
    {
        return $this->cache
            ->lookup($query)
            ->then(
                function ($cachedRecords) use ($query) {
                    return $this->buildResponse($query, $cachedRecords);
                },
                function () use ($nameServer, $query) {
                    return $this->executor
                        ->query($nameServer, $query)
                        ->then(function ($response) use ($query) {
                            $this->cache->storeResponseMessage($query->getCurrentTime(), $response);

                            return $response;
                        });
                }
            );
    }

    private function buildResponse(Query $query, array $cachedRecords) : Message
    {
        $response = new Message();

        $response->header->set('id', $this->generateId());
        $response->header->set('qr', 1);
        $response->header->set('opcode', Message::OPCODE_QUERY);
        $response->header->set('rd', 1);
        $response->header->set('rcode', Message::RCODE_OK);

        $response->questions[] = new Record($query->getName(), $query->getType(), $query->getClass());

        foreach ($cachedRecords as $record) {
            $response->answers[] = $record;
        }

        $response->prepare();

        return $response;
    }

    private function generateId() : int
    {
        return mt_rand(0, 0xffff);
    }
}
