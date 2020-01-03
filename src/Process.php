<?php
namespace Gt\Daemon;

class Process {
	const PIPE_IN = 0;
	const PIPE_OUT = 1;
	const PIPE_ERROR = 2;

	/** @var string[] */
	protected $command;
	/** @var string */
	protected $cwd;
	/** @var array Indexed array of streams: 0=>input, 1=>output, 2=>error. */
	protected $pipes;
	/** @var resource The process as returned from proc_open. */
	protected $process = null;
	protected $status;
	/** @var bool */
	protected $isBlocking = false;

	/** @param string[] $command List of arguments to execute */
	public function __construct(array $command, string $cwd = null) {
		$this->command = $command;

		if(is_null($cwd)) {
			$cwd = getcwd();
		}

		$this->cwd = $cwd;
	}

	public function __destruct() {
		$this->terminate();
	}

	/**
	 * Runs the command in a concurrent thread.
	 * Sets the input, output and errors streams.
	 */
	public function exec() {
		$descriptor = [
			self::PIPE_IN => ["pipe", "r"],
			self::PIPE_OUT => ["pipe", "w"],
			self::PIPE_ERROR => ["pipe", "w"],
		];

		$oldCwd = getcwd();
		chdir($this->cwd);

		$this->process = proc_open(
			$this->command,
			$descriptor,
			$this->pipes
		);

		$this->status = proc_get_status($this->process);

		if($this->status["exitcode"] === 127) {
			throw new CommandNotFoundException($this->command[0]);
		}

		stream_set_blocking($this->pipes[1], 0);
		stream_set_blocking($this->pipes[2], 0);
		stream_set_blocking($this->pipes[0], 0);

		if($this->isBlocking) {
			while($this->isRunning()) {
				usleep(10000);
			}
		}

		chdir($oldCwd);
	}

	public function isRunning():bool {
// Special care has to be taken to not call proc_get_status more than once
// after the process has ended. See https://php.net/manual/function.proc-get-status.php
		if($this->status["running"] ?? null) {
			$this->status = proc_get_status($this->process);
		}

		$running = $this->status["running"] ?? false;
		return (bool)$running;
	}

	public function getCommand():array {
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

		return $this->status["pid"] ?? null;
	}

	/** Closes the thread and the streams then returns the return code of the command. */
	public function terminate(int $signal = 15):void {
		if(!is_resource($this->process)) {
			return;
		}

		proc_terminate($this->process, $signal);

		foreach($this->pipes as $i => $pipe) {
			fclose($pipe);
			unset($this->pipes[$i]);
		}
	}

	public function setBlocking(bool $blocking = true):void {
		$this->isBlocking = $blocking;
	}
}