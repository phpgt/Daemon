<?php

namespace Gt\Daemon;

class Pool {
	protected $processList;

	public function __construct() {
		$this->processList = [];
	}

	public function add(string $name, Process $process):void {
		$this->processList[$name] = $process;
	}

	public function exec():void {
	}

	public function numRunning():int {

	}

	public function read():string {

	}

	public function readError():string {

	}
}