Background process execution.
=============================

Execute background processes asynchronously using an object oriented process pool.

***

<a href="https://circleci.com/gh/PhpGt/Daemon" target="_blank">
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
$infiniteProcess = new Process("while true; do echo 'background...'; sleep 1; done");
$dateProcess = new Process("watch date -d now");

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
[Ping] PING google.com (172.217.169.14) 56(84) bytes of data.
[Ping] 64 bytes from lhr25s26-in-f14.1e100.net (172.217.169.14): icmp_seq=1 ttl=54 time=27.2 ms
[Loop] background...
[Date] Fri 5 Apr 14:34:12 BST 2019
[Ping] 64 bytes from lhr25s26-in-f14.1e100.net (172.217.169.14): icmp_seq=2 ttl=54 time=17.7 ms
[Loop] background...
[Date] Fri 5 Apr 14:34:13 BST 2019
[Ping] 64 bytes from lhr25s26-in-f14.1e100.net (172.217.169.14): icmp_seq=3 ttl=54 time=14.4 ms
[Loop] background...
[Date] Fri 5 Apr 14:34:14 BST 2019
[Ping] 64 bytes from lhr25s26-in-f14.1e100.net (172.217.169.14): icmp_seq=4 ttl=54 time=24.9 ms
[Loop] background...
[Ping] 64 bytes from lhr25s26-in-f14.1e100.net (172.217.169.14): icmp_seq=5 ttl=54 time=13.9 ms
[Loop] background...
[Ping] 64 bytes from lhr25s26-in-f14.1e100.net (172.217.169.14): icmp_seq=6 ttl=54 time=15.8 ms
[Loop] background...
```

Notice how the date process is only set to loop three times, and after it is complete the other two infinite processes continue to run.