<?php 

// Config GLOBAL

if( !defined( 'APPLICATION_PATH' ) ) define( 'APPLICATION_PATH', realpath( dirname( __DIR__ ) ) );

return [
	'ftp_profile' => [
		'host' => '',
		'user_suffix' => '',
	],
	'harmony_profile' => [
		'api' => [
			'auth_url' 		=> '',
			'base_url' 		=> '',
			'token_cache' 	=> APPLICATION_PATH . '/cache/token.json',
		],
		'settings' => [
			'buid_se' => '',
			'buid_no' => '',
			'buid_dk' => '',
			'buid_fi' => '',
			'emailSetting_se' => '',
			'emailSetting_no' => '',
			'emailSetting_dk' => '',
			'emailSetting_fi' => '',
			'seedlist_se' => '',
			'seedlist_no' => '',
			'seedlist_dk' => '',
			'seedlist_fi' => '',
		],
	],
	'nl_factory' => [
		'base_path' => APPLICATION_PATH . '/../Harmony',
	],
	'twig' => [
		'debug'	=> false,
		'cache'	=> APPLICATION_PATH . '/cache',
		'charset' => 'utf-8',
	],
];
