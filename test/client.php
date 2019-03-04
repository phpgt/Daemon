<?php
$socket = null;
define("CODE_START_OF_HEADING", "\u{0001}");
define("CODE_START_OF_TEXT", "\u{0002}");

$streamName = "$argv[1]";

if(isset($argv[2])) {
	$socket = stream_socket_client(
		"tcp://" . $argv[2],
		$errno,
		$errstr,
		30
	);
}

$in = STDIN;
$out = STDOUT;

if($socket) {
	$out = $socket;
}

while(!feof($in)) {
	$msg = fread($in, 1024);

	if($msg) {
		fwrite($out, CODE_START_OF_HEADING);
		fwrite($out, $streamName);
		fwrite($out, CODE_START_OF_TEXT);
		fwrite($out, $msg);
	}

	usleep(10000);
}

if(is_resource($socket)) {
	fclose($socket);
}
