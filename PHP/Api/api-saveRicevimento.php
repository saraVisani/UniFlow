<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

function addPrepare($input, $level, $user){
    $insertTabelle = [];
    $insertCampi = [];
    $insertValori = [];
    $insertTipi = [];

    if($level === 4){
        foreach($input["adds"] ?? [] as $slot){
            $insertTabelle[] = "Slot";
            $insertCampi[] = [
                "Codice_Ric",
                "N_Slot",
                "Matricola"
            ];
            $insertValori[] = [
                $slot["codice_ric"],
                $slot["slot"],
                $slot["studente"]
            ];
            $insertTipi[] = "iii";
        }
    } else {
        foreach($input["adds"] ?? [] as $slot){
            $insertTabelle[] = "Richiesta_Ricevimento";
            $insertCampi[] = [
                "Codice",
                "Inserimento",
                "Matricola",
                "Nuovo_Slot",
                "Codice_Ric",
                "N_Slot",
                "Ricevimento",
                "Data_Richiesta"
            ];
            $insertValori[] = [
                "AUTO",
                1,
                $user,
                $slot["slot"],
                null,
                null,
                $slot["codice_ric"],
                new DateTime()
            ];
            $insertTipi[] = "iiiiiiis";
        }
    }

    return [
        "tabelle" => $insertTabelle,
        "campi" => $insertCampi,
        "valori" => $insertValori,
        "tipi" => $insertTipi
    ];
}

function editPrepare($input, $level, $user){
    if($level === 4){
        $updateTabelle = [];
        $updateCampi = [];
        $updateValori = [];
        $updateTipi = [];

        $updateWhereCampi = [];
        $updateWhereValori = [];
        $updateWhereTipi = [];

        foreach($input["edits"] ?? [] as $slot){
            $updateTabelle[] = "Slot";
            $updateCampi[] = "N_Slot";
            $updateValori[] = $slot["nuovo_slot"];
            $updateTipi[] = "i";
            $updateWhereCampi[] = [
                "Codice_Ric",
                "N_Slot"
            ];
            $updateWhereValori[] = [
                $slot["codice_ric"],
                $slot["slot"]
            ];
            $updateWhereTipi[] = [
                "ii"
            ];
        }
        return [
            $updateTabelle,
            $updateCampi,
            $updateValori,
            $updateTipi,
            $updateWhereCampi,
            $updateWhereValori,
            $updateWhereTipi
        ];
    } else {
        $insertTabelle = [];
        $insertCampi = [];
        $insertValori = [];
        $insertTipi = [];

        foreach($input["edits"] ?? [] as $slot){
            $insertTabelle[] = "Richiesta_Ricevimento";
            $insertCampi[] = [
                "Codice",
                "Inserimento",
                "Matricola",
                "Nuovo_Slot",
                "Codice_Ric",
                "N_Slot",
                "Ricevimento",
                "Data_Richiesta"
            ];
            $insertValori[] = [
                "AUTO",
                0,
                $user,
                $slot["nuovo_slot"],
                $slot["codice_ric"],
                $slot["slot"],
                null,
                new DateTime()
            ];
            $insertTipi[] = "iiiiiiis";
        }

        return [
            "tabelle" => $insertTabelle,
            "campi" => $insertCampi,
            "valori" => $insertValori,
            "tipi" => $insertTipi
        ];
    }
}

function deletePrepare($input){
    $delete = [];
    foreach($input["deletes"] ?? [] as $slot){
        $delete[] = [
            "tabella" => "Slot",
            "whereCampi" => [
                "Codice_Ric",
                "N_Slot"
            ],
            "whereValori" => [
                $slot["codice_ric"],
                $slot["slot"]
            ],
            "whereTipi" => [
                "ii"
            ]
        ];
    }
    return $delete;
}

function deleteRicPrepare($input){
    $delete = [];
    foreach($input["deletes"] ?? [] as $cod){
        $delete[] = [
            "tabella" => "Ricevimento",
            "whereCampi" => [
                "Codice"
            ],
            "whereValori" => [
                $cod
            ],
            "whereTipi" => [
                "i"
            ]
        ];
    }
    return $delete;
}

function addRicPrepare($input, $user){
    $insertTabelle = [];
    $insertCampi = [];
    $insertValori = [];
    $insertTipi = [];

    foreach($input["adds"] ?? [] as $ric){
        $insertTabelle[] = "Slot";
        $insertCampi[] = [
            "Codice_Ric",
            "Online",
            "Data_Inizio",
            "Data_Fine",
            "N_Slot",
            "Codice_Uni",
            "Codice_Stanza",
            "Matricola"
        ];
        $uni = null;
        $stanza = null;
        if($user === null) {
            $user = $ric["matricola"];
        }
        if($ric["online"] === 0){
            $uni = $ric["cod_uni"];
            $stanza = $ric["cod_stanza"];
        }
        $insertValori[] = [
            "AUTO",
            $ric["online"],
            $ric["data_inizio"],
            $ric["data_fine"],
            $ric["n_slot"],
            $uni,
            $stanza,
            $user
        ];
        $insertTipi[] = "iissiiii";
    }
    return [
        "tabelle" => $insertTabelle,
        "campi" => $insertCampi,
        "valori" => $insertValori,
        "tipi" => $insertTipi
    ];
}

function editRicPrepare($input, $user){
    $updateTabelle = [];
    $updateCampi = [];
    $updateValori = [];
    $updateTipi = [];

    $updateWhereCampi = [];
    $updateWhereValori = [];
    $updateWhereTipi = [];

    foreach($input["edits"] ?? [] as $ric){
        $campi = [];
        $valori = [];
        $tipi = [];
        if(isset($ric["online"])){
            $campi[] = "Online";
            $valori[] = $ric["online"];
            $tipi[] = "i";
        }
        if(isset($ric["data_inizio"])){
            $campi[] = "Data_Inizio";
            $valori[] = $ric["data_inizio"];
            $tipi[] = "s";
        }
        if(isset($ric["data_fine"])){
            $campi[] = "Data_Fine";
            $valori[] = $ric["data_fine"];
            $tipi[] = "s";
        }
        if(isset($ric["n_slot"])){
            $campi[] = "N_Slot";
            $valori[] = $ric["n_slot"];
            $tipi[] = "i";
        }
        if(isset($ric["cod_uni"])){
            $campi[] = "Codice_Uni";
            $valori[] = $ric["cod_uni"];
            $tipi[] = "i";
        }
        if(isset($ric["cod_stanza"])){
            $campi[] = "Codice_Stanza";
            $valori[] = $ric["cod_stanza"];
            $tipi[] = "i";
        }
        if(isset($ric["matricola"])){
            $campi[] = "Matricola";
            $valori[] = $ric["matricola"];
            $tipi[] = "i";
        }
        $updateTabelle[] = "Ricevimento";
        $updateCampi[] = $campi;
        $updateValori[] = $valori;
        $updateTipi[] = $tipi;
        $updateWhereCampi[] = [
            "Codice"
        ];
        $updateWhereValori[] = [
            $ric["codice"]
        ];
        $updateWhereTipi[] = [
            "i"
        ];
    }
    return [
        $updateTabelle,
        $updateCampi,
        $updateValori,
        $updateTipi,
        $updateWhereCampi,
        $updateWhereValori,
        $updateWhereTipi
    ];
}

$input = json_decode(file_get_contents("php://input"), true);

$action = $input["action"] ?? "";
$level = $input["level"] ?? 0;

$message = "Non è stato possibile continuare perchè l'azione o l'accesso non sono validi";
$success = false;

if($action === "" || $level === 0){
    $response=[
        "message" => $message,
        "success" => $success
    ];

    echo json_encode($response);
    exit;
}

$user = null;
if($level !== 4){
    $user = $dbh->resolveUserId($_SESSION["user"]["username"]);
}

switch($action){
    case "add":
        $insert = addPrepare($input, $level, $user);
        $dbh->saveRecordElementiBetter($insert["tabelle"], $insert["campi"], $insert["valori"], $insert["tipi"], [], [], [], [], [], [], [], [], $message, $success);
        break;
    case "delete":
        $insert = deletePrepare($input);
        $dbh->saveRecordElementiBetter([],[],[],[],[],[],[],[],[],[],[], $delete, $message, $success);
        break;
    case "edit":
        $add = addPrepare($input, $level, $user);
        $delete = deletePrepare($input);
        $edit = editPrepare($input, $level, $user);
        if($level === 4){
            $dbh->saveRecordElementiBetter(
                $add["tabelle"], $add["campi"], $add["valori"], $add["tipi"],
                $edit[0], $edit[1], $edit[2], $edit[3], $edit[4], $edit[5], $edit[6],
                $delete, $message, $success
            );
        } else {
            $dbh->saveRecordElementiBetter(
                array_merge(
                    $add["tabelle"],
                    $edit["tabelle"]
                ),
                array_merge(
                    $add["campi"],
                    $edit["campi"]
                ),
                array_merge(
                    $add["valori"],
                    $edit["valori"]
                ),
                array_merge(
                    $add["tipi"],
                    $edit["tipi"]
                ),[],[],[],[],[],[],[], $delete, $message, $success);
        }
        break;
    case "addRicevimento":
        $insert = addRicPrepare($input, $user);
        $dbh->saveRecordElementiBetter($insert["tabelle"], $insert["campi"], $insert["valori"], $insert["tipi"], [], [], [], [], [], [], [], [], $message, $success);
        break;
    case "editRicevimento":
        $add = addRicPrepare($input, $user);
        $delete = deleteRicPrepare($input);
        $edit = editRicPrepare($input, $user);
        $dbh->saveRecordElementiBetter(
            $add["tabelle"], $add["campi"], $add["valori"], $add["tipi"],
            $edit[0], $edit[1], $edit[2], $edit[3], $edit[4], $edit[5], $edit[6],
            $delete, $message, $success
        );
        break;
    case "deleteRicevimento":
        $insert = deleteRicPrepare($input);
        $dbh->saveRecordElementiBetter([],[],[],[],[],[],[],[],[],[],[], $delete, $message, $success);
        break;
}


$response=[
    "message" => $message,
    "success" => $success
];

echo json_encode($response);
exit;