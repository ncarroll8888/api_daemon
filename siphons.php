<?php
$allSilos = array ();
$siphonApiKeys = apiKeyGrabber::corp('siphon',$db);

//Curl stuff

$siloUrl = "/corp/AssetList.xml.aspx";
foreach($siphonApiKeys as $label => $apiKey) {
    $chatChannel = "siphon." . $apiKey['id'];
    $bubbles->newChannel($chatChannel, "Siphon Detector - ${label}", "Problems detected with the following silos:");
    $siphonGrabber = new apiGrabber();
    if ($assetArray = $siphonGrabber->grabArray($siloUrl,$apiKey['id'],$apiKey['key'],$db)) {
	foreach($assetArray as $xmlId => $assetDetails) {
	    if (
		($assetDetails['tag'] == 'ROW') &&
		(isset($assetDetails['attributes'])) &&
		($assetDetails['attributes']['TYPEID'] == 14343) &&
		($assetDetails['attributes']['SINGLETON'] == 1)
	    ) {
		$siloContentsId = ($xmlId + 2);
		$siloId = $assetDetails['attributes']['ITEMID'];
		$siloQuantity = $assetArray[$siloContentsId]['attributes']['QUANTITY'];
		$siloSolarSystem =  $assetDetails['attributes']['LOCATIONID'];
		$getSolarSystem = "SELECT solarSystemName from ${staticDatabase}.mapSolarSystems where solarSystemID = ${siloSolarSystem}";
		$solarSystemResult = $db->query($getSolarSystem);
		$solarSystemArray = $solarSystemResult->fetch_assoc();
		$solarSystemName = $solarSystemArray['solarSystemName'];
		$insertSilo = "INSERT IGNORE INTO silos (itemID,quantity,lastupdate) VALUES(${siloId},${siloQuantity}," . time() . ")";
		$db->query($insertSilo);
		if ($db->affected_rows > 0) {
		    $message = "Added silo with ID ${siloId} in system ${solarSystemName}";
		} else {
		    $siloCompareResult = siphon::compareSilos($db,$siloId,$siloQuantity);
		    if ( $siloCompareResult >= 1 ) {
			$message = "Warning: Siphon detected on silo ${siloId} in system ${solarSystemName}.\nCurrently ${siloQuantity} units in silo, filling at ${siloCompareResult} units/hour\n";
		    } elseif ($siloCompareResult == -1) {
			$message = "Warning: Silo with ID ${siloId} in system ${solarSystemName} is not filling.\nCurrently ${siloQuantity} units in silo.\n";
		    } elseif ($siloCompareResult == 0) {
			$message = "OK: Silo with ID ${siloId} in system ${solarSystemName} fillrate in-bounds.\nCurrently ${siloQuantity} units in silo.\n";
		    } elseif ($siloCompareResult == -2) {
			$message = "Unknown: Silo with ID ${siloId} in system ${solarSystemName} fillrate exceeds expected. Check config\n";
		    } else {
			debug::errorLog("siphon::compareSilos returned unexpected value $siloCompareResult when checking $siloId");
		    }
		}
		$updateSiloQuantity = "UPDATE silos SET quantity=${siloQuantity},lastupdate=" . time() . " WHERE itemID = ${siloId}";
		$db->query($updateSiloQuantity);
		if (isset($message)) {
		    $bubbles->newMessage($message,$chatChannel);
		}
	    }
	}
    } else {
	// TODO API queru error logging or cached
    }
    unset($siphonGrabber);
}

?>
