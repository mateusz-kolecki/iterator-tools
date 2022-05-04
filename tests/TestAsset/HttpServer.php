<?php

declare(strict_types=1);

namespace IteratorTools\TestAsset;

use Exception;
use function pcntl_fork;
use function pcntl_waitpid;
use function posix_kill;
use function socket_accept;
use function socket_bind;
use function socket_close;
use function socket_create;
use function socket_last_error;
use function socket_listen;
use function socket_read;
use function socket_strerror;
use function socket_write;
use function strlen;
use const AF_INET;
use const SIGKILL;
use const SOCK_STREAM;
use const SOL_TCP;
use const SOMAXCONN;

class HttpServer
{
    private int $port = 8123;
    private string $listenAddress = '127.0.0.1';
    private int $childPid = 0;

    public function getBaseUrl(): string
    {
        return "http://{$this->listenAddress}:{$this->port}/";
    }

    /**
     * @psalm-param callable(string):string $contentFactory
     * @throws Exception
     */
    public function start(callable $contentFactory): void
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        while (false === @socket_bind($socket, $this->listenAddress, $this->port)) {
            $this->port++;
        }

        if (false === @socket_listen($socket, SOMAXCONN)) {
            $errNo = socket_last_error($socket);
            $errMessage = socket_strerror($errNo);
            throw new Exception($errMessage, $errNo);
        }

        $pid = @pcntl_fork();

        if (-1 === $pid) {
            throw new Exception("Cant fork and create HTTP server process");
        }

        if (0 === $pid) {
            // child
            try {
                $this->serverLoop($socket, $contentFactory);
            } catch (Exception $e) {
                echo $e->getMessage() . PHP_EOL;
            } finally {
                socket_close($socket);
            }

            exit;
        }

        // parent
        socket_close($socket);
        $this->childPid = $pid;
    }

    public function stop(): void
    {
        if (0 < $this->childPid) {
            posix_kill($this->childPid, SIGKILL);
            pcntl_waitpid($this->childPid, $status);
            $this->childPid = 0;
        }
    }

    /**
     * @psalm-param resource $socket
     * @psalm-param callable(string):string $contentFactory
     */
    private function serverLoop($socket, callable $contentFactory): void
    {
        $chunkSize = 1024;
        while (false !== ($conn = @socket_accept($socket))) {
            $request = '';
            do {
                $part = socket_read($conn, $chunkSize);
                $partLength = false !== $part ? strlen($part) : 0;

                if (0 < $partLength) {
                    $request .= $part;
                }
            } while ($partLength === $chunkSize);

            $response = $contentFactory($request);

            @socket_write($conn, $response);
            @socket_close($conn);
        }
    }
}
