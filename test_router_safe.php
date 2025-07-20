<?php
// Safe router pattern - SHOULD BE WHITELISTED
$alias = $_REQUEST["alias"];
switch ($_REQUEST["alias"]) {
    case "san-pham":
        $source = "product-detail";
        break;
}
include "sources/$source.php";
?>