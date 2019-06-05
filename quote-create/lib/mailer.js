
/*
 *
 * This file exposes an API to send e-mails.
 *
 */


module.exports = {
	send,
	log
};

// Third-party packages
const nodemailer = require( "nodemailer" );





async function send ( { to, body, subject } ) {

	let transporter = nodemailer.createTransport( {
		host: "smtp.gmail.com",
		port: 465,
		secure: true,
		auth: {
			user: "google@lazaro.in",
			pass: "t34m,l4z4r0"
		}
	} );

	let envelope = {
		from: `"Lazaro Bot" <google@lazaro.in>`,
		to,
		text: body,
		html: body,
		subject
	};

	let status = await transporter.sendMail( envelope );

	return status;

}

async function log ( body, context, type = "issue" ) {

	// Set the recipients
	let to = [ "adityabhat@lazaro.in", "mario@lazaro.in", "mark@lazaro.in" ];

	// Prepare the subject
	if ( type == "issue" )
		subject = `[ Omega ] [ Issue ] : ${ context }`;
	else
		subject = `[ Omega ] [ FYI ] : ${ context }`;

	// Prepare the body
	if ( typeof body != "string" ) {
		let string = "";
		for ( let key in body ) {
			string += `<b>${ key }</b><br>${ body[ key ] }<br><br>`;
		}
		body = string;
	}
	body = `
		Something wen't wrong.
		<br><br>
		${ body }
	`.replace( /\s*\n+\s*/g, "<br>" );

	// Send the mail
	let status = await send( { to, body, subject } );

	return status;

}
