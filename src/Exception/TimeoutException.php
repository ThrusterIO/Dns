<?php

namespace Thruster\Component\Dns\Exception;

/**
 * Class TimeoutException
 *
 * @package Thruster\Component\Dns\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class TimeoutException extends \Exception
{
    public function __construct(string $name)
    {
        $message = sprintf('DNS query for %s timed out', $name);

        parent::__construct($message);
    }
}
