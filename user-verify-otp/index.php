<?php

ini_set( 'display_errors', 0 );
ini_set( 'error_reporting', E_ALL );

header( 'Access-Control-Allow-Origin: *' );

date_default_timezone_set( 'Asia/Kolkata' );

// continue processing this script even if
// the user closes the tab, or
// hits the ESC key
ignore_user_abort( true );

// do not let this script timeout
set_time_limit( 0 );

header( 'Content-Type: application/json' );

/*
 * Get the data from the request
 */
$otp = $_REQUEST[ 'otp' ];
$sessionId = $_REQUEST[ 'sessionId' ];

$apiKey = '693bc978-580e-11e8-a895-0200cd936042';



/*
 * Make the request
 */
$requestEndpoint = 'https://2factor.in/API/V1/' . $apiKey . '/SMS/VERIFY/' . $sessionId . '/' . $otp;

$request = curl_init( $requestEndpoint );

curl_setopt( $request, CURLOPT_CUSTOMREQUEST, 'GET' );
curl_setopt( $request, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $request, CURLOPT_ENCODING, '' );
curl_setopt( $request, CURLOPT_MAXREDIRS, 10 );
curl_setopt( $request, CURLOPT_TIMEOUT, 30 );
curl_setopt( $request, CURLOPT_HEADER, 'Content-Type: application/x-www-form-urlencoded' );
curl_setopt( $request, CURLOPT_HTTPHEADER, [
	'Cache-Control: no-cache',
	'Content-Type: application/json'
] );

$response = curl_exec( $request );
curl_close( $request );

echo $response;
exit;
