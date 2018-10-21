<?php
/*
Class implements PASS statistics dashboard
*/

class Statistics_Dashboard_Widget {

    /**
     * The id of this widget.
     */
    const wid = 'pass_stats_widget';

    public static function register() {        
        add_action('wp_dashboard_setup', array('Statistics_Dashboard_Widget','init') );
    }

    /**
     * Hook to wp_dashboard_setup to add the widget.
     */
    public static function init() {
        //Register widget settings...
        self::update_dashboard_widget_options(
            self::wid,                                  //The  widget id
            array(                                      //Associative array of options & default values
                'starting_date' => date("Y-m-d"),       //Defaults to today
            ),
            true                                        //Add only (will not update existing options)
        );

        //Register the widget...
        wp_add_dashboard_widget(
            self::wid,                                  //A unique slug/ID
            __( 'PASS Statistics', CARE_TEXTDOMAIN ),   //Visible name for the widget
            array('Statistics_Dashboard_Widget','widget'),      //Callback for the main widget content
            array('Statistics_Dashboard_Widget','config')       //Optional callback for widget configuration content
        );
    }

    /**
     * Load the widget code
     */
    public static function widget() {
        //require_once( 'widget.php' );
        require_once get_stylesheet_directory() . '/inc/templates/statistics_widget.php';
    }

    /**
     * Load widget config code.
     *
     * This is what will display when an admin clicks
     */
    public static function config() {
        //require_once( 'widget-config.php' );
        require_once get_stylesheet_directory() . '/inc/templates/statistics_widget_config.php';
    }

    /**
     * Gets the options for a widget of the specified name.
     *
     * @param string $widget_id Optional. If provided, will only get options for the specified widget.
     * @return array An associative array containing the widget's options and values. False if no opts.
     */
    public static function get_dashboard_widget_options( $widget_id='' )
    {
        //Fetch ALL dashboard widget options from the db...
        $opts = get_option( 'dashboard_widget_options' );

        //If no widget is specified, return everything
        if ( empty( $widget_id ) )
            return $opts;

        //If we request a widget and it exists, return it
        if ( isset( $opts[$widget_id] ) )
            return $opts[$widget_id];

        //Something went wrong...
        return false;
    }

    /**
     * Gets one specific option for the specified widget.
     * @param $widget_id
     * @param $option
     * @param null $default
     *
     * @return string
     */
    public static function get_dashboard_widget_option( $widget_id, $option, $default=NULL ) {

        $opts = self::get_dashboard_widget_options($widget_id);

        //If widget opts dont exist, return false
        if ( ! $opts )
            return false;

        //Otherwise fetch the option or use default
        if ( isset( $opts[$option] ) && ! empty($opts[$option]) )
            return $opts[$option];
        else
            return ( isset($default) ) ? $default : false;

    }

    /**
     * Saves an array of options for a single dashboard widget to the database.
     * Can also be used to define default values for a widget.
     *
     * @param string $widget_id The name of the widget being updated
     * @param array $args An associative array of options being saved.
     * @param bool $add_only If true, options will not be added if widget options already exist
     */
    public static function update_dashboard_widget_options( $widget_id , $args=array(), $add_only=false )
    {
        //Fetch ALL dashboard widget options from the db...
        $opts = get_option( 'dashboard_widget_options' );

        //Get just our widget's options, or set empty array
        $w_opts = ( isset( $opts[$widget_id] ) ) ? $opts[$widget_id] : array();

        if ( $add_only ) {
            //Flesh out any missing options (existing ones overwrite new ones)
            $opts[$widget_id] = array_merge($args,$w_opts);
        }
        else {
            //Merge new options with existing ones, and add it back to the widgets array
            $opts[$widget_id] = array_merge($w_opts,$args);
        }

        //Save the entire widgets array back to the db
        return update_option('dashboard_widget_options', $opts);
    }
    
	/**
	 * Retrieve All PASS Webinars and meta data from db
	 * @param $force Boolean to force fresh retrieval from database
	 */
	static public function getWebinarStatistics( $startDate ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log($loc);
        
        global $wpdb;
        $sql = "SELECT u.display_name as username
                        , m.meta_key
                        , m.meta_value as val
                FROM {$wpdb->prefix}usermeta m
                INNER JOIN {$wpdb->prefix}users u on u.ID = m.user_id
                where meta_key = '%s' ";
        $query = $wpdb->prepare( $sql, RecordUserWebinarProgress::META_KEY );
        $result = $wpdb->get_results( $query, ARRAY_A );

        $retval = array();
        foreach( Webinar::getWebinarDefinitions() as $webinar ) {
            $retval[$webinar['name']] = [RecordUserWebinarProgress::PENDING=>0
                                        ,RecordUserWebinarProgress::COMPLETED=>0];
        }         
        // error_log("Webinar Definitions:");
        // error_log( print_r($retval, true) ); 
        // error_log("Query results:");
        // error_log( print_r($result, true) ); 

        $start = DateTime::createFromFormat('Y-m-d', $startDate );
        error_log("Each query result:");
        $ctr = 0;
        foreach( $result as $meta ) {
            ++$ctr;
            $webinars = maybe_unserialize( $meta['val'] );
            error_log( "Meta value #{$ctr}:" );
            error_log( print_r( $webinars, true ) );

            if( !is_array( $webinars ) ) continue;

            foreach($webinars as $webinar) {
                error_log( "Webinar:" );
                error_log( print_r( $webinar, true ) );
                $strDate = @$webinar['startDate'] ? $webinar['startDate'] : date("Y-m-d");
                $webinarDate = DateTime::createFromFormat('Y-m-d', $strDate );
                if( false === $webinarDate ) {
                    $webinarDate = DateTime::createFromFormat('d-m-Y', $strDate);
                    if( false === $webinarDate ) {
                        error_log( DateTime::getLastErrors(), "Error processing start date" );
                        $webinarDate = DateTime::createFromFormat($format, '1970-01-01');
                    }
                }
                $show = $webinarDate->format('Y-m-d');
                error_log("Report date: $startDate; Webinar start: {$show}");
                if( $webinarDate < $start ) continue;
                $status = @$webinar['status'] ? $webinar['status'] : RecordUserWebinarProgress::PENDING;
                switch( $status ) {
                    case RecordUserWebinarProgress::COMPLETED:
                        $retval[$webinar['name']][RecordUserWebinarProgress::COMPLETED]++;
                        break;
                    default:
                        $retval[$webinar['name']][RecordUserWebinarProgress::PENDING]++;
                        break;
                }
            }
        }
        error_log("Webinar Stats");
        error_log( print_r( $retval, true ) );

		return $retval;
    }
    
    /**
	 * Retrieve All PASS Courses and meta data from db
	 * @param $force Boolean to force fresh retrieval from database
	 */
	static public function getCourseStatistics( $startDate ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log($loc);
        
        global $wpdb;
        $sql = "SELECT u.display_name as username
                        , m.meta_key
                        , m.meta_value as val
                FROM {$wpdb->prefix}usermeta m
                INNER JOIN {$wpdb->prefix}users u on u.ID = m.user_id
                where meta_key = '%s' ";
        $query = $wpdb->prepare( $sql, RecordUserCourseProgress::META_KEY );
        $result = $wpdb->get_results( $query, ARRAY_A );

        $retval = array();
        foreach( Course::getCourseDefinitions() as $webinar ) {
            $retval[$webinar['name']] = [RecordUserCourseProgress::PENDING=>0
                                        ,RecordUserCourseProgress::COMPLETED=>0];
        }         
        // error_log("Webinar Definitions:");
        // error_log( print_r($retval, true) ); 
        // error_log("Query results:");
        // error_log( print_r($result, true) ); 

        $start = DateTime::createFromFormat('Y-m-d', $startDate );
        error_log("Each query result:");
        $ctr = 0;
        foreach( $result as $meta ) {
            ++$ctr;
            $courses = maybe_unserialize( $meta['val'] );
            error_log( "Meta value #{$ctr}:" );
            error_log( print_r( $courses, true ) );

            if( !is_array( $courses ) ) continue;

            foreach($courses as $course) {
                error_log( "Course:" );
                error_log( print_r( $course, true ) );
                $strDate = @$course['startDate'] ? $course['startDate'] : date("Y-m-d");
                $courseDate = DateTime::createFromFormat('Y-m-d', $strDate );
                if( false === $courseDate ) {
                    $courseDate = DateTime::createFromFormat('d-m-Y', $strDate);
                    if( false === $courseDate ) {
                        error_log( DateTime::getLastErrors(), "Error processing start date" );
                        $courseDate = DateTime::createFromFormat($format, '1970-01-01');
                    }
                }
                $show = $courseDate->format('Y-m-d');
                error_log("Report date: $startDate; Course start: {$show}");
                if( $courseDate < $start ) continue;
                $status = @$course['status'] ? $course['status'] : RecordUserCourseProgress::PENDING;
                switch( $status ) {
                    case RecordUserCourseProgress::COMPLETED:
                        $retval[$course['name']][RecordUserCourseProgress::COMPLETED]++;
                        break;
                    default:
                        $retval[$course['name']][RecordUserCourseProgress::PENDING]++;
                        break;
                }
            }
        }
        error_log("Course Stats");
        error_log( print_r( $retval, true ) );

		return $retval;
    }
    
    public static function getMentorshipStatistics( $startDate ) {

        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log( $loc );

        global $wpdb;
        $sql = "SELECT u.display_name as username
                        , m.umeta_id as meta_id
                        , m.meta_key
                        , m.meta_value as val
                FROM {$wpdb->prefix}usermeta m
                INNER JOIN {$wpdb->prefix}users u on u.ID = m.user_id
                where meta_key = '%s' ";
        $query = $wpdb->prepare( $sql, RecordUserMemberData::META_KEY );
        $result = $wpdb->get_results( $query, ARRAY_A );

        $start = DateTime::createFromFormat('Y-m-d', $startDate );
        error_log( print_r( $start, true ) );
        $numInMentorship = 0;

        $ctr = 0;
        $memberName = '';
        $retval = array();
        error_log("Each query result:");
        foreach( $result as $meta ) {
            ++$ctr;
            $memberName = $meta['username'];
            $meta_id = $meta['meta_id'];
            $strDate = maybe_unserialize( $meta['val'] );
            error_log("$ctr. Member '{$memberName}' Meta id={$meta_id}" );
            error_log( print_r( $strDate, true ) );

            $joinedMentorship = false;
            $dateJoined = DateTime::createFromFormat('Y-m-d', $strDate );
            if( false === $dateJoined ) {
                $dateJoined = DateTime::createFromFormat('d-m-Y', $strDate);
                if( false === $dateJoined ) {
                    $dateJoined = '';
                }
                else {
                    $showStart = $dateJoined->format('Y-m-d');
                    $joinedMentorship = true;
                    error_log("Report date: $startDate; Joined Mentorship on: {$showStart}");
                }
            }
            else {
                $showStart = $dateJoined->format('Y-m-d');
                $joinedMentorship = true;
                error_log("Report date: $startDate; Joined Mentorship on: {$showStart}");
            }

            if( $joinedMentorship && $dateJoined >= $start ) {
                ++$numInMentorship;
            }
        }

        return $numInMentorship;
    }


}