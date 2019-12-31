<?php

/*
 *
 * The command structure:
 * 	php create-quote.php -u 2929500000002782047 -n '104 UID89' -v '14 days' -a 24123985 -p 'http://omega.api/quote-sheet.pdf'
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
$arguments = getopt( 'u:n:v:a:p:' );

if (
	empty( $arguments[ 'u' ] )
	|| empty( $arguments[ 'n' ] )
	|| empty( $arguments[ 'v' ] )
	|| empty( $arguments[ 'a' ] )
	|| empty( $arguments[ 'p' ] )
) {
	$response[ 'message' ] = 'Please specify a user id, quote name, validity period, amount, and the path to the pricing sheet, like so';
	$response[ 'message' ] .= '\n' . 'php create-quote.php -u 2929500000002782047 -n \'104 UID89\' -v \'14 days\' -a 24123985 -p \'http://omega.api/quote.pdf\'';
	fwrite( STDERR, $response[ 'message' ] );
	exit( 1 );
}

$userId = $arguments[ 'u' ];
$quote = [
	'name' => $arguments[ 'n' ],
	'amount' => $arguments[ 'a' ],
	'validFor' => (int) $arguments[ 'v' ],
	'pricingSheet' => $arguments[ 'p' ],
];
$file = $arguments[ 'p' ];





require_once __DIR__ . '/../../lib/crm.php';


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
	$quoteRecord = CRM::createQuote( $user, $quote );
	$quoteId = $quoteRecord[ 'id' ];
	$response[ 'quoteId' ] = $quoteId;
	CRM::uploadAttachment( 'Deals', $quoteId, $quote[ 'pricingSheet' ] );
	die( json_encode( $response ) );
} catch ( \Exception $e ) {
	$response[ 'message' ] = 'The quote could not be made. ' . $e->getMessage();
	fwrite( STDERR, $response[ 'message' ] );
	exit( 1 );
}
