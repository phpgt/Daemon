<?php
/**
 * Created by PhpStorm.
 * User: sayya
 * Date: 18/03/2019
 * Time: 11:59
 */


use \Gt\Daemon\Pool;
use \Gt\Daemon\Process;


require_once '../src/Pool.php';
require_once '../src/Process.php';


$pool = new Pool();

$pool->add("Numbers process",new Process("php numbers.php"));
$pool->add("Letters process",new Process("php letters.php"));


$pool->exec() ;

while($pool->numRunning() > 0){
    echo ($pool->read());
    echo ($pool->readError());

    sleep(3);

}

print_r($pool->closeAll());

echo ("Execution done." .PHP_EOL);