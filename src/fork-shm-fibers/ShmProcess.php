<?php
declare(strict_types=1);

class ShmProcess
{
    private const CALCULATION_NOT_STARTED = -1;
    private const CALCULATION_OVER = -2;

    private array $elements = [];
    private array $fibers = [];
    private int $lastNumber = self::CALCULATION_NOT_STARTED;

    public function __construct(
        readonly public int $nthPrimeNumber,
        readonly public int $numbersPerJob,
        readonly public int $procNum,
    ) {
        $this->registerShutdown();
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
        }
    }
}