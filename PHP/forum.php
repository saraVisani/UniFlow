<?php
require_once("Bootstrap.php");

$templateParams["titolo"] = "Forum | UniFlow";
$templateParams["name"] = "Forum Generale";
$templateParams["css"][] = "../CSS/placeholder.css";
$templateParams["js"][] = "../Js/forum.js";

require("Template/base.php");
?>