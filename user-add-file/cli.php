<?php

/*
 *
 * The command structure:
 * 	php user-add-file/cli.php -u 2929500000002782047 -f /http://omega.api/sheet.pdf'
 *
 */

ini_set( "display_errors", 1 );
ini_set( "error_reporting", E_ALL );

// Set the timezone
date_default_timezone_set( 'Asia/Kolkata' );
// Do not let this script timeout
set_time_limit( 0 );





/*
 *
 * Extract and Parse the input
 *
 */
$arguments = getopt( 'u:f:' );

if ( empty( $arguments[ 'u' ] ) || empty( $arguments[ 'f' ] ) ) {
	$response[ 'message' ] = 'Please specify a user id and the path to the pricing sheet, like so';
	$response[ 'message' ] .= '\n' . 'php user-add-file/cli.php -u 2929500000002782047 -f \'http://omega.api/sheet.pdf\'';
	fwrite( STDERR, $response[ 'message' ] );
	exit( 1 );
}

$userId = $arguments[ 'u' ];
$file = $arguments[ 'f' ];
// $file = __DIR__ . '/../data/pricing-sheets/blah.txt';
// $file = 'http://omega.api/data/pricing-sheets/blah.txt';





require_once __DIR__ . '/../lib/crm.php';


/*
 *
 * Check if a user exists with the given id
 *
 */
$user = CRM::getCustomerById( $userId );
if ( empty( $user ) ) {
	$response[ 'message' ] = "No user with the given ID was found.";
	fwrite( STDERR, $response[ 'message' ] );
	exit( 1 );
}

try {

	CRM::uploadAttachment( $user[ 'recordType' ], $userId, $file );
	$response[ 'message' ] = 'Successfully attached file to the user.';
	die( json_encode( $response ) );

} catch ( \Exception $e ) {

	$response[ 'message' ] = $e->getMessage();
	fwrite( STDERR, $response[ 'message' ] );
	exit( 1 );

}
