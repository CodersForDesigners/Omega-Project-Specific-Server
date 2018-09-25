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
$project = $_GET[ 'project' ];

try {

	$user = CRM\getUserByPhoneNumber( $phoneNumber, $project );

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
		'uid' => $user[ 'uid' ] ?? '',
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

	// Send a mail if required
	if ( $e->getCode() > 1 ) {
		$mailDataFilename = tempnam( sys_get_temp_dir(), 'err-user-get-by-phone' );
		$mailData = [
			'user' => [ 'From Name' => 'Omega Bot', 'email' => 'adi@lazaro.in', 'name' => 'adi', 'additionalEmails' => [ 'mark@lazaro.in' ] ],
			'mail' => [ 'Subject' => '#!ERROR on ' . $project, 'Body' => 'An error occurred while fetching a user by the phone number on ' . $project . '.<br><br>' . $e->getMessage() ]
		];
		file_put_contents( $mailDataFilename, json_encode( $mailData ) );
		exec( 'php \'' . __DIR__ . '/../mail-send/index.php\' -i \'' . $mailDataFilename . '\'' );
		unlink( $mailDataFilename );
	}

	// Respond with an error
	if ( $e->getCode() > 1 ) {
		$response[ 'statusCode' ] = -1;
		$response[ 'message' ] = 'Something wen\'t wrong.';
	}
	else {
		$response[ 'statusCode' ] = 1;
		$response[ 'message' ] = $e->getMessage();
	}
	http_response_code( 500 );
	die( json_encode( $response ) );

}
