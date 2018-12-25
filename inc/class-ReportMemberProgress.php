<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Reporting Member Progress and other PASS member data is implemented using shortcodes
 * @class  ReportMemberProgress
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class ReportMemberProgress
{ 
    private $ajax_nonce = null;
    private $errobj = null;
    private $errcode = 0;

    private $roles;
    private $log;
    
    /**
     * Register the class with all the appropriate WordPress hooks.
     */
    public static function register()
    {
        $handler = new self();
 
        add_shortcode( 'webinar_progress', array( $handler, 'webinarProgressShortcode' ) );
        add_shortcode( 'course_progress', array( $handler, 'courseProgressShortcode' ) );
        add_shortcode( 'passmember_data', array( $handler, 'memberDataShortcode' ) );
        add_action('wp_enqueue_scripts', array( $handler, 'registerScript' ) );
        $handler->registerHandlers();
    }

	/*************** Instance Methods ****************/
	public function __construct( ) {
	    $this->errobj = new WP_Error();		
        $rolesWatch = esc_attr( get_option('care_roles_that_watch') );
        $this->roles = explode( ",", $rolesWatch );
        $this->log = new BaseLogger( true );
    }

    public function registerScript() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );
    }
    
    public function registerHandlers( ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log($loc);
    }
    
	public function courseProgressShortcode( $atts, $content = null )
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

        if(! $ok ) return '';

		// $myshorts = shortcode_atts( array("user_id" => 0), $atts, 'course_progress' );
        // extract( $myshorts );
        
        if( !is_user_logged_in() ){
            return "Member is not logged in!";
        }

        $user_id = (int) $currentuser->ID;
        $this->log->error_log( sprintf("%s: User id=%d and email=%s",$loc, $user_id, $currentuser->user_email ));
    
        $overall_title  = __('Course Progress Report', CARE_TEXTDOMAIN );
        $out  = "<div id=\"progress-container\">";
        $out .= "<h3>$overall_title</h3>";

        //Currently registered course statuses
        $out .= "<hr>";
        $out .= '<table class="coursestatus">';
        $out .= '<thead><th>Course</th><th>Start Date</th><th>Status</th></thead>';
        $out .= '<tbody>';
        //$out .= sprintf("<tr id=\"add\"><td id=\"selection\" colspan=\"2\">%s</td><td>%s %s</td></tr>", $selection, $complete, $remove );

        //Note: 2-d array
        $recorded_courses = get_user_meta( $user_id, RecordUserCourseProgress::META_KEY, false );
        if( isset($recorded_courses) && count( $recorded_courses ) > 0 ) {
            $arrcourse = $recorded_courses[0];
        }
        else {
            $arrcourse = array();
        }

        $templ = <<<EOT
            <tr id="%d">
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            </tr>
EOT;
        
        $course_defns = Course::getCourseDefinitions();
        foreach( $course_defns as $defn ) {
            $gotIt = false;
            foreach( $arrcourse as $course) {
                if( $defn['id'] === $course['id']) {
                    $this->log->error_log( $course, 'Course from meta' );
                    $row = sprintf( $templ
                                , $course["id"]
                                , $course["name"]
                                , $course["startDate"]
                                , $course["status"] );
                    $gotIt = true;
                    break;
                }
            }
            if( !$gotIt ) {
                $row = sprintf( $templ
                              , $defn["id"]
                              , $defn["name"]
                              , ''
                              , '' );
            }
            $out .= $row;
        }
        $out .= '</tbody></table>';
        $out .= '</div>';
        return $out;
    }
    
	public function webinarProgressShortcode( $atts, $content = null )
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;

        $this->log->error_log( $loc );
        
        if( !is_user_logged_in() ){
            return '';
        }
        
        if( !um_is_myprofile() ) return '';

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

        if(! $ok ) return '';

		$myshorts = shortcode_atts( array("user_id" => 0), $atts, 'webinar_progress' );
        extract( $myshorts );

        $user_id = (int) $currentuser->ID;
        $this->log->error_log( sprintf("%s: User id=%d and email=%s",$loc, $user_id, $currentuser->user_email ));
    
        $overall_title  = __('Webinar Progress Report', CARE_TEXTDOMAIN );
        
        $caption = __("Please contact your case manager if you have questions.", CARE_TEXTDOMAIN) ;
        $out  = "<div id=\"progress-container\">";
        $out .= "<h3>$overall_title</h3>";

        //Current webinar statuses
        $out .= "<hr>";
        $out .= '<table class="coursestatus">';
        $out .= '<caption style="caption-side:bottom; align:right;">' . $caption . '</caption>';
        $out .= '<thead><th>Webinar</th><th>Start Date</th><th>Status</th></thead>';
        $out .= '<tbody>';
        //$out .= sprintf("<tr id=\"add\"><td id=\"selection\" colspan=\"2\">%s</td><td>%s %s</td></tr>", $selection, $complete, $remove );

        //Note: 2-d array
        $recorded_webinars = get_user_meta( $user_id, RecordUserWebinarProgress::META_KEY, false );
        if( isset($recorded_webinars) && count( $recorded_webinars ) > 0 ) {
            $arrwebinar = $recorded_webinars[0];
        }
        else {
            $arrwebinar = array();
        }
        $templ = <<<EOT
            <tr id="%d">
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            </tr>
EOT;
        
        $webinar_defns = Webinar::getWebinarDefinitions();
        foreach( $webinar_defns as $defn ) {
            $gotIt = false;
            foreach( $arrwebinar as $webinar) {
                if( $defn['id'] === $webinar['id']) {
                    $this->log->error_log( $webinar, 'Webinar from meta' );
                    $row = sprintf( $templ
                                , $webinar["id"]
                                , $webinar["name"]
                                , $webinar["startDate"]
                                , $webinar["status"] );
                    $gotIt = true;
                    break;
                }
            }
            if( !$gotIt) {
                $row = sprintf( $templ
                                , $defn["id"]
                                , $defn["name"]
                                , ''
                                , '' );
            }

            $out .= $row;
        }
        $out .= '</tbody></table>';
        $out .= '</div>';
        return $out;
    }

    public function memberDataShortcode( $atts, $content = null ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;

        $this->log->error_log( $loc );

        if( !is_user_logged_in() ) {
            return '';
        }
        
        if( !um_is_myprofile() ) return '';
        
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

        if(! $ok ) return '';

		$myshorts = shortcode_atts( array("user_id" => 0), $atts, 'webinar_progress' );
        extract( $myshorts );
        

        $user_id = (int) $currentuser->ID;
        $this->log->error_log( sprintf("%s: User id=%d and email=%s",$loc, $user_id, $currentuser->user_email ));
        
        $joinedMentorship = 'no';
        $strDateJoined = get_user_meta( $user_id, RecordUserMemberData::META_KEY, true );
        $this->log->error_log("Date joined meta string='$strDateJoined'" );
        
        $dateJoined = DateTime::createFromFormat("Y-m-d", $strDateJoined );
        if( $dateJoined === false ) {
            $this->log->error_log( DateTime::getLastErrors(), "Error getting date joined mentorship" );
            $joinedMentorship = 'no';
        }
        else {
            $joinedMentorship = 'yes';
        }
        
        $this->log->error_log( $joinedMentorship, "$loc --> joined Mentorship" );
        $label = __( "Did you join the mentorship program?", CARE_TEXTDOMAIN );
        $checked = $joinedMentorship === "yes" ? "checked" : "";
        $templ = <<<EOT
            <div>
            <label for="mentorship">%s</label>
            <input id="pass_mentorship" name="pass_mentorship" disabled type="checkbox" value="%s" %s/>
            </div>
EOT;

        $out = "<fieldset>";
        $out .= sprintf($templ, $label, $joinedMentorship, $checked);
        $out .= "</fieldset>";
        return $out;

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