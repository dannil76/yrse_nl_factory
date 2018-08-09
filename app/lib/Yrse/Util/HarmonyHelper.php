<?php

namespace Yrse\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Command\Exception\CommandClientException;
use GuzzleHttp\Command\Exception\CommandServerException;
use Zend\Config\Config;

class HarmonyHelper
{
	private static $_AUTH_URL;
	private static $_BASE_URL;

	private static $_CLIENT_ID;
	private static $_CLIENT_SECRET;
	private static $_USER_ID;
	private static $_USER_PASSWORD;

	private static $_TOKEN_CACHE;

	private static $_CLIENT;

	private $_token;

	public function __construct( Config $config = null )
	{
		$conf = $config->harmony_profile->api;

		self::$_AUTH_URL 		= $conf->auth_url;
		self::$_BASE_URL 		= $conf->base_url;

		self::$_CLIENT_ID 		= $conf->client_id;
		self::$_CLIENT_SECRET 	= $conf->client_secret;

		self::$_USER_ID 		= $conf->user_id;
		self::$_USER_PASSWORD 	= $conf->user_password;

		self::$_TOKEN_CACHE		= $conf->token_cache;

		$this->_setHttpClient();
		$this->_setToken();
	}

	public function callAPI( $url, $requestBody = null, $orgUnitId, $method )
	{
		$token 			= $this->getToken();
		$client 		= $this->getHttpClient();

		$apiResponse 	= null;

		$options = [
			'headers' => [
				'Authorization' 	=> 'Bearer ' . $token['access_token'],
				'X-OUID' 			=> $orgUnitId,
				'Content-Type' 		=> 'application/json',
				'Accept' 			=> 'application/json',
			],
			'progress' 	=> function() { echo '.'; },
		];

		if( $method != 'GET' && isset( $requestBody ) )
		{
			$options['body'] = $requestBody;
		}

		try
		{		
			$apiResponse = $client->request($method, $url, $options);
		}
		catch( ClientException $e )
		{
			throw new \Exception("Error Processing Request: " . Psr7\str( $e->getResponse() ), 500);
		}

		return $apiResponse;
	}


	// HTTP client
	//

	public function getHttpClient()
	{
		if( isset( self::$_CLIENT ) ) return self::$_CLIENT;

		$this->_setHttpClient();
	}

	private function _setHttpClient()
	{
		self::$_CLIENT = new Client();
	}


	// Token
	//

	public function getToken()
	{
		return $this->_token;
	}

	private function _setToken()
	{
		if( ( $token = @file_get_contents( self::$_TOKEN_CACHE ) ) === false )
		{
			$token = $this->_requestToken();
		}
		else
		{
			$token = json_decode( $token , true );

			// check if cached token is still valid
			$dateNow = new \DateTime('now');
			$dateToken = new \DateTime( date( 'Y-m-d H:i:s', $token['expire_time'] ) );

			if($dateNow > $dateToken) $token = $this->_requestToken();
		}

		$this->_token = $token;
	}

	private function _requestToken()
	{
		$authEnc = base64_encode( self::$_CLIENT_ID . ':' . self::$_CLIENT_SECRET );
		$params = 'scope=cn%20mail%20sn%20givenname%20uid%20employeeNumber&grant_type=password'
			. '&username=' . self::$_USER_ID
			. '&password=' . self::$_USER_PASSWORD;

		try
		{
			echo 'Warming up...';
			$client = $this->getHttpClient();
			$res = $client->request('POST', self::$_AUTH_URL, [
				'headers' => [
					'Authorization' => 'Basic ' . $authEnc,
					'Content-Type'	=> 'application/x-www-form-urlencoded'
				],
				'body' => $params,
				// 'progress' 	=> function() { echo '.'; },
			]);
			echo ' ready!' . PHP_EOL;
		}
		catch( ClientException $e )
		{
			throw new \Exception( "Error Processing Request: " . Psr7\str( $e->getResponse() ), 500 );
		}

		if( $res->getStatusCode() === 200 )
		{
			$token = json_decode( $res->getBody(), true );

			$cacheDate = new \DateTime();
			$cacheDate->add( new \DateInterval( 'PT' . ( $token['expires_in'] - 300 ) . 'S' ) );
			$token['expire_time'] = $cacheDate->getTimestamp();

			// Cache it
			file_put_contents( self::$_TOKEN_CACHE, json_encode( $token ) );

			return $token;
		}
	}
}
