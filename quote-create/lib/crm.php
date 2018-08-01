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
		'_id' => $record->data[ 'LEADID' ],
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
		'_id' => $record->data[ 'CONTACTID' ],
		'Phone' => $record->data[ 'Phone' ],
		'Full Name' => $record->data[ 'Full Name' ],
		'First Name' => $record->data[ 'First Name' ],
		'Last Name' => $record->data[ 'Last Name' ],
		'Email' => $record->data[ 'Email' ]
	];

	return $existingProspect;

}


function createQuote ( $user, $quote ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Deals', $authToken, 'com', 0 );


	/*
	 * Create the quote
	 */
	$validFor = date( 'Y-m-d', strtotime( '+ ' . $quote[ 'validFor' ] ) );


	try {
		$apiResponse = $zohoClient->insertRecords()
				->addRecord( [
					'SMOWNERID' => $user[ 'SMOWNERID' ],
					'CONTACTID' => $user[ '_id' ],
					'Amount' => $quote[ 'amount' ],
					'Deal Name' => $quote[ 'name' ],
					'Closing Date' => $validFor,
					'Stage' => 'Quote Generated',
					'Email' => $user[ 'Email' ]
				] )
				->onDuplicateError()
				// ->onDuplicateUpdate()
				->triggerWorkflow()
				->request();
		$apiResponse = array_values( $apiResponse );
	} catch ( Exception $e ) {
		throw new \Exception( 'Could not create the quote.' );
	}

	/*
	 * Attach the pricing sheet to the quote
	 */
	$quoteId = $apiResponse[ 0 ]->id;
	$pricingSheetURL = $quote[ 'pricingSheet' ];

	try {
		$apiResponse = $zohoClient->uploadFile()
			->id( $quoteId )
			// ->attachLink( $pricingSheetURL )
			->uploadFromPath( $quote[ 'pricingSheet' ] )
			->request();
	} catch ( Exception $e ) {
		throw new \Exception( 'Could not attach the pricing sheet to the quote.' );
	}

	return $quoteId;

}
