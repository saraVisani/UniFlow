<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$canale = $_GET["canale"] ?? null;

if(!$canale){
    echo json_encode([]);
    exit;
}

echo json_encode($dbh->getThreadsByChannel($canale));

exit;
?>