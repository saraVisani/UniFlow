<?php
require_once("Bootstrap.php");

$templateParams["titolo"] = "Azioni Luogo | UniFlow";
$templateParams["name"] = "Azioni su Luogo";
$templateParams["css"][] = "../CSS/archivi.css";
$templateParams["js"][] = "../Js/azioniLuogo.js";

require("Template/base.php");
?>