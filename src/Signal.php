<?php
namespace GT\Daemon;

/**
 * Enumerator for POSIX signals
 * @see https://en.wikipedia.org/wiki/Signal_(IPC)#POSIX_signals
 */
class Signal {
	const ABRT = 6;
	const ALRM = 14;
	const BABY = 31;
	const BUS = 7;
	const CHLD = 17;
	const CLD = 17;
	const CONT = 18;
	const FPE = 8;
	const HUP = 1;
	const ILL = 4;
	const INT = 2;
	const IO = 29;
	const IOT = 6;
	const KILL = 9;
	const PIPE = 13;
	const POLL = 29;
	const PWR = 30;
	const QUIT = 3;
	const URG = 23;
	const USR1 = 10;
	const USR2 = 12;
	const SEGV = 11;
	const STKFLT = 16;
	const STOP = 19;
	const SYS = 31;
	const TSTP = 20;
	const TERM = 15;
	const TTIN = 21;
	const TTOU = 22;
	const TRAP = 5;
	const WINCH = 28;
	const XCPU = 24;
	const XFSZ = 25;
}
