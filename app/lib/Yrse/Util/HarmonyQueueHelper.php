<?php

namespace Yrse\Util;

class HarmonyQueueHelper
{
	const URL = 'https://api.harmony.epsilon.com/v1/messages';

	private static $_HY;
	private $_dataBuffer;

	public function __construct( HarmonyHelper $harmonyHelper )
	{
		self::$_HY = $harmonyHelper;
	}

	public function run($cmd, $jsonBody, $unitId)
	{
		$this->_setDataBuffer($jsonBody);

		try
		{
			$request = $this->{'_' . $cmd}(); // CREATE | UPDATE | PROOF | APPROVE | SCHEDULE
		}
		catch(\Error $e)
		{
			clog($e->getMessage());
		}

		try
		{
			$response = self::$_HY->callAPI(
				self::URL . $request['query'],
				$request['body'],
				$unitId,
				$request['method']
			);

			if( $response->getStatusCode() === 200 )
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

	private function _CREATE()
	{
		$body = $this->_getDataBuffer();

		$requestBody = [
			'query' 	=> '?applyLinks=false',
			'body' 		=> $body,
			'method' 	=> 'POST',
		];

		return $requestBody;
	}

	private function _UPDATE()
	{
		$dataJson = $this->_getDataBuffer();
		$dataArray = json_decode( $dataJson, true );

		$id = $dataArray['id'];

		// We need to re-deploy after update
		if( array_key_exists('deployId', $dataArray) && array_key_exists('unitId', $dataArray) )
		{
			$deployId 	= $dataArray['deployId'];
			$uId 		= $dataArray['unitId'];

			unset($dataArray['deployId']);
			unset($dataArray['unitId']);

			$response = self::_cancelDeployment([
				'unitId'	=> $uId,
				'messageId' => $id,
				'deployId' 	=> $deployId,
			]);
		}

		$requestBody = [
			'query' 	=> '/' . $id . '?applyLinks=false',
			'body' 		=> $dataJson,
			'method' 	=> 'PUT',
		];

		return $requestBody;
	}

	private function _PROOF()
	{
		$dataJson = $this->_getDataBuffer();
		$dataArray = json_decode( $dataJson, true );

		$id = $dataArray['id'];

		$requestBody = [
			'query' => '/' . $id . '/proof',
			'body' => $dataJson,
			'method' => 'PUT',
		];

		return $requestBody;
	}

	private function _APPROVE()
	{
		$dataJson = $this->_getDataBuffer();
		$dataArray = json_decode( $dataJson, true );

		$id = $dataArray['id'];

		$requestBody = [
			'query' => '/' . $id . '/activate/lite',
			'body' => $dataJson,
			'method' => 'PUT',
		];

		return $requestBody;
	}

	private function _SCHEDULE()
	{
		$dataJson = $this->_getDataBuffer();
		$dataArray = json_decode( $dataJson, true );

		$id = $dataArray['id'];

		$date = new \DateTime($dataArray['sendDate']);

		if( $dataArray['lang'] === 'fi' && !array_key_exists('override_time', $dataArray) )
			$date->sub( new \DateInterval('PT1H') );

		$timeStamp = $date->getTimestamp() * 1000;

		$body = [
			'audienceScheduleParams' => [
				'lockAudienceType' => "DEPLOYMENT_TIME",
			],
			"limitEmailDeliveryRate" => false,
			"mdmSeedListId" => $dataArray['seedListId'],
			"deploymentDate" => $timeStamp,
			"type" => 'BATCH',
		];

		$requestBody = [
			'query' => '/' . $id . '/schedule',
			'body' => json_encode($body),
			'method' => 'PUT',
		];

		return $requestBody;
	}

	private function _getDataBuffer()
	{
		return $this->_dataBuffer;
	}

	private function _setDataBuffer($data)
	{
		$this->_dataBuffer = $data;
	}

	private static function _cancelDeployment(array $params = [])
	{
		$url = self::URL . '/' . $params['messageId'] . '/deployment/' . $params['deployId'] . '/cancel';

		try
		{
			$response = self::$_HY->callAPI(
				$url, null, $params['unitId'], 'PUT'
			);

			if( $response->getStatusCode() === 200 )
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
