#!/usr/bin/env php
<?php $minVersion = '7.1'; if( version_compare( PHP_VERSION, $minVersion, '<' ) )
	die( PHP_EOL . "I need php version $minVersion or higher!" . PHP_EOL ); passthru('clear'); ?>
---------------------------------------------------------------------------
 YRSE Newsletter generator script by Dan Nilsson 2015 (mail@dannilsson.se)
---------------------------------------------------------------------------
<?php

// Setup
//

require_once __DIR__ . '/../lib/_init.php';

$languages = ['se', 'no', 'dk', 'fi'];

if( $argc != 2 ) die( 'Opps... please enter path to newsletter source files!' . NL );

define( 'BASE_PATH',				APPLICATION_PATH . '/../newsletter/' );
define( 'NL_SRC_PATH',				BASE_PATH . 'src/' . $argv[1] . '/' );
define( 'DIST_PATH',				BASE_PATH . 'dist/' );

$errMessage = '';


// Wash out dist path
//

passthru( '/bin/rm -rf ' . DIST_PATH . '*' );


// Init template engine
//

try
{
	$twg = new Twig_Environment( new Twig_Loader_Filesystem( NL_SRC_PATH ), [ 'debug' => true ] );
}
catch( Exception $e )
{
	die( $e->getRawMessage() . NL );
}


// Generate unsub tracker
//

$campaignTmpl = basename( glob( NL_SRC_PATH . 'campaign_*.twig' )[0] );

if( substr( $argv[1], 0, 9 ) == 'treatment' )
{
	$year 				= substr( explode( '.', explode( '_', $campaignTmpl )[1] )[0], -2, 2 );
	$monthName 			= 'treatment';
	$unsubFirstPart 	= $year . '_';
}
else
{
	$year				= substr( explode( '.', explode( '_', $campaignTmpl )[1] )[0], 0, 2 );
	$monthName			= explode( '.', explode( '_', $campaignTmpl )[2] )[0];
	$unsubFirstPart 	= explode( '.', explode( '_', $campaignTmpl )[1] )[0];
}

// Main loop
//

foreach( new DirectoryIterator( NL_SRC_PATH ) as $fileInfo )
{
	if( strlen( $errMessage ) > 0 ) break;
	if(
		$fileInfo->isDot() ||
		$fileInfo->isFile() ||
		( strpos( $fileInfo->getFilename(), '_' ) === 0 )
	) continue;

	$nlDirName = $fileInfo->getFilename();
	$nlNameParts = explode( '_', $nlDirName );

	// Build unsub and nlt tracker
	$nltSuffix = $nlNameParts[1];

	$unsubLastPart = ( is_numeric( $nlNameParts[1] )
		? ( strlen( $nlNameParts[1] ) < 2
			? '0' . $nlNameParts[1]
			: $nlNameParts[1] )
		: $nlNameParts[1] );


	if( count( $nlNameParts ) === 3 )
	{
		$unsubLastPart 	= $unsubLastPart . '_' . $nlNameParts[2];
		$nltSuffix 		= $nlNameParts[1] . '_' . $nlNameParts[2];
	}

	$yearMonth 				= $unsubFirstPart;
	$unsubTracker 			= $unsubFirstPart . $unsubLastPart;
	$nltTracker				= substr( $monthName, 0, 3 ) . $year . '_' . $nltSuffix;
	$campaignNamePrefix		= $yearMonth . '_' . ucfirst( $monthName );
	$nlNumber 				= strtoupper( $nlNameParts[0] ) . $nlNameParts[1];

	mkdir( DIST_PATH . $nltTracker, 0777 ); // ex. dist/maj15_7 | dist/treatment15_birthday


	// Render html
	//

	foreach( $languages as $lang )
	{
		if( !file_exists( NL_SRC_PATH . $nlDirName . '/' . $lang ) ) continue;

		$campaignName = $campaignNamePrefix . '_' . strtoupper($lang);
		$htmlPremailedPath = $nlNumber . '/' . $nltTracker . '_' . $lang . '.html';

		try
		{
			$nlHtmlRender = $twg->render( $nlDirName . '/lang_specific.twig', [
				'base_tmpl'				=> $twg->loadTemplate( $campaignTmpl ),				// campaign_1802_februari.twig | campaign_treatment18.twig
				'lang_code'				=> $lang,											// se, no, dk, fi
				'campaign_name'			=> $campaignName,									// 1803_Mars_SE
				'html_premailed_path'	=> $htmlPremailedPath,								// NL1/feb18_1_se.html
				'nlt_tracker'			=> $nltTracker, 									// feb18_1 | feb18_1_S | feb18_1_NDF | treatment15_birthday
				'unsub_tracker'			=> $unsubTracker,									// 180201 | 180201_reminder | 1802vinnare
				'harmony_folder'		=> 'Camp_VPI_20' . $year,							// Camp_VPI_2018
				'harmony_tag'			=> 'camp_' . $yearMonth,							// camp_1808
				'local_proof'			=> true,
			]);

		}
		catch( Exception $e )
		{
			$errMessage .= $e->getRawMessage() . NL;
			break;
		}


		// Set image sizes
		$nlHtmlRender = preg_replace_callback( '/<img.*src="images\/(content|template)\/([a-z0-9-\._]+)".*width="".*\/>/',
			function( $matches ) use( $nlDirName, $lang ) {

				$imgTag		= $matches[0];
				$imgDir		= $matches[1];
				$imgName 	= $matches[2];

				$imgDir		= NL_SRC_PATH . $nlDirName . '/' . $lang . '/images/' . $imgDir . '/' . $imgName;

				$imgWidth	= @getimagesize( $imgDir )[0];
				// $imgHeight	= @getimagesize( $imgDir )[1];

				$imgWidth	= floor( $imgWidth / 2 );
				// $imgHeight	= floor( $imgHeight / 2 );

				// Fit canvas
				if( $imgWidth > 639 )
				{
					$imgWidth = 640;
				}
				elseif( $imgWidth > 319 )
				{
					$imgWidth = 320;
				}

				// $imgTag = str_replace('width=""', 'width="' . $imgWidth . '" height="' . $imgHeight . '" style="max-width:' . $imgWidth . 'px;"', $imgTag);
				$imgTag = str_replace('width=""', 'width="' . $imgWidth . '" style="max-width:' . $imgWidth . 'px;"', $imgTag);

				return $imgTag;

			}, $nlHtmlRender
		);


		// escape special tags ie. <# and </#
		$nlHtmlRender = str_replace( ['<#', '</#'], ['&lt;#', '&lt;/#'], $nlHtmlRender );

		// Copy src images to dist
		$copyDistPath = DIST_PATH . $nltTracker . '/' . $lang;
		$copySrcPath = NL_SRC_PATH . $nlDirName . '/' . $lang . '/images/';

		mkdir( $copyDistPath . '/images/content', 0777, true );
		mkdir( $copyDistPath . '/images/template', 0777, true );

		foreach( glob( $copySrcPath . 'content/*' ) as $image )
		{
			$image = basename( $image );
			copy( $copySrcPath . 'content/' . $image, $copyDistPath . '/images/content/' . $image );
		}

		foreach( glob( $copySrcPath . 'template/*' ) as $image )
		{
			$image = basename( $image );
			copy( $copySrcPath . 'template/' . $image, $copyDistPath . '/images/template/' . $image );
		}

		file_put_contents( $copyDistPath . '/index.html', $nlHtmlRender );

	}
}

if( strlen( $errMessage ) > 0 )
{
	echo $errMessage . NL;
}
else
{
	echo 'Newsletter generated successfully!' . NL;
	// passthru( 'open ' . DIST_PATH );
}
