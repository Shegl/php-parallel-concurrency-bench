<?php
declare(strict_types=1);

readonly class Single
{
    public function __construct(
        public int $nthPrimeNumber,
    ){}

    public function getNthPrimeNumber(): int
    {
        $primeNumberCounter = 0;
        $i = 0;
        while ($primeNumberCounter < $this->nthPrimeNumber) {
            if (isPrimeNumber(++$i)) {
                $primeNumberCounter++;
            }
        }
        return $i;
    }
}
