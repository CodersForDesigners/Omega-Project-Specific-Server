<?php
/*
 *
 * This script fetches a user based on a phone number from the system
 *
 */

ini_set( 'display_errors', 0 );
ini_set( 'error_reporting', E_ALL );

header( 'Access-Control-Allow-Origin: *' );

date_default_timezone_set( 'Asia/Kolkata' );

// continue processing this script even if
// the user closes the tab, or
// hits the ESC key
ignore_user_abort( true );

// do not let this script timeout
set_time_limit( 0 );

header( 'Content-Type: application/json' );

require __DIR__ . '/lib/crm.php';

$phoneNumber = $_GET[ 'phoneNumber' ];

try {

	$user = CRM\getUserByPhoneNumber( $phoneNumber );

	// If no prospect or lead was found
	if ( empty( $user ) ) {
		$response[ 'statusCode' ] = 1;
		$response[ 'message' ] = 'No matching user was found.';
		http_response_code( 404 );
		die( json_encode( $response ) );
	}

	$response[ 'statusCode' ] = 0;
	$response[ 'data' ] = [
		'_id' => $user[ '_id' ] ?? '',
		'name' => $user[ 'Full Name' ] ?? '',
		'firstName' => $user[ 'First Name' ] ?? '',
		'lastName' => $user[ 'Last Name' ] ?? '',
		'phoneNumber' => $user[ 'Phone' ] ?? '',
		'email' => $user[ 'Email' ] ?? ''
	];
	foreach ( $user as $key => $value ) {
		if ( strpos( $key, '_ ' ) === 0 )
			$response[ 'data' ][ $key ] = $value;
	}

	die( json_encode( $response ) );

} catch ( Exception $e ) {

	// Respond with an error
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = $e->getMessage();
	http_response_code( 500 );
	die( json_encode( $response ) );

}
