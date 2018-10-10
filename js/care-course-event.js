/**
 * Redirect to Event for registration for a course
 */
(function($) {
	$(document).ready( function() {
        console.log('Course-Event Interface');
		console.log(care_course_session);
		var selectId = '#' + care_course_session.selectId 

		$( selectId ).on('change', function( e ) {
			if( window.careMessageHandler ) {
				//console.log(window.careMessageHandler);
			}
			else {
				console.log("window.careMessageHandler does not exist!");
			}
			
			console.log( selectId + " fired!");
			console.log( 'selected index=' + e.target.selectedIndex );
			if( e.target.selectedIndex === 0 ) {
				$("div#titlewrap input#title").val('').focus();
			}
			else {	
				course = e.target.options[e.target.selectedIndex];
				title = course.dataset.title;
				console.log("Setting title to: " + title );
				$("div#titlewrap input#title").val(title).focus();
			}

			return false;
		});
	})
})(jQuery);
