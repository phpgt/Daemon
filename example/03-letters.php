<?php
echo "This is an example long-running PHP script." . PHP_EOL;
sleep(1);
echo "It will say the alphabet." . PHP_EOL;
echo "Every time it hits a vowel," . PHP_EOL;
echo "it will output an error to STDERR" . PHP_EOL . PHP_EOL;;
sleep(1);

echo "Starting..." . PHP_EOL . PHP_EOL;
sleep(1);

$i = null; $j = 0 ;
while(true) {
	if($i > "Z" || is_null($i)) {
		$i = "A";
	}

    fwrite(STDOUT, "The letter is '$i'." . PHP_EOL);

    if(in_array($i, ["A", "E", "I", "O", "U"])) {
        fwrite(STDERR, "An error occurred on letter '$i'!" . PHP_EOL);
    }

	
	$i++;
	if($j++ > 25 ){
	    exit(0);
    }

	sleep(1);
}
