<?php

namespace CRM;

ini_set( "display_errors", 1 );
ini_set( "error_reporting", E_ALL );

require_once __DIR__ . '/../../vendor/autoload.php';

use CristianPontes\ZohoCRMClient\ZohoCRMClient;
use CristianPontes\ZohoCRMClient\Exception as ZohoException;

/*
 *
 * Declare constants
 *
 */
$zohoAPICredentials = json_decode( file_get_contents( __DIR__ . '/../../__environment/configuration/zoho-api-v1.json' ), true );
$authToken = $zohoAPICredentials[ 'authenticationToken' ];


/*
 * -----
 * Get user by ID
 * -----
 */
function getUserById ( $id ) {

	$user = getLeadById( $id );
	if ( empty( $user ) ) {
		$user = getProspectById( $id );
	}
	return $user;

}

function getLeadById ( $id ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Leads', $authToken, 'com', 0 );

	try {
		$record = $zohoClient->getRecordById()
					->id( $id )
					->request();
		if ( ! empty( $record ) ) {
			$record = array_values( $record )[ 0 ];
		}
	} catch ( ZohoException\NoDataException $e ) {
		$record = [ ];
	} catch ( Exception $e ) {
		$record = [ ];
	}

	if ( empty( $record ) ) {
		return null;
	}

	$existingLead = [
		'type' => 'lead',
		'SMOWNERID' => $record->data[ 'SMOWNERID' ],
		'id' => $record->data[ 'LEADID' ],
		'Phone' => $record->data[ 'Phone' ],
		'Full Name' => $record->data[ 'Full Name' ],
		'First Name' => $record->data[ 'First Name' ],
		'Last Name' => $record->data[ 'Last Name' ],
		'Email' => $record->data[ 'Email' ] ?? null
	];

	return $existingLead;

}

function getProspectById ( $id ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Contacts', $authToken, 'com', 0 );

	try {
		$record = $zohoClient->getRecordById()
					->id( $id )
					->request();
		if ( ! empty( $record ) ) {
			$record = array_values( $record )[ 0 ];
		}
	} catch ( ZohoException\NoDataException $e ) {
		$record = [ ];
	} catch ( Exception $e ) {
		$record = [ ];
	}

	if ( empty( $record ) ) {
		return null;
	}

	$existingProspect = [
		'type' => 'prospect',
		'SMOWNERID' => $record->data[ 'SMOWNERID' ],
		'id' => $record->data[ 'CONTACTID' ],
		'Phone' => $record->data[ 'Phone' ],
		'Full Name' => $record->data[ 'Full Name' ],
		'First Name' => $record->data[ 'First Name' ],
		'Last Name' => $record->data[ 'Last Name' ],
		'Email' => $record->data[ 'Email' ]
	];

	return $existingProspect;

}


function attachFileToUser ( $id, $type, $resourceURL ) {

	if ( $type == 'prospect' ) {
		$apiResource = 'Contacts';
	} else if ( $type == 'lead' ) {
		$apiResource = 'Leads';
	}

	global $authToken;
	$zohoClient = new ZohoCRMClient( $apiResource, $authToken, 'com', 0 );

	try {
		$apiResponse = $zohoClient->uploadFile()
				->id( $id )
				// ->attachLink( $resourceURL )
				->uploadFromPath( $resourceURL )
				->request();
	} catch ( Exception $e ) {
		throw new \Exception( 'Could not upload file to the lead.' );
	}

	return $apiResponse;

}
