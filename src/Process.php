<?php
namespace Gt\Daemon;

class Process {
	const PIPE_IN = 0;
	const PIPE_OUT = 1;
	const PIPE_ERROR = 2;

	/** @var array<int, string> */
	protected array $command;
	protected string $cwd;
	/** @var array<int, resource> Indexed array of streams: 0=>input, 1=>output, 2=>error. */
	protected array $pipes;
	/** @var resource The process as returned from proc_open. */
	protected $process = null;
	/** @var array<string, mixed> */
	protected array $status;
	protected bool $isBlocking = false;
	/** @var array<string, string> */
	protected array $env = [];

	public function __construct(string...$command) {
		$this->command = $command;
		$this->cwd = getcwd();
		$this->pipes = [];
	}

	public function __destruct() {
		$this->terminate();
	}

	public function setExecCwd(string $cwd):void {
		$this->cwd = $cwd;
	}

	public function setEnv(string $key, string $value):void {
		$this->env[$key] = $value;
	}

	/**
	 * Runs the command in a concurrent thread.
	 * Sets the input, output and errors streams.
	 *
	 * @SuppressWarnings(PHPMD.ErrorControlOperator)
	 */
	public function exec():void {
		$descriptor = [
			self::PIPE_IN => ["pipe", "r"],
			self::PIPE_OUT => ["pipe", "w"],
			self::PIPE_ERROR => ["pipe", "w"],
		];

		$oldCwd = getcwd();
		chdir($this->cwd);

		// Parameter #1 of proc_open is an array
		// @see https://www.php.net/manual/en/function.proc-open.php
		// phpcs:ignore
		$this->process = @proc_open(
			$this->command,
			$descriptor,
			$this->pipes,
			env_vars: $this->env,
		);
		if(!$this->process) {
			throw new CommandNotFoundException($this->command[0]);
		}

		usleep(10000);

		stream_set_blocking($this->pipes[0], false);
		stream_set_blocking($this->pipes[1], false);
		stream_set_blocking($this->pipes[2], false);

		if($this->isBlocking) {
			while($this->isRunning()) {
				usleep(10000);
			}
		}

		$this->refreshStatus();

		if($this->status["exitcode"] === 127) {
			throw new CommandNotFoundException($this->command[0]);
		}

		chdir($oldCwd);
	}

	public function isRunning():bool {
		$this->refreshStatus();

		$running = $this->status["running"] ?? false;
		return (bool)$running;
	}

	public function hasNotEnded():bool {
		$this->refreshStatus();
		$running = $this->isRunning();
		if($running) {
			return true;
		}

		return $this->status["exitcode"] === 0;
	}

	/** @return array<int, string> */
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

		return $this->status["exitcode"] ?? 127;
	}

	/** @return int|null Process ID or null if it has exited. */
	public function getPid():?int {
		if(!$this->isRunning()) {
			return null;
		}

		return $this->status["pid"] ?? null;
	}

	/** Closes the thread and the streams then returns the return code of the command. */
	public function terminate(int $signal = Signal::TERM):void {
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

	/**
	 * Special care has to be taken to not call proc_get_status more than
	 * once after the process has ended.
	 * @see https://php.net/manual/function.proc-get-status.php
	 **/
	protected function refreshStatus():void {
		$running = $this->status["running"] ?? null;
		if($running || empty($this->status)) {
			if(is_resource($this->process)) {
				$this->status = proc_get_status($this->process);
			}
		}
	}
}
