<?php

$projectName = explode( '/', $_GET[ 'REQUEST_URI' ] )[ 1 ];
$redirectURI = 'http://' . $_GET[ 'HTTP_HOST' ] . '/' . $projectName . '/pricing';

// Invalidate the cookie
setcookie( 'ruser', '', time() - 999, '/' );

header( 'Location: ' . $redirectURI );
