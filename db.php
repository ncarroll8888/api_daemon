<?php
$db = new mysqli(
    $database['host'],
    $database['user'],
    $database['password'],
    $database['db']
);
if (!$db->query('SET @@session.time_zone = "+00:00"')) {
    echo "Failed to contact DB";
    exit(1);
}
?>
