<?php
$pwd = getcwd();
include('settings.php');
include('db.php');
include('functions.php');
$charIDgrabber = new apiGrabber();
$url = "/eve/CharacterID.xml.aspx";
while (TRUE) {
    echo "Names > ";
    $charName = urlencode(trim(fgets(STDIN)));
    $extra = "names=$charName";
    $cacheFile = getcwd() . "/cache/0/eve/CharacterID.xml.aspx&${extra}";
    $idArray = $charIDgrabber->grabArray($url,0,'',$db,$extra) or $idArray = file_get_contents($cacheFile);
    foreach($idArray as $charInfo) {
	if ((isset($charInfo['tag'])) && ($charInfo['tag'] == 'ROW')) {
	    $realCharName = $charInfo['attributes']['NAME'];
	    $realCharID = $charInfo['attributes']['CHARACTERID'];
	    echo "${realCharName}: ${realCharID}\n";
	}
    }
}
