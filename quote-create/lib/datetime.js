
module.exports = {
	getUnixTimestamp,
	getDatetimeStamp
};





function getUnixTimestamp () {
	return ( new Date() ).getTime();
}

var dateTimeFormat = new Intl.DateTimeFormat(
	"en-IN",
	{ timeZone: "Asia/Kolkata", hour12: false, year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit", second: "2-digit" }
);

function getDatetimeStamp () {
	return dateTimeFormat.format( new Date() )
		.replace( /[/:]/g, "-" )
		.replace( /,/g, "" )
}
