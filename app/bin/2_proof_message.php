#!/usr/bin/env php
<?php $minVersion = '7.1'; if( version_compare( PHP_VERSION, $minVersion, '<' ) )
	die( PHP_EOL . "I need php version $minVersion or higher!" . PHP_EOL ); passthru('clear'); ?>
------------------------------------------------------------------------------
 Epsilon Harmony API - Proof message by Dan Nilsson 2018 (mail@dannilsson.se)
------------------------------------------------------------------------------
<?php

// Setup
//

require_once __DIR__ . '/../lib/_init.php';

use Yrse\Util\HarmonyHelper;
use Yrse\Util\HarmonyMessageHelper;
use Yrse\Util\HarmonyFolderHelper;

// Paths
//

define( 'NL_BASE_PATH',				$config->nl_factory->base_path );
define( 'RECIPE_PATH',				NL_BASE_PATH . '/recipe/' );
define( 'QUEUE_PATH',				NL_BASE_PATH . '/queue/' );

// Main
//

$errMessage = '';

$harmonyHelper 	= new HarmonyHelper($config);
$folderApi 		= new HarmonyFolderHelper($harmonyHelper);
$messageApi		= new HarmonyMessageHelper($harmonyHelper);

array_map( 'unlink', glob( QUEUE_PATH . '*_PROOF_*' ) );

foreach( glob( RECIPE_PATH . '*.yaml' ) as $recipeFile )
{
	if( strlen( $errMessage ) > 0 ) break;

	if(
		!file_exists( $recipeFile ) ||
		( substr( basename( $recipeFile ), 0, 1 ) === '_' )
	) continue;

	$recipeData = yaml_parse_file( $recipeFile );

	// Remove shared params
	unset($recipeData['base']);

	// Build json body
	//

	foreach( $recipeData as $site => $params )
	{
		// Site: sweden, norway, denmark or finland
		//

		if( is_array( $params ) )
		{
			// Newsletter params
			//

			foreach( $params as $param )
			{
				echo NL . '--> ' . $param['name'] . NL;


				// Prepare lang stuff
				//

				$searchLang = ['Sweden', 'Norway', 'Denmark', 'Finland'];
				$site = ucfirst( strtolower( $site ) );

				$siteIndex = str_replace(
					$searchLang, [1, 2, 3, 4], $site
				);

				$langCode = str_replace(
					$searchLang, ['se', 'no', 'dk', 'fi'], $site
				);

				$unitId = getSetting('buid_' . $langCode);

				$parentId 			= $folderApi->getIdByName( $param['campaign_name'], $unitId );
				$message			= $messageApi->getByName( $param['name'], $unitId );
				$messageId			= $message['id'];
				$messageParentId	= $message['parentId'];
				$messageName 		= $message['name'];
				$proofers			= implode(',', $param['proofers']);

				// Verify we're searching in the right folder
				if( !$messageParentId === $parentId ) break;

				$jsonBody = [
					'id'				=> $messageId,
					'contentType'		=> 'HTML',
					'subjectPrefix' 	=> $messageName,
					'sendTo'			=> $proofers,
				];

				$outputFile = $siteIndex . '_' . strtoupper($langCode) . '_PROOF_' . $messageName . '.json';

				file_put_contents(
					QUEUE_PATH . $outputFile,
					json_encode( $jsonBody,
						JSON_UNESCAPED_UNICODE |
						JSON_PRETTY_PRINT
					)
				);
			}
		}
	}
}

if( strlen( $errMessage ) > 0 )
{
	echo $errMessage . NL;
}
else
{
	execQueue();
}
