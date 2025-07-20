<?php
// Test file with safe router patterns - SHOULD BE WHITELISTED
$alias = $_REQUEST['alias'];

switch($_REQUEST['alias']) {
    case 'san-pham':
        $source = 'product-detail';
        break;
    case 'lien-he':
        $source = 'contact';
        break;  
    default:
        $source = 'index';
}

if (isset($_REQUEST['alias'])) {
    $source = 'page-' . $_REQUEST['alias'];
}

// Safe include
include "sources/$source.php";
?> 