<?php

/*
 *
 * The incoming HTTP request structure:
 * 	POST /users/2929500000002782047/file
 * 		{ HTTP body }
 *
 */

ini_set( "display_errors", 1 );
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





require_once __DIR__ . '/../lib/crm.php';


/*
 *
 * Check if a user exists with the given id
 *
 */
$userId = $_GET[ '_userId' ];

$user = CRM::getCustomerById( $userId );
if ( empty( $user ) ) {
	http_response_code( 404 );
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = "No user with the given ID was found.";
	die( json_encode( $response ) );
}

$file = $_POST[ 'file' ];

try {

	CRM::uploadAttachment( $user[ 'recordType' ], $userId, $file );
	$response[ 'statusCode' ] = 0;
	$response[ 'message' ] = 'Successfully attached file to the user.';
	die( json_encode( $response ) );

} catch ( \Exception $e ) {

	http_response_code( 500 );
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = $e->getMessage();
	die( json_encode( $response ) );

}
