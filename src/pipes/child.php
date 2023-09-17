<?php
declare(strict_types = 1);
require_once __DIR__ . "/../helpers.php";

if ($argc < 3) {
    echo "Usage: php child.php <start> <end>" . PHP_EOL;
    die(0);
}

[$file, $start, $end] = $argv;

$primes = getPrimeNumberFromTo((int)$start, (int)$end);

echo implode(',', $primes);
