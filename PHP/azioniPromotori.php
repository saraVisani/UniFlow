<?php
require_once("Bootstrap.php");

$templateParams["titolo"] = "Azioni Promotore | UniFlow";
$templateParams["name"] = "Azioni su Promotori";
$templateParams["css"][] = "../CSS/archivi.css";
$templateParams["js"][] = "../Js/azioniPromotore.js";

require("Template/base.php");
?>