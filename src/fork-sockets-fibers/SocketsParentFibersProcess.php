<?php
declare(strict_types=1);

class SocketsParentFibersProcess
{
    private const CALCULATION_NOT_STARTED = -1;
    private const CALCULATION_OVER = -2;

    private array $elements = [];
    private array $fibers = [];
    private Socket $socket;
    private int $lastNumber = self::CALCULATION_NOT_STARTED;

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

        $this->registerShutdown();

        if (!socket_listen($this->socket, $this->procNum)) {
            die('Failed to listen on server socket: ' . socket_strerror(socket_last_error()) . PHP_EOL);
        }
    }

    public function acceptChild(): void
    {
        $fiber = $this->makeFiber();
        $fiber->start($this->socket, $this->numbersPerJob, $this->processOutput(...), $this->getNextStart(...));
        $this->fibers[] = $fiber;
    }

    public function getNthPrimeNumber(): int
    {
        do {
            $alive = false;
            foreach ($this->fibers as $fiber) {
                if ($fiber instanceof Fiber) {
                    if (!$fiber->isTerminated()) {
                        $alive = true;
                        $fiber->resume();
                    }
                }
            }
        } while ($alive);

        sort($this->elements, SORT_NUMERIC);
        return $this->elements[$this->nthPrimeNumber-1] ?? -1;
    }

    private function getNextStart(): int
    {
        if (count($this->elements) > $this->nthPrimeNumber || $this->lastNumber == self::CALCULATION_OVER) {
            return self::CALCULATION_OVER;
        }
        $lastNumber = $this->lastNumber;
        $this->lastNumber += $this->numbersPerJob;
        return $lastNumber;
    }

    private function processOutput(string $output): void
    {
        $primes = explode(',', $output);
        foreach ($primes as $primeNumber) {
            $this->elements[] = (int)$primeNumber;
        }
    }

    private function makeFiber(): Fiber
    {
        return new Fiber(
            static function (
                Socket $socket,
                int $numbersPerJob,
                Callable $outputFn,
                Callable $getNextFn,
            ): void {
                $clientSocket = socket_accept($socket);
                socket_set_nonblock($clientSocket);
                Fiber::suspend();
                while (true) {
                    $last = $getNextFn();
                    if ($last < 0) {
                        if ($last == self::CALCULATION_NOT_STARTED) {
                            continue;
                        } else {
                            socket_close($clientSocket);
                            return;
                        }
                    }
                    socket_write($clientSocket, sprintf('%016d:%016d', $last, $last + $numbersPerJob), 33);
                    while (true) {
                        $output = socket_read($clientSocket, $numbersPerJob * 8);
                        if ($output) {
                            $outputFn($output);
                            continue 2;
                        }
                        Fiber::suspend();
                    }
                }
            }
        );
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
        if (count($this->fibers) > 0) {
            $this->fibers = [];
            $this->lastNumber = self::CALCULATION_OVER;
            socket_close($this->socket);
            @unlink($this->socketFile);
        }
    }
}
