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
$authToken = 'd26aa791c15cd144fff5857ad96aeb39';


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
		'Phone' => $record->data[ 'Phone' ] ?? '',
		'Full Name' => $record->data[ 'Full Name' ] ?? '',
		'First Name' => $record->data[ 'First Name' ] ?? '',
		'Last Name' => $record->data[ 'Last Name' ] ?? '',
		'Email' => $record->data[ 'Email' ] ?? ''
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


function updateUser ( $id, $type, $data ) {
	if ( $type == 'prospect' ) {
		updateProspect( $id, $data );
	} else if ( $type == 'lead' ) {
		updateLead( $id, $data );
	}
}

function updateLead ( $id, $data ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Leads', $authToken, 'com', 0 );

	try {
		$apiResponse = $zohoClient->updateRecords()
					->addRecord( array_merge( $data, [ 'Id' => $id ] ) )
					->onDuplicateUpdate()
					->triggerWorkflow()
					->request();
		$apiResponse = array_values( $apiResponse )[ 0 ];
		if ( ! empty( $apiResponse->error ) ) {
			if ( ! empty( $apiResponse->error->description ) ) {
				throw new \Exception( $apiResponse->error->description );
			}
		}
	} catch ( Exception $e ) {
		throw new \Exception( 'Could not update the lead.' );
	}

	return $apiResponse;

}

function updateProspect ( $id, $data ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Contacts', $authToken, 'com', 0 );

	try {
		$apiResponse = $zohoClient->updateRecords()
					->addRecord( array_merge( $data, [ 'Id' => $id ] ) )
					->onDuplicateUpdate()
					->triggerWorkflow()
					->request();
		$apiResponse = array_values( $apiResponse )[ 0 ];
		if ( ! empty( $apiResponse->error ) ) {
			if ( ! empty( $apiResponse->error->description ) ) {
				throw new \Exception( $apiResponse->error->description );
			}
		}
	} catch ( Exception $e ) {
		throw new \Exception( 'Could not update the prospect.' );
	}

	return $apiResponse;

}
