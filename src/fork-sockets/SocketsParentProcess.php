<?php
declare(strict_types=1);

class SocketsParentProcess
{
    private array $elements = [];
    private array $sockets = [];
    private bool $alive;
    private Socket $socket;

    public function __construct(
        readonly public int $nthPrimeNumber,
        readonly public int $numbersPerJob,
        readonly public int $procNum,
        readonly public string $socketFile,
    ) {
        $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
        if (!socket_bind($this->socket, $socketFile)) {
            die('Failed bind socket to ' . $this->socketFile . PHP_EOL);
        }

        $this->alive = true;
        $this->registerShutdown();

        if (!socket_listen($this->socket, $this->procNum)) {
            die('Failed to listen on server socket: ' . socket_strerror(socket_last_error()) . PHP_EOL);
        }
    }

    public function acceptChildLoop(): void
    {
        while (true) {
            $clientSocket = socket_accept($this->socket);
            if ($clientSocket) {
                $this->sockets[] = $clientSocket;
                break;
            }
        }
    }

    public function getNthPrimeNumber(): int
    {
        // init and spawn jobs
        $lastStart = 0;
        foreach ($this->sockets as $clientSocket) {
            $lastStart = $this->addJob($clientSocket, $lastStart);
        }

        // loop
        $null = null;
        while (true) {
            if (!$this->alive) {
                return -1;
            }
            if (count($this->sockets) == 0) {
                break;
            }
            $readableSockets = $this->sockets;
            if (socket_select($readableSockets, $null, $null, 0) === 0) {
                continue;
            }
            $lastStart = $this->readSelectedSockets($readableSockets, $lastStart);
        }

        // result
        sort($this->elements, SORT_NUMERIC);
        return $this->elements[$this->nthPrimeNumber-1];
    }

    private function readSelectedSockets(array $readableSockets, int $last): int
    {
        foreach ($readableSockets as $key => $clientSocket) {
            $output = socket_read($clientSocket, $this->numbersPerJob * 8);
            $this->processOutput($output);
            // respawn
            if (count($this->elements) < $this->nthPrimeNumber) {
                // if not ready send new job
                $last = $this->addJob($clientSocket, $last);
            } else {
                $this->closeChild($this->sockets[$key]);
            }
        }
        return $last;
    }

    private function addJob(Socket $socket, int $start): int
    {
        $end = $start + $this->numbersPerJob;
        if ($this->alive) {
            socket_write(
                $socket,
                sprintf('%016d:%016d', $start, $end),
                33,
            );
        }
        return $end;
    }

    /**
     * @param string $output
     * @return void
     * If someone interested, why I'm not using pack/unpack, because it's slower than implode/explode
     */
    private function processOutput(string $output): void
    {
        $primes = explode(',', $output);
        foreach ($primes as $primeNumber) {
            $this->elements[] = (int)$primeNumber;
        }
    }

    private function closeChild(Socket $socketToClose): void
    {
        foreach ($this->sockets as $key => $socket) {
            if ($socketToClose == $socket) {
                socket_close($socket);
                unset($this->sockets[$key]);
            }
        }
    }

    private function registerShutdown(): void
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, $this->clear(...));
        pcntl_signal(SIGHUP, $this->clear(...));
        pcntl_signal(SIGINT, $this->clear(...));
        register_shutdown_function($this->clear(...));
    }

    private function clear(): void
    {
        if (!$this->alive) {
            return;
        }
        $this->alive = false;
        foreach ($this->sockets as $socket) {
            $this->closeChild($socket);
        }
        socket_close($this->socket);
        @unlink($this->socketFile);
    }
}
