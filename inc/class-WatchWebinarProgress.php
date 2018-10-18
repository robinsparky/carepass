<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Data and functions Recording Progress during Webinar using Ajax
 * @class  WatchWebinarProgress
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class WatchWebinarProgress
{ 
    /**
     * Action hook used by the AJAX class.
     *
     * @var string
     */
    const ACTION   = 'watchWebinarProgess';
    const NONCE    = 'watchWebinarProgess';

    private $ajax_nonce = null;
    private $errobj = null;
    private $errcode = 0;
    
    private $roles;

    private $log;
    
    /**
     * Register the AJAX handler class with all the appropriate WordPress hooks.
     */
    public static function register()
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        
        $handler = new self();
        add_action('wp_enqueue_scripts', array( $handler, 'registerScript' ) );
        $handler->registerHandlers();
    }

	/*************** Instance Methods ****************/
	public function __construct( ) {
	    $this->errobj = new WP_Error();
        $this->roles = array( 'um_member' );
        $this->log = new BaseLogger( true );
    }

    public function registerScript() {
        $loc = __CLASS__ . '::' . __FUNCTION__;

        $this->log->error_log($loc);

        $templ = care_get_current_template();
        $templ = isset( $templ ) ? $templ : 'No template is set yet.';
        $this->log->error_log(sprintf( "%s-->current template=%s", $loc, $templ ));

        if( 'single-carewebinar.php' === $templ ) {
            wp_register_script( 'watch_webinar'
                            , get_stylesheet_directory_uri() . '/js/care-watch-webinar.js'
                            , array('jquery') );
    
            wp_localize_script( 'watch_webinar', 'care_watch_webinar_obj', $this->get_ajax_data() );

            wp_enqueue_script( 'watch_webinar' );
        }
    }
    
    public function registerHandlers() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");

        add_action( 'wp_ajax_' . self::ACTION
                  , array( $this, 'watchWebinarProgess' ));
        add_action( 'wp_ajax_nopriv_' . self::ACTION
                  , array( $this, 'noPrivilegesHandler' ));
    }
    
    public function watchWebinarProgess() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");

        // Handle the ajax request
        check_ajax_referer( self::NONCE, 'security' );

        if( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) {
            $this->handleErrors('Not Ajax');
        }
        
        if( !is_user_logged_in() ) {           
            $this->errobj->add( $this->errcode++, __( 'Worker is not logged in!.', CARE_TEXTDOMAIN ));
        }

        $currentuser = wp_get_current_user();
        $ok = false;
        foreach( $this->roles as $role ) {
            if( in_array( $role, $currentuser->roles ) ) {
                $ok = true;
                break;
            }
        }
        
        if( current_user_can( 'manage_options' ) ) $ok = true;
        
        if ( !$ok ) {         
            $this->errobj->add( $this->errcode++, __( 'Only PASS members can record watching a webinar.', CARE_TEXTDOMAIN ));
        }
        
        //Get the registered webinars
        $webinar = null;
        if ( !empty( $_POST['webinar'] )) {
            $webinar = $_POST['webinar'];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'No webinar info received.', CARE_TEXTDOMAIN ));
        }
        
        if(count($this->errobj->errors) > 0) {
            $this->handleErrors("Errors were encountered");
        }

        $webinar['status'] = RecordUserWebinarProgress::PENDING;
        if( 0.85 <= $webinar['watchedPct'] ) {
            $webinar['status'] = RecordUserWebinarProgress::COMPLETED;
        }
        $this->log->error_log( $webinar, "Watched Webinar" );

        $webinars = [];
        $user_id = $currentuser->ID;
        $prev_webinars = get_user_meta($user_id, RecordUserWebinarProgress::META_KEY, false );
        
        if( count( $prev_webinars ) < 1 ) { 
            array_push( $webinars, $webinar );
            $meta_id = add_user_meta( $user_id, RecordUserWebinarProgress::META_KEY, $webinars, true );
            $mess = sprintf("Recorded  webinar '%s'", $webinar["name"] );
        }
        else {
            //Check to see in webinar is already stored
            //If present then set its status to completed
            $found = false;
            $arrprev = $prev_webinars[0];
            foreach( $arrprev as $prev ) {  
                if( $webinar['id'] === $prev['id'] ) {
                    $found = true;
                    $prev['startDate'] = $webinar['startDate'];
                    $prev['endDate'] = $webinar['endDate'];
                    if( $prev['status'] != RecordUserWebinarProgress::COMPLETED ) {
                        $prev['watchedPct'] = $webinar['watchedPct'];
                        $prev['status'] = $webinar['status'];
                    }
                }
                array_push($webinars, $prev);
            }

            //If not present then add it with status set to completed.
            if( ! $found ) {
                array_push( $webinars, $webinar );
            }
            
            $meta_id = update_user_meta( $user_id, RecordUserWebinarProgress::META_KEY, $webinars );
            $mess = sprintf("Updated records with watched webinar '%s'.", $webinar['name'] );
            if( false === $meta_id ) {
                $mess = "Webinar watch record was not added/updated.";
            }
        }

        $response = array();
        $response["message"] = $mess;
        $response["returnData"] = $webinars;
        wp_send_json_success( $response );
    
        wp_die(); // All ajax handlers die when finished
    }

    public function noPrivilegesHandler() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");
        // Handle the ajax request
        check_ajax_referer(  self::NONCE, 'security'  );
        $this->errobj->add( $this->errcode++, __( 'You have been reported to the authorities!', CARE_TEXTDOMAIN ));
        $this->handleErrors("You've been a bad boy.");
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
        $user_id = $user->ID;
        $existing_courses = get_user_meta($user_id, RecordUserWebinarProgress::META_KEY, false );
        
        return array ( 
             'ajaxurl' => admin_url( 'admin-ajax.php' )
            ,'action' => self::ACTION
            ,'security' => wp_create_nonce(self::NONCE)
            ,'user_id' => $user_id
            ,'existing_courses' => $existing_courses
        );
    }

    private function handleErrors( string $mess ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");
        $response = array();
        $response["message"] = $mess;
        $response["exception"] = $this->errobj;
        wp_send_json_error( $response );
        wp_die();
    }
    
}