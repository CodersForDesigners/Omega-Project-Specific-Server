
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

let printURL = "http://lw.lazaro.in/secret-soil/print-pricing-sheet";
if ( process.env.NODE_ENV != "production" ) {
	printURL = "http://pricing.om/print-pricing-sheet";
}

// let data = require( arguments.i );

async function main () {

	let inputData = require( args.i );
	let outputFilePath = args.o;
	// getURLAsPDF( "http://pricing.om/pricing/1", "test1.pdf", { cookie } )
	// let markup = render( data );
	// renderPageAsPDF( "<p>haha</p>", "direct-markup.pdf" );
	await getURLAsPDF( printURL, outputFilePath, {
		data: inputData
	} );
	// console.log( data() )
	process.stdout.write( JSON.stringify( { pricingSheet: args.o } ) );

};

main()
	// .catch( e => console.log( e ) )






function getPoints () {

	return [{"Content":"Section","Section":"Header","Font Size":"label","Font Weight":"regular"},{"Content":"Text","Name":"Mr. Potato ","Value":"Head","Font Size":"h2","Font Weight":"thin","Align":"center"},{"Content":"Text","Name":"House No. ","Value":5,"Font Size":"h1","Font Weight":"bold","Align":"center"},{"Content":"Text","Name":"RERA Carpet Area  –  ","Value":2293.78,"Format":"number","Suffix":"SFT","Font Size":"label","Font Weight":"regular","Align":"center","Preserve Whitespace":true},{"Content":"Text","Name":"Saleable Area  –  ","Value":2839,"Format":"number","Suffix":"SFT","Font Size":"label","Font Weight":"bold","Align":"center","Preserve Whitespace":true},{"Content":"Section","Section":"Light"},{"Content":"Helper","Name":"Keyplan","Font Size":"h2","Font Weight":"regular","Align":"center"},{"Content":"Image","Foreground":"Bedrooms-Studio.png","Background":"floorplan.jpg","Hide":false},{"Content":"Text","Name":"Saleable Area","Value":2839,"Format":"number","Suffix":"SFT","Font Size":"label","Font Weight":"bold"},{"Content":"Text","Name":"Basic Rate","Value":6740,"Prefix":"₹","Format":"number","Font Size":"h6","Font Weight":"regular"},{"Content":"Text","Name":"Basic Price","Value":19134860,"Prefix":"₹","Format":"number","Font Size":"h6","Font Weight":"bold"},{"Content":"Helper","Name":"Incl. 2 x Covered Carparks","Font Size":"small","Font Weight":"regular"},{"Content":"Text","Name":"East Facing Premium","Modifiable":false,"Value":709750,"Hide":false,"Prefix":"₹","Format":"number","Font Size":"label","Font Weight":"regular"},{"Content":"Text","Name":"Club Membership","Value":350000,"Prefix":"₹","Format":"number","Font Size":"label","Font Weight":"regular"},{"Content":"Text","Name":"Sale Value [ A ]","Value":20694610,"Prefix":"₹","Format":"number","Font Size":"h6","Font Weight":"bold","Horizontal Rule":"dashed"},{"Content":"Text","Name":"GST 12% [ B ]","Value":2483353.1999999997,"Prefix":"₹","Format":"number","Font Size":"label","Font Weight":"regular"},{"Content":"Section","Section":"Dark"},{"Content":"Helper","Name":"Other Charges\n","Font Size":"small","Font Weight":"bold"},{"Content":"Helper","Name":"(paid with penultimate instalment)","Font Size":"small","Font Weight":"regular"},{"Content":"Text","Name":"Infrastructure Charges @ ₹150 psft","Value":425850,"Prefix":"₹","Format":"number","Font Size":"small","Font Weight":"regular"},{"Content":"Text","Name":"Generator for 100% power backup","Value":50000,"Prefix":"₹","Format":"number","Font Size":"small","Font Weight":"regular"},{"Content":"Text","Name":"Corpus Deposit @ ₹100 psft","Value":283900,"Prefix":"₹","Format":"number","Font Size":"small","Font Weight":"regular"},{"Content":"Text","Name":"Advance Maintenance \nfor 1 Year @ ₹4 psft","Value":136272,"Prefix":"₹","Format":"number","Font Size":"small","Font Weight":"regular","Preserve Whitespace":false},{"Content":"Text","Name":"Legal Fees","Value":50000,"Prefix":"₹","Format":"number","Font Size":"small","Font Weight":"regular"},{"Content":"Text","Name":"Other Charges [ C ]","Value":946022,"Prefix":"₹","Format":"number","Font Size":"small","Font Weight":"bold","Horizontal Rule":"solid"},{"Content":"Section","Section":"Light"},{"Content":"Text","Name":"Effective Realisation psft [A] ","Modifiable":false,"Value":7289,"Hide":false,"Prefix":"₹","Format":"number","Font Size":"label","Font Weight":"regular"},{"Content":"Text","Name":"Discount","Modifiable":true,"Value":"","Prefix":"₹","Format":"number","Font Size":"label","Font Weight":"regular"},{"Content":"Section","Section":"Total"},{"Content":"Text","Name":"Grand Total \n[ A + B + C ]","Value":24123985,"Prefix":"₹","Format":"number","Font Size":"h5","Font Weight":"bold","Preserve Whitespace":false}];

}
