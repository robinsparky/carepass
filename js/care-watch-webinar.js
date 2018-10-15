(function($) {

    $(document).ready(function() {       
        let sig = '#care-resultmessage';
        console.log("Watch Webinar");
        console.log(care_watch_webinar_obj);
        //Assumes only one video in the page
        let video = document.querySelector("video");
        if( !video ) return;

        var longtimeout = 60000;
        var shorttimeout = 15000;


        let play = $('#play').on('click',function() { 
            if(video.paused) video.play()
            else video.pause();
        });

        let progress = $('#progress');
        let restart  = $('#restart').on('click', function() {$(sig).html('');progress.val(0); video.load();} );
        
        window.addEventListener( 'blur', function() { console.log('Watch Webinar: blur' + progress.val()); } );
        window.addEventListener( 'focus', function() { console.log('Watch Webinar: focus'); } );

        video.addEventListener('timeupdate', function() {
                                                let prog = video.currentTime / video.duration;
                                                let pct = (100*prog).toFixed(2);
                                                progress.val(prog);
                                                $(sig).html('<h2>' + pct + '%</h2>')	
                                            });
        video.addEventListener('ended', function() {
                                                let c = "<h2> '" + video.dataset.name + "' Completed!</h2>";
                                                $(sig).html(c);
                                                let startdate = new Date();
                                                let startdatestr = formatDate( startdate );
                                                let enddatestr = startdatestr;
                                                console.log("startDate %s", startdate );

                                                ajaxFun( {id: $('video').attr("id")
                                                         , name: video.dataset.name
                                                         , location: 'online'
                                                         , startDate: startdatestr
                                                         , endDate: enddatestr
                                                         , watchedPct: progress.val() } 
                                                         );
                                        });
        let volume = $("#volume").on('change', function(e) {
                        video.volume = e.currentTarget.value / 100;
                    });

        $("#makebig").on('click', function(e) { 
            video.width = 800; 
        });

        $("#makesmall").on('click', function(e) { 
            video.width = 300; 
        });

        $("#makenormal").on('click', function(e) { 
            video.width = 600; 
        });

        // console.log(video);
        // console.log(play);
        // console.log(pause);
        // console.log(progress);
        // console.log(volume);       
        let ajaxFun = function( webinar ) {
            console.log('Watching Webinar: ajaxFun');
            let reqData =  { 'action': care_watch_webinar_obj.action      
                            , 'security': care_watch_webinar_obj.security
                            , 'user_id' : care_watch_webinar_obj.user_id
                            , 'webinar': webinar };
            //console.log( reqData );
            console.log("************************Webinar:");
            console.log( webinar );

                // Send Ajax request with data 
            let jqxhr = $.ajax( { url: care_watch_webinar_obj.ajaxurl    
                                , method: "POST"
                                , async: true
                                , data: reqData
                                , dataType: 'json'
                        ,beforeSend: function( jqxhr, settings ) {
                            //Disable the 'Done' button
                            $(sig).html('Loading...');
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
                        console.log('jqXHR.responseText:');
                        console.log(jqXHR.responseText);
                        $(sig).addClass('care-error');
                        $(sig).html(errmess);
                    })
                    .always( function() {
                        console.log( "always" );
                        setTimeout(function(){
                                    $(sig).html('');
                                    $(sig).removeClass('care-error');
                                }, longtimeout);
                    });
            
            return false;
        }

        function formatDate( date ) {
            var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();
        
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
        
            return [day, month, year].join('-');
        }
    });
 })(jQuery);
        