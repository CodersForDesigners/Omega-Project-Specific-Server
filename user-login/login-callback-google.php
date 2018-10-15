<?php

$productionEnv = require __DIR__ . '/../env.php';
$provider = require __DIR__ . '/lib/provider.php';

$projectName = explode( '/', $_GET[ 'REQUEST_URI' ] )[ 1 ];
$frontendAddress = 'https://' . $_GET[ 'HTTP_HOST' ];
if ( $productionEnv )
	$frontendAddress .= '/' . $projectName;

$frontendAddress .= '/quote';

$cookieName = 'ruser';

$authCode = $_GET[ 'code' ] ?? 0;

// If the authorization code is not present, that means that either the user is not valid or something else wen't wrong
if ( empty( $authCode ) ) {
	exit;
}





function getUser ( $id ) {

	$users = json_decode( file_get_contents( __DIR__ . '/../data/users/users.json' ), true );
	foreach ( $users as $user ) {
		if ( $user[ 'identifier' ] == $id ) return $user;
	}

	return false;

}

// Get the access token
$token = $provider->getAccessToken( 'authorization_code', [
	'code' => $authCode
] );

try {

	// We got an access token, let's now get the owner's (user's) details
	$ownerDetails = $provider->getResourceOwner( $token );

	// Use these details to create a cookie
	$userProvider = 'Google';
	$userId = $ownerDetails->getId();
	$userFirstName = $ownerDetails->getFirstName();
	$userEmail = $ownerDetails->getEmail();

	// Check if user exists
	$user = getUser( $userId );
	// If the user does not exist, or is suspended, do not proceed
	if ( empty( $user ) || $user[ 'suspended' ] ) {
	// die( 'Location: ' . $frontendAddress . '?r=400' );
		header( 'Location: ' . $frontendAddress . '?r=400' );
		exit;
	}

	// If the user exists and is not suspended
	if ( ! isset( $_COOKIE[ $cookieName ] ) ) {
		$cookie = base64_encode( json_encode( [
			'expires' => time() + 60 * 60 * 9,
			'identifier' => $userId,
			'name' => $user[ 'name' ],
			'email' => $user[ 'email' ],
			'role' => $user[ 'role' ]
		] ) );
		// Set a cookie to be valid for 9 hours
		setcookie( $cookieName, $cookie, time() + 60 * 60 * 9, '/' );
	}
	header( 'Location: ' . $frontendAddress );
	exit;

} catch ( Exception $e ) {

	// Failed to get user details
	header( 'Location: ' . $frontendAddress . '?r=' . $e->getMessage() );
	exit;

}
