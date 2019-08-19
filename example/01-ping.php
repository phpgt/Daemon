<?php
require __DIR__ . "/../vendor/autoload.php";

use Gt\Daemon\Pool;
use Gt\Daemon\Process;

$pool = new Pool();
$pool->add("Google", new Process("ping google.com"));
$pool->add("Bing", new Process("ping bing.com"));
$pool->add("Yahoo", new Process("ping yahoo.com"));

$pool->exec();

while($pool->numRunning() > 0) {
	fwrite(STDOUT, $pool->read());
	usleep(100000); //100ms
}