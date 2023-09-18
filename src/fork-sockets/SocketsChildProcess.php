<?php
declare(strict_types=1);

class SocketsChildProcess
{
    private Socket $socket;

    public function __construct(
        readonly public string $socketFile,
    ) {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
    }

    public function connectAndServe(): void
    {
        while (!@socket_connect($this->socket, $this->socketFile)) {
            // waiting
        }
        $this->readWriteLoop();
    }

    private function readWriteLoop(): never
    {
        // implode/explode faster than pack/unpack
        while ($job = socket_read($this->socket, 33)) {
            [$start, $end] = explode(':', $job);
            $primes = getPrimeNumberFromTo((int)$start, (int)$end);
            @socket_write($this->socket, implode(',', $primes) . "\0", ($end - $start) * 8);
        }
        $this->clear();
        exit(0);
    }

    private function clear(): void
    {
        socket_close($this->socket);
    }
}