<?php

namespace Yrse\Util;

class HarmonyFolderHelper
{
	const URL = 'https://api.harmony.epsilon.com/v1/folder-items/search';

	private static $_HY;

	public function __construct( HarmonyHelper $harmonyHelper )
	{
		self::$_HY = $harmonyHelper;
	}

	public function getIdByName($name, $unitId)
	{
		$request = self::_setRequest($name);

		try
		{
			$response = self::$_HY->callAPI( self::URL, $request, $unitId, 'POST' );
		}
		catch( \Exception $e )
		{
			throw new \Exception($e->getMessage(), 500);
		}

		if( $response->getStatusCode() === 200 )
		{
			$responseBody = json_decode($response->getBody(), true);
			$data = $responseBody['data'][0];

			if( $data['name'] === $name )
			{
				return $data['id'];
			}
		}

		return false;
	}

	private static function _setRequest($name)
	{
		$request = [
			'name' 		=> $name,
			'type' 		=> 'FOLDER',
			'archived'	=> false,
		];

		return json_encode( $request, JSON_UNESCAPED_UNICODE );
	}
}
