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
            course = e.target.options[e.target.selectedIndex].text;
			console.log("Setting title to: " + course );
			if( course.includes('Remove') ) {
				$("div#titlewrap input#title").val('').focus();
			}
			else {
				//$("div#titlewrap input#title").attr("placeholder", "").val("").focus().blur();
				$("div#titlewrap input#title").val(course).focus();
			}

			return false;
		});
	})
})(jQuery);
