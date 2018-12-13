/**
* Add, remove or modify course status reports
*/
(function($) {
    
    var sig = '#care-coursemessage';
    var longtimeout = 60000;
    var shorttimeout = 15000;

   $(document).ready(function(){
       console.log( 'Course Progress:tableclass="%s"'
                  , care_userprofile_course.tableclass);

        console.log(care_userprofile_course);

       let course = '';
       let courseId = 0;
       let startdate = '1970-01-01';
       let status = care_userprofile_course.statusvalues[0];
       let statusSelect = '<select id="statusSelect" name="status">';
       care_userprofile_course.statusvalues.forEach(function(status) {
           statusSelect += '<option>' + status + '</option>';           
        });
        statusSelect += '</select>'

       let toggle_title = 'Toggle Status';
       let remove_title = 'Remove';
       //let messwindow = '<div id="caremessagewindow" style="position: absolute; top: 100px; width: 100px; height: 20px; background-color: green"></div>';
       let removeButton = "<button id='remove-course' name='remove-course' type='button'>" + remove_title + "</button>";
       let toggleButton = "<button id='toggle-course-status' name='toggle-course-status' type='button'>" + toggle_title + "</button>";

       $(sig).addClass('care-error').html(care_userprofile_course.message);
       setTimeout(function(){
                    $(sig).html('');
                    $(sig).removeClass('care-error');
                }, shorttimeout);

       $('#course-select').change(function(e) {
            console.log("#course-select fired!");
            course = e.target.options[e.target.selectedIndex].text;
            courseId = e.target.value; 

            if(courseId !== '0' ) {
                duplicate = false;          
                $("table." + care_userprofile_course.tableclass +  " > tbody > tr[id!='add']").each( function() {
                    id = $(this).attr('id');
                    console.log("testing id=%s",id);
                    if( courseId === id ) {
                        console.log("Detected duplicate: %d", courseId );
                        duplicate = true;   
                    }
                });
                if( !duplicate ) {
                    let markup = '<tr id="' + courseId + '">';
                    markup += '<td>' + course + '</td>';
                    markup += '<td><input type="date" id="start" name="startdate" value="' + startdate + '"/></td>';
                    markup += '<td class="status">' + statusSelect + '</td>';
                    markup += "<td id='operation'>";
                    markup += "</td></tr>";
                    $("table." + care_userprofile_course.tableclass +  " tbody").append(markup);
                    $("table." + care_userprofile_course.tableclass +  " tbody").children('tr:last-child').children('td:last-child').append(removeButton);
                    //$("table." + care_userprofile_course.tableclass +  " tbody").children('tr:last-child').children('td:last-child').append(toggleButton);

                    hidden = '<input type="hidden" name="coursereports[]" value="' + courseId + "|" + course + "|" + startdate + "|" + status + '">';
                    $(hidden).insertAfter("table." + care_userprofile_course.tableclass);
                }
            }
       });
       
        // Remove row
        $("table." + care_userprofile_course.tableclass).on("click", "button#remove-course", function() {
            courseId = $(this).closest("tr").attr("id");
            console.log('course remove fired: courseId=${courseId}');
            selector = "input[type='hidden'][value^='" + courseId + "']";
            $(selector).remove();
            $(this).closest("tr").remove();
        });
        
        //Modify startdate of a row; index=2
        $("table." + care_userprofile_course.tableclass).on("change", ".startdate", function(e) {
            console.log('course date change fired!');
            courseId = $(this).closest("tr").attr("id");
            console.log("CourseId=%d", courseId);
            $dateCell = $(this).closest("td.startdate"); 
            // console.log('Date Cell:');
            // console.log($dateCell);
            // console.log('First child:');
            dateElement = $dateCell.children()[0];
            //console.log(dateElement);
            newDate = dateElement.value;
            //console.log('new date is %s', newDate );
            selector = "input[type='hidden'][value^='" + courseId + "']";
            //console.log( $(selector) );
            oldVal = $(selector).val();
            //console.log("oldVal=%s", oldVal);
            //$statusCell.text(newStatus);
            arrVal = oldVal.split("|");
            arrVal[2] = newDate;
            console.log("newVal=%s", arrVal.join("|"));
            $(selector).val(arrVal.join("|"));
       });

        //Modify status of a row, index=4
        $("table." + care_userprofile_course.tableclass).on("change", "#statusSelect", function(e) {
            console.log('course status select fired!');
            newStatus = e.target.options[e.target.selectedIndex].text;
            courseId = $(this).closest("tr").attr("id");
            console.log("CourseId=%d", courseId);
            $statusCell = $(this).closest("td.status");
            selector = "input[type='hidden'][value^='" + courseId + "']";
            console.log( $(selector) );
            oldVal = $(selector).val();
            console.log("oldVal=%s", oldVal);
            //$statusCell.text(newStatus);
            arrVal = oldVal.split("|");
            arrVal[4] = newStatus;
            console.log("newVal=%s", arrVal.join("|"));
            $(selector).val(arrVal.join("|"));
       });
       
   });

})(jQuery);
