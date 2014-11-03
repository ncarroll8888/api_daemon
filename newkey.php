<?php
include('settings.php');
include('db.php');
$keyID = 0;
$vCode = '';
while ((!is_int($keyID)) || ($keyID == 0)) {
    echo "Please enter your key ID. This should be a whole number about 7 digits long.\nKey ID: ";
    $keyID = intval(trim(fgets(STDIN)));
    if ($keyID == 0) {
	echo "That's not a whole number.\n";
    }
}
while ((strlen($vCode) != 64) || (!ctype_alnum($vCode))) {
    echo "Please enter your verification code. This should be an alphanumeric string exactly 64 characters long\nvCode: ";
    $vCode = trim(fgets(STDIN));
    if ((strlen($vCode) != 64) || (!ctype_alnum($vCode))) {
	echo "I need an alphanumeric string 64 characters long.\n";
    }
}
$type = '';
while (($type != 'corp') && ($type !='char')) {
    echo "Is this a corp or char key?\nType: ";
    $type = trim(fgets(STDIN));
}
$label = '';
while ($label == '') {
    echo "Give me a label. Max 31 chars\nLabel: ";
    $label = $db->real_escape_string(trim(fgets(STDIN)));
}
if ($type == 'corp') {
    echo "Monitor siphons? ";
    $siphon = intval(trim(fgets(STDIN)));
    echo "Monitor towers? ";
    $towers = intval(trim(fgets(STDIN)));
    $query = "INSERT INTO api_corp(id,vcode,siphon,towers,label) VALUES (${keyID},'${vCode}',${siphon},${towers},'${label}')";
    $db->query($query);
}
if ($type == 'char') {
    $charID = 0;
    while ((!is_int($charID)) || ($charID == 0)) {
	echo "Please enter your character ID.\nChar ID: ";
	$charID = intval(trim(fgets(STDIN)));
	if ($charID == 0) {
            echo "That's not a whole number.\n";
	}
    }
    echo "Monitor notifications? ";
    $notifications = intval(trim(fgets(STDIN)));
    $query = "INSERT INTO api_char(id,vcode,notifications,charid,label) VALUES (${keyID},'${vCode}',${notifications},${charID},'${label}')";
    $db->query($query);
}
?>
