
/*
 *
 * This file pulls in the Quotes database and scopes it to an object
 * under a `db` attribute
 *
 */

const db = require( __dirname + "/../../data/quotes/quotes.live.json" );
module.exports = { db }
