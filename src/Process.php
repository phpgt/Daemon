<?php
namespace Gt\Daemon;

class Process {
	protected $command = "";

	/** @var array Indexed array of streams: 0=>input, 1=>output, 2=>error. */
	protected $pipes;
	/** @var resource The process as returned from proc_open. */
	private $process = null;

	public function __construct(string $command) {
		$this->command = $command;
	}


	/**
	 * Runs the command in a concurrent thread.
	 * Sets the input, output and errors streams.
	 */
	public function run() {
		$descriptor = [
			0 => ["pipe", "r"],
			1 => ["pipe", "w"],
			2 => ["pipe", "w"],
		];

		$this->process = proc_open(
			escapeshellcmd($this->command),
			$descriptor,
			$this->pipes
		);

		if(!is_resource($this->process)) {
			throw new \Exception("An unexpected error occurred while trying to run $this->command");
		}

		stream_set_blocking($this->pipes[1], 0);
		stream_set_blocking($this->pipes[2], 0);
		stream_set_blocking($this->pipes[0], 0);
	}

	public function isAlive():bool {
		if(!is_resource($this->process)) {
			return false;
		}

		return proc_get_status($this->process)["running"];
	}

	public function getCommand():string {
		return $this->command;
	}

	public function getOutput():string {
		if(!is_resource($this->process)) {
			throw new \Exception("This function should be called after the run method.");
		}

		$output = "";

		while($bytes = fread($this->pipes[1], 1024)) {
			$output .= $bytes;
		}

		return $output;
	}

	public function getErrorOutput():string {
		if(!is_resource($this->process)) {
			throw new \Exception("This function should be called after the run method.");
		}

		$output = "";

		while($bytes = fread($this->pipes[2], 1024)) {
			$output .= fread($this->pipes[2], 1024);
		}

		return $output;
	}

	/** Closes the thread and the streams then returns the return code of the command. */
	public function close():int {
		array_filter($this->pipes, function($pipe) {
			return fclose($pipe);
		});

		return proc_close($this->process);
	}
}