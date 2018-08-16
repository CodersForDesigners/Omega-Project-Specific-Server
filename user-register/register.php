<?php

$provider = require __DIR__ . '/lib/provider.php';

$authUrl = $provider->getAuthorizationUrl();
$provider->getState();

header( 'Location: ' . $authUrl );

exit;
