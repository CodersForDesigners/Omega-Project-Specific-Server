
/*
 *
 * This file pulls in the Enquiries database and scopes it to an object
 * under a `db` attribute
 *
 */

const db = require( __dirname + "/../../data/enquiries/enquiries.live.json" );
module.exports = { db }
