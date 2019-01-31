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
 * Set global constants
 *
 */
$zohoApiUrl = 'https://www.zohoapis.com/crm/v2/';



/*
 * -----
 * Get user by UID
 * -----
 */
function getUserByUid ( $uid, $project ) {

	$user = getRecordByUid( $uid, 'Leads', [ 'Project' => $project ] );

	if ( ! empty( $user ) )
		$user[ 'isProspect' ] = false;

	if ( empty( $user ) ) {
		$user = getRecordByUid( $uid, 'Contacts', [ 'Project' => $project ] );
		if ( ! empty( $user ) )
			$user[ 'isProspect' ] = true;
	}

	return $user;

}

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
		// $headers[ ] = 'Content-Type: application/x-www-form-urlencoded';
		$headers[ ] = 'Content-Type: application/json';
		// curl_setopt( $httpRequest, CURLOPT_POSTFIELDS, http_build_query( $data ) );
		curl_setopt( $httpRequest, CURLOPT_POSTFIELDS, json_encode( $data ) );
	}
	curl_setopt( $httpRequest, CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $httpRequest, CURLOPT_CUSTOMREQUEST, $method );
	$response = curl_exec( $httpRequest );
	curl_close( $httpRequest );

	return $response;

}

function getRecordByUid ( $uid, $recordType, $moreCriteria = [ ] ) {

	global $zohoApiUrl;

	$baseURL = $zohoApiUrl . $recordType . '/search';
	$criteria = '(UID:equals:' . urlencode( $uid ) . ')';
	foreach ( $moreCriteria as $name => $value ) {
		if ( empty( $value ) )
			continue;
		$criteria .= 'and(' . $name . ':equals:' . urlencode( $value ) . ')';
	}
	$criteria = '?criteria=(' . $criteria . ')';
	$endpoint = $baseURL . $criteria;

	$response = getAPIResponse( $endpoint, 'GET' );

	$body = json_decode( $response, true );

	if ( empty( $body ) )
		return [ ];

	// If an error occurred
	if ( ! empty( $body[ 'code' ] ) )
		if ( $body[ 'code' ] == 'INVALID_TOKEN' )
			throw new \Exception( 'Access token is invalid.', 10 );

	// If more than one records were found
	if ( $body[ 'info' ][ 'count' ] > 1 )
		throw new \Exception( 'More than one ' . $recordType . ' found with the UID ' . $uid . '.', 2 );

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
