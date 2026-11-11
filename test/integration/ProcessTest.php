<?php
namespace GT\Daemon\Test\Integration;

use GT\Daemon\DaemonException;
use GT\Daemon\Process;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase {
	public function testExec_reusesCompletedProcessesAndDispatchesEachCompletionOnce():void {
		$sut = new Process(PHP_BINARY, "-r", 'echo getenv("DAEMON_RUN");');
		$sut->setEnv("DAEMON_RUN", "first");
		$completions = [];
		$sut->onComplete(function(Process $completed)use(&$completions):void {
			$completions[] = $completed->getExitCode();
		});

		try {
			$sut->exec();
			$this->awaitCompletion($sut);
			self::assertSame("first", $sut->getOutput());
			$sut->setEnv("DAEMON_RUN", "second");
			$sut->exec();
			$this->awaitCompletion($sut);

			self::assertSame("second", $sut->getOutput());
			self::assertSame([0, 0], $completions);
			self::assertFalse($sut->isRunning());
			self::assertSame([0, 0], $completions);
		}
		finally {
			$sut->terminate();
		}
	}

	public function testOnComplete_lateSubscriptionsAlsoObserveFutureExecutions():void {
		$sut = new Process(PHP_BINARY, "-r", "echo '0';");
		$sut->exec();
		$this->awaitCompletion($sut);
		$completions = [];

		$sut->onComplete(function(Process $completed)use(&$completions):void {
			$completions[] = $completed->getExitCode();
		});

		try {
			self::assertSame([0], $completions);
			self::assertSame("0", $sut->getOutput());
			$sut->exec();
			$this->awaitCompletion($sut);
			self::assertSame([0, 0], $completions);
			self::assertSame("0", $sut->getOutput());
		}
		finally {
			$sut->terminate();
		}
	}

	public function testExec_rejectsRestartingARunningProcess():void {
		$sut = new Process(PHP_BINARY, "-r", 'while(true) { usleep(1000); }');
		$sut->exec();

		try {
			$this->expectException(DaemonException::class);
			$this->expectExceptionMessage("already running");
			$sut->exec();
		}
		finally {
			$sut->terminate();
		}
	}

	private function awaitCompletion(Process $process):void {
		$deadline = microtime(true) + 3;
		while($process->isRunning()) {
			if(microtime(true) >= $deadline) {
				self::fail("Process did not finish within three seconds");
			}
			usleep(1000);
		}
	}
}
