/**
* 
*/
(function($) {
   $(document).ready(function(){
       console.log('Report Course Statuses: Ajax Url="%s"',care_userprofile_obj.ajaxurl);
       if( $("#done-course-work").length === 0) return;

       console.log('Report Course Statuses: Ajax user_id="%d"', care_userprofile_obj.user_id);
       var sig = '#care-resultmessage';
       let course = '';
       let courseId = 0;
       var longtimeout = 60000;
       var shorttimeout = 15000;
       let completed = "Completed";
       let registered = "Registered";

       $("#done-course-work").attr("disabled", "disabled");
       $("#done-course-work").on('click', function() {
           console.log("DONE!");
           let list = [];
           let tmp = {};
           let i = 0;
            $("table.coursestatus > tbody > tr[id!='add']").each(function(){
                let obj = { 'id': $(this).children('td:nth-child(1)').attr('id')
                          , 'name': $(this).children('td:nth-child(1)').text()
                          , 'status': $(this).children('td:nth-child(2)').text()
                        }
                list.push(obj);
                console.log(obj);
            });
            console.log("Total being posted=%d",list.length);
            console.log( list );
            ajaxFun( list );
       });

       $('#course-select').change(function(e) {
            course = e.target.options[e.target.selectedIndex].text;
            courseId = e.target.value; 
            if(courseId !== '0' ) {
                duplicate = false;          
                $("table.coursestatus > tbody > tr[id!='add']").each( function() {
                    id = $(this).children('td:nth-child(1)').attr('id');
                    console.log("testing id=%s",id);
                    if( courseId === id ) {
                        console.log("Detected duplicate: %d", courseId );
                        duplicate = true;   
                    }
                });
                if( !duplicate ) {
                    var markup = "<tr><td id=" + courseId + " class='courseprogress'>" + course + "</td><td>" + registered + "</td><td><input type='checkbox' name='record'></td></tr>";
                    $("table.coursestatus tbody").append(markup);
                    $("#done-course-work").removeAttr("disabled");
                }
            }
       });
       
        // Find and remove selected table rows
        $("#remove-course").click(function(){
            $("table.coursestatus tbody").find('input[name="record"]').each(function(){
                if($(this).is(":checked")){
                    $(this).parents("tr").remove();
                    $("#done-course-work").removeAttr("disabled");
                }
            });
        });

       $("#record-course-completed").on('click', function() {
            $("table.coursestatus tbody").find('input[name="record"]').each(function(){
                if($(this).is(":checked")){
                    if($(this).parents("td").prev().text() === completed) {
                        $(this).parents("td").prev().text(registered);
                    }
                    else {
                        $(this).parents("td").prev().text(completed);
                    }
                    $("#done-course-work").removeAttr("disabled");
                }
            });
       });

       let ajaxFun = function( newData ) {
           console.log('ajaxFun');
           console.log(care_registration_obj.ajaxurl);
           let reqData =  { 'action': 'registerCourseProgess'       
                        , 'security': care_registration_obj.security
                        , 'user_id' : care_registration_obj.user_id
                        , 'courses': newData };
            //console.log( reqData );

           // Send Ajax request with data 
            let jqxhr = $.ajax( {url: care_registration_obj.ajaxurl    
                                , method: "POST"
                                , async: true
                                , data: reqData
                                , dataType: 'json'
                        ,beforeSend: function( jqxhr, settings ) {
                            //Disable the 'Done' button
                            $(sig).html('Loading...');
                            $("#done-course-work").attr("disabled", "disabled");
                        }})
                .done( function( res, jqxhr ) {
                    console.log("done.res:");
                    console.log(res);
                    if( res.success ) {
                        console.log('Success (res.data):');
                        console.log(res.data);
                        //Do stuff with data...
                        $(sig).html( res.data.message );
                    }
                    else {
                        console.log('Done but failed (res.data):');
                        console.log(res.data);
                        var entiremess = res.data.message + " ...<br/>";
                        for(var i=0; i < res.data.exception.errors.length; i++) {
                            entiremess += res.data.exception.errors[i].message + '<br/>';
                        }
                        $(sig).addClass('care-error');
                        $(sig).html(entiremess);
                    }
                })
                .fail( function( jqXHR, textStatus, errorThrown ) {
                    console.log("fail");
                    console.log("Error: %s -->%s", textStatus, errorThrown );
                    var errmess = "Error: status='" + textStatus + "--->" + errorThrown;
                    errmess += jqXHR.responseText;
                    console.log('jqhxr.responseText:');
                    console.log(jqXHR.responseText);
                    $(sig).addClass('care-error');
                    $(sig).html(errmess);
                })
                .always( function() {
                    console.log( "always" );
                    setTimeout(function(){
                                 $(sig).html('');
                                 $(sig).removeClass('care-error');
                             }, shorttimeout);
                });
           
           return false;
       }
       
   });
})(jQuery);
