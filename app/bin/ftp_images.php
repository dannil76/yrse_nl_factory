#!/usr/bin/env php
<?php $minVersion = '7.1'; if( version_compare( PHP_VERSION, $minVersion, '<' ) )
	die( PHP_EOL . "I need php version $minVersion or higher!" . PHP_EOL ); passthru('clear'); ?>
----------------------------------------------------------------------------------
 YRSE Newsletter ftp upload image script by Dan Nilsson 2016 (mail@dannilsson.se)
----------------------------------------------------------------------------------
<?php

// Setup
//

require_once __DIR__ . '/../lib/_init.php';

use FtpClient\FtpClient;

define( 'BASE_PATH',		APPLICATION_PATH . '/../newsletter/' );
define( 'DIST_PATH',		BASE_PATH . 'dist/' );

define( 'FTP_HOST',			$config->ftp_profile->host );
define( 'FTP_USER_SUFFIX',	$config->ftp_profile->user_suffix );
define( 'FTP_PASS_SND',		$config->ftp_profile->password->snd );
define( 'FTP_PASS_F',		$config->ftp_profile->password->f );

$errMessage = '';

// Init ftp instance
//

$ftp = new FtpClient();

// Main loop
//

foreach( new DirectoryIterator( DIST_PATH ) as $fileInfo )
{
	if( strlen( $errMessage ) > 0 ) break;
	if(
		$fileInfo->isDot() ||
		$fileInfo->isFile() ||
		( strpos( $fileInfo->getFilename(), '_' ) === 0 )
	) continue;

	$fileName = $fileInfo->getFilename();
	$path = DIST_PATH . $fileName;

	try
	{
		// SE, NO and DK
		//
		$ftp->connect( FTP_HOST );

		$ftp->raw( 'USER SE_' . FTP_USER_SUFFIX );
		$ftpResponse = $ftp->raw( 'PASS ' . base64_decode( FTP_PASS_SND ) )[0];
		$ftp->pasv(true);

		if( (int) substr( $ftpResponse, 0, 3 ) !== 230 )
			$errMessage .= 'FTP Login error: ' . substr( $ftpResponse, 4 ) . NL;

		if( !$ftp->isDir( $fileName ) )
			$ftp->mkdir( $fileName );


		if( file_exists( $path . '/se/images' ) )
		{
			$dir = $fileName . '/se';

			if( !$ftp->isDir( $dir ) )
			{
				$ftp->mkdir( $dir );
			}

			$ftp->putAll( $path . '/se', $dir );

			echo 'Uploading... [se] -> ' . $fileName . NL;
		}

		if( file_exists( $path . '/no/images' ) )
		{
			$dir = $fileName . '/no';

			if( !$ftp->isDir( $dir ) )
			{
				$ftp->mkdir( $dir );
			}

			$ftp->putAll( $path . '/no', $dir );

			echo 'Uploading... [no] -> ' . $fileName . NL;
		}

		if( file_exists( $path . '/dk/images' ) )
		{
			$dir = $fileName . '/dk';

			if( !$ftp->isDir( $dir ) )
			{
				$ftp->mkdir( $dir );
			}

			$ftp->putAll( $path . '/dk', $dir );

			echo 'Uploading... [dk] -> ' . $fileName . NL;
		}

		// FI
		//
		if( file_exists( $path . '/fi/images' ) )
		{
			$ftp->close();
			$ftp->connect( FTP_HOST );

			$ftp->raw( 'USER FI_' . FTP_USER_SUFFIX );
			$ftpResponse = $ftp->raw( 'PASS ' . base64_decode( FTP_PASS_F ) )[0];
			$ftp->pasv(true);

			if( (int) substr( $ftpResponse, 0, 3 ) !== 230 )
				$errMessage .= 'FTP Login error: ' . substr( $ftpResponse, 4 ) . NL;

			if( !$ftp->isDir( $fileName ) )
				$ftp->mkdir( $fileName );

			$dir = $fileName . '/fi';

			if( !$ftp->isDir( $dir ) )
			{
				$ftp->mkdir( $dir );
			}

			$ftp->putAll( $path . '/fi', $dir );

			echo 'Uploading... [fi] -> ' . $fileName . NL;
		}

	}
	catch( FtpException $e )
	{
		$errMessage .= $e->getMessage() . NL;
	}

}

echo NL;

if( strlen( $errMessage ) > 0 )
{
	echo $errMessage;
}
else
{
	echo 'Images uploaded successfully!' . NL;
}
