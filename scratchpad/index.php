<?php

ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );

header( 'Access-Control-Allow-Origin: *' );

date_default_timezone_set( 'Asia/Kolkata' );

// continue processing this script even if
// the user closes the tab, or
// hits the ESC key
ignore_user_abort( true );

// do not let this script timeout
set_time_limit( 0 );

header( 'Content-Type: application/json' );





http_response_code( 500 );
// die( json_encode( [ 'statusCode' => 1, 'message' => 'yada yada yada' ] ) );
die( 'yada yada yada' );
// doo();
