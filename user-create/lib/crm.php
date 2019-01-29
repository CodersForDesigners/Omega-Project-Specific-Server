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
$authToken = require __DIR__ . '/../../api.php';


/*
 * Declare a custom Exception
 */
class OmegaException extends \Exception {}



/*
 * -----
 * Create user
 * -----
 */
function createLead ( $data, $assignmentRuleId ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Leads', $authToken, 'com', 0 );

	$apiRequest = $zohoClient->insertRecords()
					->addRecord( $data )
					->onDuplicateError()
					->triggerWorkflow();
	if ( $assignmentRuleId )
		$apiRequest = $apiRequest->triggerAssignmentRule( $assignmentRuleId );
	$apiResponse = $apiRequest->request();
	$apiResponse = array_values( $apiResponse )[ 0 ];
	if ( ! empty( $apiResponse->error ) ) {
		if ( ! empty( $apiResponse->error->description ) ) {
			throw new OmegaException( $apiResponse->error->description );
		}
	}

	return $apiResponse;

}



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
	} catch ( \Exception $e ) {
		$record = [ ];
	}

	if ( empty( $record ) ) {
		return null;
	}

	$existingLead = [
		'SMOWNERID' => $record->data[ 'SMOWNERID' ],
		'_id' => $record->data[ 'LEADID' ],
		'UID' => $record->data[ 'UID' ],
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
	} catch ( \Exception $e ) {
		$record = [ ];
	}

	if ( empty( $record ) ) {
		return null;
	}

	$existingProspect = [
		'SMOWNERID' => $record->data[ 'SMOWNERID' ],
		'_id' => $record->data[ 'CONTACTID' ],
		'UID' => $record->data[ 'UID' ],
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
