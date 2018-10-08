
(function($) {
	$(document).ready( function() {
        let $win = $("#care_message_window");
        if($win.length < 1) {
            let messDiv = '<div id="care_message_window">'
                            + '<div class="care-message"></div>'
                            + '<div class="care-error"></div>'
                        + '</div>';
            $("body").prepend(messDiv)
        }

        if( window.careMessageHandler ) return;

        window.careMessageHandler = {
            _$handler: $("#care_message_window").draggable().hide(),
            showMessage: function(strMess) {
                console.log("showMessage..." + strMess);
                this._$handler.children('div.care-message').html("<p>" + strMess + "</p>");
                this._$handler.show();
            },
            showError: function(strErr) {
                console.log("showError..." + strErr);
                this._$handler.children('div.care-error').html("<p>" + strErr + "</p>");
                this._$handler.show(); 
            }
            ,hide: function() {
                console.log("hide...");
                this._$handler.children('div.care-message').html('');
                this._$handler.children('div.care-error').html('');
                this._$handler.hide();
            }
        }
    });
})(jQuery);