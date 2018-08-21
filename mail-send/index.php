<?php

ini_set( "display_errors", 'stderr' );
ini_set( "error_reporting", E_ALL );

// Set the timezone
date_default_timezone_set( 'Asia/Kolkata' );
// Do not let this script timeout
set_time_limit( 0 );

require_once __DIR__ . '/lib/mailer.php';





/*
 *
 * Extract and Parse the input
 *
 */
$arguments = getopt( 'i:' );

if ( empty( $arguments[ 'i' ] ) ) {
	$response[ 'message' ] = 'Please specify an input, like so';
	$response[ 'message' ] .= '\n' . 'php mail-send/index.php -i data.json';
	fwrite( STDERR, $response[ 'message' ] );
	exit( 1 );
}

$inputFileName = $arguments[ 'i' ];
try {
	$input = json_decode( file_get_contents( $inputFileName ), true );
} catch ( Exception $e ) {
	$response[ 'message' ] = 'Error in processing input.';
	$response[ 'message' ] .= '\n' . $e->getMessage();
	fwrite( STDERR, $response[ 'message' ] );
	exit( 1 );
}

$user = $input[ 'user' ];
$mail = $input[ 'mail' ];
$pricingSheetFilename = $input[ 'pricingSheetFilename' ];
$pricingSheetURL = $input[ 'pricingSheetURL' ];

/*
 *
 * Send a mail with the pricing sheet
 *
 */
// Prepare the envelope
$envelope = [
	'username' => 'google@lazaro.in',
	'password' => 't34m,l4z4r0',
	'from' => [
		'email' => 'google@lazaro.in',
		'name' => $mail[ 'From Name' ] ?? 'The Builders'
	],
	'to' => [
		'email' => $user[ 'email' ],
		// 'email' => 'adityabhat@lazaro.in',
		'name' => $user[ 'name' ]
	],
	'subject' => $mail[ 'Subject' ],
	'body' => preg_replace( '/\R/', '<br>', $mail[ 'Body' ] )
];
// #fornow
if ( ! empty( $pricingSheetURL ) ) {
	$envelope[ 'attachment' ] = [
		'name' => $pricingSheetFilename,
		'url' => $pricingSheetURL
	];
}
// Send the mail
try {
	$response[ 'message' ] = Mailer\send( $envelope );
	die( json_encode( $response ) );
} catch ( Exception $e ) {
	$response[ 'message' ] = 'The mail could not be sent. ' . $e->getMessage();
	fwrite( STDERR, $response[ 'message' ] );
	exit( 1 );
}
