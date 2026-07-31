<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$input;

if (isset($_POST["dati"])) {
    // richiesta multipart/form-data
    $input = json_decode($_POST["dati"], true);
} else {
    // richiesta JSON
    $input = json_decode(file_get_contents("php://input"), true);
}

$action = $input["action"] ?? "";

$message = "Non è stato possibile continuare perchè azione non è valida";
$success = false;

switch($action){
    case "add":
        $tabelle = [];
        $campi = [];
        $valori = [];
        $tipoCampi = [];
        if($input["tipo"] === "esterno"){
            $tabelle = [
                "Indirizzo",
                "Luogo",
                "Esterno"
            ];
            $campi = [
                0 => [
                    "Codice_Prov",
                    "Codice_Citta",
                    "N_Civico",
                    "Via",
                    "Nome"
                ],
                1 => [
                    "Codice",
                    "Nome",
                    "Capienza"
                ],
                2 => [
                    "Codice",
                    "Codice_Prov",
                    "Codice_Citta",
                    "N_Civico",
                    "Cod_Luogo"
                ]
            ];
            $valori = [
                0 => [
                    $input["indirizzo"]["provincia"],
                    $input["indirizzo"]["citta"],
                    $input["indirizzo"]["civico"],
                    $input["indirizzo"]["via"],
                    $input["indirizzo"]["nomeVia"]
                ],
                1 => [
                    "AUTO",
                    $input["nome"],
                    $input["capienza"]
                ],
                2 => [
                    "AUTO",
                    $input["indirizzo"]["provincia"],
                    $input["indirizzo"]["citta"],
                    $input["indirizzo"]["civico"],
                    "AUTO:Luogo"
                ]
            ];
            $tipoCampi = [
                0 => [
                    "s",
                    "s",
                    "i",
                    "s",
                    "s"
                ],
                1 => [
                    "i",
                    "s",
                    "i"
                ],
                2 => [
                    "i",
                    "s",
                    "s",
                    "i",
                    "i"
                ]
            ];
        }else{
            $tabelle = [
                "Luogo",
                "Universitario"
            ];
            $campi = [
                0 => [
                    "Codice",
                    "Nome",
                    "Capienza"
                ],
                1 => [
                    "Codice_Uni",
                    "Codice",
                    "Cod_Luogo"
                ]
            ];
            $valori = [
                0 => [
                    "AUTO",
                    $input["nome"],
                    $input["capienza"]
                ],
                1 => [
                    $input["cod_uni"],
                    $input["cod_stanza"],
                    "AUTO:Luogo"
                ]
            ];
            $tipoCampi = [
                0 => [
                    "i",
                    "s",
                    "i"
                ],
                1 => [
                    "i",
                    "i",
                    "i"
                ]
            ];
            if($input["tipo"]==="classe"){
                $tabelle[] = "Classe";
                $campi[2] = [
                    "Codice_Uni",
                    "Codice_Stanza",
                    "Lab"
                ];
                $valori[2] = [
                    $input["cod_uni"],
                    $input["cod_stanza"],
                    $input["lab"]
                ];
                $tipoCampi[2] = [
                    "i",
                    "i",
                    "i" //bool
                ];
            } else {
                $tabelle[] = "Ufficio";
                $campi[2] = [
                    "Codice_Uni",
                    "Codice_Stanza",
                    "Matricola"
                ];
                $valori[2] = [
                    $input["cod_uni"],
                    $input["cod_stanza"],
                    $input["assegnato"]
                ];
                $tipoCampi[2] = [
                    "i",
                    "i",
                    "i"
                ];
            }
        }

        $dbh->insertElement(
            $tabelle,
            $campi,
            $valori,
            $tipoCampi,
            $message,
            $success
        );
        break;
    case "addSede":
        $file = $_FILES["file"];
        $estensione = pathinfo($file["name"], PATHINFO_EXTENSION);
        $nomeFile = uniqid("sede_").".".$estensione;

        if (!move_uploaded_file($file["tmp_name"], UPLOAD_DIR . $nomeFile)) {
            $success = false;
            $message = "Errore durante il caricamento dell'immagine.";
            break;
        }

        $tabelle = ["Indirizzo", "Sede"];
        $campi = [
            0 => [
                "Codice_Prov",
                "Codice_Citta",
                "N_Civico",
                "Via",
                "Nome"
            ],
            1 => [
                "Codice",
                "Codice_Prov",
                "Codice_Citta",
                "N_Civico",
                "Nome",
                "Descrizione",
                "Path",
                "Descrizione_Img"
            ],
        ];
        $valori = [
            0 => [
                $input["indirizzo"]["provincia"],
                $input["indirizzo"]["citta"],
                $input["indirizzo"]["civico"],
                $input["indirizzo"]["via"],
                $input["indirizzo"]["nomeVia"]
            ],
            1 => [
                "AUTO",
                $input["indirizzo"]["provincia"],
                $input["indirizzo"]["citta"],
                $input["indirizzo"]["civico"],
                $input["nome"],
                $input["descrizione"],
                $nomeFile,
                $input["descrizioneImm"]
            ],
        ];
        $tipoCampi = [
            0 => [
                "s",
                "s",
                "i",
                "s",
                "s"
            ],
            1 => [
                "i",
                "s",
                "s",
                "i",
                "s",
                "s",
                "s",
                "s"
            ],
        ];

        $dbh->insertElement(
            $tabelle,
            $campi,
            $valori,
            $tipoCampi,
            $message,
            $success
        );

        if (!$success && file_exists(UPLOAD_DIR . $nomeFile)) {
            unlink(UPLOAD_DIR . $nomeFile);
        }
        break;
    case "edit":
        $tabelle = [];
        $campi = [];
        $valori = [];
        $tipi = [];
        $whereCampi = [];
        $whereValori = [];
        $whereTipi = [];
        $delete = [];

        if ($input["nome"] !== null || $input["capienza"] !== null) {
            $i = count($tabelle);
            $tabelle[$i] = "Luogo";
            $campi[$i] = [
                "Nome",
                "Capienza"
            ];
            $valori[$i] = [
                $input["nome"],
                $input["capienza"]
            ];
            $tipi[$i] = [
                "s",
                "i"
            ];
            $whereCampi[$i] = [
                "Codice"
            ];
            $whereValori[$i] = [
                $input["idLuogo"]
            ];
            $whereTipi[$i] = [
                "i"
            ];
        }

        $nuovoTipo = $input["nuovo_tipo"] ?? $input["tipo_vecchio"];
        if (
            $input["tipo_vecchio"] !== "universitario" &&
            $input["tipo_vecchio"] !== $nuovoTipo
        ) {

            $delete[] = [
                "tabella" => ucfirst($input["tipo_vecchio"]),
                "whereCampi" => [
                    "Codice_Uni",
                    "Codice_Stanza"
                ],
                "whereValori" => [
                    $input["idUni"],
                    $input["idStanza"]
                ],
                "whereTipi" => [
                    "i",
                    "i"
                ]
            ];
        }

        if ($input["tipo_vecchio"] === "esterno") {

            if ($input["indirizzo"] !== null) {

                $i = count($tabelle);

                $tabelle[$i] = "Indirizzo";

                $campi[$i] = [
                    "Codice_Prov",
                    "Codice_Citta",
                    "N_Civico",
                    "Via",
                    "Nome"
                ];

                $valori[$i] = [
                    $input["indirizzo"]["provincia"],
                    $input["indirizzo"]["citta"],
                    $input["indirizzo"]["civico"],
                    $input["indirizzo"]["via"],
                    $input["indirizzo"]["nomeVia"]
                ];

                $tipi[$i] = [
                    "s",
                    "s",
                    "i",
                    "s",
                    "s"
                ];

                $whereCampi[$i] = [
                    "Codice_Prov",
                    "Codice_Citta",
                    "N_Civico"
                ];

                $whereValori[$i] = [
                    $input["idProv"],
                    $input["idCitta"],
                    $input["idCivico"]
                ];

                $whereTipi[$i] = [
                    "s",
                    "s",
                    "i"
                ];
            }
        } else {
            if ($input["cod_stanza"] !== null) {
                $i = count($tabelle);
                $tabelle[$i] = "Universitario";
                $campi[$i] = ["Codice"];
                $valori[$i] = [$input["cod_stanza"]];
                $tipi[$i] = ["i"];

                $whereCampi[$i] = [
                    "Codice_Uni",
                    "Codice"
                ];
                $whereValori[$i] = [
                    $input["idUni"],
                    $input["idStanza"]
                ];
                $whereTipi[$i] = [
                    "i",
                    "i"
                ];
            }
            if ($nuovoTipo === "classe") {
                $i = count($tabelle);
                $tabelle[$i] = "Classe";
                $campi[$i] = [ "Lab"];
                $valori[$i] = [ $input["lab"]];
                $tipi[$i] = ["i"];

                $whereCampi[$i] = [
                    "Codice_Uni",
                    "Codice_Stanza"
                ];
                $whereValori[$i] = [
                    $input["idUni"],
                    $input["cod_stanza"] ?? $input["idStanza"]
                ];
                $whereTipi[$i] = [
                    "i",
                    "i"
                ];
            } else if ($nuovoTipo === "ufficio") {
                $i = count($tabelle);
                $tabelle[$i] = "Ufficio";
                $campi[$i] = [ "Matricola"];
                $valori[$i] = [$input["assegnato"]];
                $tipi[$i] = ["i"];

                $whereCampi[$i] = [
                    "Codice_Uni",
                    "Codice_Stanza"
                ];
                $whereValori[$i] = [
                    $input["idUni"],
                    $input["cod_stanza"] ?? $input["idStanza"]
                ];
                $whereTipi[$i] = [
                    "i",
                    "i"
                ];
            }
        }

        $dbh->updateElement(
            $tabelle,
            $campi,
            $valori,
            $tipi,
            $whereCampi,
            $whereValori,
            $whereTipi,
            $delete,
            $message,
            $success
        );
        break;
    case "editSede":
        $tabelle = [];
        $campi = [];
        $valori = [];
        $tipi = [];
        $whereCampi = [];
        $whereValori = [];
        $whereTipi = [];
        $vecchiaImmagine = null;
        $nuovaImmagine = null;

        if ($input["indirizzo"] !== null) {
            $i = count($tabelle);
            $tabelle[$i] = "Indirizzo";
            $campi[$i] = [
                "Codice_Prov",
                "Codice_Citta",
                "N_Civico",
                "Via",
                "Nome"
            ];
            $valori[$i] = [
                $input["indirizzo"]["provincia"],
                $input["indirizzo"]["citta"],
                $input["indirizzo"]["civico"],
                $input["indirizzo"]["via"],
                $input["indirizzo"]["nomeVia"]
            ];
            $tipi[$i] = [
                "s",
                "s",
                "i",
                "s",
                "s"
            ];
            $whereCampi[$i] = [
                "Codice_Prov",
                "Codice_Citta",
                "N_Civico"
            ];
            $whereValori[$i] = [
                $input["idProv"],
                $input["idCitta"],
                $input["idCivico"]
            ];
            $whereTipi[$i] = [
                "s",
                "s",
                "i"
            ];
        }

        // MODIFICA SEDE
        $campiSede = [];
        $valoriSede = [];
        $tipiSede = [];
        if($input["nome"] !== null){
            $campiSede[] = "Nome";
            $valoriSede[] = $input["nome"];
            $tipiSede[] = "s";
        }
        if($input["descrizione"] !== null){
            $campiSede[] = "Descrizione";
            $valoriSede[] = $input["descrizione"];
            $tipiSede[] = "s";
        }
        if($input["descrizioneImm"] !== null){
            $campiSede[] = "Descrizione_Img";
            $valoriSede[] = $input["descrizioneImm"];
            $tipiSede[] = "s";
        }
        if($input["nuovaImmagine"] ?? false){
            $file = $_FILES["file"];
            $estensione = pathinfo(
                $file["name"],
                PATHINFO_EXTENSION
            );
            $nuovaImmagine = uniqid("sede_").".".$estensione;
            if(!move_uploaded_file(
                $file["tmp_name"],
                UPLOAD_DIR.$nuovaImmagine
            )){
                $success = false;
                $message = "Errore caricamento immagine";
                break;
            }
            $vecchiaImmagine = $dbh -> getImmagineSede($input["idSede"]);
            $campiSede[] = "Path";
            $valoriSede[] = $nuovaImmagine;
            $tipiSede[] = "s";
        }
        if(count($campiSede) > 0){

            $i = count($tabelle);

            $tabelle[$i] = "Sede";

            $campi[$i] = $campiSede;
            $valori[$i] = $valoriSede;
            $tipi[$i] = $tipiSede;

            $whereCampi[$i] = [
                "Codice"
            ];

            $whereValori[$i] = [
                $input["idSede"]
            ];

            $whereTipi[$i] = [
                "i"
            ];
        }

        $dbh->updateElement(
            $tabelle,
            $campi,
            $valori,
            $tipi,
            $whereCampi,
            $whereValori,
            $whereTipi,
            $delete,
            $message,
            $success
        );
        if($success && $vecchiaImmagine !== null){
            if(file_exists(UPLOAD_DIR.$vecchiaImmagine)){
                unlink(UPLOAD_DIR.$vecchiaImmagine);
            }
        }
        if(!$success && $nuovaImmagine !== null){
            if(file_exists(UPLOAD_DIR.$nuovaImmagine)){
                unlink(UPLOAD_DIR.$nuovaImmagine);
            }
        }
        break;
    case "delete":
        $dbh->deleteElementi(
            "luogo",
            $input["idLuogo"],
            $message,
            $success
        );
        break;
    case "deleteSede":
        $immagine  = $dbh -> getImmagineSede($input["idSede"]);
        $dbh->deleteElementi(
            "sede",
            $input["idSede"],
            $message,
            $success
        );
        if($success && $immagine){
            $path = UPLOAD_DIR.$immagine;
            if(file_exists($path)){
                unlink($path);
            }
        }
        break;
    case "addProvincia":

        $dbh->saveRecordElementi(
            "provincia",
            $input["provincieDaAggiungere"],
            [],
            [],
            $message,
            $success
        );
    break;
    case "addCitta":

        $dbh->saveRecordElementi(
            "citta",
            $input["cittaDaAggiungere"],
            [],
            [],
            $message,
            $success
        );
    break;
    case "editProvincia":

        $dbh->saveRecordElementi(
            "provincia",
            $input["provincieDaAggiungere"] ?? [],
            $input["provincieModificare"] ?? [],
            $input["provincieDaEliminare"] ?? [],
            $message,
            $success
        );
    break;
    case "editCitta":

        $dbh->saveRecordElementi(
            "citta",
            $input["provincieDaAggiungere"] ?? [],
            $input["provincieModificare"] ?? [],
            $input["provincieDaEliminare"] ?? [],
            $message,
            $success
        );
    break;
    case "deleteProvincia":

        $dbh->deleteRecordElementi(
            "provincia",
            $input["provincieDaEliminare"],
            $message,
            $success
        );
        break;
    case "deleteCitta":

        $dbh->deleteRecordElementi(
            "citta",
            $input["cittaDaEliminare"],
            $message,
            $success
        );
        break;
}

$response=[
    "message" => $message,
    "success" => $success
];

echo json_encode($response);
exit;