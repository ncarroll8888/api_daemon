<?php
$now = time();
$microNow = microtime(TRUE);
$pwd = dirname(__FILE__);
echo "Bootstrapping\n";
require_once("${pwd}/settings.php");
require_once("${pwd}/db.php");
require_once("${pwd}/functions.php");
file_exists(dirname(error_log_dest)) or mkdir(dirname(error_log_dest),0755,TRUE);
file_exists(dirname(general_log_dest)) or mkdir(dirname(general_log_dest),0755,TRUE);
$bubbles = new chatInterface();
require_once("${pwd}/notificationqueue.php");
require_once("${pwd}/siphons.php");
require_once("${pwd}/towers.php");
require_once("${pwd}/towerdetail.php");
$bubbles->dumpMessages();
$runtime = (microtime(TRUE) - $microNow);
echo "Finished API run in ${runtime}s\n";
