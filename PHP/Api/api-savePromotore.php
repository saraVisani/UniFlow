<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

function addPromotoriComponenti($input){
    $insertTabelle = [];
    $insertCampi = [];
    $insertValori = [];
    $insertTipi = [];
    $nuoviPromotori = [];

    foreach($input["promotoriDaAggiungere"] ?? [] as $promotore){
        $nuoviPromotori[$promotore["codice"]] = true;
    }
    foreach($input["promotoriDaAggiungere"] ?? [] as $promotore){
        $insertTabelle[] = "Promotore";
        $insertCampi[] = [
            "Codice",
            "Nome",
            "Email"
        ];
        $insertValori[] = [
            "AUTO:" . $promotore["codice"],
            $promotore["nome"],
            $promotore["email"]
        ];
        $insertTipi[] = "iss";
    }
    foreach($input["componentiDaAggiungere"] ?? [] as $componente){

        $codice = $componente["codicePromotore"];
        $codiceRappresenta = $codice;

        if(isset($nuoviPromotori[$codice])){
            $codiceRappresenta = "AUTO:Promotore:" . $codice;
        }

        $insertTabelle[] = "Rappresentano";
        $insertCampi[] = [
            "Codice_Promotore",
            "CF"
        ];
        $insertValori[] = [
            $codiceRappresenta,
            $componente["cf"]
        ];
        $insertTipi[] = "is";
    }
    return [
        "tabelle" => $insertTabelle,
        "campi" => $insertCampi,
        "valori" => $insertValori,
        "tipi" => $insertTipi
    ];
}

function deletePromotoriComponenti($input){
    $delete = [];
    $promotoriEliminati =
        $input["promotoriDaRimuovere"] ?? [];
    foreach($promotoriEliminati as $codice){
        $delete[] = [
            "tabella" => "Promotore",
            "whereCampi" => [
                "Codice"
            ],
            "whereValori" => [
                $codice
            ],
            "whereTipi" => [
                "i"
            ]
        ];
    }
    foreach($input["componentiDaRimuovere"] ?? [] as $componente){
        if(in_array(
            $componente["codicePromotore"],
            $promotoriEliminati
        )){
            continue;
        }
        $delete[] = [
            "tabella" => "Rappresentano",

            "whereCampi" => [
                "Codice_Promotore",
                "CF"
            ],

            "whereValori" => [
                $componente["codicePromotore"],
                $componente["cf"]
            ],

            "whereTipi" => [
                "i",
                "s"
            ]
        ];
    }
    return $delete;
}

function editPromotori($input){

    $updateTabelle = [];
    $updateCampi = [];
    $updateValori = [];
    $updateTipi = [];

    $updateWhereCampi = [];
    $updateWhereValori = [];
    $updateWhereTipi = [];


    foreach($input["promotoriModificare"] ?? [] as $promotore){
        $campi = [];
        $valori = [];
        $tipi = [];
        if(isset($promotore["nome"])){
            $campi[] = "Nome";
            $valori[] = $promotore["nome"];
            $tipi[] = "s";
        }
        if(isset($promotore["email"])){
            $campi[] = "Email";
            $valori[] = $promotore["email"];
            $tipi[] = "s";
        }
        if(count($campi) == 0){
            continue;
        }
        $updateTabelle[] = "Promotore";
        $updateCampi[] = $campi;
        $updateValori[] = $valori;
        $updateTipi[] = $tipi;
        $updateWhereCampi[] = [
            "Codice"
        ];
        $updateWhereValori[] = [
            $promotore["codice"]
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

function editComponenti($dbh, $input, &$message){
    $updateTabelle = [];
    $updateCampi = [];
    $updateValori = [];
    $updateTipi = [];
    $updateWhereCampi = [];
    $updateWhereValori = [];
    $updateWhereTipi = [];
    $currentLevel = $_SESSION['user']['livello_accesso'] ?? 0;

    foreach($input["componentiModificare"] ?? [] as $persona){
        $levelPersona = $dbh->getLevelAccessByCF($persona["cf"]);
        if($levelPersona === false){
            $message = "Non è stato possibile recuperare il livello di accesso della persona con CF: " . $persona["cf"];
            return false;
        }
        if($levelPersona > $currentLevel){
            $message = "Non puoi modificare una persona con livello superiore al tuo.";
            return false;
        }
        $campi = [];
        $valori = [];
        $tipi = [];
        if(isset($persona["nome"])){
            $campi[] = "Nome";
            $valori[] = $persona["nome"];
            $tipi[] = "s";
        }
        if(isset($persona["cognome"])){
            $campi[] = "Cognome";
            $valori[] = $persona["cognome"];
            $tipi[] = "s";
        }
        if(isset($persona["email"])){
            $campi[] = "Email";
            $valori[] = $persona["email"];
            $tipi[] = "s";
        }
        if(count($campi) == 0){
            continue;
        }
        $updateTabelle[] = "Persona";
        $updateCampi[] = $campi;
        $updateValori[] = $valori;
        $updateTipi[] = $tipi;
        $updateWhereCampi[] = [
            "CF"
        ];
        $updateWhereValori[] = [
            $persona["cf"]
        ];
        $updateWhereTipi[] = [
            "s"
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

$message = "Non è stato possibile continuare perchè azione non è valida";
$success = false;

switch($action){

    case "add":

        $insert = addPromotoriComponenti($input);

        $dbh->saveRecordElementiBetter($insert["tabelle"], $insert["campi"], $insert["valori"], $insert["tipi"], [], [], [], [], [], [], [], [], $message, $success);

    break;
    case "edit":
        $insert = addPromotoriComponenti($input);
        $delete = deletePromotoriComponenti($input);
        $updatePromotori = editPromotori($input);
        $updatePersone = editComponenti($dbh, $input, $message);
        if(!$updatePersone){
            break;
        }

        $dbh->saveRecordElementiBetter(
                $insert["tabelle"], $insert["campi"], $insert["valori"], $insert["tipi"],
                array_merge(
                    $updatePromotori[0],
                    $updatePersone[0]
                ),
                array_merge(
                    $updatePromotori[1],
                    $updatePersone[1]
                ),
                array_merge(
                    $updatePromotori[2],
                    $updatePersone[2]
                ),
                array_merge(
                    $updatePromotori[3],
                    $updatePersone[3]
                ),
                array_merge(
                    $updatePromotori[4],
                    $updatePersone[4]
                ),
                array_merge(
                    $updatePromotori[5],
                    $updatePersone[5]
                ),
                array_merge(
                    $updatePromotori[6],
                    $updatePersone[6]
                ),
                $delete,
                $message,
                $success
            );

        break;
    case "delete":
        $delete = deletePromotoriComponenti($input);

        $dbh->saveRecordElementiBetter([],[],[],[],[],[],[],[],[],[],[], $delete, $message, $success);
        break;
}

$response=[
    "message" => $message,
    "success" => $success
];

echo json_encode($response);
exit;