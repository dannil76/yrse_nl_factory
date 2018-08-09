#!/usr/bin/env php
<?php $minVersion = '7.1'; if( version_compare( PHP_VERSION, $minVersion, '<' ) )
	die( PHP_EOL . "I need php version $minVersion or higher!" . PHP_EOL ); passthru('clear'); ?>
---------------------------------------------------------------------------------------
 YRSE Newsletter screen dump generator script by Dan Nilsson 2015 (mail@dannilsson.se)
---------------------------------------------------------------------------------------
<?php

// Setup
//

require_once __DIR__ . '/../lib/_init.php';

$languages = ['se', 'no', 'dk', 'fi'];

define( 'HTMLTOIMG_BIN_PATH',		APPLICATION_PATH . '/vendor/bin/' );
define( 'BASE_PATH',				APPLICATION_PATH . '/../newsletter/' );
define( 'DIST_PATH',				BASE_PATH . 'dist/' );
define( 'SCREEN_DUMP_PATH',			BASE_PATH . 'screen_dump/' );


// Main loop
//

$errMessage = '';
foreach( new DirectoryIterator( DIST_PATH ) as $fileInfo )
{
	if( strlen( $errMessage ) > 0 ) break;
	if(
		$fileInfo->isDot() ||
		$fileInfo->isFile()
	) continue;

	$nlPathName = $fileInfo->getFilename();
	$nlPathNameParts = explode( '_', $nlPathName );

	$nlNum = $nlPathNameParts[1];
	$nlName = $nlPathNameParts[0];

	$nlScreenDumpDestPath = SCREEN_DUMP_PATH . 'nl_screen_dump_' . $nlName;

	if( !file_exists( $nlScreenDumpDestPath ) )
		mkdir( $nlScreenDumpDestPath, 0777, true );

	// Render screen dump from html
	//

	foreach( $languages as $lang )
	{
		if( !file_exists( DIST_PATH . $nlPathName . '/' . $lang ) ) continue;

		echo NL . 'Generating screen dump for newsletter: ' . $nlPathName . ' ' . $lang . NL;
		passthru( HTMLTOIMG_BIN_PATH . 'wkhtmltoimage-osx-x86-64 --width 700 ' . DIST_PATH . $nlPathName . '/' . $lang . '/index.html ' . $nlScreenDumpDestPath . '/' . $lang . '_' . $nlPathName . '.jpg' );
	}
}

if( strlen( $errMessage ) > 0 )
{
	echo $errMessage . NL;
}
else
{
	echo 'Screen dumps generated successfully!' . NL;
	passthru( 'open ' . SCREEN_DUMP_PATH );
}
