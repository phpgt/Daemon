<?php
namespace GT\Daemon\Test\Integration;

use GT\Daemon\ContinuousPool;
use GT\Daemon\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContinuousPoolTest extends TestCase {
	#[DataProvider("exitCodes")]
	public function testTick_restartsRealProcessesAndPreservesOutput(int $exitCode):void {
		$process = new Process(PHP_BINARY, "-r", 'echo getenv("DAEMON_MESSAGE"); fwrite(STDERR, "error\n"); exit((int)$argv[1]);', (string)$exitCode);
		$process->setEnv("DAEMON_MESSAGE", "output\n");
		$sut = new ContinuousPool(0);
		$sut->add("worker", $process);
		$completions = [];
		$sut->onComplete(function(Process $completed)use(&$completions):void {
			$completions[] = $completed->getExitCode();
		});

		try {
			$sut->exec();
			$this->awaitCompletion($process);
			$sut->tick();
			$this->awaitCompletion($process);
			self::assertTrue($sut->isActive());
		}
		finally {
			$sut->close();
		}

		self::assertSame([$exitCode, $exitCode], $completions);
		self::assertSame("output\noutput\n", $sut->readOutputOf("worker"));
		self::assertSame("error\nerror\n", $sut->readErrorOf("worker"));
		self::assertFalse($sut->isActive());
		$sut->tick();
		self::assertSame([$exitCode, $exitCode], $completions);
	}

	public static function exitCodes():iterable {
		yield "success" => [0];
		yield "failure" => [1];
	}

	private function awaitCompletion(Process $process):void {
		$deadline = microtime(true) + 3;
		while($process->isRunning()) {
			if(microtime(true) >= $deadline) {
				self::fail("Worker did not complete within three seconds");
			}
			usleep(1000);
		}
	}
}
