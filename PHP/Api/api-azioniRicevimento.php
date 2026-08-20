<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

if(!isUserLoggedIn()){
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "redirect" => "/index.php"
    ]);

    exit;
} else {
    $action = $_GET['azione'] ?? null;
    $date = $_GET["date"] ?? date("Y-m-d");
    $range = $_GET["range"] ?? "giorno";

    $dt = DateTime::createFromFormat("Y-m-d", $date);

    if($dt === false || $dt->format("Y-m-d") !== $date){
        $dt = new DateTime();
    }

    $inizio = clone $dt;
    $fine = clone $dt;

    switch($range){

        case "giorno":
            $inizio->setTime(0, 0, 0);
            $fine = clone $inizio;
            $fine->modify("+1 day");
            break;

        case "settimana":
            $inizio->modify("monday this week")->setTime(0, 0, 0);
            $fine = clone $inizio;
            $fine->modify("+1 week");
            break;

        case "mese":
            $inizio->modify("first day of this month")->setTime(0, 0, 0);
            $fine = clone $inizio;
            $fine->modify("+1 month");
            break;

        default:
            $inizio = new DateTime();
            $inizio->setTime(0, 0, 0);
            $fine = clone $inizio;
            $fine->modify("+1 day");
    }

    $inizio = $inizio->format("Y-m-d H:i:s");
    $fine = $fine->format("Y-m-d H:i:s");
    $response = [];
    if($action !== "nothing"){
        if(isUserStudent()){
            switch($action){
                case null:
                    $select = [
                        "nothing"=> "--Seleziona Azione--",
                        "view"   => "Visualizza Appuntamenti",
                        "add"    => "Aggiungi un Appuntamento",
                        "edit"   => "Modifica un Appuntamento",
                        "delete" => "Elimina un Appuntamento"
                    ];
                    $response["select"] = $select;
                    break;
                case "view":
                    $response = [
                        "ricevimenti" => $dbh->getAllReunions($inizio, $fine),
                        "appuntamenti" => $dbh->getAllUserSlots($inizio, $fine, $_SESSION["user"]["username"])
                    ];
                    break;
                case "add":
                case "edit":
                    $prof = $_GET['prof'] ?? null;
                    if($prof === null){
                        $response = [
                            "professori" => $dbh->getProfessors(),
                        ];
                    } else {
                        $response = [
                            "professori" => $dbh->getProfessors(),
                            "ricevimenti" => $dbh->getAllReunionsByProfessor($inizio, $fine, $prof),
                            "appuntamenti" => $dbh->getAllSlots($inizio, $fine),
                            "appuntamenti_user" => $dbh->getAllUserSlots($inizio, $fine, $_SESSION["user"]["username"]),
                            "ok" => true
                        ];
                    }
                    break;
                case "delete":
                    $response = [
                        "ricevimenti" => $dbh->getAllReunions($inizio, $fine),
                        "appuntamenti" => $dbh->getAllUserSlots($inizio, $fine, $_SESSION["user"]["username"]),
                        "ok" => true
                    ];
                    break;
            }
        }

        if(isUserProfessor()){
            switch($action){
                case null:
                    $select = [
                        "nothing"=> "--Seleziona Azione--",
                        "view"   => "Visualizza Appuntamenti",
                        "addRicevimento"    => "Aggiungi un Ricevimento",
                        "editRicevimento"   => "Modifica un Ricevimento",
                        "deleteRicevimento" => "Elimina un Ricevimento"
                    ];
                    $response["select"] = $select;
                    break;
                case "view":
                    $response = [
                        "ricevimenti" => $dbh->getAllReunionsByProfessor($inizio, $fine, $_SESSION["user"]["username"]),
                        "ok" => true
                    ];
                    break;
                case "addRicevimento":
                    $response = [
                        "uffici" => $dbh->getAllOfficesByProfessor($_SESSION["user"]["username"]),
                        "ok" => true
                    ];
                    break;
                case "editRicevimento":
                    $response = [
                        "ricevimenti" => $dbh->getAllReunionsByProfessor($inizio, $fine, $_SESSION["user"]["username"]),
                        "uffici" => $dbh->getAllOfficesByProfessor($_SESSION["user"]["username"]),
                        "ok" => true
                    ];
                    break;
                case "deleteRicevimento":
                    $response = [
                        "ricevimenti" => $dbh->getAllReunionsByProfessor($inizio, $fine, $_SESSION["user"]["username"]),
                        "ok" => true
                    ];
                    break;
            }
        }

        if(isUserSegretary()){
            switch($action){
                case null:
                    $select = [
                        "nothing"=> "--Seleziona Azione--",
                        "view"   => "Visualizza Appuntamenti",
                        "add"    => "Aggiungi un Appuntamento",
                        "edit"   => "Modifica un Appuntamento",
                        "delete" => "Elimina un Appuntamento",
                        "addRicevimento"    => "Aggiungi un Ricevimento",
                        "editRicevimento"   => "Modifica un Ricevimento",
                        "deleteRicevimento" => "Elimina un Ricevimento"
                    ];
                    $response["select"] = $select;
                    break;
                case "view":
                    $prof = $_GET['prof'] ?? null;
                    $stud = $_GET['stud'] ?? null;
                    if(($prof === null && $stud === null) || ($prof !== null && $stud !== null)){
                        $response = [
                            "professori" => $dbh->getProfessors(),
                            "studenti" => $dbh->getStudents(),
                        ];
                    } else if ($prof !== null && $stud === null){
                        $response = [
                            "professori" => $dbh->getProfessors(),
                            "studenti" => $dbh->getStudents(),
                            "ricevimenti" => $dbh->getAllReunionsByProfessor($inizio, $fine, $prof),
                        ];
                    } else if ($prof === null && $stud !== null){
                        $response = [
                            "professori" => $dbh->getProfessors(),
                            "studenti" => $dbh->getStudents(),
                            "ricevimenti" => $dbh->getAllReunions($inizio, $fine),
                            "appuntamenti" => $dbh->getAllUserSlots($inizio, $fine, $stud)
                        ];
                    }
                    break;
                case "add":
                case "edit":
                    $stud = $_GET['stud'] ?? null;
                    if($stud === null){
                        $response = [
                            "studenti" => $dbh->getStudents(),
                            "professori" => $dbh->getProfessors(),
                        ];
                    } else {
                        $prof = $_GET['prof'] ?? null;
                        if($prof === null){
                            $response = [
                                "professori" => $dbh->getProfessors(),
                                "studenti" => $dbh->getStudents(),
                            ];
                        } else {
                            $response = [
                                "studenti" => $dbh->getStudents(),
                                "professori" => $dbh->getProfessors(),
                                "ricevimenti" => $dbh->getAllReunionsByProfessor($inizio, $fine, $prof),
                                "appuntamenti" => $dbh->getAllSlots($inizio, $fine),
                                "appuntamenti_user" => $dbh->getAllUserSlots($inizio, $fine, $stud),
                                "ok" => true
                            ];
                        }
                    }
                    break;
                case "delete":
                    $stud = $_GET['stud'] ?? null;
                    if($stud === null){
                        $response = [
                            "studenti" => $dbh->getStudents(),
                        ];
                    } else {
                        $response = [
                            "studenti" => $dbh->getStudents(),
                            "ricevimenti" => $dbh->getAllReunions($inizio, $fine),
                            "appuntamenti" => $dbh->getAllUserSlots($inizio, $fine, $stud),
                            "ok" => true
                        ];
                    }
                    break;
                case "addRicevimento":
                    $response = [
                        "uffici" => $dbh->getAllOffices(),
                        "professori" => $dbh->getProfessors(),
                        "ok" => true
                    ];
                    break;
                case "editRicevimento":
                    $prof = $_GET['prof'] ?? null;
                    if($prof === null){
                        $response = [
                            "professori" => $dbh->getProfessors(),
                        ];
                    } else {
                        $response = [
                            "professori" => $dbh->getProfessors(),
                            "ricevimenti" => $dbh->getAllReunionsByProfessor($inizio, $fine, $prof),
                            "uffici" => $dbh->getAllOfficesByProfessor($prof),
                            "ok" => true
                        ];
                    }
                    break;
                case "deleteRicevimento":
                    $prof = $_GET['prof'] ?? null;
                    if($prof === null){
                        $response = [
                            "professori" => $dbh->getProfessors(),
                        ];
                    } else {
                        $response = [
                            "professori" => $dbh->getProfessors(),
                            "ricevimenti" => $dbh->getAllReunionsByProfessor($inizio, $fine, $prof),
                            "ok" => true
                        ];
                    }
                    break;
            }
        }
    }
    $response["level"] = $_SESSION["user"]["livello_accesso"];
    $response["success"] = true;
    echo json_encode($response);
    exit;
}

