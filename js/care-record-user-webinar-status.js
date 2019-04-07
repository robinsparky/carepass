/**
* Add, remove or modify webinar progress reports
*/
(function($) {
    var longtimeout = 60000;
    var shorttimeout = 15000;
    var sig = '#care-webinarmessage';

   $(document).ready(function(){
       console.log( 'Webinar Progress:tableclass="%s"'
                  , care_userprofile_webinar.tableclass);
        console.log(care_userprofile_webinar);

       let webinar = '';
       let webinarId = 0;
       let startdate = '1970-01-01';
       let status = care_userprofile_webinar.statusvalues[0];
       let statusSelect = '<select id="statusSelect" name="status">';
       care_userprofile_webinar.statusvalues.forEach(function(status) {
           statusSelect += '<option>' + status + '</option>';           
        });
        statusSelect += '</select>'

       let toggle_title = 'Toggle Status';
       let remove_title = 'Remove';
       //let messwindow = '<div id="caremessagewindow" style="position: absolute; top: 100px; width: 100px; height: 20px; background-color: green"></div>';
       let removeButton = "<button id='remove-webinar' name='remove-webinar' type='button'>" + remove_title + "</button>";
       let toggleButton = "<button id='toggle-webinar-status' name='toggle-webinar-status' type='button'>" + toggle_title + "</button>";

       $(sig).addClass('care-error').html(care_userprofile_webinar.message);
       setTimeout(function(){
                    $(sig).html('');
                    $(sig).removeClass('care-error');
                }, shorttimeout);

       $('#webinar-select').change(function(e) {
            console.log("#webinar-select fired!");
            webinar = e.target.options[e.target.selectedIndex].text;
            webinarId = e.target.value; 
            
            if(webinarId !== '0' ) {
                duplicate = false;          
                $("table." + care_userprofile_webinar.tableclass + " > tbody > tr[id!='add']").each( function() {
                    id = $(this).attr('id');
                    console.log("testing id=%s",id);
                    if( webinarId === id ) {
                        console.log("Detected duplicate webinar: %d", webinarId );
                        duplicate = true;   
                    }
                });
                if( !duplicate ) {
                    let markup = '<tr id="' + webinarId + '">';
                    markup += '<td class="name">' + webinar + '</td>';
                    markup += '<td class="startdate"><input type="date" id="start" name="startdate" value="' + startdate + '"/></td>';
                    markup += '<td class="status">' + statusSelect + '</td>';
                    markup += '<td class="operation">';
                    markup += '</td></tr>';
                    $("table." + care_userprofile_webinar.tableclass + " tbody").append(markup);
                    $("table." + care_userprofile_webinar.tableclass + " tbody").children('tr:last-child').children('td:last-child').append(removeButton);
                    //$("table." + care_userprofile_webinar.tableclass + " tbody").children('tr:last-child').children('td:last-child').append(toggleButton);

                    hidden = '<input type="hidden" name="webinarreports[]" value="' + webinarId + "|" + webinar + "|" + startdate + "|" + status + '">';
                    $(hidden).insertAfter("table." + care_userprofile_webinar.tableclass);
                }
            }
       });
       
        // Remove row
        $("table." + care_userprofile_webinar.tableclass).on("click", "button#remove-webinar", function() {
            webinarId = $(this).closest("tr").attr("id");
            console.log('webinar remove fired: webinarId=%s', webinarId);
            selector = "input[type='hidden'][value^='" + webinarId + "']";
            $(selector).remove();
            $(this).closest("tr").remove();
        });
        
        //Modify startdate of a row; index=2
        $("table." + care_userprofile_webinar.tableclass).on("change", "input[type='date']", function(e) {
            console.log('webinar date change fired!');
            webinarId = $(this).closest("tr").attr("id");
            console.log("WebinarId=%d", webinarId);
            dateElement = $(this)[0];
            //console.log(dateElement);
            newDate = dateElement.value;
            console.log('new date is %s', newDate );
            selector = "input[type='hidden'][value^='" + webinarId + "']";
            console.log( $(selector) );
            oldVal = $(selector).val();
            console.log("oldVal=%s", oldVal);
            arrVal = oldVal.split("|");
            arrVal[2] = newDate;
            console.log("newVal=%s", arrVal.join("|"));
            $(selector).val(arrVal.join("|"));
       });

        //Modify status of a row; index=4
        $("table." + care_userprofile_webinar.tableclass).on("change", "#statusSelect", function(e) {
            console.log('webinar status select fired!');
            newStatus = e.target.options[e.target.selectedIndex].text;
            webinarId = $(this).closest("tr").attr("id");
            console.log("WebinarId=%d", webinarId);
            $statusCell = $(this).closest("td.status");
            selector = "input[type='hidden'][value^='" + webinarId + "']";
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

   function getNextStatus( currentStatus ) {
        switch(currentStatus) {
            case pending:
                return completed;
            case completed:
                return pending;
            default:
                return pending;
        }
   }
})(jQuery);
