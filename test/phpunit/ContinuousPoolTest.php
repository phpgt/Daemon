<?php
namespace GT\Daemon\Test;

use GT\Daemon\CommandNotFoundException;
use GT\Daemon\ContinuousPool;
use GT\Daemon\DaemonException;
use GT\Daemon\Process;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContinuousPoolTest extends TestCase {
	public function testExec_usesNonBlockingProcessesForPolling():void {
		$process = $this->createMock(Process::class);
		$process->expects(self::once())->method("setBlocking")->with(false);
		$sut = new ContinuousPool();

		$sut->add("worker", $process);
		$sut->exec();
	}

	public function testTick_waitsWithoutBlockingOrRestartingRunningProcesses():void {
		$process = $this->createMock(Process::class);
		$process->expects(self::exactly(2))->method("exec");
		$process->method("isRunning")->willReturn(false);
		$sut = $this->poolWithClock(1.0);
		$sut->add("worker", $process);
		$sut->exec();

		$sut->tick();
		self::assertTrue($sut->isActive());
		self::assertSame(0, $sut->numRunning());
		$sut->time = 0.99;
		$sut->tick();
		$sut->time = 1.0;
		$sut->tick();
		$sut->tick();
	}

	public function testTick_leavesRunningProcessesAlone():void {
		$process = $this->createMock(Process::class);
		$process->expects(self::once())->method("exec");
		$process->method("isRunning")->willReturn(true);
		$sut = new ContinuousPool(0);
		$sut->add("worker", $process);
		$sut->exec();

		$sut->tick();
		$sut->tick();
		self::assertSame(1, $sut->numRunning());
	}

	public function testTick_zeroDelayRestartsAtMostOncePerTick():void {
		$process = $this->createMock(Process::class);
		$process->expects(self::exactly(3))->method("exec");
		$process->method("isRunning")->willReturn(false);
		$sut = new ContinuousPool(0);
		$sut->add("worker", $process);
		$sut->exec();

		$sut->tick();
		$sut->tick();
	}

	public function testClose_cancelsPendingRestarts():void {
		$process = $this->createMock(Process::class);
		$process->expects(self::once())->method("exec");
		$process->expects(self::once())->method("terminate");
		$process->method("isRunning")->willReturn(false);
		$process->method("getExitCode")->willReturn(0);
		$sut = $this->poolWithClock(5);
		$sut->add("worker", $process);
		$sut->exec();
		$sut->tick();

		self::assertSame(["worker" => 0], $sut->close());
		$sut->time = 100;
		$sut->tick();
		self::assertFalse($sut->isActive());
	}

	public function testRead_preservesUnreadOutputAcrossRestarts():void {
		$outputs = [Process::PIPE_OUT => ["old output\n", "new output\n"], Process::PIPE_ERROR => ["old error\n", "new error\n"]];
		$process = $this->createMock(Process::class);
		$process->method("isRunning")->willReturn(false);
		$process->method("getOutput")->willReturnCallback(function(int $pipe)use(&$outputs):string {
			return array_shift($outputs[$pipe]) ?? "";
		});
		$sut = new ContinuousPool(0);
		$sut->add("worker", $process);
		$sut->exec();
		$sut->tick();

		self::assertSame("[worker] old output\n[worker] new output\n", $sut->read());
		self::assertSame("[worker *] old error\n[worker *] new error\n", $sut->readError());
		self::assertSame("", $sut->readOutputOf("worker"));
		self::assertSame("", $sut->readErrorOf("worker"));
	}

	public function testTick_completionCanCloseThePoolBeforeRestart():void {
		$sut = new ContinuousPool(0);
		$notify = null;
		$process = $this->createMock(Process::class);
		$process->expects(self::once())->method("exec");
		$process->method("getExitCode")->willReturn(0);
		$process->method("onComplete")->willReturnCallback(function(callable $callback)use(&$notify):void {
			$notify = $callback;
		});
		$process->method("isRunning")->willReturnCallback(function()use(&$notify, $process):bool {
			$notify($process);
			return false;
		});
		$sut->add("worker", $process);
		$sut->onComplete(function()use($sut):void {
			$sut->close();
		});
		$sut->exec();

		$sut->tick();

		self::assertFalse($sut->isActive());
	}

	public function testTick_workersHaveIndependentRestartDeadlines():void {
		$firstStarts = 0;
		$secondStarts = 0;
		$secondRunning = true;
		$first = $this->createMock(Process::class);
		$first->method("isRunning")->willReturn(false);
		$first->method("exec")->willReturnCallback(function()use(&$firstStarts):void {
			$firstStarts++;
		});
		$second = $this->createMock(Process::class);
		$second->method("isRunning")->willReturnCallback(function()use(&$secondRunning):bool {
			return $secondRunning;
		});
		$second->method("exec")->willReturnCallback(function()use(&$secondStarts):void {
			$secondStarts++;
		});
		$sut = $this->poolWithClock(1);
		$sut->add("first", $first);
		$sut->add("second", $second);
		$sut->exec();
		$sut->tick();
		$secondRunning = false;
		$sut->time = 0.5;
		$sut->tick();
		$sut->time = 1;
		$sut->tick();

		self::assertSame(2, $firstStarts);
		self::assertSame(1, $secondStarts);
		$sut->time = 1.5;
		$sut->tick();
		self::assertSame(2, $secondStarts);
	}

	public function testExec_completionCanStopBeforeOtherWorkersStart():void {
		$sut = new ContinuousPool(0);
		$notify = null;
		$first = $this->createMock(Process::class);
		$first->method("onComplete")->willReturnCallback(function(callable $callback)use(&$notify):void {
			$notify = $callback;
		});
		$first->expects(self::once())->method("exec")->willReturnCallback(function()use(&$notify, $first):void {
			$notify($first);
		});
		$first->method("getExitCode")->willReturn(0);
		$second = $this->createMock(Process::class);
		$second->expects(self::never())->method("exec");
		$second->method("getExitCode")->willReturn(127);
		$sut->add("first", $first);
		$sut->add("second", $second);
		$sut->onComplete(function()use($sut):void {
			$sut->close();
		});

		$sut->exec();

		self::assertFalse($sut->isActive());
	}

	public function testExec_emptyPoolIsInactive():void {
		$sut = new ContinuousPool();
		$sut->exec();
		$sut->tick();
		self::assertFalse($sut->isActive());
	}

	public function testExec_launchFailureStopsThePoolAndPropagates():void {
		$process = $this->createMock(Process::class);
		$process->method("exec")->willThrowException(new CommandNotFoundException("missing"));
		$process->method("getOutput")->willThrowException(new DaemonException("Process is not running."));
		$process->method("getExitCode")->willReturn(127);
		$process->expects(self::once())->method("terminate");
		$sut = new ContinuousPool();
		$sut->add("worker", $process);

		try {
			$sut->exec();
			self::fail("Expected the launch failure");
		}
		catch(CommandNotFoundException $exception) {
			self::assertSame("missing", $exception->getMessage());
			self::assertFalse($sut->isActive());
		}
	}

	public function testTick_restartFailureStopsThePool():void {
		$starts = 0;
		$process = $this->createMock(Process::class);
		$process->method("exec")->willReturnCallback(function()use(&$starts):void {
			if(++$starts === 2) {
				throw new CommandNotFoundException("removed-worker");
			}
		});
		$process->method("isRunning")->willReturn(false);
		$process->method("getExitCode")->willReturn(0);
		$sut = new ContinuousPool(0);
		$sut->add("worker", $process);
		$sut->exec();

		try {
			$sut->tick();
			self::fail("Expected the restart failure");
		}
		catch(CommandNotFoundException $exception) {
			self::assertSame("removed-worker", $exception->getMessage());
			self::assertFalse($sut->isActive());
		}
	}

	public function testExec_rejectsAnAlreadyActivePool():void {
		$sut = new ContinuousPool();
		$sut->add("worker", $this->createMock(Process::class));
		$sut->exec();
		$this->expectException(DaemonException::class);
		$sut->exec();
	}

	public function testAdd_rejectsChangesToAnActivePool():void {
		$sut = new ContinuousPool();
		$sut->add("worker", $this->createMock(Process::class));
		$sut->exec();
		$this->expectException(DaemonException::class);
		$sut->add("other", $this->createMock(Process::class));
	}

	public function testReadOutputOf_rejectsUnknownNames():void {
		$sut = new ContinuousPool();
		$this->expectException(DaemonException::class);
		$sut->readOutputOf("unknown");
	}

	#[DataProvider("invalidDelays")]
	public function testConstruct_rejectsInvalidDelays(float $delay):void {
		$this->expectException(InvalidArgumentException::class);
		$sut = new ContinuousPool($delay);
	}

	public static function invalidDelays():iterable {
		yield [-1];
		yield [INF];
		yield [NAN];
	}

	private function poolWithClock(float $delay):ContinuousPool {
		return new class($delay) extends ContinuousPool {
			public float $time = 0;
			protected function now():float {
				return $this->time;
			}
		};
	}
}
