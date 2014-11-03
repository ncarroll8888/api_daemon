<?php
$towerKeys = apiKeyGrabber::corp("towers",$db);
$now = time();
$towerUrl="/corp/StarbaseList.xml.aspx";
foreach($towerKeys as $towerKeyLabel => $towerKey) {
    $chatChannel = "tower." . $towerKey['id'];
    $bubbles->newChannel($chatChannel,"Tower additions for ${towerKeyLabel}","The following towers have been added");
    $towerGrabber = new apiGrabber();
    if ($towerArray = $towerGrabber->grabArray($towerUrl,$towerKey['id'],$towerKey['key'],$db)) {
	foreach($towerArray as $towerID => $towerData) {
	    if ((isset($towerData['tag'])) && ($towerData['tag'] == 'ROW')) {
		$itemID = $towerData['attributes']['ITEMID'];
		$typeID = $towerData['attributes']['TYPEID'];
		$moonID = $towerData['attributes']['MOONID'];
		$selectMoonQuery = "SELECT '1' FROM location WHERE moonID=${moonID}";
		$result = $db->query($selectMoonQuery);
		if ($result->num_rows == 0) {
		    $updateMoonQuery = "INSERT INTO location(itemid,typeid,moonid,lastupdate,apiid) VALUES(${itemID},${typeID},${moonID},${now}," . $towerKey['id'] . ")";
		    if ( $db->query($updateMoonQuery)) {
			$newTowerInfoQuery = "select (select typeName from ${staticDatabase}.invTypes where typeID=${typeID}) as towerName," .
					"(select itemName from ${staticDatabase}.mapDenormalize where itemID=${moonID}) as moonName";
			$towerInfoResult = $db->query($newTowerInfoQuery);
			$towerInfoArray = $towerInfoResult->fetch_array();
			$bubbles->newMessage("Added " . $towerInfoArray['towerName'] . " at " . $towerInfoArray['moonName'],$chatChannel);
		    }
		} else {
		    $updateMoonQuery = "UPDATE location SET itemid=${itemID},typeid=${typeID},lastupdate=${now},apiid=" . $towerKey['id'] . " WHERE moonid=${moonID}";
		    $db->query($updateMoonQuery);
		}
	    }
	}
    }
    unset($towerGrabber);
}

//TODO add a checker for towers that no longer exist
$bubbles->newChannel("oldtowers","Tower deletions","The following towers have not been queried from the API for more than 12 hours and have been deleted");
$getOldTowersQuery = "SELECT moonid,
			     ${staticDatabase}.mapDenormalize.itemName as moonName,
			     ${staticDatabase}.invTypes.typeName as towerName
			     FROM location
			     INNER JOIN ${staticDatabase}.mapDenormalize on (${staticDatabase}.mapDenormalize.itemID = moonid)
			     INNER JOIN ${staticDatabase}.invTypes on (${staticDatabase}.invTypes.typeID = location.typeid)
			     WHERE (${now} - lastupdate) > 43140"; //30 seconds of grace
$oldTowersResult = $db->query($getOldTowersQuery);
while ( $oldTower = $oldTowersResult->fetch_array() ) {
    $deleteOldTowerQuery = "DELETE FROM location WHERE moonid = " . $oldTower['moonid'];
    if ($db->query($deleteOldTowerQuery)) {
	$bubbles->newMessage("\nDeleted" . $oldTower['towerName'] . " from " . $oldTower['moonName'],"oldtowers");
    }
}

?>
