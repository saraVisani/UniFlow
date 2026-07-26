<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);

$action = $input["action"] ?? "";

$message = "Non è stato possibile continuare perchè azione non è valida";
$success = false;

if($action == "add"){
    $input["richiedente"] = $dbh->giveIdRichiedente("Inserimento_Evento", $_SESSION['user']["username"], $input, $message, $success);
    if(!$success){
        $response=[
            "message" => $message,
            "success" => $success
        ];

        echo json_encode($response);
        exit;
    }
}else{
    $input["richiedente"] = $dbh->giveIdRichiedente("Other_Evento",  $_SESSION['user']["username"], $input, $message, $success);
}

switch($action){

    case "add":
        if(userLevelForAccess(4)){
            $dbh->addNewEvent($input, $message, $success);
        } else {
            $dbh->addNewRequestEvent($input, $message, $success);
        }
        break;

    case "edit":
        $richiedeApprovazione = $input["richiedeApprovazione"] ?? false;

        if ($richiedeApprovazione && !userLevelForAccess(4)) {
            $dbh->editNewRequestEvent($input, $message, $success);
        } else {
            $dbh->editNewEvent($input, $message, $success);
        }
        break;

    case "delete":
        if(userLevelForAccess(4)){
            $dbh->deleteNewEvent($input, $message, $success);
        } else {
            $dbh->deleteNewRequestEvent($input, $message, $success);
        }
        break;

    case "addOrario":
        if(userLevelForAccess(4)){
            $dbh->addNewEventDate($input, $message, $success);
        } else {
            $dbh->addNewRequestEventDate($input, $message, $success);
        }
        break;

    case "editOrario":
        if(userLevelForAccess(4)){
            $dbh->editNewEventDate($input, $message, $success);
        } else {
            $dbh->editNewRequestEventDate($input, $message, $success);
        }
        break;

    case "deleteOrario":
        if(userLevelForAccess(4)){
            $dbh->deleteNewEventDate($input, $message, $success);
        } else {
            $dbh->deleteNewRequestEventDate($input, $message, $success);
        }
        break;
}

$response=[
    "message" => $message,
    "success" => $success
];

echo json_encode($response);
exit;