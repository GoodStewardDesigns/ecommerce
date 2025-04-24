<?php

/*
    *   File name: home.inc.php
    *   Created by: Brandon Hills
    *   Contact: brandon@goodstewarddesigns.com
    *   Last modified:
    *
    *   This is the main content module.
    *   This page is included by index.php.
*/

// Redirect if this page was accessed directly:
    if (!defined('BASE_URL')) {

        // Need the BASE_URL, defined in the config file:
        require('../../includes/config.inc.php');
    
        // Redirect to the index page:
        header ('Location: ' . BASE_URL . 'index.php');
        exit;
    
    } // End of defined() IF.
    
?>

<main id="home">
</main>