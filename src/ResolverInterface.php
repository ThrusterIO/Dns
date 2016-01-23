<?php

namespace Thruster\Component\Dns;

use Thruster\Component\Promise\PromiseInterface;

/**
 * Interface ResolverInterface
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface ResolverInterface
{
    public function resolve(string $domain) : PromiseInterface;
}
