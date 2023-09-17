<?php
echo 'Hello, it\'s opcache checker. ' . PHP_EOL;
$info = opcache_get_status(false);
if ($info === false) {
    echo 'Opcache is off. ' . PHP_EOL;
} else {
    echo 'Opcache is on. ' . PHP_EOL;
    echo 'Jit is ' . ($info['jit']['enabled'] ? 'on' : 'off') . '.' . PHP_EOL;
}
