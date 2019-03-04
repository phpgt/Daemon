<?php
define("CODE_START_OF_HEADING", "\u{0001}");
define("CODE_START_OF_TEXT", "\u{0002}");

$socket = stream_socket_server(
	"tcp://127.0.0.1:0",
	$errno,
	$errstr
);

if(!$socket) {
	die("Error: $errstr ($errno)\n");
}

$name = stream_socket_get_name($socket, false);
$process = proc_open(
// TODO: Brackets may need to be braces in bash to create sub-shell.
	"( php counter.php 2>&3 | php client.php out $name; ) 3>&1 1>&2 | php client.php err $name;",
	[],
	$pipes
);

if(!$process) {
	die("Process failed to start." . PHP_EOL);
}

socket_set_blocking($socket, false);

$streams = [];
$numStreams = 2;
for($i = 0; $i < $numStreams; $i++) {
	$s = stream_socket_accept($socket, 1);
	stream_set_blocking($s, false);
	$streams []= $s;
}

while(!feof($streams[0]) && !feof($streams[1])) {
	foreach($streams as $s) {
		$outPipe = null;
		$transmission = fread($s, 2048);
		$msg = "";

		if(strlen($transmission) === 0) {
			continue;
		}

		if($transmission[0] === CODE_START_OF_HEADING) {
			$outPipe = "";
			$i = 1;

			while(strlen($transmission) >= $i
			&& CODE_START_OF_TEXT !== ($char = $transmission[$i])) {
				$outPipe .= $char;
				$i++;
			}

			$msg = substr($transmission, $i + 1);
		}
		else {
			$msg = $transmission;
		}

		switch($outPipe) {
		case "out":
			fwrite(STDOUT, $msg);
			break;

		case "err":
			fwrite(STDERR, $msg);
			break;

		default:
			echo "$outPipe: $msg";
		}
	}

	usleep(10000);
}
fclose($streams[0]);
fclose($streams[1]);
fclose($socket);