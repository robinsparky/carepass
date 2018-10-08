<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Data and functions for Reporting Course Progress using Ajax
 * @class  ReportCourseProgress
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class ReportCourseProgress
{ 

    /**
     * Action hook used by the AJAX class.
     *
     * @var string
     */
    const ACTION   = 'reportCourseProgess';
    const NONCE    ='reportCourseProgess';
    const META_KEY = "course_status";

    //Only emit on this page
    const PAGE_SLUG = 'user';

    private $ajax_nonce = null;
    private $errobj = null;
    private $errcode = 0;

    private $roles;
    
    /**
     * Register the AJAX handler class with all the appropriate WordPress hooks.
     */
    public static function register()
    {
        $handler = new self();
 
        add_shortcode( 'user_status', array( $handler, 'courseProgressShortcode' ) );
        add_action('wp_enqueue_scripts', array( $handler, 'registerScript' ) );
        $handler->registerHandlers();
    }

	/*************** Instance Methods ****************/
	public function __construct( ) {
	    $this->errobj = new WP_Error();
        $this->roles = array('um_caremember');
    }

    public function registerScript() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log( "$loc ==========================" );

        //Make sure we are rendering the "user" page
        if( is_page( ReportCourseProgress::PAGE_SLUG ) ) {
            wp_register_script( 'care-courses'
                            , get_stylesheet_directory_uri() . '/js/care-register-course-progress.js'
                            , array('jquery') );
    
            wp_localize_script( 'care-courses', 'care_registration_obj', $this->get_ajax_data() );

            wp_enqueue_script( 'care-courses' );
        }
    }
    
    public function registerHandlers( ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log($loc);
        add_action( 'wp_ajax_' . self::ACTION
                  , array( $this, 'registerCourseProgess' ));
        add_action( 'wp_ajax_no_priv_' . self::ACTION
                  , array( $this, 'noPrivilegesHandler' ));
    }
    
    public function registerCourseProgess() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log($loc);

        // Handle the ajax request
        check_ajax_referer( self::NONCE, 'security' );

        if( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) {
            $this->handleErrors('Not Ajax');
        }
        
        if( !is_user_logged_in() ){
            $this->handleErrors( __( 'Worker is not logged in!.', CARE_TEXTDOMAIN ));
        }

        $worker_user = wp_get_current_user();
        if ( ! ( $worker_user instanceof WP_User ) || empty( $worker_user ) ) {           
             $this->errobj->add( $this->errcode++, __( 'Logged in user not defined!!!', CARE_TEXTDOMAIN ));
        }
        
        //Get the registered courses
        if ( !empty( $_POST['courses'] )) {
            $courses = $_POST['courses'];
        }
        else {
            $courses = array();
        }

        //Get the user id
        if ( !empty( $_POST['user_id'] )) {
            $user_id = $_POST['user_id'];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Target user is not defined', CARE_TEXTDOMAIN ));
        }

        $targetuser = get_user_by( 'id', $user_id );
        if( false === $targetuser ) {         
            $this->errobj->add( $this->errcode++, __( 'Could not find target user', CARE_TEXTDOMAIN ));
        }
        foreach( $this->roles as $role ) {
            if( in_array( $role, $targetuser->roles ) ) {
                $ok = true;
                break;
            }
        }
        
        if( count( $this->errobj->errors ) > 0 ) {
            $this->handleErrors( "Server side errors" );
            exit;
        }

        //TODO: Need to make sure that the course still exists in carecourse's.
        $len = count( $courses );
        if( 0 === $len ) {
            if( delete_user_meta( $user_id, RegisterCourseProgress::META_KEY) ) {
                $mess = "Removed all course registrations.";
            }
            else {
                $mess = "No course registrations removed.";
            }
        }
        else {
            //array is returned because $single is false
            $prev_courses = get_user_meta($user_id, RegisterCourseProgress::META_KEY, false );
            if( count( $prev_courses ) < 1 ) {
                $meta_id = add_user_meta( $user_id, RegisterCourseProgress::META_KEY, $courses, true );
                $mess = sprintf("Added %d course %s. (Meta id=%d)", $len, $len === 1 ? 'registration' : 'registrations', $meta_id );
            }
            else {
                $meta_id = update_user_meta( $user_id, RegisterCourseProgress::META_KEY, $courses );
                if( true === $meta_id ) {
                    $mess = sprintf("Updated %d course %s.", $len, $len === 1 ? 'registration' : 'registrations' );
                }
                elseif( is_numeric( $meta_id ) ) {
                    $mess = sprintf("Added %d course %s. (Meta id=%d)", $len, $len === 1 ? 'registration' : 'registrations', $meta_id );
                }

            }
            if( false === $meta_id ) {
                $mess = "Course registrations and status were not added/updated.";
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
    
	public function courseProgressShortcode( $atts, $content = null )
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;

        error_log( "$loc ========================================" );
        
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
        error_log( sprintf("%s: User id=%d and email=%s",$loc, $user_id, $currentuser->user_email ));
    
        //Setup the course selection options
        // $select_title = __('---Select A Course or Webinar To Register---', CARE_TEXTDOMAIN );
        // $tmpl = '<option value="%s">%s</option>';
        // $selection = '<select id ="course-select">';
        // $selection .= '<option value="0" selected="selected">' . $select_title . '</option>';
        // $args = array( 'post_type' => 'carecourse', 'posts_per_page' => -1 ); 
        // $loop = new WP_Query( $args );
        // if ($loop->have_posts()) {
        //     while ( $loop->have_posts() ) {
        //         $loop->the_post();
        //         $selection .= sprintf( $tmpl, get_the_ID(), get_the_title() );
        //     }
        // }
        // else {
        //     $selection .= sprintf( $tmpl, 0, "No Courses found" );
        // }    
        // $selection .= "</select>";
        // // Reset Post Data 
        // wp_reset_postdata();
        // wp_reset_query();

        // $register_title = __('Register', CARE_TEXTDOMAIN );
        // $toggle_title   = __('Toggle Status', CARE_TEXTDOMAIN );
        // $remove_title   = __('Remove Registration', CARE_TEXTDOMAIN );
        // $save_title     = __('Save', CARE_TEXTDOMAIN );
        $overall_title  = __('Course and Webinar Progress Report', CARE_TEXTDOMAIN );

        $out  = "<div id=\"progress-container\">";
        $out .= "<h2>$overall_title</h2>";
        
        //Now provide means to record progress or register re other courses
        // $register = "<button id='register-for-course' name='register-for-course' type='button'>$register_title</button>";
        // $complete = "<button id='record-course-completed' name='record-course-completed' type='button'>$toggle_title</button>";
        // $remove   = "<button id='remove-course' name='remove-course' type='button'>$remove_title</button>";
        // $done     = "<button id='done-course-work' name='done-course-work' type='button'>$save_title</button>";
        $caption = "Please contact your case manager if you have questions.";

        //Currently registered course statuses
        $out .= "<hr>";
        $out .= '<table class="coursestatus">';
        $out .= '<caption style="caption-side:bottom; align:right;">' . $caption . '</caption>';
        $out .= '<thead><th>Course</th><th>Location</th><th>Start Date</th><th>End Date</th><th>Status</th></thead>';
        $out .= '<tbody>';
        //$out .= sprintf("<tr id=\"add\"><td id=\"selection\" colspan=\"2\">%s</td><td>%s %s</td></tr>", $selection, $complete, $remove );

        //Note: recorded_courses is a 2-d array
        $recorded_courses = get_user_meta( $user_id, ReportCourseProgress::META_KEY, false );

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
            error_log("$loc --> course statuses from user meta...");
            foreach( $arrcourse as $course) {
                error_log( print_r( $course, true ));
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
        
        return array( 
             'ajaxurl' => admin_url( 'admin-ajax.php' )
            ,'action' => self::ACTION
            ,'security' => wp_create_nonce(self::NONCE)
            ,'user_id' => $user_id
        );
    }

    private function handleErrors( string $mess ) {
        $response = array();
        $response["message"] = $mess;
        $response["exception"] = $this->errobj;
        wp_send_json_error( $response );
        //TODO: check "exit" vs "wp_die"
        exit;
    }
    
}