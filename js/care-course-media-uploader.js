jQuery(document).ready( function($) {

    console.log("Care Course Media Uploader ... local object:");
    console.log(care_course_media_obj);

    let mediaUploader;
    let metaBoxId = "#" + care_course_media_obj.metaBoxId + ".postbox";
    let mediaInput = "#" + care_course_media_obj.mediaInput;
    let mediaContainerClass = care_course_media_obj.mediaContainer;
    let mediaType = care_course_media_obj.mediaType;
    let metaBox = $(metaBoxId), 
                  addMediaLink = metaBox.find('#care-media-add'),
                  removeMediaLink = metaBox.find('#care-media-remove'),
                  mediaContainer = metaBox.find("." + mediaContainerClass),
                  mediaIdInput = metaBox.find(mediaInput); 

    addMediaLink.on('click', function(e) {
        e.preventDefault();
        if(mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose a Video'
            ,button: {text:'Select Video'}
            ,multiple: false            ,
        });
    
        mediaUploader.on('select', function() {
            attachment = mediaUploader.state().get('selection').first().toJSON();
            mediaIdInput.val(attachment.url);
            
            // Send the attachment URL to our custom image input field.
            if(mediaType === 'video') {
                markup = "<video id='temp' data-name='temp' controls style='max-width:100%;'>"
                markup += '<source src="' + attachment.url + '" type="video/mp4">';
                markup += 'HTML5 video not supported.'
                markup += '</video>';
                
                mediaContainer.html( markup );
            }
            else {
                mediaContainer.append( '<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>' );
            }
            
            // Hide the add image link
            addMediaLink.addClass( 'hidden' );

            // Unhide the remove image link
            removeMediaLink.removeClass( 'hidden' );
        });
    
        mediaUploader.open();
    });
    
    // Remove the link
    removeMediaLink.on( 'click', function( e ){

        e.preventDefault();

        // Remove the media url from the hidden input
        mediaIdInput.val('');

        // Clear out the preview media
        mediaContainer.html("<div class='" + mediaContainerClass + "'><span>Nothing Selected</span></div>" );

        // Un-hide the add media link
        addMediaLink.removeClass( 'hidden' );

        // Hide the remove media link
        removeMediaLink.addClass( 'hidden' );
    });

});