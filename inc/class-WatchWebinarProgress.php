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
    const META_KEY = "webinar_status";

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
        //add_action( 'template-redirect', array( $handler, 'registerScript') );
        add_shortcode( 'user_webinar_status', array( $handler, 'webinarProgressShortcode' ) );
        add_action('wp_enqueue_scripts', array( $handler, 'registerScript' ) );
        $handler->registerHandlers();
    }

	/*************** Instance Methods ****************/
	public function __construct( ) {
	    $this->errobj = new WP_Error();
        $this->roles = array('um_caremember', 'um_member');
        $this->log = new BaseLogger( false );
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
    
            wp_localize_script( 'watch_webinar', 'care_webinar_obj', $this->get_ajax_data() );

            wp_enqueue_script( 'watch_webinar' );
        }
    }
    
    public function registerHandlers() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");

        add_action( 'admin_enqueue_scripts', array( $this, 'care_admin_scripts' ));
        add_action( 'wp_ajax_' . self::ACTION
                  , array( $this, 'watchWebinarProgess' ));
        add_action( 'wp_ajax_no_priv_' . self::ACTION
                  , array( $this, 'noPrivilegesHandler' ));
    }
    
    public function care_admin_scripts( $hook ) {
        if( 'myplugin_settings.php' != $hook ) return;
        // wp_enqueue_script( 'ajax-script',
        //     plugins_url( '/js/myjquery.js', __FILE__ ),
        //     array( 'jquery' )
        // );
    }
    
    public function watchWebinarProgess() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");

        // Handle the ajax request
        check_ajax_referer( self::NONCE, 'security' );

        if( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) {
            $this->handleErrors('Not Ajax');
        }
        
        if( !is_user_logged_in() ){
            $this->handleErrors( __( 'Worker is not logged in!.', CARE_TEXTDOMAIN ));
        }

        $currentuser = wp_get_current_user();
        if ( ! ( $currentuser instanceof WP_User ) || empty( $currentuser ) ) {           
             $this->errobj->add( $this->errcode++, __( 'Logged in user not defined!!!', CARE_TEXTDOMAIN ));
        }

        $ok = false;
        foreach( $this->roles as $role ) {
            if( in_array( $role, $currentuser->roles ) ) {
                $ok = true;
                break;
            }
        }
        
        if( current_user_can( 'manage_options' ) ) $ok = true;
        
        if ( !$ok ) {         
            $this->errobj->add( $this->errcode++, __( 'Only Care or site members can record watching a webinar.', CARE_TEXTDOMAIN ));
        }
        
        //Get the registered courses
        if ( !empty( $_POST['webinar'] )) {
            $webinar = $_POST['webinar'];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'No webinar info received.', CARE_TEXTDOMAIN ));
        }

        $this->log->error_log( $webinar, "Watched Webinar" );

        $courses = [];
        $user_id = $currentuser->ID;
        $prev_courses = get_user_meta($user_id, self::META_KEY, false );
        
        if( count( $prev_courses ) < 1 ) { 
            array_push($courses, $webinar );
            $meta_id = add_user_meta( $user_id, self::META_KEY, $courses, true );
            $mess = sprintf("Recorded  webinar '%s' as Completed", $webinar["name"] );
        }
        else {
            //Check to see in webinar is already stored
            //If present then set its status to completed
            $found = false;
            foreach($prev_courses as $arrprev) {
                foreach( $arrprev as $prev ) {  
                    if( $webinar['id'] === $prev['id'] ) {
                        $found = true;
                        $pc['status'] = 'Completed';
                    }
                    array_push($courses, $prev);
                }
            }

            //If not present then add it with status set to completed.
            if( ! $found ) {
                array_push( $courses, $webinar );
            }
            
            $meta_id = update_user_meta( $user_id, self::META_KEY, $courses );
            $mess = sprintf("Updated records with webinar '%s'.", $webinar['name'] );
            if( false === $meta_id ) {
                $mess = "Webinar record was not added/updated.";
            }
        }

        $response = array();
        $response["message"] = $mess;
        $response["returnData"] = $courses;
        wp_send_json_success( $response );
    
        wp_die(); // All ajax handlers die when finished
    }

    public function noPrivilegesHandler() {
        // Handle the ajax request
        check_ajax_referer(  self::NONCE, 'security'  );
        $response["message"] = "You have been reported to the authorities!";
        wp_send_json_error( $response );
        
        wp_die(); // All ajax handlers die when finished
    }
     
	public function webinarProgressShortcode( $atts, $content = null )
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;

        $this->log->error_log( $loc );
        

        $currentuser = wp_get_current_user();
        if ( ! ( $currentuser instanceof WP_User ) ) {
            return '<h1>ET call home!</h1>';
        }

        $ok = false;

        foreach( $this->roles as $role ) {
            if( in_array( $role, $currentuser->roles ) ) {
                $ok = true;
                break;
            }
        }

        if( current_user_can( 'manage_options' ) ) $ok = true;
 
        if(! $ok ) return '';

		$myshorts = shortcode_atts( array("user_id" => 0), $atts, 'user_status' );
        extract( $myshorts );
        
        if( !is_user_logged_in() ){
            return "User is not logged in!";
        }

        $user_id = (int) $currentuser->ID;
        $this->log->error_log( sprintf("%s: User id=%d and email=%s",$loc, $user_id, $currentuser->user_email ));
    
        $overall_title  = __('Webinar Progress Report', CARE_TEXTDOMAIN );

        $out  = "<div id=\"progress-container\">";
        $out .= "<h2>$overall_title</h2>";
        
        $caption = "Please contact your case manager if you have questions.";

        //Currently registered course statuses
        $out .= "<hr>";
        $out .= '<table class="coursestatus">';
        $out .= '<caption style="caption-side:bottom; align:right;">' . $caption . '</caption>';
        $out .= '<thead><th>Webinar</th><th>Location</th><th>Start Date</th><th>End Date</th><th>Status</th></thead>';
        $out .= '<tbody>';

        //Note: recorded_courses is a 2-d array
        $recorded_courses = get_user_meta( $user_id, self::META_KEY, false );

        $templ = <<<EOT
            <tr id="%d">
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            </tr>
EOT;
        $ctr = 0;
        foreach( $recorded_courses as $arrcourse ) {
            $this->log->error_log("$loc --> webinar statuses from user meta...");
            foreach( $arrcourse as $course) {
                $this->log->error_log( $course );
                $row = sprintf( $templ
                              , $course["id"]
                              , $course["name"]
                              , $course["location"]
                              , $course["startDate"]
                              , $course["endDate"]
                              , $course["status"] );
                $out .= $row;
            }
        }
        $out .= '</tbody></table>';   

        $out .= "<div id='care-resultmessage'></div>";
        $out .= "</div>";

        return $out;
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
        $existing_courses = get_user_meta($user_id, self::META_KEY, false );
        
        return array ( 
             'ajaxurl' => admin_url( 'admin-ajax.php' )
            ,'action' => self::ACTION
            ,'security' => wp_create_nonce(self::NONCE)
            ,'user_id' => $user_id
            ,'existing_courses' => $existing_courses
        );
    }

    private function handleErrors( string $mess ) {
        $response = array();
        $response["message"] = $mess;
        $response["exception"] = $this->errobj;
        wp_send_json_error( $response );
        wp_die();
    }
    
}