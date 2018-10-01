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
$authFileHandle = fopen( $authCredentialsFilename, 'r' );
if ( $authFileHandle and flock( $authFileHandle, LOCK_SH ) ) {
	$authCredentials = json_decode( fread( $authFileHandle, filesize( $authCredentialsFilename ) ), true );
	// Close the file and release the lock
	flock( $authFileHandle, LOCK_UN );
	fclose( $authFileHandle );
}


/*
 * -----
 * Get user by UID
 * -----
 */
function getUserByUid ( $uid ) {

	$user = getRecordByUid( $uid, 'Leads' );
	if ( ! $user ) {
		$user = getRecordByUid( $uid, 'Contacts' );
		$user[ 'isProspect' ] = true;
	}

	return $user;

}

function getRecordByUid ( $uid, $recordType ) {

	$accessToken = getAuthTokens()[ 'access_token' ];

	$endpoint = 'https://www.zohoapis.com/crm/v2/' . $recordType . '/search?criteria=((UID:equals:' . $uid . '))';

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

	return $body;

}




function generateAuthTokens ( $urlParameters ) {

	$urlParameterString = http_build_query( $urlParameters, '', '&', PHP_QUERY_RFC3986 );
	$url = 'https://accounts.zoho.com/oauth/v2/token?' . $urlParameterString;

	$httpRequest = curl_init();
	curl_setopt( $httpRequest, CURLOPT_URL, $url );
	curl_setopt( $httpRequest, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $httpRequest, CURLOPT_USERAGENT, 'Zo Ho Ho' );
	curl_setopt( $httpRequest, CURLOPT_CUSTOMREQUEST, 'POST' );

	$response = curl_exec( $httpRequest );
	curl_close( $httpRequest );

	return json_decode( $response, true );

}


// function getAuthTokensFromGrantToken ( $grantToken ) {

// 	$urlParameters = [
// 		'grant_type' => 'authorization_code',
// 		'client_id' => '1000.XHYX53L6GUT664831A7Z07UD7GDWKU',
// 		'client_secret' => 'cb6a0bff1fdaf089f1ef31fad0090e111aedecea41',
// 		'redirect_uri' => 'http://kl.lazaro.in/omega/oauthcallback',
// 		'code' => $grantToken,
// 	];

// 	return getAuthTokens( $urlParameters );

// }



/*
 *
 * Generates a fresh access token
 *
 */
function getAuthTokens () {

	global $authCredentials;

	// If the access token has not yet expired, then there's no need to refresh
	$expiresAt = $authCredentials[ 'expires_at' ];
	if ( time() < $expiresAt )
		return $authCredentials;


	// Attempt to acquire an exclusive lock to the file
	global $authCredentialsFilename;
	$authFileHandle = fopen( $authCredentialsFilename, 'c' );
	if ( ! ( $authFileHandle and flock( $authFileHandle, LOCK_EX ) ) )
		return $authCredentials;

	// If the credentials file was updated a few moments ago
	if ( time() - filemtime( $authCredentialsFilename ) < 9 ) {
		// Close the file and release the lock
		flock( $authFileHandle, LOCK_UN );
		fclose( $authFileHandle );
		// Return the freshly update auth tokens
		$authCredentials = json_decode( file_get_contents( $authCredentialsFilename ), true );
		return $authCredentials;
	}

	// Now, actually prepare to refresh the tokens
	$urlParameters = array_intersect_key( $authCredentials, array_flip( [ 'client_id', 'client_secret', 'refresh_token' ] ) );
	$urlParameters[ 'grant_type' ] = 'refresh_token';

	$freshTokens = generateAuthTokens( $urlParameters );

	$authCredentials[ 'access_token' ] = $freshTokens[ 'access_token' ];
	// $authCredentials[ 'refresh_token' ] = $freshTokens[ 'refresh_token' ];
	$authCredentials[ 'expires_at' ] = time()
									+ ( $freshTokens[ 'expires_in' ] / 1000 )
									- ( 5 * 60 );

	// Write the credentials back to the file
	ftruncate( $authFileHandle, filesize( $authCredentialsFilename ) );
	fwrite( $authFileHandle, json_encode( $authCredentials, JSON_PRETTY_PRINT ) );
	// Close the file and release the lock
	flock( $authFileHandle, LOCK_UN );
	fclose( $authFileHandle );

	return $authCredentials;

}
