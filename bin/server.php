<?php

$servers = [
    ['bin/cake', 'server'],
    ['yarn', 'watch'],
];

$pids = [];

foreach ($servers as $server) {
    $pid = pcntl_fork();
    if ($pid === -1) {
        die('Could not fork');
    }
    if (!$pid) {
        // In child process.
        pcntl_signal(SIGTERM, function () {
            exit(0);
        });
        shell_exec(implode(' ', array_map('escapeshellarg', $server)));
    }
}
