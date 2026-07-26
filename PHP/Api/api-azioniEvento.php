<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$action = $_GET['azione'] ?? null;

if (!$action || $action === "nothing") {
    echo json_encode([
        "action" => "nothing",
        "logged" => isUserLoggedIn()
    ]);
    exit;
}

if ($action === "add") { // Give information to complete the add form of event
    $response = [
        "action" => "add",
        "persone" => $dbh->getPeoples(),
        "luoghi" => $dbh->getAllPlaces(),
        "totPromotori" => $dbh->getAllPromoters(),
        "eventi" => $dbh->getAllEvents(),
        "logged" => isUserLoggedIn()
    ];
    echo json_encode($response);
    exit;
}

$selectedEvent = $_GET['evento'] ?? null;

if ($selectedEvent === null) { // Give info to choose an event to edit or delete
    $response = [
        "action" => $action,
        "idEvento" => $dbh->getAllEvents(),
        "logged" => isUserLoggedIn()
    ];
    echo json_encode($response);
    exit;
}else{
    switch ($action) {
        case "delete": // Define delete before confermation
            $response = [
                "action" => "delete",
                "persone" => $dbh->getPeoples(),
                "evento" => $dbh->getEventById($selectedEvent)
            ];
            break;
        case "edit": // Give info of the event to edit
            $response = [
                "action" => "edit",
                "persone" => $dbh->getPeoples(),
                "luoghi" => $dbh->getAllPlaces(),
                "totPromotori" => $dbh->getAllPromoters(),
                "evento" => $dbh->getEventById($selectedEvent),
                "collaboratori" => $dbh->getCollaboratorsByEvent($selectedEvent),
                "promotori" => $dbh->getPromotersByEvent($selectedEvent),
                "orari" => $dbh->getEventDatesByEvent($selectedEvent)
            ];
            break;
        case "addOrario": // Give info to complete the add form of event date
            $response = [
                "action" => "addOrario",
                "persone" => $dbh->getPeoples(),
                "evento" => $dbh->getEventById($selectedEvent),
                "luoghi" => $dbh->getAllPlaces(),
                "orari" => $dbh->getEventDatesByEvent($selectedEvent)
            ];
            break;
        case "deleteOrario": // Define delete before confermation
            $response = [
                "action" => "deleteOrario",
                "persone" => $dbh->getPeoples(),
                "evento" => $dbh->getEventById($selectedEvent),
                "orari" => $dbh->getEventDatesByEvent($selectedEvent)
            ];
            break;
        case "editOrario":
            $response = [
                "action" => "editOrario",
                "persone" => $dbh->getPeoples(),
                "luoghi" => $dbh->getAllPlaces(),
                "evento" => $dbh->getEventById($selectedEvent),
                "orari" => $dbh->getEventDatesByEvent($selectedEvent)
            ];
            break;

        default:
            http_response_code(400);
            echo json_encode([
                "error" => "Azione non valida."
            ]);
            exit;
    }
}

$response["logged"] = isUserLoggedIn();

echo json_encode($response);
exit;