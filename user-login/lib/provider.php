<?php

require __DIR__ . '/../../vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

// Replace these with your token settings
// Create a project at https://console.developers.google.com/
$clientId = '126828542038-93st8bcdheq25gp4ajj7afsjjr0cbc28.apps.googleusercontent.com';
$clientSecret = 'dVkf9HNY6ZYYk2n6h4apYwp1';
// Change this if you are not using the built-in PHP server
$redirectUri = 'http://pricing.om/login/callback/google';

// Start the session
session_start();

// Initialize the provider
$provider = new Google( [
	'clientId' => $clientId,
	'clientSecret' => $clientSecret,
	'redirectUri' => $redirectUri
] );

// No HTML for demo, prevents any attempt at XSS
header( 'Content-Type', 'text/plain' );

return $provider;
