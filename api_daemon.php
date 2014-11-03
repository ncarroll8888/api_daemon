#!/usr/bin/php
<?php
if (posix_getuid() == 0) {
    echo "Don't run me as root.\n";
}
declare(ticks = 1);
$_SERVER['PATH'] = "/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
$stdout = fopen('php://stdout','a');
ignore_user_abort(true);
class daemon {
    function __construct() {
	$this->init();
	while ($this->run) {
	    echo shell_exec($this->command);
	    sleep($this->sleep);
	    $this->iterations++;
	}
    }
    private function init() {
	$this->pid = getmypid();
	$this->iterations = 0;
	$this->run = TRUE;
	$this->sleep = 15;
	pcntl_signal(SIGUSR1,array($this,'dumpStatus'));
	pcntl_signal(SIGHUP,array($this,'cleanStop'));
	pcntl_signal(SIGINT,array($this,'cleanStop'));
	$this->command = "/usr/bin/php " . dirname(__FILE__) . "/api_common.php";
	$this->descriptorspec = array(
	    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	    2 => array("pipe", "a") // stderr is a file to write to
	);
	echo $this->pid;
    }
    private function cleanStop ($killSignal) {
	if ($this->run) {
	    $this->run = FALSE;
	    echo "Caught ${killSignal} - Finishing up current iteration then shutting down\n";
	} else {
	    "Caught another ${killSignal} - Shutting down immediately\n";
	    exit(0);
	}
    }
    private function dumpStatus () {
	echo "Running with PID: " . strval($this->pid) . "\nPolls complete: " . strval($this->iterations) . "\n";
    }
}
$ricky = new daemon;
