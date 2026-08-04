<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$action = $_GET['azione'] ?? null;

function getPromotoriVisibili(){
    global $dbh;
    if(isUserLoggedIn() && $_SESSION['user']['livello_accesso'] == 4){

        return $dbh->getAllPromoters();

    }
    $user = isUserLoggedIn()
        ? $_SESSION['user']['username']
        : null;
    if($user === null){
        $user = $_GET['user'] ?? null;
        if($user === null){
            return [];
        }
        $persone = $dbh->getPersone();
        foreach($persone as $persona){
            if($dbh->getCFfromMat($persona["matricola"]) === $user){
                // è un utente presente nel sistema
                // quindi non deve poter essere scelto da URL
                return [];
            }
        }
    }
    return $dbh->getAllPromoters($user);
}
function getLoggedData(){
    $data = [
        "logged" => isUserLoggedIn()
    ];

    if(isUserLoggedIn()){
        $data["level"] = $_SESSION['user']['livello_accesso'];
    }

    return $data;
}

if($action === null){
    $select = [
        "nothing"=> "--Seleziona Azione--",
        "add"    => "Aggiungi Luogo",
        "edit"   => "Modifica Luogo",
        "delete" => "Elimina Luogo"
    ];
    echo json_encode([
        "select" => $select
    ]);
    exit;
}

switch($action){
    case "nothing":
        echo json_encode([
            "action"=>"nothing",
            ...getLoggedData()
        ]);
        exit;
    case "add":
        $response["persone"] = $dbh->getPersone();
        $response += getLoggedData();
        echo json_encode($response);
        exit;
    case "delete":
    case "edit":
        $response = [
            "action" => $action,
            "promotori" => getPromotoriVisibili(),
            "persone" => $dbh->getPersone()
        ];
        $response += getLoggedData();
        echo json_encode($response);
        exit;
}


