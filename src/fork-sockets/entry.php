<?php
declare(strict_types=1);

require_once __DIR__ . "/../helpers.php";
require_once __DIR__ . "/SocketsChildProcess.php";
require_once __DIR__ . "/SocketsParentProcess.php";

if ($argc < 2) {
    echo 'Usage: php entry.php <n>. Where n is a Nth prime number and n > 9999.' . PHP_EOL;
    die(0);
}

$nthPrimeNumber = (int)$argv[1];
$numbersPerJob = 5000;
$procNum = 8;

if ($nthPrimeNumber < 10000) {
    die('Nth Prime number is too small for this benchmark. ' . PHP_EOL);
}

$socketFileName = '/tmp/pcntl-test-socket.sock';

$pid = 0;
$parent = null;

for ($i = 0; $i < $procNum; $i++) {
    $pid = pcntl_fork();
    if ($pid == -1) {
        die('Oops, when perform fork. ');
    }
    if ($pid === 0) {
        break;
    }
    if (is_null($parent)) {
        $parent = new SocketsParentProcess($nthPrimeNumber, $numbersPerJob, $procNum, $socketFileName);
    }
    $parent->acceptChildLoop();
}

// child will not escape this
if ($pid == 0) {
    $child = new SocketsChildProcess($socketFileName);
    $child->connectAndServe();
}

// start, at parent
$startTime = microtime(true);
$number = $parent->getNthPrimeNumber();
$tookTime = microtime(true) - $startTime;
$tookMem = memory_get_usage(true) >> 20;

if ($number == -1) {
    die(' < Interrupted. ' . PHP_EOL);
}

// output
echo sprintf('%dnth prime number is %d', $nthPrimeNumber, $number) . PHP_EOL;
echo sprintf('Time elapsed: %.2fs, Memory: %d Mb', $tookTime, $tookMem) . PHP_EOL;
