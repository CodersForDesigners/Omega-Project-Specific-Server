<?php

$redirectURI = $_GET[ 'redirect' ];

// Invalidate the cookie
setcookie( 'ruser', '', time() - 999, '/' );

header( 'Location: ' . $redirectURI );
