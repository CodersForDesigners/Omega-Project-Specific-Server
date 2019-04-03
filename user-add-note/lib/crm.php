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
 * Set constant values
 *
 */
$zohoApiUrl = 'https://www.zohoapis.com/crm/v2/';
// $zohoApiUrl = 'https://sandbox.zohoapis.com/crm/v2/';
$operatorRelationMap = [
	'=' => 'equals',
	'^=' => 'starts_with'
];




/*
 * -----
 * A generic API request function
 * -----
 */
function getAPIResponse ( $endpoint, $method, $data = [ ] ) {

	global $authCredentials;
	$accessToken = $authCredentials[ 'access_token' ];

	$httpRequest = curl_init();
	curl_setopt( $httpRequest, CURLOPT_URL, $endpoint );
	curl_setopt( $httpRequest, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $httpRequest, CURLOPT_USERAGENT, 'Zo Ho Ho' );
	$headers = [
		'Authorization: Zoho-oauthtoken ' . $accessToken,
		'Cache-Control: no-cache, no-store, must-revalidate'
	];
	if ( ! empty( $data ) ) {
		$headers[ ] = 'Content-Type: application/json';
		curl_setopt( $httpRequest, CURLOPT_POSTFIELDS, json_encode( $data ) );
	}
	curl_setopt( $httpRequest, CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $httpRequest, CURLOPT_CUSTOMREQUEST, $method );
	$response = curl_exec( $httpRequest );
	curl_close( $httpRequest );

	return $response;

}

/*
 * -----
 * Get user by UID
 * -----
 */
function getUserByUid ( $uid, $client ) {

	$user = getRecordByUid( 'Leads', [
		'UID' => $uid,
		'Project' => [ '^=', $client ]
	] );
	if ( empty( $user ) ) {
		$user = getRecordByUid( 'Contacts', [
			'UID' => $uid,
			'Project' => [ '^=', $client ]
		] );
		if ( ! empty( $user ) )
			$user[ 'isProspect' ] = true;
	}

	return $user;

}

function getRecordByUid ( $recordType, $criteria = [ ] ) {

	global $zohoApiUrl;
	global $authCredentials;
	global $operatorRelationMap;

	$baseURL = $zohoApiUrl . $recordType . '/search';

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

	$response = getAPIResponse( $endpoint, 'GET' );

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

function addNoteToUser ( $note, $id, $isProspect ) {

	global $zohoApiUrl;

	if ( $isProspect )
		$recordType = 'Contacts';
	else
		$recordType = 'Leads';

	$endpoint = "${zohoApiUrl}${recordType}/${id}/Notes";

	$data = [
		'data' => [
			[
				'Note_Title' => $note[ 'title' ],
				'Note_Content' => $note[ 'content' ]
				// '$editable' => false
			]
		]
	];

	$response = getAPIResponse( $endpoint, 'POST', $data );

	$body = json_decode( $response, true );

	if ( empty( $body ) )
		return [ ];

	// If an error occurred
	if ( ! empty( $body[ 'code' ] ) )
		if ( $body[ 'code' ] == 'INVALID_TOKEN' )
			throw new \Exception( 'Access token is invalid.', 10 );

	$body = array_filter( $body[ 'data' ][ 0 ] );

	return $body;

}



/*
 * Update the "Last Website Visit" timestamp
 */
function updateWebsiteActivityTimestamp ( $id, $isProspect ) {

	global $zohoApiUrl;

	if ( $isProspect )
		$recordType = 'Contacts';
	else
		$recordType = 'Leads';

	$endpoint = "${zohoApiUrl}${recordType}";

	$currentTimestamp = date( 'Y-m-d' ) . 'T' . date( 'H:i:s' ) . '+05:30';
	$data = [
		'data' => [
			[
				'id' => $id,
				'Last_Website_Visit' => $currentTimestamp
			]
		]
	];

	$response = getAPIResponse( $endpoint, 'PUT', $data );

	$body = json_decode( $response, true );

	if ( empty( $body ) )
		return [ ];

	// If an error occurred
	if ( ! empty( $body[ 'code' ] ) )
		if ( $body[ 'code' ] == 'INVALID_TOKEN' )
			throw new \Exception( 'Access token is invalid.', 10 );

	$body = array_filter( $body[ 'data' ][ 0 ] );

	return $body;

}
