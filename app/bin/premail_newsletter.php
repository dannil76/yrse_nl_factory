#!/usr/bin/env php
<?php $minVersion = '7.1'; if( version_compare( PHP_VERSION, $minVersion, '<' ) )
	die( PHP_EOL . "I need php version $minVersion or higher!" . PHP_EOL ); passthru('clear'); ?>
---------------------------------------------------------------------------
 YRSE Newsletter premailer script by Dan Nilsson 2015 (mail@dannilsson.se)
---------------------------------------------------------------------------
<?php

// Setup
//

require_once __DIR__ . '/../lib/_init.php';

$languages = ['se', 'no', 'dk', 'fi'];

use Adamlc\Premailer\Command;
use Adamlc\Premailer\Email;
use PHPWee\HtmlMin;

define( 'BASE_PATH',		APPLICATION_PATH . '/../' );
define( 'SRC_PATH',			BASE_PATH . '/newsletter/dist/' );			// jun15_3/se,no,dk,fi/index.html
define( 'DIST_PATH',		BASE_PATH . '/Harmony/html/' );				// NLx
define( 'IMG_BASE_URL', 	'pictures.yvesrocher.com' );				// Where to find images

$curlOpt = [
	CURLOPT_VERBOSE					=> false,
	CURLOPT_URL 					=> 'http://premailer.dialect.ca/api/0.1/documents',
	CURLOPT_POST 					=> true,
	CURLOPT_RETURNTRANSFER 			=> true,
	CURLOPT_FORBID_REUSE 			=> true,
	CURLOPT_FRESH_CONNECT 			=> true,
	CURLOPT_NOPROGRESS 				=> false,
	CURLOPT_PROGRESSFUNCTION 		=> function() { echo '.'; }
];

$errMessage = '';

// Premailer loop
//

foreach( new DirectoryIterator( SRC_PATH ) as $fileInfo )
{
	if( strlen( $errMessage ) > 0 ) break;
	if( $fileInfo->isDot() || $fileInfo->isFile() ) continue;

	$nlName = $fileInfo->getFilename();					// string: jun15_3
	$nlNameParts = explode( '_', $nlName );				// array: [ jun15, 3 ]

	$nlPathName = 'NL' . $nlNameParts[1];

	if( !file_exists( DIST_PATH . $nlPathName ) )
	{
		// DIST_PATH/NL3
		mkdir( DIST_PATH . $nlPathName );
	}

	// Language loop
	//

	foreach( $languages as $lang )
	{
		$nlSrcLangPath = SRC_PATH . $nlName . '/' . $lang;
		if( !file_exists( $nlSrcLangPath ) ) continue;

		$htmlSaveFile = $nlName . '_' . $lang . '.html';

		$htmlRaw = trim( file_get_contents( $nlSrcLangPath . '/index.html' ) );


		// Remove local proof block
		//

		$htmlRaw = preg_replace( '/\s*<!--\sSUBJECT\sLINE\sPROOF\sBLOCK\sSTART\s-->.*<!--\sSUBJECT\sLINE\sPROOF\sBLOCK\sEND\s-->/s', null, $htmlRaw );


		// Premailing using API
		//

		echo 'Premailing: ' . $htmlSaveFile;

		$imageUrl = ( $lang == 'fi' )
			? '/FI/' . $nlName . '/fi/'
			: '/SE/' . $nlName . '/' . $lang . '/';

		$ch = curl_init();
		curl_setopt_array( $ch, $curlOpt );

		curl_setopt( $ch, CURLOPT_POSTFIELDS, 'base_url=http://' . IMG_BASE_URL
			. $imageUrl
			. '&preserve_styles=false&remove_classes=true&adapter=nokogiri&html='
			. rawurlencode( $htmlRaw )
		);

		$result = curl_exec( $ch );
		$httpStatus = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		if( $httpStatus == 201 )
		{
			$jsonResult = json_decode( $result );

			$premailedHtml = file_get_contents( $jsonResult->documents->html );
			$premailedHtml = html_entity_decode( $premailedHtml, ENT_XHTML );

			file_put_contents(
				DIST_PATH . $nlPathName . '/' . $htmlSaveFile,
				// str_replace( '&amp;', '&', HtmlMin::minify( $premailedHtml, false, false ) )
				str_replace( '&amp;', '&', $premailedHtml )
			);

			echo ' ' . $jsonResult->message . NL;
		}
		else
		{
			$errMessage .= 'Error with status code: ' . $httpStatus . NL;
			break;
		}

		curl_close( $ch );
	}

	echo NL;
}

if( strlen( $errMessage ) > 0 )
{
	echo $errMessage;
}
else
{
	echo 'Newsletters premailed successfully!' . NL;
	// passthru( 'open ' . DIST_PATH );
}
