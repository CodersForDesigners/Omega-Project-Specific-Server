
module.exports = processQuote;





// Standard libraries
let util = require( "util" );
let os = require( "os" );
let child_process = require( "child_process" );
let fs = require( "fs" );

// Third-party packages
// let axios = require( "axios" );

// Our custom imports
let jsonFs = require( "./json-fs.js" );
let quotes = require( "../db/quotes.js" );

/*
 * Constants declarations
 */
let rootDir = __dirname + "/../../";
let liveQuotesLogFileName = rootDir + "data/quotes/quotes.live.json";
let processedQuotesLogFileName = rootDir + "data/quotes/quotes.processed.json";
let errorQuotesLogFileName = rootDir + "data/quotes/quotes.errors.json";
let quoteSheetDirectory = rootDir + "data/quote-sheets/";


// Promisify-ing the following functions so that it plays well with the async/await syntax
let exec = util.promisify( child_process.exec );
let writeFile = util.promisify( fs.writeFile );



async function processQuote ( cb ) {

	// Find the first quote whose state is "processing"
	let quote = quotes.db.find( function ( quote ) {
		return quote._state == "processing";
	} );
	if ( ! quote ) {
		cb();
		return;
	}
	quote.errors = "";
	quote.pricingSheetFilename = quote.crm[ "Pricing sheet name" ].trim().replace( /[/:]/g, "-" ) + ".pdf";
	if ( process.env.NODE_ENV == "production" )
		quote.pricingSheetURL = encodeURI( quote._hostname + "/omega/data/quote-sheets/" + quote.pricingSheetFilename );
	else
		quote.pricingSheetURL = encodeURI( quote._hostname + "/data/quote-sheets/" + quote.pricingSheetFilename );
	// This object stores a log of all the actions that have been performed on the quote
	quote.pipeline = { };

	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */
	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */
	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */

	// Generate the pricing sheet
	if ( ! quote.pipeline.generateQuoteSheet ) {
		try {
			await generateQuoteSheet( quote );
			quote.pipeline.generateQuoteSheet = true;
		} catch ( e ) {
			quote.errors += e.message;
		}
	}

	// Send an e-mail to the customer
	// if ( ! quote.pipeline.sendMail ) {
	// 	try {
	// 		await sendMail( quote );
	// 		quote.pipeline.sendMail = true;
	// 	} catch ( e ) {
	// 		quote.errors += e.message;
	// 	}
	// }

	// Ingest the pricing sheet into the CRM
	if ( ! quote.pipeline.createQuoteOnCRM ) {
		try {
			await createQuoteOnCRM( quote );
			quote.pipeline.createQuoteOnCRM = true;
		} catch ( e ) {
			quote.errors += e.message;
		}
	}

	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */
	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */
	/* -X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X-X- */

	// Remove the quote from the live quotes database
	quotes.db = quotes.db.filter( function ( currentQuote ) {
		return currentQuote._id != quote._id;
	} );
	await writeFile( liveQuotesLogFileName, JSON.stringify( quotes.db ) );

	// Notify us if there were any errors
	if ( quote.errors ) {
		// Log them separately
		await jsonFs.add( errorQuotesLogFileName, quote );
		cb();
		// Send mail to us
		// axios.get( "http://ser.om/notify-error", { params: quote } );
		return;
	}

	// Finally, write the results back to the log file
	quote._state = "processed";
	// quote.description = "Finished end-to-end processing of the quote.";
	await jsonFs.add( processedQuotesLogFileName, quote );
	cb();

	return;

}


/*
 *
 * Generate a quote sheet
 *
 */
async function generateQuoteSheet ( quote ) {

	try {

		// Plonk the input into a temporary file
		let tmpFile = os.tmpdir() + "/tmp-quote.json";
		let quoteSheetFilePath = quoteSheetDirectory + quote.pricingSheetFilename;

		await writeFile( tmpFile, JSON.stringify( {
			meta: quote.meta,
			user: quote.user,
			points: quote.pdf
		} ) );

		let command = "node pdf-create/index.js -i '" + tmpFile + "' -o '" + quoteSheetFilePath + "'";
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
async function sendMail ( quote ) {

	// Plonk the input into a temporary file
	let tmpFile = os.tmpdir() + "/tmp.json";
	await writeFile( tmpFile, JSON.stringify( quote ) );

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
async function createQuoteOnCRM ( quote ) {

/*
 * - user . _id
 * - user . SMOWNERID
 * - user . email
 * - quote name
 * - validFor
 * - amount ( grand total )
 * - pricingSheetURL
 */

	let userId = quote.customer._id;
	let quoteName = quote.crm[ "Quote name" ];
	let validFor = quote.meta[ "Quote Valid For" ];
	let amount = quote.unit.grandTotal;
	let filePath = __dirname + "/../../data/quote-sheets/" + quote.pricingSheetFilename;

	try {
		let command = "php create-quote.php "
					+ "-u " + userId + " "
					+ "-n '" + quoteName + "' "
					+ "-v '" + validFor + "' "
					+ "-a " + amount + " "
					+ "-p '" + filePath + "'";
		let { stdout } = await exec( command, { cwd: __dirname } );
		let response = JSON.parse( stdout );

		return response;
	} catch ( e ) {

		let message = "[Creating Quote on CRM]\n"
					+ e.stderr + "\n\n";
		throw new Error( message );

	}

}
