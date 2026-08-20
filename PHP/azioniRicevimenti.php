<?php
require_once("Bootstrap.php");

$templateParams["titolo"] = "Azioni Ricevimento | UniFlow";
$templateParams["name"] = "Azioni su Ricevimento";
$templateParams["css"][] = "../CSS/archivi.css";
$templateParams["js"][] = "../Js/azioniRicevimento.js";

require("Template/base.php");
?>