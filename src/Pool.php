<?php
namespace Gt\Daemon;

class Pool {
	/** @var Process[] Associative array of name=>Process */
	protected $processList;

	public function __construct() {
		$this->processList = [];
	}

	public function add(string $name, Process $process) {
		$this->processList[$name] = $process;
	}

	/** Starts the execution of all processes */
	public function exec():void {
		foreach($this->processList as $name => $process) {
			$process->exec();
		}
	}

	public function numRunning():int {
		$num = 0;

		foreach($this->processList as $name => $process) {
			$num += (int)$process->isRunning();
		}

		return $num;
	}

	/** Returns output for all the processes in the $processList */
	public function read(int $pipe = Process::PIPE_OUT):string {
		$output = "";

		foreach($this->processList as $name => $process) {
			$outLines = explode(
				PHP_EOL,
				$process->getOutput($pipe)
			);

			foreach($outLines as $i => $line) {
				if($line === "") {
					unset($outLines[$i]);
				}
			}

			foreach($outLines as $line) {
				if($pipe === Process::PIPE_ERROR) {
					$output .= "[$name ERROR] $line";
				}
				else {
					$output .= "[$name] $line";
				}
				$output .= PHP_EOL;
			}
		}

		return $output;
	}

	/** Returns errors for all the processes in the $processList */
	public function readError():string {
		return $this->read(Process::PIPE_ERROR);
	}

	public function readOutputOf(
		string $name,
		int $pipe = Process::PIPE_OUT
	):string {
		if(!array_key_exists($name, $this->processList)) {
			throw new DaemonException("No process named $name found.");
		}

		$process = $this->processList[$name];
		return $process->getOutput($pipe);
	}

	public function readErrorOf(string $name):string {
		return $this->readOutputOf($name, Process::PIPE_ERROR);
	}

	/** @return int[] Associative array of each closed process's exit code. */
	public function close():array {
		foreach($this->processList as $name => $process) {
			$process->terminate();
		}

		$codes = [];
		do {
			foreach($this->processList as $name => $process) {
				$code = $process->getExitCode();
				if(!is_null($code)) {
					$codes[$name] = $code;
				}
			}

			usleep(100000);
		}
		while(count($codes) < count($this->processList));

		return $codes;
	}
}