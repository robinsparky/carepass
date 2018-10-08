<?php
//TODO: use namespaces
//namespace Care\Custom;

/** 
 * Data and functions for the CourseSession which adds Care Course data to Events Manager
 * @class  CourseSession
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class CourseSession {

	//Meta Keys in Event Manager's meta table
	const COURSEID_META_KEY = 'care_session_course_id';
	const COURSENAME_META_KEY = 'care_session_course_name';

	//Field names of data posted to/from server
	const COURSE_SESSION_ID = 'care_course_session_courseid';
	const COURSE_SESSION_NAME = 'care_course_session_name';
	
	private $hooks;
	private $roles;

	private $log;

	public static function getCourseSessions( $courseId ) {
		$loc = __CLASS__ . '::' . __FUNCTION__;
		error_log( "$loc --> courseId=$courseId" );

		global $wpdb;
		$eventsTable = $wpdb->prefix . 'em_events';
		$locationsTable = $wpdb->prefix . 'em_locations';
		$metaTable = EM_META_TABLE;
		$sqlText = "select ev.event_id
				   , ev.event_name
				   , ev.event_slug
				   , ev.event_start_date
				   , ev.event_start_time
				   , ev.event_end_date
				   , ev.event_end_time
				   , em.meta_value as CourseId
				   , el.location_name
					from $eventsTable ev
					inner join $metaTable em on em.object_id = ev.event_id
					inner join $locationsTable el on el.location_id = ev.location_id
					where meta_key in ('%s')
					and em.meta_value = '%s';";
		$sql = $wpdb->prepare($sqlText, self::COURSEID_META_KEY, $courseId );
		$sessions = $wpdb->get_results( $sql, ARRAY_A );
		
		return $sessions;

	}

	public function __construct() {
		$loc = __CLASS__ . '::' . __FUNCTION__;

		$this->hooks = array();
		$this->roles = array();
		$this->log = new BaseLogger( false );
	}

	public function register() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			
		//Add column to Events showing Course Title
		add_filter( 'manage_' . EM_POST_TYPE_EVENT . '_posts_columns', array( $this, 'addColumns' ), 10 );
		add_action( 'manage_' . EM_POST_TYPE_EVENT . '_posts_custom_column', array( $this, 'getColumnValues'), 10, 2 );

		//Required actions for meta box
		//In this case capture the parent course id
		add_action( 'add_meta_boxes', array( $this, 'metaBoxes' ) );
		
		//Show the course definitions in the front end form
		add_action('em_front_event_form_footer', array( $this, 'frontendFormInput' ) );
		
		//Save the parent course id when the event is saved
		add_filter( 'em_event_save', array( $this, 'sessionSave'), 1, 2 );
		//add_action( 'save_post', array( $this, 'sessionSave' ) ) ;

		//Get the parent course id when event is loaded
		add_action( 'em_event', array( $this, 'sessionEventLoad' ), 1, 1 );

		//Create custom placeholder for Event formatting
		add_filter('em_event_output_placeholder', array( $this, 'sessionPlaceholders'), 1, 3 );

		//Hook into the search filters
		add_filter('em_events_get_default_search', array( $this, 'getDefaultSearch'), 1, 2 );
		add_filter('em_calendar_get_default_search',array( $this, 'getDefaultSearch'), 1, 2 );
		add_filter( 'em_events_build_sql_conditions', array( $this, 'buildSqlSearchConditions'), 1, 2 );
		add_action('em_template_events_search_form_footer', array( $this, 'courseSearchForm') );
		add_filter('em_accepted_searches', array( $this, 'acceptedSearches'), 1, 1 );

		add_filter('em_content_events_args', array( $this, 'filterEventArgs'), 1, 1 );
		
	}

	public function filterEventArgs( $args ) {
		$loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $args, $loc );
	}

	public function acceptedSearches( $searches ) { 
		$loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log($loc);

		$searches[] = 'CareCourse';
		return $searches;
	}

	public function courseSearchForm() { 
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$courses = Course::getCourseDefinitions();
		?>
		<!-- START Care Courses Search -->
		<div class="em-search-field">
			<label>
				<span>Course</span>
				<select name="CareCourses">
					<option value=''>All Courses</option>
					<?php foreach($courses as $course): ?>
					 <option value="<?php echo $course['id']; ?>" <?php echo (!empty($_REQUEST['CareCourse']) && $_REQUEST['CareCourse'] == $course['name']) ? 'selected="selected"':''; ?>><?php echo $course['name']; ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
		<!-- END Care Courses Search -->
		<?php
	}

	public function getDefaultSearch( $args, $array ) { 
		$loc = __CLASS__ . '::' . __FUNCTION__;
		
		$args['CareCourse'] = false; //registers CareCourse as an acceptable value, although set to false by default
		if( !empty( $array['CareCourse'] ) && is_string( $array['CareCourse'] ) ) {
			$args['CareCourse'] = $array['CareCourse'];
		}
		return $args;
	}

	public function buildSqlSearchConditions( $conditions, $args ) { 
		$loc = __CLASS__ . '::' . __FUNCTION__;
		
		global $wpdb;
		if( !empty( $args['CareCourse'] ) && is_string( $args['CareCourse'] ) ) {
			$myval = $args['CareCourse'];
			$sql = $wpdb->prepare( "SELECT object_id FROM " . EM_META_TABLE . " WHERE meta_value like '%%%s%%' AND meta_key='" . self::COURSENAME_META_KEY . "' ", $wpdb->esc_like( $myval ) );
			$conditions['CareCourse'] = "event_id IN ($sql)";
		}
		return $conditions;
	}
	
	// Add Course columns
	public function addColumns( $columns ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );

		// column vs displayed title
		$columns['course_title'] = __('Course Title', CARE_TEXTDOMAIN );
		$this->log->error_log( $columns );
		return $columns;
	}

	// Populate the Course columns with values
	public function getColumnValues( $column_name, $postID ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( "$loc --> $column_name, $postID" );

		if( $column_name === 'course_title' ){
			$args = array("post_id" => $postID);
			$ev = EM_Events::get( $args )[0];
			$this->log->error_log("$loc -->{$ev->post_id}");
			$this->sessionEventLoad( $ev );
			//$this->log->error_log( $ev );
			$val = !empty($ev->course_Title) ? $ev->course_Title : "n/a";
			echo $val;
		}
	}

	/**
	 * Create a custom placeholder for Event formatting
	 */
	public function sessionPlaceholders( $replace, $EM_Event, $result ) {    
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( "$loc --> $result" ); 
		
		if( $result == '#_COURSEID' ) {
			$replace = '';
			if( $EM_Event->courseId > 0 ) {
				$replace = $EM_Event->course_Id;
			}
			$this->log->error_log( "$loc --> courseId='$replace'" );
		} 
		else if( $result == '#_COURSETITLE' ) {
			$replace = '';
			if( strlen($EM_Event->course_Title) > 0 ) {
				$replace = $EM_Event->course_Title;
			}
			$this->log->error_log( "$loc --> courseName='$replace'" );
		}
    	return $replace;
	}
	
	/**
	 * Enqueue any needed JS files
	 */
	public function enqueue( $hook ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( "$loc --> $hook" ); 

        //Make sure we are rendering the "user-edit" page
        if( in_array( $hook, $this->hooks ) ) {
            //Enqueue WP media js
            //wp_enqueue_media();

            // wp_register_script( 'care-media-uploader'
            //                 , get_stylesheet_directory_uri() . '/js/care-course-media-uploader.js'
            //                 , array('jquery') );
    
            // wp_localize_script( 'care-media-uploader', self::JS_OBJECT, $this->get_data() );

            // wp_enqueue_script( 'care-media-uploader' );
		}
	}

	/**
	 * Retrieve the parent course id and title if it exists
	 * for the given event
	 * @param $EM_Event 
	 */
	public function sessionEventLoad( $EM_Event ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );
		
		global $wpdb;
		$sqlText = "SELECT meta_value from " . EM_META_TABLE . " WHERE object_id=%d AND meta_key='%s'";
		$sql = $wpdb->prepare( $sqlText
							 , $EM_Event->event_id, self::COURSEID_META_KEY );

		$result = $wpdb->get_col( $sql, 0 );
		if( !empty( $result ) && is_array( $result ) ) {
			$EM_Event->course_Id = $result[0];
		}
		else {
			$EM_Event->course_Id = 0;
		}
		
		$sql = $wpdb->prepare( $sqlText
							 , $EM_Event->event_id, self::COURSENAME_META_KEY );
		$result = $wpdb->get_col( $sql, 0 );
		if( !empty( $result ) && is_array( $result ) ) {
			$EM_Event->course_Title = $result[0];
		}
		else {
			$EM_Event->course_Title = '';
		}
	}
	
	/**
	 * Add the meta box for EM_Event objects
	 * so that a parent course can be associated with the event
	 */
	public function metaBoxes() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $loc );
		
		/* Curriculum meta box */
		add_meta_box( 'care_course_session_meta_box' //id
					, 'Course or Workshop' //Title
					, array( $this, 'sessionCallback' ) //Callback
					, EM_POST_TYPE_EVENT //mixed: screen et cpt name, or ???
					, 'normal' //context: normal, side, 
					, 'high' // priority: low, high, default
						// array callback args
					);
	}
	
	public function frontendFormInput() {
		?>
		<fieldset>
			<legend>Course or Workshop</legend>
			<?php $this->sessionCallback(); ?>
		</div>
		<?php
	}

	/**
	 *  Display Select element as a metabox in Events Manager's Events
	 * 
	 * */
	public function sessionCallback( $post ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;

		global $EM_Event;

		$this->log->error_log("$loc --> actual='{$EM_Event->course_Id}'");

		//Now echo the html desired
		$sel = sprintf('<select name="%s">', self::COURSE_SESSION_ID );
		echo $sel; //'<select name="care_course_session_field">';
		$courses = Course::getCourseDefinitions();
		$sel = '';
		$options = array();
		foreach( $courses as $course ) {
			$disp = esc_attr( $course['name'] );
			$value = esc_attr( $course['id'] );
			if($EM_Event->course_Id === $value) {
				$sel = 'selected';
				$options[] = "<option value='$value' selected='$sel'>$disp</option>";
			}
			else {
				$options[] = "<option value='$value'>$disp</option>";
			}
		}
		$mess = __( 'Select Course/Workshop or This to Remove', CARE_TEXTDOMAIN );
		$instr = "<option value='0'>$mess</option>";
		if( strlen( $sel ) === 0 ) {
			$instr = "<option value='0' selected='selected' >$mess</option>";
		}
		array_unshift( $options, $instr );
		echo implode( $options );
		echo '</select>';
	}
	
	/** 
	 * Save the selected post ID of the parent course and the course's title for this session (aka event)
	 * @param $result
	 * @param $EM_Event
	 */
	public function sessionSave( $result, $EM_Event ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
		$this->log->error_log( $_POST, "$loc: _POST:" );
		
		// what are the possible values of $result?
		// "In many cases it’s handy to check $result 
		// as we then know if an event was successfully submitted without errors"
		$this->log->error_log( $result, "$loc: result:" );
		
		// $this->log->error_log( $EM_Event, "$loc: EM_Event:" );

		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			$this->log->error_log("$loc --> doing autosave");
			return $result;
		}

		if( ! current_user_can( 'edit_post', $EM_Event->post_id ) ) {
			$this->log->error_log("$loc --> cannot edit post");
			return $result;
		}

		if( !isset( $_POST[ self::COURSE_SESSION_ID ] ) 
		 || !is_numeric( $_POST[ self::COURSE_SESSION_ID ] )) {
			$this->log->error_log("$loc --> session course id field missing or invalid");
			return $result;
		}

		$courseId = $_POST[ self::COURSE_SESSION_ID ];
		if( $courseId < 1 ) {
			$this->log->error_log("$loc --> remove course association");
		}

		$courseTitle = "";
		if( $courseId > 0 ) {
			foreach( Course::getCourseDefinitions() as $course ) {
				if( $course['id'] == $courseId ) {
					$courseTitle = $course['name'];
					break;
				}
			}
			if( empty( $courseTitle ) ) $courseId = 0; //Course no longer exists so let's trigger deletion
		}
		
		global $wpdb;
		//First delete any old saves
		$this->log->error_log("$loc --> em meta table=" . EM_META_TABLE );
		if( $EM_Event->event_id ) {
			$meta_key_courseId = self::COURSEID_META_KEY;
			$sql = sprintf( "DELETE FROM %s WHERE object_id=%d AND (meta_key='%s' OR meta_key='%s');"
						  , EM_META_TABLE, $EM_Event->event_id, self::COURSEID_META_KEY, self::COURSENAME_META_KEY );
			$result = $wpdb->query( $sql );
			$this->log->error_log( "$loc --> rows affected by delete=$result" );

			$EM_Event->course_Id = $courseId;
			$EM_Event->course_Title = $courseTitle;

			if( $courseId > 0 ) {
				$sql = sprintf( "INSERT INTO %s (object_id, meta_key, meta_value) values (%d, '%s', '%s'), (%d, '%s', '%s');"
							, EM_META_TABLE
							, $EM_Event->event_id, self::COURSEID_META_KEY, $courseId
							, $EM_Event->event_id, self::COURSENAME_META_KEY, $courseTitle );
				$result = $wpdb->query( $sql );
				$this->log->error_log( "$loc --> rows affected by insert=$result" );
			}
		}
		return $result;
	}

} //end class

if( class_exists( 'CourseSession' ) && class_exists( 'EM_Event' ) ) {
	global $EM_Event;
	$care_course_session = new CourseSession();
	$care_course_session->register();
}
