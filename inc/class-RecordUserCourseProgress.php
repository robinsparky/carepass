<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Data and functions for Recording Course Progress
 * @class  RecordUserCourseProgress
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class RecordUserCourseProgress
{ 

    //Action hook used by the AJAX class.
    const ACTION     = 'recordCourseProgess';

    const NONCE      = 'recordCourseProgess';
    const META_KEY   = "course_status";

    //Course registration workflow
    const COMPLETED  = "Completed";
    const PENDING    = "In Progress";
    const REGISTERED = "Registered";

    const TABLE_CLASS = 'course-status';

    const SEP = '|';

    private $ajax_nonce = null;
    private $errobj = null;
    private $errcode = 0;

    private $hooks;
    private $statuses;
    private $roles;
    private $log;
    /**
     * Register class with all the appropriate WordPress hooks.
     */
    public static function register() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log("$loc");
        $handler = new self();
        add_action('admin_enqueue_scripts', array( $handler, 'registerAdminScript' ) );
        
        $handler->registerHandlers();
    }

	/*************** Instance Methods ****************/
	public function __construct( ) {
        $this->errobj = new WP_Error();
        $this->hooks = array( 'profile.php', 'user-edit.php' );		
        $rolesWatch = esc_attr( get_option('care_roles_that_watch') );
        $this->roles = explode( ",", $rolesWatch );
        $this->statuses = array( self::PENDING, self::COMPLETED );

        $this->log = new BaseLogger( true );
    }

    public function registerScript( $hook ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( "$loc" ); 

    }
    
    public function registerAdminScript( $hook ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( "$loc" ); 

        //Make sure we are rendering the "user-edit" page
        if( in_array( $hook, $this->hooks ) ) {
            wp_register_script( 'care-userprofile-courseprogress'
                            , get_stylesheet_directory_uri() . '/js/care-record-user-course-status.js'
                            , array('jquery') );
    
            wp_localize_script( 'care-userprofile-courseprogress', 'care_userprofile_course', $this->get_data() );

            wp_enqueue_script( 'care-userprofile-courseprogress' );
        }
    }

    public function registerHandlers() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");

        //Show the data
        add_action( 'show_user_profile', array( $this, 'courseUserProfileFields' ), 10, 1  );
        add_action( 'edit_user_profile', array( $this, 'courseUserProfileFields' ), 10, 1  );
        add_action( 'user_new_form', array( $this, 'courseUserProfileFields' ) ); // creating a new user
        
        //Save the entered data
        add_action( 'personal_options_update', array( $this, 'progressProfileSave' ) );
        add_action( 'edit_user_profile_update', array( $this, 'progressProfileSave' ) );
        add_action( 'user_register', array( $this, 'progressProfileSave' ) );
    }

    public function courseUserProfileFields( $profileuser ) {	
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );


        if( !current_user_can( 'edit_user' ) ) return;

        $ok = false;
        foreach( $this->roles as $role ) {
            if( in_array( $role, $profileuser->roles  ) ) {
                $ok = true;
                break;
            }
        }
        if( !$ok ) return;
    ?>
    <h3>Course Progress Reports</h3>
		<table class="form-table">
			<tr>
				<td>
                    <?php 
                        echo $this->progressEmitter( $profileuser );
                    ?>
				</td>
		</table>
    <?php
    }

	public function progressEmitter( $user )
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );

		$user_id = $user->ID;
        $this->log->error_log( sprintf("%s: User id=%d and email=%s",$loc, $user_id, $user->user_email ));
    
        //Setup the course selection options
        $select_title = __('---Select A Course To Report---', CARE_TEXTDOMAIN );
        $tmpl = '<option value="%s">%s</option>';
        $selection = '<select id="course-select">';
        $selection .= '<option value="0" selected="selected">' . $select_title . '</option>';
        $args = array( 'post_type' => Course::CUSTOM_POST_TYPE, 'posts_per_page' => -1 ); 
        $loop = new WP_Query( $args );
        if ($loop->have_posts()) {
            while ( $loop->have_posts() ) {
                $loop->the_post();
                $selection .= sprintf( $tmpl, get_the_ID(), get_the_title() );
            }
        }
        else {
            $selection .= sprintf( $tmpl, 0, "No Courses found" );
        }    
        // Reset Post Data 
        wp_reset_postdata();
        wp_reset_query();
        
        $selection .= "</select>";

        $register_title = __('Register', CARE_TEXTDOMAIN );
        $toggle_title   = __('Toggle Status', CARE_TEXTDOMAIN );
        $remove_title   = __('Remove', CARE_TEXTDOMAIN );
        $save_title     = __('Save', CARE_TEXTDOMAIN );
        //$overall_title  = __('Course and Webinar Progress Report', CARE_TEXTDOMAIN );

        $hidden = "";
        $out  = $selection;
        
        //Now provide means to record progress or register re other courses
        $register = "<button id='register-for-course' name='register-for-course' type='button'>$register_title</button>";
        //$toggle = "<button id='toggle-course-status' name='record-course-completed' type='button'>$toggle_title</button>";
        $remove   = "<button id='remove-course' name='remove-course' type='button'>$remove_title</button>";
        //$done     = "<button id='done-course-work' name='done-course-work' type='button'>$save_title</button>";
		//$caption = __('Please click "Update" when done.', CARE_TEXTDOMAIN );
        //Currently registered course statuses
        $classes = self::TABLE_CLASS;
        $out .= '<table class="' . $classes . '">';
        //$out .= '<caption style="caption-side:bottom; align:right;">' . $caption . '</caption>';
        $out .= '<thead><th>Course</th><th>Start Date</th><th>Status</th><th>Operation</th></thead>';
        $out .= '<tbody>';
        //$out .= sprintf("<tr id=\"add\"><td id=\"selection\" colspan=\"2\">%s</td><td>%s %s</td></tr>", $selection, $complete, $remove );

        //Note: recorded_courses is a 2-d array
        //delete_user_meta( $user_id, RecordUserCourseProgress::META_KEY);
        $recorded_courses = get_user_meta( $user_id, self::META_KEY, false );
        $this->log->error_log( $recorded_courses,"$loc Entire course record from user meta:" );

        $templ = <<<EOT
            <tr id="%d">
            <td class="name">%s</td>
            <td class="startdate"><input id="start" name="startdate" type="date" value="%s"/></td>
            <td class="status">%s</td>
            <td class="operation">%s</td>
            </tr>
EOT;
        $ctr = 0;
        foreach( $recorded_courses as $arrcourse ) {
            $this->log->error_log("$loc --> Course Reports from user meta...");
            foreach( $arrcourse as $course) {
                $this->log->error_log( $course );
                $st = isset( $course['status'] ) ? $course['status'] : self::PENDING;
                $select = '<select id="statusSelect" name="status">';
                foreach($this->statuses as $status ) {
                    if( $st === $status ) {
                        $select .= "<option selected='selected'>$status</option>";
                    }
                    else {
                        $select .= "<option>$status</option>";
                    }
                }
                $select .= "</select>";
                $row = sprintf( $templ
                              , $course['id']
                              , $course['name']
                              , isset($course["startDate"]) ? $course["startDate"] : '1970-01-01'
                              , $select
                              , $remove );
                $out .= $row;
                $val = sprintf("%d|%s|%s|%s"
                              , $course['id']
                              , $course['name']
                              , isset($course["startDate"]) ? $course["startDate"] : '1970-01-01'
                              , $st );
                $hidden .= sprintf("<input type=\"hidden\" name=\"coursereports[]\" value=\"%s\"> ", $val );
            }
        }
        $out .= '</tbody></table>';  
        $out .= $hidden; 

        $out .= "<div id='care-coursemessage'></div>";
        $out .= "</div>";

        return $out;
    }
    
    public function progressProfileSave( $userId ) 
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc: _POST:");
        $this->log->error_log( $_POST );
    
        if ( !current_user_can('edit_user', $userId)) {
            return;
        }

        //Get the registered courses
        if ( !empty( $_POST['coursereports'] )) {
            $coursereports = $_POST['coursereports'];
        }
        elseif ( !empty( $_GET['coursereports'] )) {
            $coursereports = $_GET['coursereports'];
        }
        else {
            $coursereports = array();
        }

        if( is_string( $coursereports ) ) {
            $s = $coursereports;
            $coursereports = array();
            array_push( $coursereports, $s );
        }
        $this->log->error_log( $coursereports, "Length of course reports=" . count( $coursereports ) );

        $this->storeCourseProgress( $userId, $coursereports );

        return;
    }

    private function storeCourseProgress( $userId, $statusreports ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );

        $courses = array();
        $tracker = array();
        $format = 'Y-m-d';
        foreach( $statusreports as $report ) {
            $arr = explode( "|", $report );
            if( !in_array( $arr[0], $tracker ) ) {
                $this->log->error_log( $report );
                array_push( $tracker, $arr[0] );
                $course = array();
                $course['id']        = $arr[0];
                $course['name']      = $arr[1];
                $date = DateTime::createFromFormat($format, $arr[2]);
                if( false === $date ) {
                    $this->log->error_log( DateTime::getLastErrors(), "Error processing start date" );
                    $date = DateTime::createFromFormat($format, '1970-01-01');
                }
                $this->log->error_log($date->format( $format ), "Date" );
                $course["startDate"] = $date->format( $format );
                $course['status']    = $arr[3];
                array_push( $courses, $course );
            }
        }
        
        $this->log->error_log( $courses, "Courses..." );

        //TODO: Need to make sure that the course still exists in carecourse's.
        $len = count( $courses );
        if( 0 === $len ) {
            if( delete_user_meta( $userId, self::META_KEY) ) {
                $mess = "Removed all course progress reports.";
            }
            else {
                $mess = "No course progress reports existed so none were removed.";
            }
        }
        else {
            //array is returned because $single is false
            $prev_courses = get_user_meta( $userId, self::META_KEY, false );
            if( count( $prev_courses ) < 1 ) {
                $meta_id = add_user_meta( $userId, self::META_KEY, $courses, true );
                $mess = sprintf("Added %d course %s. (Meta id=%d)", $len, $len === 1 ? 'progress report' : 'progress reports', $meta_id );
            }
            else {
                $meta_id = update_user_meta( $userId, self::META_KEY, $courses );
                if( true === $meta_id ) {
                    $mess = sprintf("Updated %d course %s.", $len, $len === 1 ? 'progress report' : 'progress reports' );
                }
                elseif( is_numeric( $meta_id ) ) {
                    $mess = sprintf("Added %d course %s. (Meta id=%d)", $len, $len === 1 ? 'progress report' : 'progress reports', $meta_id );
                }

            }
            if( false === $meta_id ) {
                $mess = "Course progress reports were not added/updated.";
            }
        }

        return $mess;

    }
 
    /**
     * Get the AJAX data that WordPress needs to output.
     *
     * @return array
     */
    private function get_data()
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );

        $mess = "Greetings!";
        return array( 'tableclass' => self::TABLE_CLASS
                    , 'message' => $mess
                    , 'statusvalues' => $this->statuses
                   );
    }

    private function handleErrors( string $mess ) {
        wp_die( $mess );
    }
    
} //end of class