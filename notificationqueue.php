<?php
$notificationKeys = apiKeyGrabber::char('notifications',$db);
$mailRcpts = apiKeyGrabber::rcpts('notifications',$db);
$mail = FALSE;
$mailSubject = "EVE - Notifications";
$mailBody = "Notifications pulled from eve API\n";
$notificationUrl = "/char/Notifications.xml.aspx";
foreach($notificationKeys as $keyName => $apiKey) {
    $notifies = new apiGrabber();
    $notifyCounter = new notifyCounter();
    $possibleKeys = array_keys($notifyCounter->alertCounters);
    $channel = "notify ${keyName}";
    $bubbles->newChannel($channel,"Notifications from ${keyName}",'Important messages in notification queue');
    $extra = "characterID=" . $apiKey['charid'];
    if ($xmlArray = $notifies->grabArray($notificationUrl,$apiKey['id'],$apiKey['key'],$db,$extra)) {
	$now = time();
	foreach($xmlArray as $notificationOutput) {
	    if ($notificationOutput['tag'] == 'ROW') {
		$notificationID = intval($notificationOutput['attributes']['TYPEID']);
		$notificationTime = strtotime($notificationOutput['attributes']['SENTDATE']);
		$age = ($now - $notificationTime);
		$inarray = in_array($notificationID,$possibleKeys);
		if (($age <= 1830) && ($inarray))  {
		    $notifyCounter->increment($notificationID);
	    	}
	    }
	}
	$countedNotifications = $notifyCounter->dump();
	$mailBody .= "\nNotifications for ${keyName}\n";
	foreach ($countedNotifications as $notificationDetail) {
	    $mail = TRUE;
	    $message = $notificationDetail['description'] . ': ' . $notificationDetail['count'];
	    $bubbles->newMessage($message,$channel);
	    $mailBody .= "${message}\n";
	}
    } else {
	//Some sort of logging to indicate cache hasn't yet expired or the API call failed.
    }
    unset($notifies);  
    unset($notifyCounter);
}
if ($mail) {
    foreach ($mailRcpts as $rcpt) {
	mail($rcpt, $mailSubject, $mailBody, "From: Bubbles <bubbles@violentsociety.org>","-fbubbles@violentsociety.org");
    }
}
?>
