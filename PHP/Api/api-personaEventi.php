<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$luogo = $_GET['luogo'] ?? null;
$range = $_GET['range'] ?? "week";
$date = $_GET['date'] ?? null;

$response = [
    "sede"    => $dbh->getAllPlaces(),
    "eventi"  => $dbh->getEventsByPerson($_SESSION['user']['username'], $luogo, $range, $date)
];

echo json_encode($response);
exit;