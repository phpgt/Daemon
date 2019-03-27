<?php
namespace Gt\Daemon;

class Process {
	const PIPE_IN = 0;
	const PIPE_OUT = 1;
	const PIPE_ERROR = 2;

	/** @var string */
	protected $command;
	/** @var array Indexed array of streams: 0=>input, 1=>output, 2=>error. */
	protected $pipes;
	/** @var resource The process as returned from proc_open. */
	protected $process = null;
	protected $status;

	public function __construct(string $command) {
		$this->command = $command;
	}

	/**
	 * Runs the command in a concurrent thread.
	 * Sets the input, output and errors streams.
	 */
	public function exec(bool $blocking = false) {
		$descriptor = [
			0 => ["pipe", "r"],
			1 => ["pipe", "w"],
			2 => ["pipe", "w"],
		];

		$this->process = proc_open(
			$this->command,
			$descriptor,
			$this->pipes
		);

		$this->status = proc_get_status($this->process);

		stream_set_blocking($this->pipes[1], 0);
		stream_set_blocking($this->pipes[2], 0);
		stream_set_blocking($this->pipes[0], 0);

		if($blocking) {
			while($this->isRunning()) {
				usleep(10000);
			}
		}
	}

	public function isRunning():bool {
// Special care has to be taken to not call proc_get_status more than once
// after the process has ended. See https://php.net/manual/function.proc-get-status.php
		if($this->status["running"]) {
			$this->status = proc_get_status($this->process);
		}

		return $this->status["running"];
	}

	public function getCommand():string {
		return $this->command;
	}

	public function getOutput(int $pipe = self::PIPE_OUT):string {
		if(!is_resource($this->process)) {
			throw new DaemonException("Process is not running.");
		}

		$output = "";

		while($bytes = fread($this->pipes[$pipe], 1024)) {
			$output .= $bytes;
		}

		return $output;
	}

	public function getErrorOutput():string {
		return $this->getOutput(self::PIPE_ERROR);
	}

	/** @return int|null Exit code or null if has not exited yet. */
	public function getExitCode():?int {
		if($this->isRunning()) {
			return null;
		}

		return $this->status["exitcode"];
	}

	/** @return int|null Process ID or null if it has exited. */
	public function getPid():?int {
		if(!$this->isRunning()) {
			return null;
		}

		return $this->status["pid"];
	}

	/** Closes the thread and the streams then returns the return code of the command. */
	public function close():int {
		array_filter($this->pipes, function($pipe) {
			return fclose($pipe);
		});

		return proc_close($this->process);
	}
}