/**
* Add, remove or modify
* course or webinar status reports
*/
(function($) {
   $(document).ready(function(){
       console.log( 'Record User Courses:tableclass="%s"'
                  , care_userprofile_obj.tableclass);

       var sig = '#care-resultmessage';
       let course = '';
       let courseId = 0;
       var longtimeout = 60000;
       var shorttimeout = 15000;
       let completed = "Completed";
       let pending   = "Pending";
       let registered = "Registered";
       let toggle_title = 'Toggle Status';
       let remove_title = 'Remove';
       let messwindow = '<div id="caremessagewindow" style="position: absolute; top: 100px; width: 100px; height: 20px; background-color: green"></div>';
       let removeButton = "<button id='remove-course' name='remove-course' type='button'>" + remove_title + "</button>";
       let toggleButton = "<button id='toggle-course-status' name='toggle-course-status' type='button'>" + toggle_title + "</button>";

       $(sig).addClass('care-error').html(care_userprofile_obj.message);
       setTimeout(function(){
                    $(sig).html('');
                    $(sig).removeClass('care-error');
                }, shorttimeout);

       $('#course-select').change(function(e) {
            console.log("#course-select fired!");
            course = e.target.options[e.target.selectedIndex].text;
            courseId = e.target.value; 
            status = pending;
            if(courseId !== '0' ) {
                duplicate = false;          
                $("table.course-status > tbody > tr[id!='add']").each( function() {
                    id = $(this).children('td:nth-child(1)').attr('id');
                    console.log("testing id=%s",id);
                    if( courseId === id ) {
                        console.log("Detected duplicate: %d", courseId );
                        duplicate = true;   
                    }
                });
                if( !duplicate ) {
                    var markup = '<tr><td id="' + courseId + '" class="course-status">' + course + '</td>';
                    markup += "<td>" + status + "</td><td id='operations'>"
                    markup += "</td></tr>";
                    $("table.course-status tbody").append(markup);
                    $("table.course-status tbody").children('tr:last-child').children('td:last-child').append(removeButton);

                    $("table.course-status tbody").children('tr:last-child').children('td:last-child').append(toggleButton);

                    hidden = '<input type="hidden" name="statusreports[]" value="' + courseId + "|" + course + "|" + status + '">';
                    $(hidden).insertAfter('table.form-table');
                }
            }
       });
       
        // Remove row
        $("table.course-status").on("click", "button#remove-course", function() {
            courseId = $(this).closest("tr").attr("id");
            console.log('remove fired: courseId=%s', courseId);
            selector = "input[type='hidden'][value^='" + courseId + "']";
            $(selector).remove();
            $(this).closest("tr").remove();
        });

        //Modify status of a row
        $("table.course-status").on("click", "button#toggle-course-status", function() {
            console.log('toggle fired!');
            courseId = $(this).closest("tr").attr("id");
            console.log("CourseId=%d", courseId);
            $statusCell = $(this).closest("td").prev(); //status cell is just to left of operations cell
            selector = "input[type='hidden'][value^='" + courseId + "']";
            oldVal = $(selector).val();
            $statusCell.text(getNextStatus($statusCell.text()));
            arrVal = oldVal.split("|");
            arrVal[5] = $statusCell.text();
            $(selector).val(arrVal.join("|"));
       });
       
   });

   function getNextStatus( currentStatus ) {
        let completed = "Completed";
        let registered = "Registered";
        let pending = "Pending"
        switch(currentStatus) {
            case pending:
                return registered;
            case registered:
                return completed;
            default:
                return pending;
        }
   }
})(jQuery);
