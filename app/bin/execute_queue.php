#!/usr/bin/env php
<?php $minVersion = '7.1'; if( version_compare( PHP_VERSION, $minVersion, '<' ) )
	die( PHP_EOL . "I need php version $minVersion or higher!" . PHP_EOL ); passthru('clear'); ?>
------------------------------------------------------------------------------
 Epsilon Harmony API - Execute queue by Dan Nilsson 2018 (mail@dannilsson.se)
------------------------------------------------------------------------------
<?php

// Setup
//

require_once __DIR__ . '/../lib/_init.php';

use Yrse\Util\HarmonyHelper;
use Yrse\Util\HarmonyQueueHelper;


// Paths
//

define( 'BASE_PATH',	$config->nl_factory->base_path );
define( 'QUEUE_PATH',	BASE_PATH . '/queue/' );


// Main
//

echo NL . 'Processing queue...' . NL;

$errMessage = '';

$harmonyHelper 	= new HarmonyHelper($config);
$queueApi 		= new HarmonyQueueHelper($harmonyHelper);

foreach( glob( QUEUE_PATH . '*.json' ) as $queueFile )
{
	if( strlen( $errMessage ) > 0 ) break;

	if(
		!file_exists( $queueFile ) ||
		( substr( basename( $queueFile ), 0, 1 ) === '_' )
	) continue;

	$queueData = file_get_contents( $queueFile );
	$fileName = basename( $queueFile );

	echo NL . '--> ' . $fileName . NL;

	$fileNameParts 	= explode('_', $fileName);
	$langCode		= strtolower( $fileNameParts[1] );
	$apiCommand		= $fileNameParts[2];

	$unitId = getSetting('buid_' . $langCode);

	try
	{
		$response = $queueApi->run( $apiCommand, $queueData, $unitId );
	}
	catch( \Exception $e )
	{
		$errMessage .= $e->getMessage();
		clog($errMessage);
	}

	$resultCode = $response['resultCode'];
	if( $resultCode == 'OK' ) echo NL . $resultCode . NL;

	unlink($queueFile);
}

if( strlen( $errMessage ) > 0 )
{
	echo $errMessage . NL;
	die;
}

echo NL . 'Done!' . NL;
