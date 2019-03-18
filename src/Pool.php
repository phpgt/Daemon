<?php
/**
 * By: sayyassine
 * For the love of code
 *
 */


namespace Gt\Daemon;
use phpDocumentor\Parser\Exception;

require_once __DIR__ . '\Process.php';


class Pool {
	protected $processList;

	public function __construct() {
		$this->processList = [];
	}


    /**
     * Adds the Process process to the list with the name $name
     * @param string $name
     * @param Process $process
     */
	public function add(string $name, Process $process) {
		$this->processList[$name] = $process;
	}

    /**
     * Starts the execution of all proccesses
     */
	public function exec() {
	    foreach($this->processList as $name=>$process ){
	        $process->run();
        }
	}

    /**
     * Returns how many processes are still running.
     * @return int
     */
	public function numRunning():int {
        $num = 0 ;
        foreach($this->processList as $name=>$process){
            $num += ($process->isAlive() ? 1 : 0);
        }
        return $num;
	}

    /**
     * Returns ouptput for all the proccesses in the $processList
     * @return string
     */
	public function read():string {
        $output = "" ;
        foreach($this->processList as $name=>$process){
            $out = $process->getOutput();
            if( !empty($out) ){
                $output .= "OUTPUT for [$name] : " . PHP_EOL . $out . PHP_EOL .PHP_EOL;
            }
        }

        return $output ;
	}

    /**
     * Returns errors for all the proccesses in the $processList
     * @return string
     */
	public function readError():string {
        $output = "" ;
        foreach($this->processList as $name=>$process){
            $out = $process->getErrorOutput();
            if( !empty($out) ){
                $output .= "ERROR for [$name] : " . PHP_EOL . $out . PHP_EOL .PHP_EOL;
            }
        }

        return $output ;
	}

    /**
     * @param string $processName
     * the name of the proccess that will get it's errors read.
     * @return string
     * @throws \Exception
     */
	public function readErrorOf(string $processName):string
    {
        if( ! array_key_exists( $processName ,$this->processList) &&
            is_resource($this->processList[$processName])) {
            throw new \Exception("No process named $processName found .");
        }

        return $this->processList[$processName]->getErrorOuput();

    }

    /**
     * @param string $processName
     * the name of the proccess that will get it's output read.
     * @return string
     * @throws \Exception
     */
	public function readOutputOf(string $processName):string
    {
        if( ! array_key_exists( $processName ,$this->processList) &&
            is_resource($this->processList[$processName])) {
            throw new \Exception("No process named $processName found .");
        }

        return $this->processList[$processName]->getErrorOuput();

    }

    /**
     * Executes only the proccess having $processName as a name
     * @param string $processName
     * @throws \Exception
     */
    public function executeOne(string $processName)
    {
        if( ! array_key_exists( $processName ,$this->processList) &&
            is_resource($this->processList[$processName])) {
            throw new \Exception("No process named $processName found .");
        }

        $this->processList[$processName]->run();

    }

    /**
     * Stops all the processes and returns an array mapping each proccess name with it's return code
     * This method should only be called if you want to stop the execution of all the processes
     *
     * example :
     *      $return_codes = $pool->closeAll() ;
     *      echo $return_codes['process1'] ; //assuming that the pool contained a process having 'process1' for name
     *      //this will return the return code of 'process1'
     * @return array
     */
    public function closeAll():array
    {
        $codes = [] ;
        foreach($this->processList as $name=>$process){
            $codes[$name] = $process->close();
        }

        return $codes;

    }

}