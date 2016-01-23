<?php

require_once __DIR__ . '/vendor/autoload.php';

$loop = new \Thruster\Component\EventLoop\EventLoop();

$executor = new \Thruster\Component\Dns\Executor($loop);
$executor = new \Thruster\Component\Dns\CachedExecutor($executor, new \Thruster\Component\Dns\RecordCache());

$resolver = new \Thruster\Component\Dns\MultiServerResolver(['8.8.8.8:53', '8.8.4.4:53'], $executor);

$i = 10;
$a = function() use ($resolver, &$a, $i) {
    $start = microtime(true);
    $resolver->resolve('google.com')->then(
        function ($address) use ($start, $a, $i) {
            var_dump($address);

            echo (microtime(true) - $start) * 1000;
            if (--$i > 0) {
                $a();
            }
        },
        function () {
            var_dump(func_get_args());
        }
    );
};

$a();


$loop->run();
