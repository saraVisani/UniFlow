<?php
require_once("Bootstrap.php");

$templateParams["titolo"] = "Eventi Persona | UniFlow";
$templateParams["name"] = "Eventi";
$templateParams["css"][] = "../CSS/archivi.css";
$templateParams["js"][] = "../Js/eventiPersona.js";

require("Template/base.php");
?>