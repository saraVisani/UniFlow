<?php
require_once("Bootstrap.php");
//prepariamo parametri template
$templateParams["titolo"] = "Sede | UniFlow";
$templateParams["name"] = "Sede";
$templateParams["css"][] = "../CSS/index.css";
$templateParams["js"][] = "../Js/campusSlider.js";
$templateParams["js"][] = "../Js/placeholder.js";

/*var_dump($templateParams["articolicasuali"]);//debug*/
require("Template/base.php");
?>