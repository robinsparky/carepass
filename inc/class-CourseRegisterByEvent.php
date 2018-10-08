<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Register for a course using redirecting to an Event
 * @class  CourseRegisterByEvent
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class CourseRegisterByEvent
{ 

    /**
     * Action hook used by the AJAX class.
     *
     * @var string
     */
    const ACTION   = 'handleEvent';
    const NONCE    ='registercoursebyevent';

    private $hooks = null;
    private $ajax_nonce = null;
    private $errobj = null;
    private $errcode = 0;
    private $roles = null;

    private $log;

	/*************** Instance Methods ****************/
	public function __construct( ) {
        $this->errobj = new WP_Error();
        $this->hooks = array('single-carecourse.php');
        $this->roles = array('um_caremember', 'admin');
        $this->log = new BaseLogger();
    }

    /**
     * Register this AJAX class with all the appropriate WordPress hooks.
     */
    public function register() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );
        
        add_action('wp_enqueue_scripts', array( $this, 'registerScript' ) );
        $this->registerHandlers();

    }

    public function registerScript() {
        $loc = __CLASS__ . '::' . __FUNCTION__;

        $templ = care_get_current_template();
        $templ = @$templ ? $templ : 'No template is set yet.';
        $this->log->error_log(sprintf( "%s-->current template='%s'", $loc, $templ ));

        if( in_array( $templ, $this->hooks ) ) {
            wp_register_script( 'register_by_event'
                            , get_stylesheet_directory_uri() . '/js/care-register-by-event.js'
                            , array('jquery') );
    
            wp_localize_script( 'register_by_event', 'care_event_obj', $this->get_ajax_data() );

            wp_enqueue_script( 'register_by_event' );
        }
    }
    
    public function registerHandlers() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );
    }

    /**
     * Get the AJAX data that WordPress needs to output.
     *
     * @return array
     */
    private function get_ajax_data()
    {
        $user = wp_get_current_user();
        if ( ! ( $user instanceof WP_User ) ) {
            throw new Exception('ET call home!');
        }
        
        return array ( 
             'action' => self::ACTION
            ,'security' => wp_create_nonce(self::NONCE)
        );
    }
    
} //end class

if( class_exists( 'CourseRegisterByEvent' ) ) {
    $registerByEvent = new CourseRegisterByEvent();
    $registerByEvent->register();
}