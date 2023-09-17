<?php
declare(strict_types=1);

// here is isPrimeNumber function which can be adjusted for your test
// also here function called gerPrimeNumber which search for prime numbers in range
if (!function_exists("isPrimeNumber")) {
    function getPrimeNumberFromTo(int $start, int $end): array {
        $primeNumbers = [];
        for ($i = $start; $i < $end; $i++) {
            if (isPrimeNumber($i)) {
                $primeNumbers[] = $i;
            }
        }
        return $primeNumbers;
    }

    function isPrimeNumber(int $number): bool {
        for ($n = 2; $n <= floor(sqrt($number)); $n++) {
            if ($number % $n == 0) {
                return false;
            }
        }
        return $number > 1;
    }
}

if (!function_exists('isJitEnabled')) {
    function isJitEnabled(): bool
    {
        if (!function_exists('opcache_get_status')) {
            return false;
        }
        $info = @opcache_get_status(false);
        return $info && $info['jit']['enabled'];
    }
}
