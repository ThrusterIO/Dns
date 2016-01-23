<?php

namespace Thruster\Component\Dns;

use Thruster\Component\Dns\Exception\BadServerException;
use Thruster\Component\Dns\Exception\TimeoutException;
use Thruster\Component\EventLoop\EventLoopInterface;
use Thruster\Component\Promise\Deferred;
use Thruster\Component\Promise\PromiseInterface;
use Thruster\Component\Socket\Connection;
use Thruster\Component\Socket\Exception\ConnectionException;

/**
 * Class Executor
 *
 * @package Thruster\Component\Dns
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Executor implements ExecutorInterface
{
    /**
     * @var EventLoopInterface
     */
    private $loop;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Dumper
     */
    private $dumper;

    /**
     * @var int
     */
    private $timeout;

    public function __construct(EventLoopInterface $loop, int $timeout = 5)
    {
        $this->loop    = $loop;
        $this->parser  = new Parser();
        $this->dumper  = new Dumper();
        $this->timeout = $timeout;
    }

    public function query(string $nameServer, Query $query) : PromiseInterface
    {
        $request = $this->prepareRequest($query);

        $queryData = $this->dumper->toBinary($request);

        $transport = strlen($queryData) > 512 ? 'tcp' : 'udp';

        return $this->doQuery($nameServer, $transport, $queryData, $query->getName());
    }

    private function prepareRequest(Query $query)
    {
        $request = new Message();

        $request->header->set('id', $this->generateId());
        $request->header->set('rd', 1);
        $request->questions[] = $query;
        $request->prepare();

        return $request;
    }

    private function doQuery($nameServer, $transport, $queryData, $name) : PromiseInterface
    {
        $response = new Message();
        $deferred = new Deferred();

        $retryWithTcp = function () use ($nameServer, $queryData, $name) {
            return $this->doQuery($nameServer, 'tcp', $queryData, $name);
        };

        $connection = $this->createConnection($nameServer, $transport);

        $timer = $this->loop->addTimer($this->timeout, function () use ($connection, $name, $deferred) {
            $connection->close();

            $deferred->reject(new TimeoutException($name));
        });


        $connection->on(
            'data',
            function ($data) use ($retryWithTcp, $connection, &$response, $transport, $deferred, $timer) {
                $responseReady = $this->parser->parseChunk($data, $response);

                if (!$responseReady) {
                    return;
                }

                $timer->cancel();

                if ($response->header->isTruncated()) {
                    if ('tcp' === $transport) {
                        $deferred->reject(
                            new BadServerException(
                                'The server set the truncated bit although we issued a TCP request'
                            )
                        );
                    } else {
                        $connection->end();
                        $deferred->resolve($retryWithTcp());
                    }

                    return;
                }

                $connection->end();
                $deferred->resolve($response);
            }
        );

        $connection->write($queryData);

        return $deferred->promise();
    }

    private function generateId() : int
    {
        return mt_rand(0, 0xffff);
    }

    private function createConnection($nameServer, $transport) : Connection
    {
        $fd = stream_socket_client(
            $transport . '://' . $nameServer,
            $errNo,
            $errStr,
            0,
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
        );

        if (false === $fd) {
            $message = sprintf(
                'Could not bind to %s://%s: %s',
                $transport,
                $nameServer,
                $errStr
            );

            throw new ConnectionException($message, $errNo);
        }

        stream_set_blocking($fd, 0);

        $connection = new Connection($fd, $this->loop);

        return $connection;
    }
}
