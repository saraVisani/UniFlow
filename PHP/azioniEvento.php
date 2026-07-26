<?php
require_once("Bootstrap.php");

$templateParams["titolo"] = "Azioni Evento | UniFlow";
$templateParams["name"] = "Azioni su Evento";
$templateParams["css"][] = "../CSS/archivi.css";
$templateParams["js"][] = "../Js/azioniEvento.js";

require("Template/base.php");
?>