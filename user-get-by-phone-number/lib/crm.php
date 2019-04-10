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
 * Criterion stringifier
 * -----
 */
function getStringifiedCriterion ( $name, $relation__value ) {

	if ( empty( $relation__value ) ) {
		$criteriaString = '';
	}
	else if ( is_array( $relation__value ) ) {
		global $operatorRelationMap;
		$operator = $relation__value[ 0 ];
		$value = $relation__value[ 1 ];
		$criteriaString = '(' . $name . ':' . $operatorRelationMap[ $operator ] . ':' . urlencode( $value ) . ')';
	}
	else {
		$value = $relation__value;
		$criteriaString = '(' . $name . ':equals:' . urlencode( $value ) . ')';
	}

	return $criteriaString;

}
/*
 * -----
 * Criteria resolver
 * -----
 */
function getResolvedCriteria ( $criteria ) {

	$name = array_keys( $criteria )[ 0 ];

	if ( in_array( $name, [ 'or', 'and' ] ) ) {
		$operator = $name;
		$subCriteria = $criteria[ $operator ];
		$subCriteriaStrings = array_map( function ( $name, $value ) {
			return getResolvedCriteria( [ $name => $value ] );
		}, array_keys( $subCriteria ), array_values( $subCriteria ) );
		return '(' . implode( $operator, $subCriteriaStrings ) . ')';
	}
	else {
		return getStringifiedCriterion(
			array_keys( $criteria )[ 0 ],
			array_values( $criteria )[ 0 ]
		);
	}

}

/*
 * -----
 * Get user by phone number
 * -----
 */
function getUserByPhoneNumber ( $phoneNumber, $client ) {

	$user = getRecordWhere( 'Leads', [
		'and' => [
			'Is_Duplicate' => 'false',
			'Project' => [ '^=', $client ],
			'or' => [
				'Phone' => $phoneNumber,
				'Alt_Mobile' => $phoneNumber
			]
		]
	] );
	if ( empty( $user ) ) {
		$user = getRecordWhere( 'Contacts', [
			'and' => [
				'Is_Duplicate' => 'false',
				'Project' => [ '^=', $client ],
				'or' => [
					'Phone' => $phoneNumber,
					'Other_Phone' => $phoneNumber,
					'Mobile' => $phoneNumber,
					'Home_Phone' => $phoneNumber,
					'Asst_Phone' => $phoneNumber
				]
			]
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
	$criteriaString = '?criteria=(' . getResolvedCriteria( $criteria ) . ')';
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
		$errorMessage = 'More than one ' . $recordType . ' found with the given criteria; ' . json_encode( $criteria ) . '.';
		throw new \Exception( $errorMessage, 2 );
	}

	$body = array_filter( $body[ 'data' ][ 0 ] );

	return $body;

}
