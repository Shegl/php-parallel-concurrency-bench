<?php
declare(strict_types=1);

require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/Pipes.php";

if ($argc < 2) {
    echo 'Usage: php entry.php <n>. Where n is a Nth prime number and n > 9999.' . PHP_EOL;
    die(0);
}

// setup
$nthPrimeNumber = (int)$argv[1];
$numbersPerJob = 5000;
$procNum = 8;

if ($nthPrimeNumber < 10000) {
    die('Nth Prime number is too small for this benchmark. ' . PHP_EOL);
}

if (isJitEnabled()) {
    $command = 'php -d opcache.enable=1 \
                -d opcache.enable_cli=1 \
                -d opcache.jit_buffer_size=500000000 \
                -d opcache.jit=1255 \%s/child.php %d %d';
} else {
    $command = 'php -d opcache.enable=1 \
                -d opcache.enable_cli=1 \
                -d opcache.jit=0000 \%s/child.php %d %d';
}

$pipes = new Pipes(
    $nthPrimeNumber,
    $numbersPerJob,
    $procNum,
    $command,
);

// start
$startTime = microtime(true);
$number = $pipes->getNthPrimeNumber();
$tookTime = microtime(true) - $startTime;
$tookMem = memory_get_usage(true) >> 20;

// cleanup
$pipes->reset();

// output
echo sprintf('%dnth prime number is %d', $nthPrimeNumber, $number) . PHP_EOL;
echo sprintf('Time elapsed: %.2fs, Memory: %d Mb', $tookTime, $tookMem) . PHP_EOL;


