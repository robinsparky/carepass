<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Register for a course using Email
 * @class  CourseRegisterByEmail
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class CourseRegisterByEmail
{ 

    /**
     * Action hook used by the AJAX class.
     *
     * @var string
     */
    const ACTION   = 'handleEmail';
    const NONCE    ='registercoursebyemail';

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
        $this->roles = array('um_caremember');
        $this->log = new BaseLogger( false );
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
            wp_register_script( 'register_by_email'
                            , get_stylesheet_directory_uri() . '/js/care-register-by-email.js'
                            , array('jquery') );
    
            wp_localize_script( 'register_by_email', 'care_email_obj', $this->get_ajax_data() );

            wp_enqueue_script( 'register_by_email' );
        }
    }
    
    public function registerHandlers() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );

        add_action( 'wp_ajax_' . self::ACTION
                  , array( $this, self::ACTION ));
        add_action( 'wp_ajax_no_priv_' . self::ACTION
                  , array( $this, 'noPrivilegesHandler' ));
    }

    
    public function handleEmail() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );

        // Handle the ajax request
        check_ajax_referer( self::NONCE, 'security' );

        $this->log->error_log("$loc --> _POST");
        $this->log->error_log( print_r( $_POST, true) );

        if( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) {
            $this->errobj->add( $this->errcode++, __( 'Not ajax.', CARE_TEXTDOMAIN ));
            wp_send_json_error( $this->errobj );
        }
        
        if( !is_user_logged_in() ){
            $this->errobj->add( $this->errcode++, __( 'Worker is not logged in!.', CARE_TEXTDOMAIN ));
            wp_send_json_error( $this->errobj );
        }

        $worker_user = wp_get_current_user();
        if ( ! ( $worker_user instanceof WP_User ) || empty( $worker_user ) ) {           
             $this->errobj->add( $this->errcode++, __( 'Logged in user not defined!!!', CARE_TEXTDOMAIN ));
             wp_send_json_error( $this->errobj );
        }
    
        $canDo = false;
        foreach( $worker_user->roles as $role ) {
            if( in_array( $role, $this->roles ) ) {
                $canDo = true;
                break;
            }
        }

        if ( current_user_can( 'manage_options' ) ) $canDo = true;

        if ( !$canDo ) {
            $this->errobj->add( $this->errcode++, __( 'Insufficient privileges to register for a course.', CARE_TEXTDOMAIN ) );
            wp_send_json_error( $this->errobj );
        }
             
        $user_info = get_userdata( $worker_user->ID );
        // $this->log->error_log("$loc --> User Info");
        // $this->log->error_log( $user_info );

        //Get the email address
        $sendto = $user_info->user_email; 
        if ( empty( $sendto ) ) {
            $this->errobj->add( $this->errcode++, __( 'Cannot determine email address.', CARE_TEXTDOMAIN ) );
        }
        
        //Set the email subject
        $subject = __( 'Pending Course Registration', CARE_TEXTDOMAIN );
        
        //Get the course name
        $courseName = '';
        if ( !empty( $_POST[ 'courseName' ] ) ) {
            $courseName = $_POST[ 'courseName' ];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Could not determine course name.', CARE_TEXTDOMAIN ) );
        }
        
        //Get the course session date
        //$sessionStartDate = date( 'l F j, Y \a\t g:i:s a' );
        //$this->log->error_log("$loc --> today is $sessionStartDate");

        //Start Date
        if ( !empty( $_POST[ 'startDate' ] ) ) {
            $sessionStartDate = $_POST[ 'startDate' ];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Could not determine session start date.', CARE_TEXTDOMAIN ) );
        }
        
        //Start Time
        if ( !empty( $_POST[ 'startTime' ] ) ) {
            $sessionStartTime = $_POST[ 'startTime' ];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Could not determine session start time.', CARE_TEXTDOMAIN ) );
        }
        
        //End Date
        if ( !empty( $_POST[ 'endDate' ] ) ) {
            $sessionEndDate = $_POST[ 'endDate' ];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Could not determine session end date.', CARE_TEXTDOMAIN ) );
        }
        
        //End Time
        if ( !empty( $_POST[ 'endTime' ] ) ) {
            $sessionEndTime = $_POST[ 'endTime' ];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Could not determine session end time.', CARE_TEXTDOMAIN ) );
        }
        
        //Get the course session location
        $sessionLocation = null;
        if ( !empty( $_POST[ 'sessionLocation' ] ) ) {
            $sessionLocation = $_POST[ 'sessionLocation' ];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Could not determine session location.', CARE_TEXTDOMAIN ) );
        }

        //Get the course ID
        $courseId = 0;
        if ( !empty( $_POST[ 'courseId' ] )) {
            $courseId = $_POST[ 'courseId' ];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Could not determine course Id.', CARE_TEXTDOMAIN ) );
        }

        if( $courseId < 1 ) {
            $this->errobj->add( $this->errcode++, __( 'Invalid course Id.', CARE_TEXTDOMAIN ) );
        }

        if( count( $this->errobj->errors ) > 0 ) {
            wp_send_json_error( $this->errobj );
            exit;
        }

        //No errors so far..
        $templ = <<<EOT
        <h2>Course Registration Request</h2>
        <ul>For '%s' (%s) 
        <li>Course '%s'
        <li>Location '%s'
        <li>Start Date '%s'
        <li>Start Time: '%s'
        <li>End Date: '%s'
        <li>End Time: '%s'
        </ul>
EOT;
        //$mask = __( "Please register '%s' (%s) <br /> in Course '%s' <br /> at location %s <br /> on date %s", CARE_TEXTDOMAIN );
        $registrant = $user_info->user_nicename;
        if( !empty( $user_info->display_name ) ) {
            $registrant = $user_info->display_name;
        }
        $message = sprintf( $templ, $registrant, $sendto, $courseName
                                  , $sessionLocation
                                  , $sessionStartDate
                                  , $sessionStartTime
                                  , $sessionEndDate
                                  , $sessionEndTime );
        $this->log->error_log("$loc --> email message='$message");

        //Attempt sending the emails
        $res = $this->sendMail( $sendto, $subject, $message );
        $res = true;
        if( is_wp_error( $res ) ) {
            wp_send_json_error( $res );
            exit;
        }
        elseif( !$res ) {
            $this->errobj->add( $this->errcode++, __( "Something went wrong in WP emailer! To=$sendto Subject=$subject", CARE_TEXTDOMAIN ) );
            wp_send_json_error( $this->errobj );
            exit;
        }

        $arrCourse = array( 'id' => $courseId
                          , 'name' => $courseName
                          , 'location' => $sessionLocation
                          , 'startDate' => $sessionStartDate
                          , 'startTime' => $sessionStartTime
                          , 'endDate' => $sessionEndDate
                          , 'endTime' => $sessionEndTime
                          , 'status' => RecordUserCourseProgress::PENDING );
        $mess = $this->addCourseRegistration( $worker_user->ID, $arrCourse );

        $response['result'] = "Your " . RecordUserCourseProgress::PENDING . " course registration email was sent.<br />" . $mess;
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

    private function addCourseRegistration( int $user_id, array $course ): string {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc --> Course Session to be added...");
        $this->log->error_log( $course );

        //delete_user_meta( $user_id, RecordUserCourseProgress::META_KEY );

        //TODO: Need to make sure that the course still exists in carecourse's.
        $prev_value = get_user_meta( $user_id, RecordUserCourseProgress::META_KEY, true );
        $prev_value = @$prev_value ? $prev_value : array();

        $this->log->error_log( $prev_value, "$loc --> Before ..." );
        $current = array();
        foreach( $prev_value as $val ) {
            array_push( $current, $val );
        }
        $this->log->error_log( $current, "$loc --> extracted..." );

        if( count( $prev_value ) < 1 ) {
            array_push( $current, $course );
            $meta_id = add_user_meta( $user_id, RecordUserCourseProgress::META_KEY, $current, false );
            if( is_numeric( $meta_id ) ) {
                $mess = sprintf( "Added pending registration for '%s'. (Meta id=%d)", $course['name'], $meta_id );
            }
            else {
                $mess = sprintf("Could not add pending registration for '%s'", $course['name'] );
            }
        }
        else {
            $found = false;
            foreach( $current as $c ) {
                $this->log->error_log( $c, "$loc --> Comparing..." );
                if( $c['id'] === $course['id'] ) {
                    $found = true;
                    break;
                }
            }
            if( !$found ) {
                array_push( $current, $course );
                $meta_id = update_user_meta( $user_id, RecordUserCourseProgress::META_KEY, $current );
                if( true === $meta_id ) {
                    $mess = sprintf( "Updated pending registration for '%s'.", $course['name'] );
                }
                elseif( is_numeric( $meta_id ) ) {
                    $mess = sprintf( "Added?? pending registration for course '%s'. (Meta id=%d)", $course['name'], $meta_id );
                }
            }
            else {
                $mess = "Course registration already submitted.";
            }
        }
        $test = get_user_meta( $user_id, RecordUserCourseProgress::META_KEY, true );
        $this->log->error_log( $test, "$loc --> After ..." );
        
        $this->log->error_log( $mess );
        return $mess;
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
             'ajaxurl' => admin_url( 'admin-ajax.php' )
            ,'action' => self::ACTION
            ,'security' => wp_create_nonce(self::NONCE)
        );
    }
    
    private function sendMail( $sendto, $subject, $message ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );

        // To send HTML mail, the Content-type header must be set
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $num = 1;

        // Additional headers
        $headers .= 'To: Robin Smith <robin.sparky@gmail.com>' . "\r\n";
        $headers .= 'From: info <info@care4nurses.org>' . "\r\n";

        $sent_message = false;
        try {
            // send message using wp_mail function.
            //throw new Exception("On Purpose");
            $sent_message = wp_mail( $sendto, $subject, $message, $headers );
            //$sent_message = true;
        }
        catch(Exception $ex) {
            $data = "Headers=$headers; Count=$num; To=$sendto; Subject=$subject; Message=$message";
            $err = new WP_Error('send',$data,$ex->getMessage());
            return $err;
        }

        return $sent_message;
    }

    private function handleErrors( string $mess ) {
        $response = array();
        $response["message"] = $mess;
        $response["exception"] = $this->errobj;
        wp_send_json_error( $response );
        //TODO: check "exit" vs "wp_die"
        exit;
    }
} //end class

if( class_exists( 'CourseRegisterByEmail' ) ) {
    $registerByEmail = new CourseRegisterByEmail();
    $registerByEmail->register();
}