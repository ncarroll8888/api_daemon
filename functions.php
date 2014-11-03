<?php

class siphon {
    static function compareSilos($db, $itemId, $new) {
	$grace = 1.05; //Allow 5% grace on comparison in case the API runs slow.
	$now = time();
	$query = "SELECT quantity,lastupdate,fillrate FROM silos WHERE itemId = ${itemId}";
	$result = $db->query($query);
	$resultArray = $result->fetch_assoc();
	$deltaQuantity = ($new - $resultArray['quantity']);
	$deltaTime = ($now - $resultArray['lastupdate']);
	$actualFillRate = intval(($deltaQuantity / ($deltaTime/3600))); //Hourly
	$expectedFillRate = $resultArray['fillrate']; //Also Hourly
	if (($deltaQuantity > 0) && (
	    ($deltaQuantity % $expectedFillRate > 0) ||
	    (($actualFillRate * $grace) < $expectedFillRate)
	    )
	) {
	    return $actualFillRate;       //Return fill rate if siphon detected
	} elseif ($deltaQuantity == 0) {
	    return -1;	//-1 means a silo isn't filling
	} elseif ($actualFillRate > $expectedFillRate) {
	    return -2;	//-2 means exceeded fill rate - probably wrong DB field
	} else {
	    return 0;	//We good, dog.
	}
    }
}


class apiKeyGrabber {
    static function char($type,$db) {
	$getKeys = "SELECT * FROM api_char WHERE ${type}=1";
	$apiKeys = array();
	if ($resultKeys = $db->query($getKeys)) {
	    while ($key = $resultKeys->fetch_assoc()) {
		$apiKeys[$key['label']] = array (
		    'id' => $key['id'],
		    'key' => $key['vcode'],
		    'charid' => $key['charid'],
		);
	    }
	} else {
	    $apiKeys = FALSE;
	}
	return($apiKeys);
    }
    static function corp($type,$db) {
	$getKeys = "SELECT * FROM api_corp WHERE ${type}=1";
	$apiKeys = array();
	if ($resultKeys = $db->query($getKeys)) {
	    while ($key = $resultKeys->fetch_assoc()) {
		$apiKeys[$key['label']] = array (
		    'id' => $key['id'],
		    'key' => $key['vcode'],
		);
	    }
	} else {
	    $apiKeys = FALSE;
	}
	return($apiKeys);
    }
    static function rcpts($type,$db) {
	$getRcpts = "SELECT * FROM recipients WHERE ${type}=1";
	$mailRcpts = array();
	if ($resultRcpts = $db->query($getRcpts)) {
	    while ($key = $resultRcpts->fetch_assoc()) {
		array_push($mailRcpts, $key['recipient']);
	    }
	} else {
	    $mailRcpts = FALSE;
	}
	return($mailRcpts);
    }
}
class notifyCounter {
    function __construct() {
	$this->alertCounters = array (
	    75 => array(
		'description' => 'Control tower under attack',
		'count' => 0,
	    ),
	    76 => array(
		'description' => 'Control tower low on fuel',
		'count' => 0,
	    ),
	    93 => array(
		'description' => 'Customs office under attack',
		'count' => 0,
	    ),
	    94 => array(
		'description' => 'Customs office reinforced',
		'count' => 0,
	    ),
	    11 => array(
		'description' => 'Bill unpaid because of low ISK',
		'count' => 0,
	    ),
	);
    }
    public function increment($id) {
	$this->alertCounters[$id]['count']++;
    }
    public function dump () {
	foreach($this->alertCounters as $alertID => $alert) {
	    if ($alert['count'] == 0) {
		unset($this->alertCounters[$alertID]);
	    }
	}
	return($this->alertCounters);
    }
}

class apiGrabber {
    function __construct() {
	$this->parser = xml_parser_create();
	$this->curl = curl_init();
	curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($this->curl, CURLOPT_POST, 1);
	$this->pwd = getcwd();
    }
    public function grabArray($url,$id = 0,$key = '',$db,$extra = NULL) {
	$xmlStruct = NULL;
	$apiResource = api_url . $url;
	$postFields = '';
	if ($id > 0) {
	    $postFields .= "keyID=${id}&";
	}
	if ($key != '') {
	    $postFields .= "vCode=${key}&";
	}
	if (strlen($extra) > 0) {
	    $postFields .= $extra;
	}
	$postFields = trim($postFields,'&');
	$checkCache = "SELECT cacheduntil FROM cache_timers WHERE id=${id} AND url='${url}' AND extra='${extra}'";
	if ( $cacheResult = $db->query($checkCache)) {
	    if ($cacheResult->num_rows == 0) {
		$newCacheRow = "INSERT INTO cache_timers (id,url,extra) VALUES (${id},'${url}','${extra}')";
		$db->query($newCacheRow);
		$cacheResultArray['cacheduntil'] = 0;
	    } else {
		$cacheResultArray = $cacheResult->fetch_assoc();
	    }
	    if ($cacheResultArray['cacheduntil'] <= time()) {
		curl_setopt($this->curl, CURLOPT_URL, $apiResource);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postFields);
		echo "Running API get on $apiResource\nPost fields: $postFields\n";
		$xmlDump = curl_exec($this->curl);
		$curlStatus = curl_getinfo($this->curl,CURLINFO_HTTP_CODE);
		echo "Status: $curlStatus\n\n";
		if (intval($curlStatus) == 200) {
		    xml_parse_into_struct($this->parser,$xmlDump,$xmlStruct,$index);
		    xml_parser_free($this->parser);
		    $this->parser = xml_parser_create();
		    $xmlDumpFile = $this->pwd . "/cache/" . $id . $url . "&${extra}";
		    $parentDir = dirname($xmlDumpFile);
		    if (!file_exists($parentDir)) {
			mkdir($parentDir,0755,TRUE);
		    }
		    file_put_contents($xmlDumpFile,$xmlDump);
		} else {
		   // Acknowledge that curl failed.
		}
	    } else {
		// Acknowledge that cache hasn't expired
	    }
	} else {
	    // SQL error logging
	}
	if (is_array($xmlStruct)) {
	    foreach($xmlStruct as $xmlData) {
		if((isset($xmlData['tag'])) && ($xmlData['tag'] == 'CACHEDUNTIL')) {
		    $xmlCacheTimer = strtotime($xmlData['value']);
		}
		//print_r($xmlStruct);
	    }
	    $updateCacheTimer = "UPDATE cache_timers SET cacheduntil=${xmlCacheTimer} WHERE id=${id} AND url='${url}' AND extra='${extra}'";
	    $db->query($updateCacheTimer);
	    return($xmlStruct);
	} else {
	    return(FALSE);
	}
    }
}

class chatInterface {
    function __construct() {
	$this->chat = curl_init();
	curl_setopt($this->chat, CURLOPT_URL, chat_url);
	curl_setopt($this->chat, CURLOPT_POST, 1);
	curl_setopt($this->chat, CURLOPT_PORT, 8080);
	$this->messages = array(
	    'default' => array(
		'description' => 'Uncategorized messages',
		'header' => 'General notify',
		'notify' => FALSE,
		'text' => '',
	    )
	);
    }
    public function newMessage ($message,$channel = 'default') {
	$this->messages[$channel]['text'] .= "\n$message";
	$this->messages[$channel]['notify'] = TRUE;
    }
    public function newChannel ($channel = 'default', $description = 'Generic', $header = 'Undefined channel name') {
	$this->messages[$channel] = array (
	    'description' => $description,
	    'header' => $header,
	    'notify' => FALSE,
	    'text' => '',
	);
    }
    public function dumpMessages () {
	foreach($this->messages as $channel => $message) {
	    if ($message['notify']) {
		$sendMessage = urlencode($message['header'] . ":\n" . $message['description'] . "\n" . $message['text']);
		$chatPost = "room=" . chatroom . "&message=${sendMessage}";
		curl_setopt($this->chat, CURLOPT_POSTFIELDS, $chatPost);
		curl_exec($this->chat);
	    }
	}
    }
}
