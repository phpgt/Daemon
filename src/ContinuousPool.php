<?php
namespace GT\Daemon;

use InvalidArgumentException;
use Throwable;

class ContinuousPool extends Pool {
	private bool $active = false;
	/** @var array<string, float> */
	private array $restartAt = [];
	/** @var array<string, array<int, string>> */
	private array $output = [];

	public function __construct(private readonly float $restartDelay = 1.0) {
		if(!is_finite($restartDelay) || $restartDelay < 0) {
			throw new InvalidArgumentException(
				"Restart delay must be finite and non-negative."
			);
		}
		parent::__construct();
	}

	public function add(string $name, Process $process):void {
		if($this->active) {
			throw new DaemonException(
				"Add processes before starting the continuous pool."
			);
		}
		parent::add($name, $process);
	}

	public function exec():void {
		if($this->active) {
			throw new DaemonException("Continuous pool is already active.");
		}
		$this->active = $this->processList !== [];
		$this->restartAt = [];

		foreach($this->processList as $process) {
			if(!$this->active) {
				break;
			}
			$this->start($process);
		}
	}

	public function isActive():bool {
		return $this->active;
	}

	public function tick():void {
		foreach($this->processList as $name => $process) {
			if(!$this->active) {
				return;
			}
			if($process->isRunning()) {
				continue;
			}
			// Completion callbacks may have closed the pool.
			if(!$this->isActive()) {
				return;
			}

			$this->restartAt[$name] ??= $this->now() + $this->restartDelay;
			if($this->now() < $this->restartAt[$name]) {
				continue;
			}

			$this->captureOutput($name);
			unset($this->restartAt[$name]);
			$this->start($process);
		}
	}

	public function readOutputOf(
		string $name,
		int $pipe = Process::PIPE_OUT
	):string {
		if(!array_key_exists($name, $this->processList)) {
			throw new DaemonException("No process named $name found.");
		}
		$output = $this->output[$name][$pipe] ?? "";
		if($this->active) {
			$output .= parent::readOutputOf($name, $pipe);
		}
		unset($this->output[$name][$pipe]);
		return $output;
	}

	/** @return array<string, int> */
	public function close():array {
		$this->active = false;
		$this->restartAt = [];
		foreach(array_keys($this->processList) as $name) {
			$this->captureOutput($name);
		}
		return parent::close();
	}

	/** @phpstan-impure */
	protected function now():float {
		return hrtime(true) / 1_000_000_000;
	}

	private function start(Process $process):void {
		try {
			$process->setBlocking(false);
			$process->exec();
		}
		catch(Throwable $exception) {
			$this->close();
			throw $exception;
		}
	}

	private function captureOutput(string $name):void {
		foreach([Process::PIPE_OUT, Process::PIPE_ERROR] as $pipe) {
			try {
				$this->output[$name][$pipe] = ($this->output[$name][$pipe] ?? "")
					. $this->processList[$name]->getOutput($pipe);
			}
			catch(DaemonException) {
				// Failed starts and already-closed streams have no output to drain.
				continue;
			}
		}
	}
}
