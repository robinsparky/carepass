/**
 * Redirect to Event for registration for a course
 */
(function($) {
	$(document).ready( function() {
		console.log('Register by Event');

		$('#carecourse_register').on('click', function() {
			if( window.careMessageHandler ) {
				console.log(window.careMessageHandler);
			}
			else {
				console.log("window.careMessageHandler does not exist!");
			}

			// console.log("Dataset:");
			// console.log(this.dataset);
			var eventdata = {'courseId': this.dataset.courseid
	        	            ,'courseName': this.dataset.coursename
							,'sessionLocation': this.dataset.sessionlocation
							,'startDate': this.dataset.startdate
							,'startTime': this.dataset.starttime
							,'endDate': this.dataset.enddate
							,'endTime': this.dataset.endtime
							,'sessionSlug': this.dataset.sessionslug
						};

			console.log("Redirecting to: %s",eventdata.sessionSlug);
			window.careMessageHandler.showMessage("Register by for course: " + this.dataset.coursename);

			window.location.href = eventdata.sessionSlug;

			return false;
		});
	})
})(jQuery);
