<?php
echo "This is an example long-running PHP script." . PHP_EOL;
sleep(1);
echo "It will count up from 0." . PHP_EOL;
echo "Every time it hits a 5," . PHP_EOL;
echo "it will output an error to STDERR" . PHP_EOL . PHP_EOL;;
sleep(1);

echo "Starting..." . PHP_EOL . PHP_EOL;
sleep(1);

$i = $j = 0;
while(true) {
	fwrite(STDOUT, "The number is '$i'." . PHP_EOL);
	
	if($i % 5 === 0 && $i > 0) {
		fwrite(STDERR, "An error occurred on number '$i'!" . PHP_EOL);
	}
	
	$i++;
	
	sleep(1);

	if($j++ > 20){
	    exit(9);
    }
}
