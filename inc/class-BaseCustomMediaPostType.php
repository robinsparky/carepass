<?php
/** 
 * Base class for Custom Post Types
 * that have at least one custom field which is a media type
 * @class  BaseCustomMediaPostType
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
abstract class BaseCustomMediaPostType {

    protected $mediaType      = 'video';
    protected $mediaMetaBoxId = 'mediaMetaBoxId';
    protected $mediaInput     = 'care-media-id';
    protected $mediaContainer = 'custom-media-container';

    /**
     * Render the html5 for a WP Media object
     * Requires appropriate JS code for support
     * @see care-course-media-uploader.js
     * @see care-webinar-media-uploader.js
     */
	protected function mediaSelectorEmitter( $url = '' )
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log( $loc );

        $hideAdd = '';
        $hideRemove = '';
        if(strlen( $url ) > 0 ) {
            $hideAdd = 'hidden'; 
            $hideRemove = '';
        }
        else {
            $hideAdd = ''; 
            $hideRemove = 'hidden';
        }

        $out = '';
        $out .= "<input id='care-media-add' type='button' class='button secondary-button $hideAdd' value='Add'>";
        $out .= "<input id='care-media-remove' type='button' class='button secondary-button $hideRemove' value='Remove'>";
        $out .= "<input id='$this->mediaInput' type='hidden' value='$url' name='care-media-selection'>";
        return $out;
    }
    
    /**
     * Get the js data to output.
     *
     * @return array
     */
    protected function get_data()
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log( $loc );
	
        return array( 'mediaType' => $this->mediaType
                    , 'metaBoxId' => $this->mediaMetaBoxId
                    , 'mediaInput' => $this->mediaInput
					, 'mediaContainer' => $this->mediaContainer );
    }
}