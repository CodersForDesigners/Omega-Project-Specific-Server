<?php

namespace CRM;

ini_set( "display_errors", 0 );
ini_set( "error_reporting", E_ALL );

// Set the timezone
date_default_timezone_set( 'Asia/Kolkata' );
// Do not let this script timeout
set_time_limit( 0 );



/*
 *
 * Get the auth credentials
 *
 */
$authCredentialsFilename = __DIR__ . '/../../configuration/zoho.json';
if ( empty( realpath( $authCredentialsFilename ) ) )
	sleep( 1 );
$authCredentials = json_decode( file_get_contents( $authCredentialsFilename ), true );

/*
 *
 * Define some constant values
 *
 */
$operatorRelationMap = [
	'=' => 'equals',
	'^=' => 'starts_with'
];

/*
 * -----
 * Get user by phone number
 * -----
 */
function getUserByPhoneNumber ( $phoneNumber, $client ) {

	$user = getRecordWhere( 'Leads', [
		'Phone' => $phoneNumber,
		'Project' => [ '^=', $client ]
	] );
	if ( empty( $user ) ) {
		$user = getRecordWhere( 'Contacts', [
			'Phone' => $phoneNumber,
			'Project' => [ '^=', $client ]
		] );
		if ( ! empty( $user ) )
			$user[ 'isProspect' ] = true;
	}

	return $user;

}

function getRecordWhere ( $recordType, $criteria = [ ] ) {

	global $authCredentials;
	global $operatorRelationMap;
	$accessToken = $authCredentials[ 'access_token' ];

	$baseURL = 'https://www.zohoapis.com/crm/v2/' . $recordType . '/search';
	$criteriaString = '';

	foreach ( $criteria as $name => $relation__value ) {

		if ( empty( $relation__value ) )
			continue;

		if ( is_array( $relation__value ) ) {
			$operator = $relation__value[ 0 ];
			$value = $relation__value[ 1 ];
			$criteriaString .= 'and(' . $name . ':' . $operatorRelationMap[ $operator ] . ':' . urlencode( $value ) . ')';
		}
		else {
			$value = $relation__value;
			$criteriaString .= 'and(' . $name . ':equals:' . urlencode( $value ) . ')';
		}

	}
	$criteriaString = '?criteria=(' . substr( $criteriaString, 3 ) . ')';
	$endpoint = $baseURL . $criteriaString;

	$httpRequest = curl_init();
	curl_setopt( $httpRequest, CURLOPT_URL, $endpoint );
	curl_setopt( $httpRequest, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $httpRequest, CURLOPT_USERAGENT, 'Zo Ho Ho' );
	curl_setopt( $httpRequest, CURLOPT_HTTPHEADER, [
		'Authorization: Zoho-oauthtoken ' . $accessToken,
		'Cache-Control: no-cache, no-store, must-revalidate'
	] );
	curl_setopt( $httpRequest, CURLOPT_CUSTOMREQUEST, 'GET' );
	$response = curl_exec( $httpRequest );
	curl_close( $httpRequest );

	$body = json_decode( $response, true );

	if ( empty( $body ) )
		return [ ];

	// If an error occurred
	if ( ! empty( $body[ 'code' ] ) )
		if ( $body[ 'code' ] == 'INVALID_TOKEN' )
			throw new \Exception( 'Access token is invalid.', 10 );

	// If more than one records were found
	if ( $body[ 'info' ][ 'count' ] > 1 ) {
		$errorMessage = 'More than one ' . $recordType . ' found with the given criteria; ';
		foreach ( $criteria as $name => $relation__value ) {

			if ( empty( $relation__value ) )
				continue;

			if ( is_array( $relation__value ) ) {
				$operator = $relation__value[ 0 ];
				$value = $relation__value[ 1 ];
				$errorMessage .= $name . ' ' . $operatorRelationMap[ $operator ] . ' ' . $value;
			}
			else {
				$value = $relation__value;
				$errorMessage .= $name . ' equals ' . $value;
			}

		}
		$errorMessage .= '.';
		throw new \Exception( $errorMessage, 2 );
	}

	$body = array_filter( $body[ 'data' ][ 0 ] );

	return $body;

}
