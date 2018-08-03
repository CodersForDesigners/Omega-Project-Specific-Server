<?php

if ( getenv( 'EXECUTION_ENVIRONMENT' ) == 'production' ) {
	return true;
} else {
	return false;
}
