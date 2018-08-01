
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

		fs.open( fileName, "a", function ( e, fd ) {
			if ( e ) { return reject( e ); }
			fs.fstat( fd, function ( e, fstat ) {
				if ( e ) { return reject( e ); }
				let text;
				let writePosition;
				if ( fstat.size == 0 ) {
					text = "[" + JSON.stringify( record ) + "]";
					writePosition = 0;
				}
				else {
					text = "," + JSON.stringify( record ) + "]";
					writePosition = fstat.size - 1;
				}
				fs.write( fd, text, writePosition, function ( e ) {
					if ( e ) { return reject( e ); }
					fs.close( fd, function ( e ) {
						if ( e ) { return reject( e ); }
						resolve( record );
					} );
				} );
			} );
		} );

	} );

}
