Background process execution.
=============================

Execute background processes asynchronously using an object oriented process pool.

***

<a href="https://github.com/PhpGt/Daemon/actions" target="_blank">
	<img src="https://badge.status.php.gt/daemon-build.svg" alt="Build status" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Daemon" target="_blank">
	<img src="https://badge.status.php.gt/daemon-quality.svg" alt="Code quality" />
</a>
<a href="https://scrutinizer-ci.com/g/PhpGt/Daemon" target="_blank">
	<img src="https://badge.status.php.gt/daemon-coverage.svg" alt="Code coverage" />
</a>
<a href="https://packagist.org/packages/PhpGt/Daemon" target="_blank">
	<img src="https://badge.status.php.gt/daemon-version.svg" alt="Current version" />
</a>
<a href="https://www.php.gt/eaemon" target="_blank">
	<img src="https://badge.status.php.gt/daemon-docs.svg" alt="PHP.G/Daemon documentation" />
</a>

## Example usage

```php
<?php
use Gt\Daemon\Process;
use Gt\Daemon\Pool;

// Create three long-running processes:
$pingProcess = new Process("ping google.com");
$infiniteProcess = new Process("while true; do echo 'background...'; sleep 3; done");
$dateProcess = new Process("while true; do echo $(date -d now); sleep 2; done");

// Add all three processes to a pool:
$pool = new Pool();
$pool->add("Ping", $pingProcess);
$pool->add("Loop", $infiniteProcess);
$pool->add("Date", $dateProcess);

// Start the execution of all processes:
$pool->exec();

// While processes are running, write their output to the terminal:
do {
	echo $pool->read();
	// Sleep to avoid hogging the CPU.
	sleep(1);
}
while($pool->numRunning() > 0);
```

Outputs something similar to:

```
[Ping] PING google.com (172.217.169.78) 56(84) bytes of data.
[Ping] 64 bytes from lhr48s09-in-f14.1e100.net (172.217.169.78): icmp_seq=1 ttl=52 time=8.78 ms
[Loop] background...
[Date] Mon 19 Aug 09:58:54 BST 2019
[Ping] 64 bytes from lhr48s09-in-f14.1e100.net (172.217.169.78): icmp_seq=2 ttl=52 time=8.75 ms
[Ping] 64 bytes from lhr48s09-in-f14.1e100.net (172.217.169.78): icmp_seq=3 ttl=52 time=8.75 ms
[Date] Mon 19 Aug 09:58:56 BST 2019
[Ping] 64 bytes from lhr48s09-in-f14.1e100.net (172.217.169.78): icmp_seq=4 ttl=52 time=8.75 ms
[Loop] background...
[Ping] 64 bytes from lhr48s09-in-f14.1e100.net (172.217.169.78): icmp_seq=5 ttl=52 time=8.80 ms
[Date] Mon 19 Aug 09:58:58 BST 2019
```

Notice how the date process is only set to loop three times, and after it is complete the other two infinite processes continue to run.