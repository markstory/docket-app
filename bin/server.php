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
    if ($pid) {
        $pids[] = $pid;
    }
    if (!$pid) {
        // Start shell task.
        shell_exec(implode(' ', array_map('escapeshellarg', $server)));
    }
}

while (count($pids) > 0) {
    foreach ($pids as $i => $pid) {
        $res = pcntl_waitpid($pid, $status, WNOHANG);

        // Child has already exited.
        if ($res == -1 || $res > 0) {
            unset($pids[$i]);
        }
    }
    sleep(1);
}
