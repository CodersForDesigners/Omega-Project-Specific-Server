
// Standard libraries
let path = require( "path" );

// Third-party packages
let yargs = require( "yargs" );

// Our custom imports
let { getURLAsPDF } = require( "./lib/pdf-er.js" );

let args = yargs.argv;

if ( ! ( args.i && args.o ) ) {
	console.log( "Please specify an input and output, like so:" );
	console.log( "node " + path.basename( __filename ) + " -i data.json -o output.pdf" );
	process.exit( 1 );
}





async function main () {

	let inputData = require( args.i );
	let outputFilePath = args.o;
	// getURLAsPDF( "http://pricing.om/pricing/1", "test1.pdf", { cookie } )
	// let markup = render( data );
	// renderPageAsPDF( "<p>haha</p>", "direct-markup.pdf" );
	let printURL = inputData.meta[ "Print Sheet Route" ];
	if ( ! printURL )
		throw new Error( "No print sheet template route provided." );

	if ( process.env.NODE_ENV != "production" ) {
		printURL = "http://pricing.om/print-pricing-sheet";
	}
	await getURLAsPDF( printURL, outputFilePath, {
		data: inputData
	} );
	// console.log( data() )
	process.stdout.write( JSON.stringify( { pricingSheet: args.o } ) );

};

main()
	.catch( e => {
		process.stderr.write( e.message );
		process.exit( 1 );
	} )
