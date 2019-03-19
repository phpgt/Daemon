<?php
/**
 * Created by PhpStorm.
 * User: sayya
 * Date: 18/03/2019
 * Time: 11:54
 */

require "../src/Process.php";


use Gt\Daemon\Process;
use Gt\Daemon\Pool;



// First, start both processes in the background.
$procNum = new Process("php numbers.php");
$procLet = new Process("php letters.php");

//running the proccesses
$procNum->run();
$procLet->run();

$i = 0 ;
// Quit if either process ends:
while($procNum->isAlive() || $procLet->isAlive()) {
// If the numbers process has output, show it to terminal:
    $outputNum = $procNum->getOutput();

    $errorNum = $procNum->getErrorOutput();
    $outputNum .= $procNum->getOutput();

    if(strlen($outputNum) > 0) {
        echo  PHP_EOL .PHP_EOL . "Num output: " . $outputNum;
    }

// If the numbers process has an error, show it to terminal:
    if(strlen($errorNum) > 0) {
        echo "Num error: " . $errorNum;
    }

// If the letters process has output, show it to terminal:
    $outputLet = $procLet->getOutput();
    $errorLet = $procLet->getErrorOutput();
    if(strlen($outputLet) > 0) {
        echo PHP_EOL .PHP_EOL . "Let output: "  . $outputLet;
    }
    $i++ ;
// If the letters process has an error, show it to terminal:
    if(strlen($errorLet) > 0) {
        echo "Let error: " . $errorLet;
    }

    echo PHP_EOL;
    echo "Waiting...";
    echo PHP_EOL;
    sleep(4);
}

echo "Program quit. Exit codes:" . PHP_EOL;
echo "numbers.php exited with code " . $procNum->close() . PHP_EOL;
echo "letters.php exited with code " . $procLet->close() . PHP_EOL;