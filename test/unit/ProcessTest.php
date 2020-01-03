<?php
namespace Gt\Daemon\Test;

use Gt\Daemon\CommandNotFoundException;
use Gt\Daemon\Process;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ProcessTest extends TestCase {
	protected $tmpBase;

	protected function setUp():void {
		$this->tmpBase = implode(DIRECTORY_SEPARATOR, [
			sys_get_temp_dir(),
			"phpgt",
			"test",
			"daemon",
		]);
	}

	public function tearDown():void {
		if(!is_dir($this->tmpBase)) {
			return;
		}

		$directory = new RecursiveDirectoryIterator(
			$this->tmpBase,
			RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
			| RecursiveDirectoryIterator::KEY_AS_PATHNAME
		);
		$iterator = new RecursiveIteratorIterator(
			$directory,
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach($iterator as $file) {
			/** @var SplFileInfo $file */
			if($file->getFilename() === "."
			|| $file->getFilename() === "..") {
				continue;
			}

			if($file->isFile()) {
				unlink($file->getPathname());
			}
			else {
				rmdir($file->getPathname());
			}
		}

		rmdir($this->tmpBase);
	}

	public function testExec() {
		$tmpFile = implode(DIRECTORY_SEPARATOR, [
			$this->tmpBase,
			uniqid(),
		]);
		if(!is_dir(dirname($tmpFile))) {
			mkdir(dirname($tmpFile), 0775, true);
		}
		$command = [PHP_BINARY, "-r", "touch(\"$tmpFile\");"];
		$sut = new Process($command);

		self::assertFileNotExists($tmpFile);
		$sut->exec();
		while($sut->isRunning()) {
			usleep(100000);
		}

		self::assertFileExists($tmpFile);
	}

	public function testExecFailure() {
		$sut = new Process(["/this/does/not/exist/" . uniqid()]);
		$sut->setBlocking();
		$sut->exec();
		self::assertEquals(127, $sut->getExitCode());
	}

	public function testGetCommand() {
		$rawCommand = [
			"/path/to/binary",
			"attr1key=attr1value",
			"--name='yes/no'",
		];
		$sut = new Process($rawCommand);
		$actualCommand = $sut->getCommand();

		self::assertEquals(
			$rawCommand,
			$actualCommand
		);
	}

	public function testGetOutputNotRunning() {
		self::expectExceptionMessage("Process is not running");
		$sut = new Process("echo 'test-message'");
		$sut->getOutput();
	}

	public function testGetOutput() {
		$sut = new Process(["echo", "test-message"]);
		$sut->exec();

		while($sut->isRunning()) {
			usleep(100000);
		}

		$output = $sut->getOutput();
		self::assertEquals("test-message\n", $output);
	}

	public function testExecutingNonExistantCommand() {
		$sut = new Process(["/does/not/exist"]);

		self::expectException(CommandNotFoundException::class);
		$sut->exec();
	}

	public function testGetExistCodeRunning() {
		$sut = new Process("sleep 1");
		$sut->exec();
		self::assertNull($sut->getExitCode());
	}

	public function testGetExitCodeTerminate() {
		$sut = new Process("echo 'quick'");
		$sut->exec();

		while($sut->isRunning()) {
			usleep(100000);
		}

		self::assertEquals(0, $sut->getExitCode());
	}

	public function testGetPidNotRunning() {
		$sut = new Process("echo 'not running'");
		self::assertNull($sut->getPid());
	}

	public function testGetPid() {
		$sut = new Process("sleep 1");
		$sut->exec();
		self::assertIsInt($sut->getPid());
	}

	public function testExecBlocking() {
		$sut = new Process("sleep 0.1");
		$sut->exec();
		self::assertTrue($sut->isRunning());

		$sut = new Process("sleep 0.1");
		$sut->setBlocking();
		$sut->exec();
		self::assertFalse($sut->isRunning());
	}
}