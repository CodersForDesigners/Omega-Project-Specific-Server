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



function createLead ( $data ) {

	global $authToken;
	$zohoClient = new ZohoCRMClient( 'Leads', $authToken, 'com', 0 );

	$apiResponse = $zohoClient->insertRecords()
				->addRecord( $data )
				->onDuplicateError()
				->triggerWorkflow()
				->request();
	$apiResponse = array_values( $apiResponse )[ 0 ];
	if ( ! empty( $apiResponse->error ) ) {
		if ( ! empty( $apiResponse->error->description ) ) {
			throw new OmegaException( $apiResponse->error->description );
		}
	}

	return $apiResponse;

}
