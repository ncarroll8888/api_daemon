<?php
$pwd = dirname(__FILE__);
require_once("${pwd}/settings.php");
require_once("${pwd}/db.php");
require_once("${pwd}/functions.php");
$bubbles = new chatInterface();
$staticDatabase = 'rubicon';
require_once("${pwd}/notificationqueue.php");
require_once("${pwd}/siphons.php");
require_once("${pwd}/towers.php");
require_once("${pwd}/towerdetail.php");
$bubbles->dumpMessages();
