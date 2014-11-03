<?php
$now = time();
$towerDetailKeys = apiKeyGrabber::corp('towers',$db);;
$towerMailRcpts = apiKeyGrabber::rcpts('towers',$db);
$towerDetailUrl = "/corp/StarbaseDetail.xml.aspx";
$mail=FALSE;

//Let's do this first, but be silent about it. We've already notified about dead towers.
$cleanup = "DELETE FROM details WHERE itemid NOT IN (SELECT itemid from location)";
$db->query($cleanup);


foreach($towerDetailKeys as $apiChar => $apiKey) {
    $chatChannel = "towerupdates.${apiChar}";
    $bubbles->newChannel($chatChannel,"Updated control tower states","The status of the following control towers has changed:");
    $towerDetailGrabber = new apiGrabber();
    $getTowerLocations = "SELECT itemid,moonid,typeid FROM location WHERE apiid = " . $apiKey['id'];
    $towerLocations = $db->query($getTowerLocations);
    while ($location = $towerLocations->fetch_array()) {
	$typeID = $location['typeid'];
	$moonID = $location['moonid'];
	$extra = "itemID=" . $location['itemid'];
	if ($towerDetailXml = $towerDetailGrabber->grabArray($towerDetailUrl,$apiKey['id'],$apiKey['key'],$db,$extra)) {
	    foreach ($towerDetailXml as $valName => $valArray) {
		if (isset($valArray['tag'])) {
		    if ($valArray['tag'] == 'STATE') {
			$towerState = $valArray['value'];
		    } elseif ($valArray['tag']  == 'STATETIMESTAMP') {
			$towerTimer = strtotime($valArray['value']);
		    }
		}
	    }
	    $towerInfoQuery = "select (select typeName from ${staticDatabase}.invTypes where typeID=${typeID}) as towerName," .
				 "(select itemName from ${staticDatabase}.mapDenormalize where itemID=${moonID}) as moonName";
	    $towerInfoResult = $db->query($towerInfoQuery);
	    $towerInfo = $towerInfoResult->fetch_array();
	    $moonName = $towerInfo['moonName'];
	    $towerName = $towerInfo['towerName'];
	    switch($towerState) {
		case '0':
		    $towerStateName = 'Unanchored ';
		    break;
		case '1':
		    $towerStateName = 'Anchored ';
		    break;
		case '2':
		    $towerStateName = 'Onlining ';
		    break;
		case '3':
		    $towerStateName = 'Reinforced ';
		    break;
		case '4':
		    $towerStateName = 'Online ';
		    break;
	    }
	    $getCurrentTowerState = "SELECT state from details where itemid = " . $location['itemid'];
	    $currentTowerResult = $db->query($getCurrentTowerState);
	    if ($currentTowerResult->num_rows == 0) {
		$insertDetails = "INSERT INTO details (itemid,state,timer,lastupdate,typeid) VALUES (" . $location['itemid'] . ",${towerState},${towerTimer},${now},${typeID})";
		if ($db->query($insertDetails)) {
		    $bubbles->newMessage("Added state details for $towerStateName${towerName} at ${moonName}",$chatChannel);
		}
	    } else {
		$currentTowerState = $currentTowerResult->fetch_array();
		$updateQuery = "UPDATE details SET state=${towerState}, lastupdate=${now}, timer=${towerTimer} where itemid = " . $location['itemid'];
		if ($towerState <> $currentTowerState['state']) {
		    $bubbles->newMessage("Changed ${towerName} at ${moonName} to state ${towerStateName}",$chatChannel);
		}
		$db->query($updateQuery);
	    }
	}
    }
    unset($towerDetailGrabber);
}
$bubbles->newChannel("reinforced","Reinforcement detector","The following towers are in reinforced mode");
$reinforcedTowerQuery = "SELECT ${staticDatabase}.mapDenormalize.itemName as moonName, " .
		    "FROM_UNIXTIME(details.timer) as strontTimer, " .
		    "${staticDatabase}.invTypes.typeName as towerName " . 
		    "FROM details INNER JOIN location using (itemid) " .
		    "INNER JOIN ${staticDatabase}.mapDenormalize on (location.moonid = ${staticDatabase}.mapDenormalize.itemID) " .
		    "INNER JOIN ${staticDatabase}.invTypes on (location.typeid = ${staticDatabase}.invTypes.typeID) " .
		    "WHERE details.state = 3;";
$reinforcedResult = $db->query($reinforcedTowerQuery);
while ($reinforcedTower = $reinforcedResult->fetch_array()) {
    $reinforcedType = $reinforcedTower['towerName'];
    $reinforcedUntil = $reinforcedTower['strontTimer'];
    $reinforcedMoon = $reinforcedTower['moonName'];
    $bubbles->newMessage("$reinforcedType at $reinforcedMoon is in reinforced mode until $reinforcedUntil" ,'reinforced');
    $mail = TRUE;
}
/*
if ($mail) {
    foreach ($mailRcpts as $rcpt) {
	mail($rcpt, "VSO - Control Tower State Updates", $chatMessage, "From: Bubbles <bubbles@violentsociety.org>","-fbubbles@violentsociety.org");
    }
}
*/
