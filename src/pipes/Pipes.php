<?php
declare(strict_types=1);

class Pipes
{
    private array $handles = [];
    private array $elements = [];

    public function __construct(
        readonly public int $nthPrimeNumber,
        readonly public int $numbersPerJob,
        readonly public int $procNum,
        readonly public string $command,
    ) {}

    public function getNthPrimeNumber(): int
    {
        $last = 0;
        for ($i = 0; $i < $this->procNum; $i++) {
            $this->handles[] = $this->addJob($last);
            $last += $this->numbersPerJob;
        }

        // collect/respawn loop
        while (count($this->elements) < $this->nthPrimeNumber) {
            for ($h = 0; $h < $this->procNum; $h++) {
                $output = fgets($this->handles[$h]);
                if (!empty($output)) {
                    $this->processOutput($output);
                    $this->endJob($this->handles[$h]);
                    $this->handles[$h] = $this->addJob($last);
                    $last += $this->numbersPerJob;
                }
            }
        }

        sort($this->elements, SORT_NUMERIC);

        return $this->elements[$this->nthPrimeNumber-1];
    }

    private function addJob(int $from): mixed
    {
        $res = popen(sprintf($this->command, __DIR__, $from, $from + $this->numbersPerJob), 'r');
        stream_set_blocking($res, false);
        return $res;
    }

    private function endJob($handle): void
    {
        @pclose($handle);
    }

    private function processOutput(string $output): void
    {
        $primes = explode(',', $output);
        foreach ($primes as $primeNumber) {
            $this->elements[] = (int)$primeNumber;
        }
    }

    public function reset(): void
    {
        for ($h = 0; $h < $this->procNum; $h++) {
            $this->endJob($this->handles[$h]);
        }
        $this->elements = [];
        $this->handles = [];
    }
}

