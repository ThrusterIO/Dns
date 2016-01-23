<?php

namespace Thruster\Component\Dns;

use Thruster\Component\Dns\Exception\TimeoutException;
use Thruster\Component\Promise\Deferred;
use Thruster\Component\Promise\PromiseInterface;

/**
 * Class RetryExecutor
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class RetryExecutor implements ExecutorInterface
{
    /**
     * @var ExecutorInterface
     */
    private $executor;

    /**
     * @var int
     */
    private $retries;

    public function __construct(ExecutorInterface $executor, int $retries = 2)
    {
        $this->executor = $executor;
        $this->retries  = $retries;
    }

    public function query(string $nameServer, Query $query) : PromiseInterface
    {
        $deferred = new Deferred();

        $this->tryQuery($nameServer, $query, $this->retries, $deferred);

        return $deferred->promise();
    }

    public function tryQuery(string $nameServer, Query $query, int $retries, Deferred $deferred)
    {
        $errorCallback = function ($error) use ($nameServer, $query, $retries, $deferred) {
            if (false === ($error instanceof TimeoutException)) {
                $deferred->reject($error);

                return;
            }

            if (0 >= $retries) {
                $error = new \RuntimeException(
                    sprintf("DNS query for %s failed: too many retries", $query->getName()),
                    0,
                    $error
                );

                $deferred->reject($error);

                return;
            }

            $this->tryQuery($nameServer, $query, $retries - 1, $deferred);
        };

        $this->executor
            ->query($nameServer, $query)
            ->then([$deferred, 'resolve'], $errorCallback);
    }
}
