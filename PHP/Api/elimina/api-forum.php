<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$level = isUserLoggedIn() ? $_SESSION["user"]["level"] : 0;

echo json_encode([
    "forums" => $dbh->getForumsWithChannels($level)
]);

exit;
?>