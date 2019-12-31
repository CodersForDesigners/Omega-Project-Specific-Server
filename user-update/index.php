<?php

/*
 *
 * The incoming HTTP request structure:
 * 	POST /users/2929500000002782047
 * 		{ HTTP body }
 *
 */

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





require_once __DIR__ . '/../lib/crm.php';




/*
 *
 * Ingest enquiry to Zoho CRM as a new lead, or additional data to an existing lead or prospect.
 *
 *
 * Here's what this script does:
 * 	1. Check if a prospect matching the given details exists.
 * 	2.1. If it does, then update the lead with new information.
 * 	2.2. Then add the pricing sheet as an attachment. END.
 * 	3. If it does not, then check if a lead matching the given details exists.
 * 	4.1. If it does, then update the lead with new information.
 * 	4.2. Then add the pricing sheet as an attachment. END.
 * 	5. If it does not, then create a new lead with the given details.
 *
 */
/*
 *
 * Check if a user exists with the given id
 *
 */
$userId = $_GET[ '_userId' ];

$user = CRM::getCustomerById( $userId );
if ( empty( $user ) ) {
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = "No user with the given ID was found.";
	http_response_code( 404 );
	die( json_encode( $response ) );
}

/* ------------------------------------- \
 * Interpret and Prepare the input
 \-------------------------------------- */
// Extract the changeset, replacing all spaces in the field names with an underscore
// 	( for compatibility with the frontend )
$formattedFieldNames = array_map( function ( $name ) {
	return preg_replace( '/\s+/', '_', $name );
}, array_keys( $_POST[ 'fields' ] ) );
$data = array_combine( $formattedFieldNames, array_values( $_POST[ 'fields' ] ) );
// The "Last Name" field is mandatory (on Zoho's end)
// 	hence if it is empty, do not let it through
if ( empty( trim( $data[ 'Last_Name' ] ) ) )
	unset( $data[ 'Last_Name' ] );



try {

	CRM::updateRecord( $user[ 'recordType' ], $user[ 'id' ], $data );
	$response[ 'statusCode' ] = 0;
	$response[ 'message' ] = 'Successfully updated the user.';
	die( json_encode( $response ) );

} catch ( Exception $e ) {

	http_response_code( 500 );
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = $e->getMessage();
	die( json_encode( $response ) );

}
