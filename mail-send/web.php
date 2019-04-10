<?php

ini_set( "display_errors", 0 );
ini_set( "error_reporting", E_ALL );

// Set the timezone
date_default_timezone_set( 'Asia/Kolkata' );
// Do not let this script timeout
set_time_limit( 0 );

// continue processing this script even if
// the user closes the tab, or
// hits the ESC key
ignore_user_abort( true );

// Allow cross-origin requests
header( 'Access-Control-Allow-Origin: *' );

// Respond in JSON format
header( 'Content-Type: application/json' );





require_once __DIR__ . '/lib/mailer.php';





/*
 *
 * Pull out the data from the request
 *
 */
// if ( $_SERVER[ 'CONTENT_TYPE' ] != 'application/json' )
// 	die( json_encode( [
// 		'statusCode' => 1,
// 		'message' => 'Request body should be in JSON.'
// 	] ) );
// $input = json_decode( file_get_contents( 'php://input' ), true );
$subject = $_POST[ 'subject' ] ?? '';
$body = $_POST[ 'body' ] ?? '(blank)';
$toAddress = $_POST[ 'to' ] ?? 'adityabhat@lazaro.in';

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
		'email' => 'omega@lazaro.in',
		'name' => 'Omega'
	],
	'to' => [
		'email' => $toAddress,
		'name' => '',
		// 'additionalEmails' => empty( $user[ 'otherEmails' ] ) ? [ ] : $user[ 'otherEmails' ]
	],
	'subject' => $subject,
	'body' => preg_replace( '/\R/', '<br>', $body )
];

// Send the mail
try {
	$response[ 'message' ] = Mailer\send( $envelope );
	$response[ 'statusCode' ] = 0;
} catch ( \Exception $e ) {
	http_response_code( 500 );
	$response[ 'message' ] = 'The mail could not be sent. ' . $e->getMessage();
	$response[ 'statusCode' ] = -1;
}
die( json_encode( $response ) );
