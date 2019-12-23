<?php
require __DIR__ . "/../vendor/autoload.php";
use Gt\Daemon\Process;
use Gt\Daemon\Pool;

// Create three long-running processes:
$pingProcess = new Process("ping google.com");
$infiniteProcess = new Process("while true; do echo 'background...'; sleep 3; done");
$dateProcess = new Process("while true; do echo $(date -d now); sleep 2; done");

// Add all three processes to a pool:
$pool = new Pool();
$pool->add("Ping", $pingProcess);
$pool->add("Loop", $infiniteProcess);
$pool->add("Date", $dateProcess);

// Start the execution of all processes:
$pool->exec();

// While processes are running, write their output to the terminal:
do {
	echo $pool->read();
	// Sleep to avoid hogging the CPU.
	sleep(1);
}
while($pool->numRunning() > 0);