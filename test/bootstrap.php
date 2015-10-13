<?php
// Command that starts the built-in web server
$command = sprintf('php -S 0.0.0.0:8889 -t . example.php >/dev/null 2>&1 & echo $!');
// Execute the command and store the process ID
$output = array();
exec($command, $output);
$pid = (int) $output[0];
echo sprintf('%s - Web server started on 0.0.0.0:8889 with PID %d', date('r'), $pid). PHP_EOL;
// Kill the web server when the process ends
register_shutdown_function(function() use ($pid) {
    echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
    exec('kill ' . $pid);
});
// More bootstrap code
include ("vendor/autoload.php");

