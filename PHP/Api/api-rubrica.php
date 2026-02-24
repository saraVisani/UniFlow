<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$persone = $dbh->getPersone();

if (!$persone) {
    echo json_encode(["error" => "Persona non trovata"]);
    exit;
}

echo json_encode($persone);
?>