<?php





class CRM {

	public static $apiUrl = 'https://www.zohoapis.com/crm/v2/';
	public static $accountsUrl = 'https://accounts.zoho.com';
	// public static $apiUrl = 'https://sandbox.zohoapis.com/crm/v2/';
	public static $authCredentials;
	public static $operatorRelationMap = [
		'=' => 'equals',
		'^=' => 'starts_with'
	];



	/*
	 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
	 *  API Keys
	 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
	 */
	public static function getAccessToken ( $credentialsFilename ) {
		if ( empty( realpath( $credentialsFilename ) ) )
			sleep( 1 );
		self::$authCredentials = json_decode( file_get_contents( $credentialsFilename ), true );
		return self::$authCredentials;
	}

	public static function refreshAccessToken ( $credentialsFilename ) {

		// Build the query parameters
		$queryParameters = [
			'client_id' => self::$authCredentials[ 'client_id' ],
			'client_secret' => self::$authCredentials[ 'client_secret' ],
			'refresh_token' => self::$authCredentials[ 'refresh_token' ],
			'grant_type' => 'refresh_token'
		];
		$queryParameterString = http_build_query( $queryParameters, '', '&', PHP_QUERY_RFC3986 );
		$endpoint = self::$accountsUrl . '/oauth/v2/token' . '?' . $queryParameterString;

		// Get the new access token
		$apiCredentials = self::httpRequest( $endpoint, 'POST' );

		// Set the new access token
		self::$authCredentials[ 'access_token' ] = $apiCredentials[ 'access_token' ];

		// Construct the new API credentials file-name
		$new__credentialsFilename = preg_replace(
			'/\.json$/',
			'.' . date( 'Ymd.His' ) . '.json',
			$credentialsFilename
		);
		// Write the updated API credentials to the file
		file_put_contents( $new__credentialsFilename, json_encode( self::$authCredentials, JSON_PRETTY_PRINT ) );

		// Un-link and re-link the API credentials file to the new one
		$previous__credentialsFilename = realpath( $credentialsFilename );
		unlink( $credentialsFilename );
		symlink( $new__credentialsFilename, $credentialsFilename );
		unlink( $previous__credentialsFilename );

	}



	/*
	 * -----
	 * Criterion stringifier
	 * -----
	 */
	public static function getStringifiedCriterion ( $name, $relation__value ) {

		if ( empty( $relation__value ) ) {
			$criteriaString = '';
		}
		else if ( is_array( $relation__value ) ) {
			$operator = $relation__value[ 0 ];
			$value = $relation__value[ 1 ];
			$criteriaString = '(' . $name . ':' . self::$operatorRelationMap[ $operator ] . ':';
			// If the value has spaces, then urlencode it, else don't
			if ( preg_match( '/\s/', $value ) === 1 )
				$criteriaString .= urlencode( $value ) . ')';
			else
				$criteriaString .= $value . ')';
		}
		else {
			$value = $relation__value;
			$criteriaString = '(' . $name . ':equals:';
			// If the value has spaces, then urlencode it, else don't
			if ( preg_match( '/\s/', $value ) === 1 )
				$criteriaString .= urlencode( $value ) . ')';
			else
				$criteriaString .= $value . ')';
		}

		return $criteriaString;

	}
	/*
	 * -----
	 * Criteria resolver
	 * -----
	 */
	public static function getResolvedCriteria ( $criteria ) {

		$name = array_keys( $criteria )[ 0 ];

		if ( in_array( $name, [ 'or', 'and' ] ) ) {
			$operator = $name;
			$subCriteria = $criteria[ $operator ];
			$subCriteriaStrings = array_map( function ( $name, $value ) {
				return self::getResolvedCriteria( [ $name => $value ] );
			}, array_keys( $subCriteria ), array_values( $subCriteria ) );
			return '(' . implode( $operator, $subCriteriaStrings ) . ')';
		}
		else {
			return self::getStringifiedCriterion(
				array_keys( $criteria )[ 0 ],
				array_values( $criteria )[ 0 ]
			);
		}

	}



	/*
	 * -----
	 * Get a record with the given id
	 * -----
	 */
	public static function getRecordById ( $type, $id ) {

		$endpoint = self::$apiUrl . $type . '/' . $id;
		$responseBody = self::httpRequest( $endpoint, 'GET' );

		if ( ! isset( $responseBody[ 'data' ] ) )
			return null;

		$record = $responseBody[ 'data' ][ 0 ];
		$record[ 'recordType' ] = $type;

		return $record;

	}



	public static function getRecordWhere ( $recordType, $criteria = [ ] ) {

		$baseURL = self::$apiUrl . $recordType . '/search';
		$criteriaString = '?criteria=(' . self::getResolvedCriteria( $criteria ) . ')';
		$endpoint = $baseURL . $criteriaString;

		$responseBody = self::httpRequest( $endpoint );

		// If no record was found
		if ( empty( $responseBody ) || empty( $responseBody[ 'data' ] ) )
			return null;

		// If more than one record was found
		// if ( $responseBody[ 'info' ][ 'count' ] > 1 ) {
		// 	$errorMessage = 'More than one ' . $recordType . ' found with the given criteria; ' . json_encode( $criteria ) . '.';
		// 	throw new \Exception( $errorMessage, 4002 );
		// }

		$record = array_filter( $responseBody[ 'data' ][ 0 ] );
		$record[ 'recordType' ] = $recordType;

		return $record;

	}



	/*
	 * -----
	 * Update a record with a given id
	 * -----
	 */
	public static function updateRecord ( $type, $id, $data ) {

		$endpoint = self::$apiUrl . $type . '/' . $id;

		$responseRaw = self::httpRequest( $endpoint, 'PUT', [
			'data' => [ $data ],
			'trigger' => [ 'approval', 'workflow', 'blueprint' ]
		] );

		if ( ! isset( $responseRaw[ 'data' ] ) )
			throw new \Exception( 'Response from update operation was empty.', 4002 );

		$response = $responseRaw[ 'data' ][ 0 ];

		if ( strtolower( $response[ 'code' ] ) != 'success' ) {
			$errorMessage = 'The update operation was not successful.'
							. PHP_EOL . $response[ 'message' ];
			throw new \Exception( $errorMessage, 4003 );
		}

		return $response[ 'details' ];

	}



	public static function httpRequest ( $endpoint, $method = 'GET', $data = [ ] ) {

		$request = curl_init();
		curl_setopt( $request, CURLOPT_URL, $endpoint );
		curl_setopt( $request, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $request, CURLOPT_USERAGENT, 'Zo Ho Ho' );
		$headers = [
			'Authorization: Zoho-oauthtoken ' . self::$authCredentials[ 'access_token' ],
			'Cache-Control: no-cache, no-store, must-revalidate'
		];
		if ( ! empty( $data ) ) {
			if ( is_array( $data ) ) {
				$headers[ ] = 'Content-Type: application/json';
				$requestBody = json_encode( $data );
			}
			// Else if it is a path to a resource that is to be uploaded or attached
			else if ( is_string( $data ) ) {

				$isURL = preg_match( '/^https?:\/\//', $data );

				// If it **is** a URL
				if ( $isURL ) {
					$resourcePath = $data;

					// Build the request body
					$requestBody = [
						'attachmentUrl' => $resourcePath
					];
				}
				else {
					// If it is an absolute path, leave it as is
					if ( $data[ 0 ] == '/' )
						$resourcePath = $data;
					// Else prepend a base path to it
					else {
						$basePath = $_SERVER[ 'DOCUMENT_ROOT' ] ?: __DIR__;
						$resourcePath = $basePath . '/' . $data;
					}
					// Determine the MIME type of the file
					$mimeType = mime_content_type( $resourcePath ) ?? 'application/octet-stream';

					// Build the request body
					$requestBody = [
						'file' => new CurlFile( $resourcePath, $mimeType )
					];
				}

			}

			curl_setopt( $request, CURLOPT_POSTFIELDS, $requestBody );
		}
		curl_setopt( $request, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $request, CURLOPT_CUSTOMREQUEST, $method );
		$response = curl_exec( $request );
		curl_close( $request );

		$body = json_decode( $response, true );

		if ( empty( $body ) )
			return [ ];
			// throw new \Exception( 'Response is empty.', 10 );

		// If an error occurred
		if ( ! empty( $body[ 'code' ] ) ) {
			if ( $body[ 'code' ] == 'INVALID_TOKEN' )
				throw new \Exception( 'Access token is invalid.', 5001 );
			if ( $body[ 'code' ] == 'AUTHENTICATION_FAILURE' )
				throw new \Exception( 'Failure in authenticating.', 5002 );
		}

		return $body;

	}



	/*
	 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
	 *  CUSTOMERS
	 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
	 */
	/*
	 * -----
	 * Creates a customer record with the given data
	 * -----
	 */
	public static function createCustomer ( $data ) {

		$endpoint = self::$apiUrl . 'Contacts';

		$requestBody = [
			'data' => [
				[
					'Stage' => 'Lead',
					'Project' => [ $data[ 'project' ] ],	// because it's a list
					'Phone' => $data[ 'phoneNumber' ],
					'Email' => $data[ 'email' ] ?? '',
					'First_Name' => $data[ 'firstName' ],
					'Last_Name' => $data[ 'lastName' ],
					'Lead_Source' => $data[ 'context' ],
					'Customer_Status' => 'Fresh',
					'Mobile' => $data[ 'phoneNumber2' ] ?? '',
					'Home_Phone' => $data[ 'phoneNumber3' ] ?? '',
					'Asst_Phone' => $data[ 'phoneNumber4' ] ?? '',
					'Other_Phone' => $data[ 'phoneNumber5' ] ?? ''
				]
			],
			'trigger' => [
				'approval',
				'workflow',
				'blueprint'
			]
		];
		if ( ! empty( $data[ 'ownerId' ] ) )
			$requestBody[ 'data' ][ 0 ][ 'Owner' ] = $data[ 'ownerId' ];

		$responseBody = self::httpRequest( $endpoint, 'POST', $requestBody );

		if ( empty( $responseBody ) )
			return [ ];

		// Pull out the (internal) id of the newly created customer
		$responseBody = array_filter( $responseBody[ 'data' ][ 0 ] );
		$recordId = trim( $responseBody[ 'details' ][ 'id' ] );

		// Now, get the `Hidden UID` value
		$customer = self::getCustomerById( $recordId );
		$uid = trim( $customer[ 'Hidden_UID' ] );

		// Return the record Id and the UID
		return [
			'_id' => $recordId,
			'uid' => $uid
		];

	}



	public static function getCustomerById ( $id ) {

		$customer = self::getRecordById( 'Contacts', $id );
		if ( empty( $customer ) )
			$customer = self::getRecordById( 'Leads', $id );
		else
			$customer[ 'isProspect' ] = true;

		return $customer;

	}



	public static function getCustomerByUID ( $uid ) {
		$customer = self::getRecordWhere( 'Contacts', [ 'UID' => $uid ] );
		return $customer;
	}



	public static function getCustomerByPhoneNumber ( $phoneNumber ) {

		$customer = self::getRecordWhere( 'Leads', [
			// 'and' => [
			// 	'Project' => [ '^=', $client ],
				'or' => [
					'Phone' => $phoneNumber,
					'Secondary_Phone' => $phoneNumber
				]
			// ]
		] );
		if ( empty( $customer ) ) {
			$customer = self::getRecordWhere( 'Contacts', [
				// 'and' => [
				// 	'Project' => [ '^=', $client ],
					'or' => [
						'Phone' => $phoneNumber,
						'Secondary_Phone' => $phoneNumber
					]
				// ]
			] );
			if ( ! empty( $customer ) )
				if ( $customer[ 'recordType' ] == 'Contacts' )
					$customer[ 'isProspect' ] = true;
		}

		return $customer;

	}



	function updateCustomer ( $recordType, $id, $data ) {
		return self::updateRecord( $recordType, $id, $data );
	}



	/*
	 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
	 *  QUOTES
	 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
	 */
	public static function createQuote ( $customer, $quote ) {

		$validFor = date( 'Y-m-d', strtotime( '+ ' . $quote[ 'validFor' ] . ' days' ) );

		$endpoint = self::$apiUrl . 'Deals';

		$requestBody = [
			'data' => [
				[
					'SMOWNERID' => $customer[ 'Owner' ][ 'id' ],
					'Contact_Name' => $customer[ 'id' ],
					'Amount' => $quote[ 'amount' ],
					'Deal_Name' => $quote[ 'name' ],
					'Closing_Date' => $validFor,
					'Stage' => 'Quote Generated',
					'Email' => $customer[ 'Email' ]
				]
			],
			'trigger' => [
				'approval',
				'workflow',
				'blueprint'
			]
		];

		$responseBody = self::httpRequest( $endpoint, 'POST', $requestBody );

		if ( empty( $responseBody ) )
			return [ ];

		$record = array_filter( $responseBody[ 'data' ][ 0 ] );
		$quoteRecord = $record[ 'details' ];

		return $quoteRecord;

	}



	/*
	 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
	 *  OTHER
	 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
	 */
	public static function uploadAttachment ( $recordType, $recordId, $resourcePath ) {

		$endpoint = self::$apiUrl . $recordType . '/' . $recordId . '/Attachments';

		$responseBody = self::httpRequest( $endpoint, 'POST', $resourcePath );

		return $responseBody;

	}

}




// Get the access token
$authCredentialsFilename = __DIR__ . '/../__environment/configuration/zoho.json';
CRM::getAccessToken( $authCredentialsFilename );
