
module.exports = processEnquiry;





// Standard libraries
let util = require( "util" );
let os = require( "os" );
let child_process = require( "child_process" );
let fs = require( "fs" );

// Third-party packages
// let axios = require( "axios" );

// Our custom imports
let jsonFs = require( "./json-fs.js" );
let enquiries = require( "../db/enquiries.js" );

/*
 * Constants declarations
 */
let rootDir = __dirname + "/../../";
let liveEnquiriesLogFileName = rootDir + "data/enquiries/enquiries.live.json";
let processedEnquiriesLogFileName = rootDir + "data/enquiries/enquiries.processed.json";
let errorEnquiriesLogFileName = rootDir + "data/enquiries/enquiries.errors.json";
let pricingSheetDirectory = rootDir + "data/pricing-sheets/";


// Promisify-ing the following functions so that it plays well with the async/await syntax
let exec = util.promisify( child_process.exec );
let writeFile = util.promisify( fs.writeFile );



async function processEnquiry ( cb ) {

	// Find the first enquiry whose state is "processing"
	let enquiry = enquiries.db.find( function ( enquiry ) {
		return enquiry._state == "processing";
	} );
	if ( ! enquiry ) {
		cb();
		return;
	}
	enquiry.errors = "";
	enquiry.pricingSheetFilename = enquiry.crm[ "Pricing sheet name" ].trim().replace( /[/:]/g, "-" ) + ".pdf";
	if ( process.env.NODE_ENV == "production" )
		enquiry.pricingSheetURL = encodeURI( enquiry._hostname + "/omega/data/pricing-sheets/" + enquiry.pricingSheetFilename );
	else
		enquiry.pricingSheetURL = encodeURI( enquiry._hostname + "/data/pricing-sheets/" + enquiry.pricingSheetFilename );
	enquiry.pricingSheetFilePath = pricingSheetDirectory + enquiry.pricingSheetFilename;
	// This object stores a log of all the actions that have been performed on the enquiry
	enquiry.pipeline = { };

	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */
	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */
	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */

	// Generate the pricing sheet
	if ( ! enquiry.pipeline.generatePricingSheet ) {
		try {
			await generatePricingSheet( enquiry );
			enquiry.pipeline.generatePricingSheet = true;
		} catch ( e ) {
			enquiry.errors += e.message;
		}
	}

	// Send an e-mail to the customer
	if ( ! enquiry.pipeline.sendMail ) {
		try {
			await sendMail( enquiry );
			enquiry.pipeline.sendMail = true;
		} catch ( e ) {
			enquiry.errors += e.message;
		}
	}

	// Ingest the pricing sheet into the CRM
	if ( ! enquiry.pipeline.addPricingSheetToCRM ) {
		try {
			await attachFileToUserOnCRM( enquiry );
			enquiry.pipeline.addPricingSheetToCRM = true;
		} catch ( e ) {
			enquiry.errors += e.message;
		}
	}

	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */
	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */
	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */

	// Remove the enquiry from the live enquiries database
	enquiries.db = enquiries.db.filter( function ( currentEnquiry ) {
		return currentEnquiry._id != enquiry._id;
	} );
	await writeFile( liveEnquiriesLogFileName, JSON.stringify( enquiries.db ) );

	// Notify us if there were any errors
	if ( enquiry.errors ) {
		// Log them separately
		await jsonFs.add( errorEnquiriesLogFileName, enquiry );
		cb();
		// Send mail to us
		// axios.get( "http://ser.om/notify-error", { params: enquiry } );
		return;
	}

	// Finally, write the results back to the log file
	enquiry._state = "processed";
	// enquiry.description = "Finished end-to-end processing of the enquiry.";
	await jsonFs.add( processedEnquiriesLogFileName, enquiry );
	cb();

	return;

}


/*
 *
 * Generate a pricing sheet
 *
 */
async function generatePricingSheet ( enquiry ) {

	try {

		// Plonk the input into a temporary file
		let tmpFile = os.tmpdir() + "/tmp-enquiry.json";
		let pricingSheetFilePath = enquiry.pricingSheetFilePath;

		await writeFile( tmpFile, JSON.stringify( enquiry.pdf ) );

		let command = "node pdf-create/index.js -i '" + tmpFile + "' -o '" + pricingSheetFilePath + "'";
		if ( process.env.NODE_ENV == "production" ) {
			command = "NODE_ENV=production " + command;
		}
		let { stdout } = await exec( command, { cwd: rootDir } );
		let response = JSON.parse( stdout );

		return response;

	} catch ( e ) {

		let message = "[Generating a PDF]\n"
					+ e.stdout + "\n"
					+ e.stderr + "\n\n";
		throw new Error( message );

	}

}

/*
 *
 * Send a mail to the user
 *
 */
async function sendMail ( enquiry ) {

	// Plonk the input into a temporary file
	let tmpFile = os.tmpdir() + "/tmp-enquiry.json";
	await writeFile( tmpFile, JSON.stringify( enquiry ) );

	try {
		let command = "php mail-send/index.php -i '" + tmpFile + "'";
		let { stdout } = await exec( command, { cwd: rootDir } );
		let response = JSON.parse( stdout );

		return response;
	} catch ( e ) {

		let message = "[Posting a Mail]\n"
					+ e.stdout + "\n"
					+ e.stderr + "\n\n";
		throw new Error( message );

	}

}

/*
 *
 * Attach the pricing sheet to the user on the CRM
 *
 */
async function attachFileToUserOnCRM ( enquiry ) {

	let userId = enquiry.user._id;
	let filePath = __dirname + "/../../data/pricing-sheets/" + enquiry.pricingSheetFilename;

	try {
		let command = "php user-add-file/cli.php -u " + userId + " -f '" + filePath + "'";
		let { stdout } = await exec( command, { cwd: rootDir } );
		let response = JSON.parse( stdout );

		return response;
	} catch ( e ) {

		let message = "[Attaching file to User]\n"
					+ e.stderr + "\n\n";
		throw new Error( message );

	}

}
