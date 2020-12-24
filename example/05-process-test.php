<?php
require "../src/Process.php";

use Gt\Daemon\Process;

// First, start both processes in the background.
$procLet = new Process("php 03-letters.php");
$procNum = new Process("php 04-numbers.php");

$procList = [
	"numbers" => $procNum,
	"letters" => $procLet,
];

//running the proccesses
$procNum->exec();
$procLet->exec();

$i = 0 ;

do {
	$numRunning = 0;

	foreach($procList as $name => $proc) {
		/** @var Process $proc */
		if($proc->isRunning()) {
			$numRunning++;
		}
		$output = $proc->getOutput();
		$error = $proc->getErrorOutput();

		if(strlen($output) > 0) {
			fwrite(STDOUT, "[$name] $output");
		}
		if(strlen($error) > 0) {
			fwrite(STDOUT, "[$name ERROR] $error");
		}
	}

	usleep(100000);
}
while($numRunning > 0);

echo "Program quit. Exit codes:" . PHP_EOL;
echo "numbers.php exited with code " . $procNum->close() . PHP_EOL;
echo "letters.php exited with code " . $procLet->close() . PHP_EOL;