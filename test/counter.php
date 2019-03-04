<?php
for($i = 0; $i < 10; $i++) {
	fwrite(STDOUT, "The number is $i" . PHP_EOL);

	if($i === 5) {
		fwrite(STDERR, "OH NO, AN ERROR!" . PHP_EOL);
	}

	usleep(500000);
}