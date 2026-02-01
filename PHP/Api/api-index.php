<?php
require_once(__DIR__ . "/../Bootstrap.php");
header('Content-Type: application/json');

$response = [
    "user" => [
        "logged" => false,
        "role" => null,
        "level" => null
    ],
    "titles" => [
        "mainTitleOne" => "I Nostri Campus",
        "mainTitleTwo" => "Mappa Interattiva",
        "asideTitleOne" => "Eventi Recenti",
        "asideTitleTwo" => "Domande",
        "asideInnerTitleOne" => "Hai una domanda?",
        "asideInnerTitleTwo" => "Domande Frequenti"
    ],
    "data" => [
        "campus" => $dbh->getAllCampuses(),
        "eventi" => $dbh->getMostRecentPublicEvents(),
        "faq" => $dbh->getMostPopularFAQsByLevel()
    ]
];

if(isUserLoggedIn()){
    $response["user"]["logged"] = true;
    $response["user"]["role"] = $_SESSION["user"]["lavoro"];
    $response["user"]["level"] = $_SESSION["user"]["livello_accesso"];

    $response["titles"] = [
        "mainTitleOne" => "Orari",
        "mainTitleTwo" => "Eventi",
        "mainTitleTwoS1" => "Parte dello Staff",
        "mainTitleTwoS2" => "Iscritto",
        "asideTitleOne" => "Canali Seguiti",
    ];

    if(isUserStudent()){
        if(userLevelForAccess(2)){
            // studentRep
        } else {
            // student normale
        }
        $orario = $dbh->getTimesStudent($_SESSION["user"]["id"]);
        $ricevimenti = $dbh->getReunionStudent($_SESSION["user"]["id"]);
        $response["titles"]["mainTitleThree"] = "Ricevimenti";
        $response["titles"]["asideTitleTwo"] = "Notifiche";
    } else if (isUserProfessor()){
        $orario = $dbh->getTimesProfessor($_SESSION["user"]["id"]);
        $ricevimenti = $dbh->getReunionProfessor($_SESSION["user"]["id"]);
        $response["titles"]["mainTitleThree"] = "Ricevimenti";
        $response["titles"]["asideTitleTwo"] = "Notifiche";
    } else { // segreteria
        $orario = [];
        $ricevimenti = [];
        $response["titles"]["mainTitleOne"] = "Notifiche";
        $response["titles"]["mainTitleThree"] = "Cartine Aule";
    }

    $response["data"] = [
        "orario" => $orario,
        "ricevimenti" => $ricevimenti,
        "canali_Seguiti" => $dbh->getSignInChannals($_SESSION["user"]["id"]),
        "eventi_staff" => $dbh->getStaffEvents($_SESSION["user"]["id"]),
        "eventi_iscritto" => $dbh->getSignInEvents($_SESSION["user"]["id"]),
        "notifiche" => $dbh->getNotifications($_SESSION["user"]["id"])
    ];
}

echo json_encode($response);
exit;
?>