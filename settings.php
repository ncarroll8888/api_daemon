<?php
$database = array (
    'host' => 'db-rw',
    'user' => 'apidata',
    'password' => 'iGh6air5Phoh',
    'db' => 'apidata'
);
$staticDatabase = 'rubicon';
$logLevel = 5; //Hilariously verbose
define('api_url','https://api.eveonline.com');
define('chat_url','http://localhost:8080/hubot/say');
define('chatroom',urlencode("corp@conference.chat.violentsociety.org"));
define('general_log_dest',"${pwd}/logs/general.log");
define('error_log_dest',"${pwd}/logs/error.log");
?>
