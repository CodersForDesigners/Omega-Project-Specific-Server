
module.exports = {
	schedule,
	onStopped
};

function schedule ( task, interval ) {

	interval = interval * 1000;
	var status = null;
	var timerId = null;

	/*
	 *
	 * This function simply schedules an execution of the task based on the
	 * given interval
	 *
	 */
	function scheduleNextExecution () {
		if ( status == "running" ) {
			timerId = setTimeout( runAndScheduleTask, interval );
		}
		else {
			// if the status is not "running", then it must be "stopping",
			// in which case, make it "stopped"
			status = "stopped";
		}
	}

	/*
	 *
	 * This function simply runs the task,
	 * passing in the `scheduleNextExecution` function as a callback to be
	 * exectued once the task is finished. This is because the tasks are async.
	 *
	 */
	function runAndScheduleTask () {
		task( scheduleNextExecution );
	}

	/*
	 *
	 * This is the API that we're returning
	 *
	 */
	return {
		get status () {
			return status;
		},
		set status ( message ) {
			status = message;
		},
		start: function () {
			if ( status == "running" ) return;
			status = "running";
			scheduleNextExecution();
		},
		stop: function () {
			clearTimeout( timerId );
			timerId = null;
			status = "stopping";
		},
	};

}

function onStopped ( task, callback ) {

	var timestamp = ( new Date() ).getTime() / 1000;
	function checkIfTheTaskHasStopped () {
		var currentTimestamp = ( new Date() ).getTime() / 1000;
		if ( task.status == "stopped" || currentTimestamp - timestamp >= 15 )
			callback();
		else
			setTimeout( checkIfTheTaskHasStopped, 1000 );
	}

	checkIfTheTaskHasStopped();

}
