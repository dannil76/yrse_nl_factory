#!/usr/bin/env php
<?php $minVersion = '7.1'; if( version_compare( PHP_VERSION, $minVersion, '<' ) )
	die( PHP_EOL . "I need php version $minVersion or higher!" . PHP_EOL ); passthru('clear'); ?>
--------------------------------------------------------------------------------------
 Epsilon Harmony API - Create/update message by Dan Nilsson 2018 (mail@dannilsson.se)
--------------------------------------------------------------------------------------
<?php

// Setup
//

require_once __DIR__ . '/../lib/_init.php';

use WyriHaximus\HtmlCompress\Factory as HtmlCompressor;

use Yrse\Util\HarmonyHelper;
use Yrse\Util\HarmonyFolderHelper;
use Yrse\Util\HarmonyAudienceHelper;
use Yrse\Util\HarmonyMessageHelper;

// Paths
//

define( 'NL_BASE_PATH',				$config->nl_factory->base_path );
define( 'HTML_PATH',				NL_BASE_PATH . '/html/' );
define( 'RECIPE_PATH',				NL_BASE_PATH . '/recipe/' );
define( 'QUEUE_PATH',				NL_BASE_PATH . '/queue/' );


// Check arguments
//

$updateMessage = false;

if( isset( $argv[1] ) && $argv[1] == '--update' )
{
	echo NL . '### UPDATE MODE ###' . NL;
	$updateMessage = true;
}


// Main
//

$errMessage = '';

$harmonyHelper 	= new HarmonyHelper($config);
$folderApi 		= new HarmonyFolderHelper($harmonyHelper);
$audienceApi	= new HarmonyAudienceHelper($harmonyHelper);
$messageApi		= new HarmonyMessageHelper($harmonyHelper);

$htmlCompressor = HtmlCompressor::construct();

array_map( 'unlink', glob( QUEUE_PATH . '*_CREATE_*' ) );

foreach( glob( RECIPE_PATH . '*.yaml' ) as $recipeFile )
{
	if( strlen( $errMessage ) > 0 ) break;

	if(
		!file_exists( $recipeFile ) ||
		( substr( basename( $recipeFile ), 0, 1 ) === '_' )
	) continue;

	$recipeData = yaml_parse_file( $recipeFile );

	// Remove yaml shared param
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
				if( !file_exists( $htmlFile = HTML_PATH . $param['html'] ) ) continue;

				echo NL . '--> ' . $param['name'] . NL;

				$htmlBody = null;
				$htmlBody = trim( file_get_contents( $htmlFile ) );

				if( !array_key_exists( 'campaign_name', $param ) )
				{
					// Get campaign name from html source
					//

					$cmpPattern = '/^<!--\sCAMPAIGN_NAME:\s(?P<campaign_name>\d{4}_(?:Januari|Februari|Mars|April|Maj|Juni|Juli|Augusti|September|Oktober|November|December)_(?:SE|NO|DK|FI))\s-->$/m';

					preg_match( $cmpPattern, $htmlBody, $cmpMatch );

					if( !isset( $cmpMatch['campaign_name'] ) )
					{
						$errMessage .= NL . 'Error! Missing campaign name: ' . $param['html'] . NL;
						break;
					}

					$param['campaign_name'] = $cmpMatch['campaign_name'];
				}


				// Get subject line from html source
				//

				$subPattern = '/^<!--\sSUBJECT_LINE:\s(?P<text>.+)\s-->$/m';
				preg_match( $subPattern, $htmlBody, $subMatch );

				if( !isset( $subMatch['text'] ) )
				{
					$errMessage .= NL . 'Error! Missing subject line: ' . $param['html'] . NL;
					break;
				}

				$param['subject'] = $subMatch['text'];


				// Get urls for tracking
				//

				// preg_match_all( '/href="(https?:\/\/.*?)"/', $htmlBody, $urls );


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


				// Prepare json body
				//

				$unitId = getSetting('buid_' . $langCode);

				$audience = [];
				if( array_key_exists( 'send_to', $param ) )
				{
					foreach( $param['send_to'] as $key )
						$audience[] = $audienceApi->getIdByName( $key['name'], $unitId );
				}

				$mode = '_CREATE_';

				$messageName		= $param['name'];
				$messageSubject		= $param['subject'];
				$messageTags		= isset($param['tags']) ? $param['tags'] : null;
				$parentId 			= $folderApi->getIdByName( $param['campaign_name'], $unitId );
				$htmlBody 			= $htmlCompressor->compress($htmlBody);
				$emailConfigId 		= getSetting( 'emailSetting_' . $langCode );

				$jsonBody = [
					'name'				=> $messageName,
					'parentId'			=> $parentId,
					'includeAudience'	=> $audience,
					'emailConfigId'		=> $emailConfigId,
					'category'			=> 'OTHER_BAU',
					'type'				=> 'MESSAGE',
					'subType'			=> 'EMAIL_LIST_BASED',
					'characterSet'		=> 'UTF_8',

					'contentSubject' => [
						'content' 		=> $messageSubject,
						'contentType'	=> 'TEXT',
						'characterSet'	=> 'UTF_8',
					],

					'html' => [
						'content' 		=> $htmlBody,
						'contentType' 	=> 'HTML',
						'characterSet'	=> 'UTF_8',
					],
				];

				if( !is_null($messageTags) )
					$jsonBody['tags'] = $messageTags;

				if($updateMessage)
				{
					$mode = '_UPDATE_';

					// Retrive message to update
					$message			= $messageApi->getByName( $messageName, $unitId );
					$messageModDate		= $message['modifiedDate'];
					$messageId			= $message['id'];
					$messageParentId	= $message['parentId'];
					$messageName 		= $message['name'];

					// Verify we're searching in the right folder
					if( !$messageParentId === $parentId )
						clog('Error: Message parent id and parent id mismatching!');

					$messageName = array_key_exists( 'new_name', $param )
						? $param['new_name'] : $messageName;

					$jsonUpdateBody = [
						'id'			=> $messageId,
						'name'			=> $messageName,
						'parentId'		=> $messageParentId,
						'modifiedDate'	=> $messageModDate,
					];

					$jsonBody = array_merge($jsonUpdateBody, $jsonBody);


					// Check if deployed
					$messageItem = $messageApi->getMessage( $messageId, $unitId );
					if( $deployments = $messageItem['data']['deployments'] )
					{
						foreach( $deployments as $data ) {
							if( $data['name'] === $messageName && $data['deliveryStatus'] === 'NOT_SENT' )
							{
								$jsonBody['deployId']	= $data['id'];
								$jsonBody['unitId'] 	= $unitId;
							}
						}
					}
				}

				$outputFile = $siteIndex . '_' . strtoupper($langCode) . $mode . $messageName . '.json';

				file_put_contents(
					QUEUE_PATH . $outputFile,
					json_encode( $jsonBody,
						JSON_UNESCAPED_UNICODE |
						JSON_UNESCAPED_SLASHES |
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
