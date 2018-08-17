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
 * Get user by ID
 * -----
 */
function getUserById ( $id ) {

	$user = getLeadById( $id );
	if ( ! $user )
		$user = getProspectById( $id );
	return $user;

}

function getLeadById ( $id ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Leads', $authToken, 'com', 0 );

	try {
		$record = $zohoClient->getRecordById()
					->id( $id )
					->request();
		$record = array_values( $record )[ 0 ];
	} catch ( ZohoException\NoDataException $e ) {
		$record = [ ];
	} catch ( Exception $e ) {
		$record = [ ];
	}

	if ( empty( $record ) ) {
		return null;
	}

	$existingLead = [
		'SMOWNERID' => $record->data[ 'SMOWNERID' ],
		'_id' => $record->data[ 'LEADID' ],
		'Phone' => $record->data[ 'Phone' ] ?? '',
		'Full Name' => $record->data[ 'Full Name' ] ?? '',
		'First Name' => $record->data[ 'First Name' ] ?? '',
		'Last Name' => $record->data[ 'Last Name' ] ?? '',
		'Email' => $record->data[ 'Email' ] ?? ''
	];
	foreach ( $record->data as $key => $value ) {
		if ( strpos( $key, '_ ' ) === 0 )
			$existingLead[ $key ] = $value;
	}

	return $existingLead;

}

function getProspectById ( $id ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Contacts', $authToken, 'com', 0 );

	try {
		$record = $zohoClient->getRecordById()
					->id( $id )
					->request();
		$record = array_values( $record )[ 0 ];
	} catch ( ZohoException\NoDataException $e ) {
		$record = [ ];
	} catch ( Exception $e ) {
		$record = [ ];
	}

	if ( empty( $record ) ) {
		return null;
	}

	$existingProspect = [
		'SMOWNERID' => $record->data[ 'SMOWNERID' ],
		'_id' => $record->data[ 'CONTACTID' ],
		'Phone' => $record->data[ 'Phone' ],
		'Full Name' => $record->data[ 'Full Name' ],
		'First Name' => $record->data[ 'First Name' ],
		'Last Name' => $record->data[ 'Last Name' ],
		'Email' => $record->data[ 'Email' ]
	];
	foreach ( $record->data as $key => $value ) {
		if ( strpos( $key, '_ ' ) === 0 )
			$existingProspect[ $key ] = $value;
	}

	return $existingProspect;

}
