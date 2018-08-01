
// Standard libraries
let util = require( "util" );
let fs = require( "fs" );

// Third-party packages
let express = require( "express" );
let bodyParser = require( "body-parser" );

// Our custom imports
let datetime = require( "./lib/datetime.js" );
let scheduler = require( "./lib/scheduler.js" );
let processEnquiry = require( "./lib/enquiry-processor.js" );
let enquiries = require( "./db/enquiries.js" );



/*
 * Constants declarations
 */
let httpPort = 9991;
let credentialsFileName = __dirname + "/../data/users/users.json";
let logFileName = __dirname + "/../data/enquiries/enquiries.live.json";

// Promisify-ing the "writeFile" function so that it plays well with the async/await syntax
let writeFile = util.promisify( fs.writeFile );

// Initiate the background task
var backgroundTask = scheduler.schedule( processEnquiry, 5 );
backgroundTask.start();

/*
 * Set up the HTTP server and the routes
 */
let router = express();
// Create an HTTP body parser for the type "application/json"
let jsonParser = bodyParser.json()
// Create an HTTP body parser for the type "application/x-www-form-urlencoded"
// let urlencodedParser = bodyParser.urlencoded( { extended: true } )

// Plugging in the middleware
// router.use( urlencodedParser );
router.use( jsonParser );


router.options( "/enquiries", async function ( req, res ) {
	res.header( "Access-Control-Allow-Origin", req.headers.origin );
	res.header( "Access-Control-Allow-Credentials", "true" );
	res.header( "Access-Control-Allow-Methods", "OPTIONS, POST" );
	res.header( "Access-Control-Allow-Headers", "Content-Type, Authorization, Content-Length, X-Requested-With" );
	res.sendStatus( 200 );
} );

router.post( "/enquiries", async function ( req, res ) {

	// res.header( "Access-Control-Allow-Origin", "*" );
	res.header( "Access-Control-Allow-Origin", req.headers.origin );
	res.header( "Access-Control-Allow-Credentials", "true" );

	/*
	 * Log the enquiry
	 */
	let _when = req.body.timestamp;
	delete req.body.timestamp;
	var enquiry = {
		_id: datetime.getUnixTimestamp(),
		_when,
		_state: "processing",
		_hostname: `${req.protocol}://${req.headers[ "x-forwarded-host" ]}`,
		_user: "regular",
		...req.body
	};
	enquiries.db.push( enquiry );
	await writeFile( logFileName, JSON.stringify( enquiries.db ) );
	// fs.writeFileSync( logFileName, JSON.stringify( enquiries.db ) );

	// Respond back
	res.json( { message: "We're processing the enquiry." } );
	res.end();

} );





let httpServer = router.listen( httpPort, function (  ) {
	if ( process.env.NODE_ENV != "production" )
		console.log( "Server listening at " + httpPort + "." );
	if ( process.send )
		process.send( "ready" );
} );


/*
 * Handle process shutdown
 *
 * 1. Stop the background task.
 * 2. Once that is done, then close the HTTP server.
 * 3. Finally, quit the process.
 *
 * ** ONLY IF THIS IS A PRODUCTION ENVIRONMENT
 *
 */
process.on( "SIGINT", function () {

	if ( process.env.NODE_ENV != "production" )
		return process.exit( 0 );

	backgroundTask.stop();
	scheduler.onStopped( backgroundTask, function () {
		httpServer.close();
		return process.exit( 0 );
	} );

} );
