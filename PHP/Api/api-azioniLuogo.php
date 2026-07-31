<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$action = $_GET['azione'] ?? null;
function getLoggedData(){
    $data = [
        "logged" => isUserLoggedIn()
    ];

    if(isUserLoggedIn()){
        $data["level"] = $_SESSION['user']['livello_accesso'];
    }

    return $data;
}
function getIds($dbh){

    $prov = $dbh->getProvincies();
    $citta = $dbh->getcities();

    return [
        "idP" => array_map(
            fn($p) => $p["codice"],
            $prov
        ),

        "idC" => array_map(
            fn($c) => $c["cod_Prov"]."-".$c["codice"],
            $citta
        )
    ];
}
function getIdIndirizzi($dbh){

    return array_map(
        fn($i)=>
            $i["cod_Prov"]."-".
            $i["cod_Citta"]."-".
            $i["civico"],
        $dbh->getIndirizzi()
    );
}

if($action === null){
    if (isUserLoggedIn() && $_SESSION['user']['livello_accesso'] == 4) {
        $select = [
            "nothing"         => "--Seleziona Azione--",
            "add"             => "Aggiungi Luogo",
            "edit"            => "Modifica Luogo",
            "delete"          => "Elimina Luogo",
            "addProvincia"    => "Aggiungi Provincia",
            "editProvincia"   => "Modifica Provincia",
            "deleteProvincia" => "Elimina Provincia",
            "addCitta"        => "Aggiungi Città",
            "editCitta"       => "Modifica Città",
            "deleteCitta"     => "Elimina Città",
            "addSede"         => "Aggiungi Sede",
            "editSede"        => "Modifica Sede",
            "deleteSede"      => "Elimina Sede"
        ];
    } else {
        $select = [
            "nothing"         => "--Seleziona Azione--",
            "add"    => "Aggiungi Luogo",
            "edit"   => "Modifica Luogo",
            "delete" => "Elimina Luogo"
        ];
    }
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

        $response = [
            "action"=>$action,
            "citta"=>$dbh->getcities(),
            "provincie"=>$dbh->getProvincies(),
            "via"=>$dbh->getAllVie(),
            "idI"=>getIdIndirizzi($dbh)
        ];
        if(isUserLoggedIn() && $_SESSION['user']['livello_accesso']==4){
            $response["professori"] = $dbh->getProfessors();
        }
        $response += getLoggedData();
        echo json_encode($response);
        exit;

    case "addSede":

        $response = [
            "action"=>$action,
            "citta"=>$dbh->getcities(),
            "provincie"=>$dbh->getProvincies(),
            "via"=>$dbh->getAllVie(),
            "idI"=>getIdIndirizzi($dbh)
        ];
        $response += getLoggedData();
        echo json_encode($response);
        exit;

    case "addProvincia":
        $response = [
            "action"=>$action
        ];
        $response += getIds($dbh);
        $response += getLoggedData();
        echo json_encode($response);
        exit;

    case "addCitta":
        $response = [
            "action"=>$action,
            "citta"=>$dbh->getcities(),
            "provincie"=>$dbh->getProvincies()
        ];
        $response += getIds($dbh);
        $response += getLoggedData();
        echo json_encode($response);
        exit;

    case "editProvincia":
        $response = [
            "action"=>$action,
            "provincie"=>$dbh->getProvincies()
        ];
        $response += getIds($dbh);
        $response += getLoggedData();
        echo json_encode($response);
        exit;

    case "deleteProvincia":
        $response = [
            "action"=>$action,
            "provincie"=>$dbh->getProvincies()
        ];
        $response += getIds($dbh);
        $response += getLoggedData();
        echo json_encode($response);
        exit;

    case "editCitta":
        $response = [
            "action"=>$action,
            "citta"=>$dbh->getcities(),
            "provincie"=>$dbh->getProvincies()
        ];
        $response += getIds($dbh);
        $response += getLoggedData();
        echo json_encode($response);
        exit;

    case "deleteCitta":
        $response = [
            "action"=>$action,
            "citta"=>$dbh->getcities(),
            "provincie"=>$dbh->getProvincies()
        ];
        $response += getIds($dbh);
        $response += getLoggedData();
        echo json_encode($response);
        exit;
    default:
        $selectedPlace = $_GET['luogo'] ?? null;
        if ($selectedPlace === null) {
            if($action === "edit" || $action === "delete"){
                if(isUserLoggedIn() && $_SESSION['user']['livello_accesso'] == 4){
                    $response = [
                            "action" => $action,
                            "luoghi" => $dbh->getAllPlaces(),
                        ];
                }else{
                    $response = [
                            "action" => $action,
                            "luoghi" => $dbh->getAllExternPlaces(),
                        ];
                }
            } else if($action === "editSede" || $action === "deleteSede") {
                $response = [
                    "action" => $action,
                    "sedi" => $dbh->getAllCampusesWithCode(),
                ];
            }
            $response += getLoggedData();
            echo json_encode($response);
            exit;
        } else {
            switch($action){
                case "edit":
                    if(isUserLoggedIn() && $_SESSION['user']['livello_accesso'] == 4){
                        $response = [
                            "action" => $action,
                            "luogo" => $dbh->getPlaceByCode($selectedPlace),
                            "citta" => $dbh->getcities(),
                            "provincie" => $dbh->getProvincies(),
                            "sedi" => $dbh->getAllCampusesWithCode(),
                            "via" => $dbh->getAllVie(),
                            "idI"=>getIdIndirizzi($dbh),
                            "professori" => $dbh->getProfessors()
                        ];
                    }else{
                        $response = [
                            "action" => $action,
                            "luogo" => $dbh->getPlaceByCode($selectedPlace),
                            "citta" => $dbh->getcities(),
                            "provincie" => $dbh->getProvincies(),
                            "via" => $dbh->getAllVie(),
                            "idI"=>getIdIndirizzi($dbh)
                        ];
                    }
                    $response += getLoggedData();
                    echo json_encode($response);
                    exit;
                case "editSede":
                    $response = [
                        "action" => $action,
                        "luogo" => $dbh->getSedeByCode($selectedPlace),
                        "citta" => $dbh->getcities(),
                        "provincie" => $dbh->getProvincies(),
                        "via" => $dbh->getAllVie(),
                        "idI"=>getIdIndirizzi($dbh)
                    ];
                    $response += getLoggedData();
                    echo json_encode($response);
                    exit;
                case "deleteSede":
                        $response = [
                            "action" => $action,
                            "luogo" => $dbh->getSedeByCode($selectedPlace)
                        ];
                        $response += getLoggedData();
                        echo json_encode($response);
                        exit;
                case "delete":
                    $response = [
                        "action" => $action,
                        "luogo" => $dbh->getPlaceByCode($selectedPlace)
                    ];
                    $response += getLoggedData();
                    echo json_encode($response);
                    exit;
            }
        }
}
