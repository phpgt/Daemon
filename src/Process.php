<?php
namespace Gt\Daemon;

class Process {
	public function __construct(
		string $command,
		array $descriptorspec = null,
		array &$pipes = null,
		string $cwd = null,
		string $env = null
	) {
	}

	public function isRunning():bool {

	}

	public function readOut():string {

	}

	public function readError():string {

	}

	public function write(string $input):int {

	}

	/** @return resource */
	public function getInStream() {

	}

	/** @return resource */
	public function getOutStream() {

	}

	/** @return resource */
	public function getErrorStream() {

	}
}