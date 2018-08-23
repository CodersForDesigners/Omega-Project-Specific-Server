
/*
 *
 * This module has functions to handle data in JSON files
 *
 */

module.exports = {
	add
};





// Standard libraries
let fs = require( "fs" );

function add ( fileName, record ) {

	return new Promise( function ( resolve, reject ) {

		let recordText = "," + JSON.stringify( record );
		fs.appendFile( fileName, recordText, function ( e ) {
			if ( e )
				return reject( e );
			return resolve( record );
		} );

	} );

}
