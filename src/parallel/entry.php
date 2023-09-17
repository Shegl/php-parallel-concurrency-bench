<?php
declare(strict_types=1);

require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/Fibers.php";

if ($argc < 2) {
    echo 'Usage: php entry.php <n>. Where n is a Nth prime number and n > 9999.' . PHP_EOL;
    die(0);
}

// setup
$numbersPerJob = 5000;
$fibersNum = 8;
$nthPrimeNumber = (int)$argv[1];


if ($nthPrimeNumber < 10000) {
    die('Nth Prime number is too small for this benchmark. ' . PHP_EOL);
}

$fibersApproach = new Fibers(
    $nthPrimeNumber,
    $numbersPerJob,
    $fibersNum,
);

// start
$startTime = microtime(true);
$number = $fibersApproach->getNthPrimeNumber();
$tookTime = microtime(true) - $startTime;
$tookMem = memory_get_usage(true) >> 20;

// output
echo sprintf('%dnth prime number is %d', $nthPrimeNumber, $number) . PHP_EOL;
echo sprintf('Time elapsed: %.2fs, Memory: %d Mb', $tookTime, $tookMem) . PHP_EOL;


