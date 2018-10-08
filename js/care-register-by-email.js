/**
 * Submit pending regisgtration for a course by email
 */
(function($) {
	$(document).ready( function() {
		console.log('Register by Email:Ajax Url="%s"',care_email_obj.ajaxurl);
		console.log(care_email_obj);

		var longtimeout = 60000;
		var shorttimeout = 10000;

		$('#carecourse_register').on('click', function() {
			//alert("Sending course registration for:" + this.dataset.coursename);
			if( window.careMessageHandler ) {
				console.log(window.careMessageHandler);
			}
			else {
				console.log("window.careMessageHandler does not exist!");
			}

			console.log("Dataset:");
			console.log(this.dataset);
			var emaildata = {'action': care_email_obj.action
			                ,'security': care_email_obj.security
	        	            ,'courseId': this.dataset.courseid
	        	            ,'courseName': this.dataset.coursename
							,'sessionLocation': this.dataset.sessionlocation
							,'startDate': this.dataset.startdate
							,'startTime': this.dataset.starttime
							,'endDate': this.dataset.enddate
							,'endTime': this.dataset.endtime
						};

			console.log("Email Data:");
			console.log(emaildata);

			// Send Ajax request with data 
			$.ajax( {url: care_email_obj.ajaxurl
				    , method: "POST"
				    , data: emaildata
			        , dataType: 'json'
					,beforeSend: function( jqxhr, settings ) {
						//Disable the 'Done' button
						window.careMessageHandler.showMessage('Sending email ...');
					}})
				    .done( function (  res, jqxhr ) {
				    	if(res.success) {
							console.log('Success:');
							console.log(res.data);
							//Do stuff with data...
							window.careMessageHandler.showMessage( res.data.result );
				    		setTimeout(function(){window.careMessageHandler.hide();},shorttimeout);
				    	}
				    	else {
							console.log('Error:');
							console.log(res.data);
							var entiremess = "";
							for(var i=0; i < res.data.length; i++) {
								entiremess += res.data[i].message;
							}
							window.careMessageHandler.showError(entiremess);
				    		setTimeout(function(){window.careMessageHandler.hide();},longtimeout);	
				    	}
					})
				   .fail(function( jqxhr,  textStatus, errorThrown ) {
						console.log('fail:');
						console.log(jqxhr);
						var errmess = "Error: status='" + textStatus + "' - '" + errorThrown + "'";
						console.log(errmess);
						console.log(jqxhr.responseText);
						window.careMessageHandler.showError(jqxhr.responseText);		
						setTimeout(function(){window.careMessageHandler.hide();},longtimeout);				
					})
					.always( function() {
						console.log( "always:" );
						setTimeout(function(){window.careMessageHandler.hide();}, longtimeout);
					})
			
			return false;
		});
	})
})(jQuery);
