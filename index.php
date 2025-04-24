<?php

/*
    *   File name: index.php
    *   Created by: Brandon Hills
    *   Contact: brandon@goodstewarddesigns.com
    *   Last modified:
    *
    *   This is the main page.
    *   This page includes the configuration file,
    *   the templates, and any content-specific modules.
*/

// Require the configuration file before any PHP code:
require('./includes/config.inc.php');

// Validate what page to show:
if (isset($_GET['p'])) {
    $p = $_GET['p'];
} elseif (isset($_POST['p'])) {  // Forms
    $p = $_POST['p'];
} else {
    $p = NULL;
}

// Determine what page to display:
switch ($p) {

    case 'home':
        $page = 'home.inc.php';
        $page_title = 'Home | ';
        $page_css = '<link rel="stylesheet" href="assets/css/home.css" />';
        break;

    // Default is to include the home page.
    default:
        $page = 'home.inc.php';
        $page_title = 'Home | ';
        $page_css = '<link rel="stylesheet" href="assets/css/home.css" />';
        break;

} // End of main SWITCH.

// Make sure the file exists:
if (!file_exists('./assets/modules/' . $page)) {
    $page = 'home.inc.php';
    $page_title = 'Home | ';
}

// Include the header file:
include('./includes/header.inc.html');

// Include the content-specific module:
// $page is determined from the above SWITCH.
include('./assets/modules/' . $page);

// Include the footer file to complete the template:
include('./includes/footer.inc.html');

?>