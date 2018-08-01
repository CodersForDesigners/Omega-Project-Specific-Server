<?php

/*
 *
 * The incoming HTTP request structure:
 * 	POST /users/2929500000002782047
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





require_once __DIR__ . '/lib/crm.php';




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
 * 	6. Append the pricing sheet as an attachment. END.
 *
 */
/*
 *
 * Check if a user exists with the given id
 *
 */
$userId = $_GET[ '_userId' ];

$user = CRM\getUserById( $userId );
if ( empty( $user ) ) {
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = "No user with the given ID was found.";
}

$data = $_POST[ 'fields' ];
// The "Last Name" field is mandatory, hence if it is empty, do not let it through
if ( empty( $data[ 'Last Name' ] ) )
	unset( $data[ 'Last Name' ] );

try {

	CRM\updateUser( $userId, $user[ 'type' ], $data );
	$response[ 'statusCode' ] = 0;
	$response[ 'message' ] = 'Successfully updated the user.';
	die( json_encode( $response ) );

} catch ( Exception $e ) {

	http_response_code( 500 );
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = $e->getMessage();
	die( json_encode( $response ) );

}
