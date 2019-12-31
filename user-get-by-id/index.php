<?php
/*
 *
 * This script fetches a user based on its (unique) id from the system
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

require __DIR__ . '/../lib/crm.php';

$id = $_GET[ 'id' ];

try {

	$user = CRM::getCustomerById( $id );

	// If no prospect or lead was found
	if ( empty( $user ) ) {
		$response[ 'statusCode' ] = 1;
		$response[ 'message' ] = 'No matching user was found.';
		http_response_code( 404 );
		die( json_encode( $response ) );
	}

	$response[ 'statusCode' ] = 0;
	$response[ 'data' ] = [
		'recordType' => $user[ 'recordType' ],
		'_id' => $user[ 'id' ] ?? '',
		'uid' => trim( $user[ 'UID' ] ) ?? trim( $user[ 'Hidden_UID' ] ) ?? '',
		'isProspect' => $user[ 'isProspect' ] ?? false,
		'project' => empty( $project ) ? $user[ 'Project' ][ 0 ] : '',
		'name' => $user[ 'Full_Name' ] ?? '',
		'firstName' => $user[ 'First_Name' ] ?? '',
		'lastName' => $user[ 'Last_Name' ] ?? '',
		'phoneNumber' => $user[ 'Phone' ] ?? '',
		'email' => $user[ 'Email' ] ?? '',
		'isDuplicate' => $user[ 'Is_Duplicate' ] ?? false,
		'_ Special Discount' => $user[ 'Special_Discount' ] ?? '',
		'_ Discount Valid Till' => $user[ 'Discount_Valid_Till' ] ?? ''
	];
	// foreach ( $user as $key => $value ) {
	// 	if ( strpos( $key, '_ ' ) === 0 )
	// 		$response[ 'data' ][ $key ] = $value;
	// }
	die( json_encode( $response ) );

} catch ( Exception $e ) {

	// Respond with an error
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = $e->getMessage();
	http_response_code( 500 );
	die( json_encode( $response ) );

}
