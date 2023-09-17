<?php
declare(strict_types=1);

class Fibers
{
    private array $elements = [];
    private array $fibers = [];
    public function __construct(
        readonly public int $nthPrimeNumber,
        readonly public int $numbersPerJob,
        readonly public int $fibersNum,
    ) {
        for ($i = 0; $i < $this->fibersNum; $i++) {
            $this->fibers[] = $this->newFiber();
        }
    }

    public function getNthPrimeNumber(): int
    {
        $lastStart = 0;

        while (count($this->elements) < $this->nthPrimeNumber) {
            foreach ($this->fibers as $key => $fiber) {
                if (!$fiber->isRunning()) {
                    $this->fibers[$key] = $this->newFiber();
                    $lastStart = $this->startFiber($this->fibers[$key], $lastStart);
                }
            }
        }

        sort($this->elements, SORT_NUMERIC);

        return $this->elements[$this->nthPrimeNumber-1];
    }

    private function newFiber(): Fiber
    {
        return new Fiber(function (int $start, int $end, Callable $callback): void {
            $primes = getPrimeNumberFromTo($start, $end);
            $callback($primes);
        });
    }

    private function startFiber(Fiber $fiber, $lastStart): int
    {
        $end = $lastStart + $this->numbersPerJob;
        $fiber->start($lastStart, $end, $this->processOutput(...));
        return $end;
    }

    private function processOutput(array $primes): void
    {
        foreach ($primes as $primeNumber) {
            $this->elements[] = $primeNumber;
        }
    }
}

