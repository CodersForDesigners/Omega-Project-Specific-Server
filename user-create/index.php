<?php

/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * SCRIPT SETUP
 * /-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
 *
 */
ini_set( 'display_errors', 0 );
ini_set( 'error_reporting', E_ALL );

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

$input = &$_REQUEST;





/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * SCRIPT CORE
 * /-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
 *
 */
require __DIR__ . '/lib/crm.php';



// Check if required input is present and valid
if ( empty( $input[ 'phoneNumber' ] ) ) {
	$response[ 'statusCode' ] = 1;
	$response[ 'message' ] = 'No phone number was provided.';
	http_response_code( 500 );
	die( json_encode( $response ) );
}

// Pull all the input data
$project = $input[ 'project' ] ?? 'Dasta Concerto';
$assignmentRuleId = $input[ 'assignmentRuleId' ] ?? false;
$phoneNumber = $input[ 'phoneNumber' ];
// $unit = $input[ 'unit' ];
$firstName = $input[ 'firstName' ] ?? 'lazaro test';
$lastName = $input[ 'lastName' ] ?? 'hia there';
$leadSource = $input[ 'context' ] ?? 'Website';
$leadData = [
	'Project' => $project,
	'Lead Status' => 'Fresh',
	'Lead Source' => $leadSource,
	'First Name' => $firstName,
	'Last Name' => $lastName,
	'Phone' => $phoneNumber
];

try {

	// Create the lead
	$lead = CRM\createLead( $leadData, $assignmentRuleId );

	// Construct a response and respond back
	$response[ 'statusCode' ] = 0;
	$response[ 'data' ] = [
		'_id' => $lead->id ?? ''
	];
	$response[ 'message' ] = 'User created.';
	die( json_encode( $response ) );

} catch ( Exception $e ) {

	// If the error is generic
	if ( get_class( $e ) != "OmegaException" ) {
		http_response_code( 500 );
		$response[ 'statusCode' ] = 1;
	// If the error if of our custom-defined type
	} else {
		$response[ 'statusCode' ] = -1;
	}
	$response[ 'message' ] = $e->getMessage();

	die( json_encode( $response ) );

}
