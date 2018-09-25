<?php

namespace CRM;

ini_set( "display_errors", 0 );
ini_set( "error_reporting", E_ALL );

require_once __DIR__ . '/../../vendor/autoload.php';

use CristianPontes\ZohoCRMClient\ZohoCRMClient;
use CristianPontes\ZohoCRMClient\Exception as ZohoException;

/*
 *
 * Declare constants
 *
 */
$authToken = require __DIR__ . '/../../api.php';


/*
 * -----
 * Get user by UID
 * -----
 */
function getUserByUid ( $uid ) {

	$user = getLeadByUid( $uid );
	if ( ! $user ) {
		$user = getProspectByUid( $uid );
		$user[ 'isProspect' ] = true;
	}
	return $user;

}

function getLeadByUid ( $uid ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Leads', $authToken, 'com', 0 );

	try {
		$records = $zohoClient->searchRecords()
					->where( 'UID', $uid )
					->request();
		$records = array_values( $records );
	} catch ( ZohoException\NoDataException $e ) {
		$records = [ ];
	} catch ( \Exception $e ) {
		$records = [ ];
	}

	if ( empty( $records ) ) {
		return null;
	}

	if ( count( $records ) > 1 ) {
		throw new \Exception( 'More than one user found with the UID ' . $uid . '.', 2 );
	}

	$existingLead = [
		'SMOWNERID' => $records[ 0 ]->data[ 'SMOWNERID' ],
		'_id' => $records[ 0 ]->data[ 'LEADID' ],
		'uid' => $records[ 0 ]->data[ 'UID' ],
		'Phone' => $records[ 0 ]->data[ 'Phone' ] ?? '',
		'Full Name' => $records[ 0 ]->data[ 'Full Name' ] ?? '',
		'First Name' => $records[ 0 ]->data[ 'First Name' ] ?? '',
		'Last Name' => $records[ 0 ]->data[ 'Last Name' ] ?? '',
		'Email' => $records[ 0 ]->data[ 'Email' ] ?? '',
		'Co-applicant Name' => $records[ 0 ]->data[ 'Co-applicant Name' ] ?? ''
	];
	foreach ( $records[ 0 ]->data as $key => $value ) {
		if ( strpos( $key, '_ ' ) === 0 )
			$existingLead[ $key ] = $value;
	}

	return $existingLead;

}

function getProspectByUid ( $uid ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Contacts', $authToken, 'com', 0 );

	try {
		$records = $zohoClient->searchRecords()
					->where( 'UID', $uid )
					->request();
		$records = array_values( $records );
	} catch ( ZohoException\NoDataException $e ) {
		$records = [ ];
	} catch ( \Exception $e ) {
		$records = [ ];
	}

	if ( empty( $records ) ) {
		return null;
	}

	if ( count( $records ) > 1 ) {
		throw new \Exception( 'More than one user found with the provided UID ' . $uid . '.', 2 );
	}

	$existingProspect = [
		'SMOWNERID' => $records[ 0 ]->data[ 'SMOWNERID' ],
		'_id' => $records[ 0 ]->data[ 'CONTACTID' ],
		'uid' => $records[ 0 ]->data[ 'UID' ],
		'Phone' => $records[ 0 ]->data[ 'Phone' ] ?? '',
		'Full Name' => $records[ 0 ]->data[ 'Full Name' ] ?? '',
		'First Name' => $records[ 0 ]->data[ 'First Name' ] ?? '',
		'Last Name' => $records[ 0 ]->data[ 'Last Name' ] ?? '',
		'Co-applicant Name' => $records[ 0 ]->data[ 'Co-applicant Name' ] ?? '',
		'Email' => $records[ 0 ]->data[ 'Email' ] ?? ''
	];
	foreach ( $records[ 0 ]->data as $key => $value ) {
		if ( strpos( $key, '_ ' ) === 0 )
			$existingProspect[ $key ] = $value;
	}

	return $existingProspect;

}
