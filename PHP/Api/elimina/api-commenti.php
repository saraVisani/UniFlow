<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$thread = $_GET["thread"] ?? null;

if(!$thread){
    echo json_encode([]);
    exit;
}

echo json_encode($dbh->getCommentsByThread($thread));
exit;
?>