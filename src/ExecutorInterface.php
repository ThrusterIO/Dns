<?php

namespace Thruster\Component\Dns;

use Thruster\Component\Promise\PromiseInterface;

/**
 * Interface ExecutorInterface
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface ExecutorInterface
{
    public function query(string $nameServer, Query $query) : PromiseInterface;
}
