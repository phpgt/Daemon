<?php
namespace Gt\Daemon\Test;

use Gt\Daemon\Pool;
use Gt\Daemon\Process;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase {
	public function testAddExec() {
		/** @var MockObject|Process $proc1 */
		$proc1 = self::createMock(Process::class);
		$proc1->expects($this->once())
			->method("exec");

		/** @var MockObject|Process $proc2 */
		$proc2 = self::createMock(Process::class);
		$proc2->expects($this->once())
			->method("exec");

		$sut = new Pool();
		$sut->add("test1", $proc1);
		$sut->add("test2", $proc2);
		$sut->exec();
	}

	public function testNumRunning() {
		/** @var MockObject|Process $proc1 */
		$proc1 = self::createMock(Process::class);
		$proc1->method("isRunning")
			->willReturn(false);

		/** @var MockObject|Process $proc2 */
		$proc2 = self::createMock(Process::class);
		$proc2->method("isRunning")
			->willReturn(true);

		$sut = new Pool();
		$sut->add("test1", $proc1);
		$sut->add("test2", $proc2);

		self::assertEquals(
			1,
			$sut->numRunning()
		);
	}

	public function testRead() {
		/** @var MockObject|Process $proc1 */
		$proc1 = self::createMock(Process::class);
		$proc1->method("getOutput")
		->will($this->returnCallback(function(int $pipe) {
			if($pipe === Process::PIPE_OUT) {
				return "Here is some output from proc1";
			}
			else {
				return "Here is an error from proc1";
			}
		}));

		/** @var MockObject|Process $proc2*/
		$proc2= self::createMock(Process::class);
		$proc2->method("getOutput")
		->will($this->returnCallback(function(int $pipe) {
			if($pipe === Process::PIPE_OUT) {
				return "Here is some output from proc2";
			}
			else {
				return "Here is an error from proc2";
			}
		}));

		$sut = new Pool();
		$sut->add("test1", $proc1);
		$sut->add("test2", $proc2);

		$error = $sut->read(Process::PIPE_ERROR);
		$output = $sut->read();
		self::assertStringContainsString(
			"[test1] Here is some output from proc1",
			$output
		);
		self::assertStringContainsString(
			"[test2] Here is some output from proc2",
			$output
		);

		self::assertStringContainsString(
			"[test2 ERROR] Here is an error from proc2",
			$error
		);
		self::assertStringContainsString(
			"[test1 ERROR] Here is an error from proc1",
			$error
		);
	}

	public function testReadError() {
		/** @var MockObject|Process $proc1 */
		$proc1 = self::createMock(Process::class);
		$proc1->method("getOutput")
		->will($this->returnCallback(function(int $pipe) {
			if($pipe === Process::PIPE_OUT) {
				return "Here is some output from proc1";
			}
			else {
				return "Here is an error from proc1";
			}
		}));

		/** @var MockObject|Process $proc2*/
		$proc2= self::createMock(Process::class);
		$proc2->method("getOutput")
		->will($this->returnCallback(function(int $pipe) {
			if($pipe === Process::PIPE_OUT) {
				return "Here is some output from proc2";
			}
			else {
				return "Here is an error from proc2";
			}
		}));

		$sut = new Pool();
		$sut->add("test1", $proc1);
		$sut->add("test2", $proc2);

		$error = $sut->readError();
		self::assertStringContainsString(
			"[test2 ERROR] Here is an error from proc2",
			$error
		);
		self::assertStringContainsString(
			"[test1 ERROR] Here is an error from proc1",
			$error
		);
	}

	public function testReadOutputOfNotExists() {
		/** @var MockObject|Process $proc1 */
		$proc1 = self::createMock(Process::class);
		/** @var MockObject|Process $proc2*/
		$proc2= self::createMock(Process::class);

		$sut = new Pool();
		$sut->add("test1", $proc1);
		$sut->add("test2", $proc2);

		self::expectExceptionMessage("No process named test3 found");
		$sut->readOutputOf("test3");
	}

	public function testReadOutputOf() {
		/** @var MockObject|Process $proc1 */
		$proc1 = self::createMock(Process::class);
		$proc1->method("getOutput")
			->willReturn("Output from proc1");

		/** @var MockObject|Process $proc2*/
		$proc2= self::createMock(Process::class);
		$proc2->method("getOutput")
			->willReturn("Output from proc2");

		$sut = new Pool();
		$sut->add("test1", $proc1);
		$sut->add("test2", $proc2);

		$output2 = $sut->readOutputOf("test2");
		$output1 = $sut->readOutputOf("test1");

		self::assertEquals("Output from proc1", $output1);
		self::assertEquals("Output from proc2", $output2);
	}

	public function testReadErrorOf() {
		/** @var MockObject|Process $proc1 */
		$proc1 = self::createMock(Process::class);
		$proc1->method("getOutput")
			->with(Process::PIPE_ERROR)
			->willReturn("Error from proc1");

		/** @var MockObject|Process $proc2*/
		$proc2= self::createMock(Process::class);
		$proc2->method("getOutput")
			->with(Process::PIPE_ERROR)
			->willReturn("Error from proc2");

		$sut = new Pool();
		$sut->add("test1", $proc1);
		$sut->add("test2", $proc2);

		$output2 = $sut->readErrorOf("test2");
		$output1 = $sut->readErrorOf("test1");

		self::assertEquals("Error from proc1", $output1);
		self::assertEquals("Error from proc2", $output2);
	}

	public function testClose() {
		/** @var MockObject|Process $proc1 */
		$proc1 = self::createMock(Process::class);
		$proc1->expects($this->once())
			->method("getExitCode")
			->willReturn(0);

		/** @var MockObject|Process $proc2*/
		$proc2= self::createMock(Process::class);
		$proc2->expects($this->once())
			->method("getExitCode")
			->willReturn(0);

		$sut = new Pool();
		$sut->add("test1", $proc1);
		$sut->add("test2", $proc2);

		$sut->close();
	}
}