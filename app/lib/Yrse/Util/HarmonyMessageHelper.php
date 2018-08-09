<?php

namespace Yrse\Util;

class HarmonyMessageHelper
{
	const URL_SEARCH	= 'https://api.harmony.epsilon.com/v1/folder-items/search';
	const URL_MESSAGE	= 'https://api.harmony.epsilon.com/v1/messages/';

	private static $_HY;

	public function __construct( HarmonyHelper $harmonyHelper )
	{
		self::$_HY = $harmonyHelper;
	}

	public function getByName($name, $unitId)
	{
		$request = [
			'name' 		=> $name,
			'type' 		=> 'MESSAGE',
			'subType'	=> 'EMAIL_LIST_BASED',
			'archived'	=> false,
		];

		$jsonRequest = json_encode($request, JSON_UNESCAPED_UNICODE);

		try
		{
			$response = self::$_HY->callAPI( self::URL_SEARCH, $jsonRequest, $unitId, 'POST' );

			if($response->getStatusCode() === 200)
			{
				$responseBody = json_decode($response->getBody(), true);

				foreach($responseBody['data'] as $data)
				{
					if( $data['name'] === $name ) return $data;
				}
			}
		}
		catch( \Exception $e )
		{
			throw new \Exception($e->getMessage(), 500);
		}

		return false;
	}

	public function getMessage($id, $unitId)
	{
		$request = [
			'id' => $id,
		];

		$jsonRequest = json_encode($request, JSON_UNESCAPED_UNICODE);

		$url = self::URL_MESSAGE . $id;

		try
		{
			$response = self::$_HY->callAPI( $url, $request, $unitId, 'GET' );

			if($response->getStatusCode() === 200)
			{
				return json_decode($response->getBody(), true);
			}
		}
		catch( \Exception $e )
		{
			throw new \Exception($e->getMessage(), 500);
		}

		return false;
	}
}
