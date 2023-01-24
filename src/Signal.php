<?php
namespace Gt\Daemon;

/**
 * Enumerator for POSIX signals
 * @see https://en.wikipedia.org/wiki/Signal_(IPC)#POSIX_signals
 */
class Signal {
	const ABRT = SIGABRT;
	const ALRM = SIGALRM;
	const BABY = SIGBABY;
	const BUS = SIGBUS;
	const CHLD = SIGCHLD;
	const CLD = SIGCLD;
	const CONT = SIGCONT;
	const FPE = SIGFPE;
	const HUP = SIGHUP;
	const ILL = SIGILL;
	const INT = SIGINT;
	const IO = SIGIO;
	const IOT = SIGIOT;
	const KILL = SIGKILL;
	const PIPE = SIGPIPE;
	const POLL = SIGPOLL;
	const PWR = SIGPWR;
	const QUIT = SIGQUIT;
	const URG = SIGURG;
	const USR1 = SIGUSR1;
	const USR2 = SIGUSR2;
	const SEGV = SIGSEGV;
	const STKFLT = SIGSTKFLT;
	const STOP = SIGSTOP;
	const SYS = SIGSYS;
	const TSTP = SIGTSTP;
	const TERM = SIGTERM;
	const TTIN = SIGTTIN;
	const TTOU = SIGTTOU;
	const TRAP = SIGTRAP;
	const WINCH = SIGWINCH;
	const XCPU = SIGXCPU;
	const XFSZ = SIGXFSZ;
}