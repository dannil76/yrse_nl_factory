<?php

define( 'NL', PHP_EOL );


// Harmony API
//

define( 'CLIENT_ID', 		$config->harmony_profile->api->client_id );
define( 'CLIENT_SECRET', 	$config->harmony_profile->api->client_secret );
define( 'API_USR_ID', 		$config->harmony_profile->api->user_id );
define( 'PASSWORD', 		$config->harmony_profile->api->user_password );
define( 'AUTH_URL', 		$config->harmony_profile->api->auth_url );
define( 'BASE_URL', 		$config->harmony_profile->api->base_url );

define( 'HY_SETTINGS',		$config->harmony_profile->settings->toArray() );


// Get stuff
//

function getSetting( $key )
{
	static $settings;

	if( !is_array( $settings ) )
	{
		$settings = [];

		foreach( HY_SETTINGS as $index => $value )
		{
			$settings[$index] = $value;
		}
	}

	if( !array_key_exists($key, $settings) )
		return false;

	return $settings[$key];
}


// Data helpers
//

function sanitizeOutput( $buffer, $enable = true )
{
	if( !$enable ) return $buffer;

	$search = array(
		'/\>[^\S ]+/s',  // strip whitespaces after tags, except space
		'/[^\S ]+\</s',  // strip whitespaces before tags, except space
		'/(\s)+/s'       // shorten multiple whitespace sequences
	);

	$replace = array(
		'>',
		'<',
		'\\1'
	);

	return preg_replace( $search, $replace, $buffer );
}


// Debug helpers
//

function wline($string)
{
	echo $string . NL;
}

function debug( $data, $halt = false, $web = true )
{
	echo ($web) ? '<pre>' : NL;

	if( is_array( $data ) )
	{
		print_r( $data );
	}
	elseif( is_object( $data ) )
	{
		var_dump( $data );
	}
	else
	{
		echo $data;
	}

	echo ($web) ? '</pre>' : NL . NL;

	$halt && die;
}

function dbug( $data )
{
	debug( $data, true );
}

function clog( $data, $halt = true )
{
	debug( $data, $halt, false );
}
