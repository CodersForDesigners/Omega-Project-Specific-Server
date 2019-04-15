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
 * -----
 * Get user by UID
 * -----
 */
function getUserByUid ( $uid, $project ) {

	$user = getRecordByUid( $uid, 'Leads', [ 'Project' => $project ] );
	if ( empty( $user ) ) {
		$user = getRecordByUid( $uid, 'Contacts', [ 'Project' => $project ] );
		if ( ! empty( $user ) )
			if ( $user[ 'Stage' ] == 'Prospect' )
				$user[ 'isProspect' ] = true;
	}

	return $user;

}

function getRecordByUid ( $uid, $recordType, $moreCriteria = [ ] ) {

	global $authCredentials;
	$accessToken = $authCredentials[ 'access_token' ];

	$baseURL = 'https://www.zohoapis.com/crm/v2/' . $recordType . '/search';
	$criteria = '(UID:equals:' . urlencode( $uid ) . ')';
	foreach ( $moreCriteria as $name => $value ) {
		if ( empty( $value ) )
			continue;
		$criteria .= 'and(' . $name . ':equals:' . urlencode( $value ) . ')';
	}
	$criteria = '?criteria=(' . $criteria . ')';
	$endpoint = $baseURL . $criteria;

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
	if ( $body[ 'info' ][ 'count' ] > 1 )
		throw new \Exception( 'More than one ' . $recordType . ' found with the UID ' . $uid . '.', 2 );

	$body = array_filter( $body[ 'data' ][ 0 ] );
	$body[ 'recordType' ] = $recordType;

	return $body;

}
