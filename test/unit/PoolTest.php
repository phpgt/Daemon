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
}