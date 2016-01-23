<?php

namespace Thruster\Component\Dns;

use Thruster\Component\EventLoop\EventLoopInterface;
use Thruster\Component\Promise\PromiseInterface;

/**
 * Class MultiServerResolver
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class MultiServerResolver extends Resolver
{
    const STRATEGY_RANDOM = 1;
    const STRATEGY_ROUND_ROBIN = 2;

    /**
     * @var string[]
     */
    private $nameServers;

    /**
     * @var int
     */
    private $picker;

    /**
     * @var int
     */
    private $current = 0;

    public function __construct(array $nameServers, ExecutorInterface $executor, int $strategy = self::STRATEGY_RANDOM)
    {
        $this->nameServers = $nameServers;
        $this->picker      = $this->getNameServerSelectFunction($strategy);

        parent::__construct('', $executor);
    }

    public function resolve(string $domain) : PromiseInterface
    {
        $query = new Query($domain, Message::TYPE_A, Message::CLASS_IN, time());

        $nameServer = $this->{$this->picker}();

        var_dump($nameServer);

        return $this->resolveInternal($nameServer, $query);
    }

    private function getNameServerSelectFunction(int $strategy)
    {
        switch ($strategy) {
            case self::STRATEGY_RANDOM:
                return 'getNameServerRandom';
            case self::STRATEGY_ROUND_ROBIN:
                return 'getNameServerRoundRobin';
        }
    }

    private function getNameServerRandom() : string
    {
        return $this->nameServers[array_rand($this->nameServers)];
    }

    private function getNameServerRoundRobin() : string
    {
        $this->current += 1;

        return $this->nameServers[$this->current % count($this->nameServers)];
    }
}
