<?php

// Init app
//

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'memory_limit', '128M' );
date_default_timezone_set( 'Europe/Stockholm' );

define( 'APPLICATION_PATH', realpath( dirname( __DIR__ ) ) );

require_once APPLICATION_PATH . '/vendor/autoload.php';


// Set config
//

$configPaths = sprintf(
    "%s/config/{,*.}{global,local}.php",
    APPLICATION_PATH
);

$config = \Zend\Config\Factory::fromFiles(
	glob( $configPaths, GLOB_BRACE | GLOB_NOSORT ),
	true
);

// Helper functions
//

require_once APPLICATION_PATH . '/lib/helpers.php';
