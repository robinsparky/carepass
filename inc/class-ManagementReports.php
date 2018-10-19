<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Data and functions for management reporting
 * @class  ManagementReports
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class ManagementReports 
{ 

    //Action hook used by the AJAX class.
    const ACTION     = 'passManagementReports';
    const NONCE      = 'passManagementReports';

    private $ajax_nonce = null;
    private $errobj = null;
    private $errcode = 0;

    private $hooks;
    private $statuses;
    private $roles;
    private $log;
    /**
     * Register the AJAX handler class with all the appropriate WordPress hooks.
     */
    public static function register() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        error_log("$loc");
        $handler = new self();
        add_shortcode( 'pass_mgmt_reports', array( $handler, 'renderReportControls' ) );
        add_action('wp_enqueue_scripts', array( $handler, 'registerScript' ) );
        $handler->registerHandlers();
    }

	/*************** Instance Methods ****************/
	public function __construct( ) {
        $this->errobj = new WP_Error();
        //Only emit on this page
        $this->hooks = array( 'management-reports' );
        $this->roles = array( 'um_admin', 'administrator' );

        $this->log = new BaseLogger( false );
    }

    public function registerScript( $hook ) {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( "$loc --> $hook" ); 
        wp_register_script( 'care-pass-mgmt'
                        , get_stylesheet_directory_uri() . '/js/care-pass-mgmtreports.js'
                        , array('jquery') );

        wp_localize_script( 'care-pass-mgmt', 'care_pass_mgmt', $this->get_data() );

        wp_enqueue_script( 'care-pass-mgmt' );

    }

    public function registerHandlers() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");
        
        add_action( 'wp_ajax_' . self::ACTION
                  , array( $this, 'getReportParameters' ));
        add_action( 'wp_ajax_nopriv_' . self::ACTION
                  , array( $this, 'noPrivilegesHandler' ));

    }
    
    public function noPrivilegesHandler() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");
        // Handle the ajax request
        check_ajax_referer(  self::NONCE, 'security'  );
        $this->errobj->add( $this->errcode++, __( 'You have been reported to the authorities!', CARE_TEXTDOMAIN ));
        $this->handleErrors("You've been a bad boy.");
    }

    public function renderReportControls() {
        
        $select_title = __('Select Report', CARE_TEXTDOMAIN );
        $tmpl = '<option value="%s">%s</option>';

        $selection = '<select id="report-select">';
        //$selection .= '<option value="0" selected="selected">' . $select_title . '</option>';
        
        $selection .= '<optgroup label="Webinars">';
        $args = array( 'post_type' => Webinar::CUSTOM_POST_TYPE, 'posts_per_page' => -1, 'orderby'=>'title', 'order'=>'ASC' ); 
        $loop = new WP_Query( $args );
        if ($loop->have_posts()) {
            while ( $loop->have_posts() ) {
                $loop->the_post();
                $selection .= sprintf( $tmpl, Webinar::CUSTOM_POST_TYPE . ':' . get_the_ID(), get_the_title() );
            }
        }

        // Reset Post Data
        wp_reset_postdata();
        wp_reset_query();
        
        $args = array( 'post_type' => Course::CUSTOM_POST_TYPE, 'posts_per_page' => -1, 'orderby'=>'title', 'order'=>'ASC' ); 

        $selection .= '<optgroup label="Courses">';
        $loop = new WP_Query( $args );
        if ($loop->have_posts()) {
            while ( $loop->have_posts() ) {
                $loop->the_post();
                $selection .= sprintf( $tmpl, Course::CUSTOM_POST_TYPE . ':' . get_the_ID(), get_the_title() );
            }
        }  
        // Reset Post Data 
        wp_reset_postdata();
        wp_reset_query();
        
        $selection .= "</select>";

        $out = "<table class='management-report'>";
        $out .= "<thead><th>Select Report</th><th>Starting</th><th>Ending</th></thead>";
        $out .= "<tbody>";
        $out .= "<tr><td>$selection</td>";
        $out .= "<td><input type='date' id='report_start' name='report_start' value='2018-1-1'></td>";
        $out .= "<td><input type='date' id='report_end' name='report_end' value='2018-12-31'></td>";
        $out .= "<td><button id='pass_get_report' name='pass_get_report' type='button'>Run</button>";
        $out .= " <button id='pass_clear_report' name='pass_clear_report' type='button'>Clear</button></td>";
        $out .= "</tr></tbody></table>";

        $out .= "<div id='pass-reportmessage'></div>";

        $out .= "<div id='report-container'></div>";

        return $out;
    }

    public function getReportParameters() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");

        $this->log->error_log( $_POST, "POST:" );

        // Handle the ajax request
        check_ajax_referer( self::NONCE, 'security' );

        if( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) {
            $this->handleErrors('Not Ajax');
        }
        
        if( !is_user_logged_in() ) {           
            $this->errobj->add( $this->errcode++, __( 'Not logged in.', CARE_TEXTDOMAIN ));
        }

        if( !current_user_can( 'manage_options' ) ) {
            $this->errobj->add( $this->errcode++, __( 'Not an administrator.', CARE_TEXTDOMAIN ));
        }
        
        //Get the selected webinars
        $report = '';
        if ( !empty( $_POST['id'] )) {
            $report = $_POST['id'];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Webinar or Course not selected.', CARE_TEXTDOMAIN ));
        }
        
        //Get the start date
        $startDate = '';
        if ( !empty( $_POST['report_start'] )) {
            $startDate = $_POST['report_start'];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Report start date not entered.', CARE_TEXTDOMAIN ));
        }
        
        //Get the end date
        $endDate = '';
        if ( !empty( $_POST['report_end'] )) {
            $endDate = $_POST['report_end'];
        }
        else {
            $this->errobj->add( $this->errcode++, __( 'Report end date not entered.', CARE_TEXTDOMAIN ));
        }

        if(count($this->errobj->errors) > 0) {
            $this->handleErrors("Errors were encountered");
        }

        $postType = explode( ":", $report, 2 )[0];
        $id  = (integer) explode( ":", $report, 2 )[1];

        $result = $this->mgmtReport( $id, $postType, $startDate, $endDate );


        $response = array();
        $response["message"] = "Results";
        $response["returnData"] = $result;
        wp_send_json_success( $response );

        wp_die();

    }
    
	private function mgmtReport($id, $reportType, $startDate = '1970-01-01', $endDate = '2299-01-01')
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( "$loc --> $id, $reportType, $startDate, $endDate" );

        $metaKey = '';
        switch($reportType) {
            case Course::CUSTOM_POST_TYPE:
                $metaKey = RecordUserCourseProgress::META_KEY;
                break;
            case Webinar::CUSTOM_POST_TYPE:
                $metaKey = RecordUserWebinarProgress::META_KEY;
                break;
        }

        $this->log->error_log("Meta key is '$metaKey'");
        global $wpdb;
        $sql = "SELECT u.display_name as username
                        , m.umeta_id as meta_id
                        , m.meta_key
                        , m.meta_value as val
                FROM {$wpdb->prefix}usermeta m
                INNER JOIN {$wpdb->prefix}users u on u.ID = m.user_id
                where meta_key = '%s' ";
        $query = $wpdb->prepare( $sql, $metaKey );
        $result = $wpdb->get_results( $query, ARRAY_A );
      
        $this->log->error_log($result, "Query results:");

        $start = DateTime::createFromFormat('Y-m-d', $startDate );
        $end   = DateTime::createFromFormat('Y-m-d', $endDate );
        $this->log->error_log($start, "$loc --> Start date string: $startDate");
        $this->log->error_log($end, "$loc --> End date string: $endDate");
        
        $ctr = 0;
        $memberName = '';
        $retval = array();
        foreach( $result as $meta ) {
            ++$ctr;
            $memberName = $meta['username'];
            $meta_id = $meta['meta_id'];
            $webinars = maybe_unserialize( $meta['val'] );
            $this->log->error_log( $webinars, "$ctr. Member '{$memberName}' Meta id={$meta_id} :" );

            if( !is_array( $webinars ) ) continue;

            foreach($webinars as $webinar) {
                $this->log->error_log( $webinar, "Webinar:" );
                $strDate = @$webinar['startDate'] ? $webinar['startDate'] : date("Y-m-d");
                $webinarDate = DateTime::createFromFormat('Y-m-d', $strDate );
                if( false === $webinarDate ) {
                    $webinarDate = DateTime::createFromFormat('d-m-Y', $strDate);
                    if( false === $webinarDate ) {
                        $this->log->error_log( DateTime::getLastErrors(), "Error processing start date" );
                        $webinarDate = DateTime::createFromFormat($format, '1970-01-01');
                    }
                }
                $showStart = $webinarDate->format('Y-m-d');
                $this->log->error_log("Report date: $startDate; Webinar start: {$showStart}");

                if( $webinarDate < $start || $webinarDate > $end ) continue;
                if( $webinar['id'] != $id ) continue;

                $status = @$webinar['status'] ? $webinar['status'] : RecordUserWebinarProgress::PENDING;
                array_push($retval, array($memberName, $webinarDate->format('Y-m-d'), $status) );
            }
        }

        return $retval;
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
        return array('ajaxurl' => admin_url( 'admin-ajax.php' )
                    ,'action' => self::ACTION
                    ,'security' => wp_create_nonce(self::NONCE)
                    ,'message' => $mess
                    ,'reporttarget' => 'report-container'
                    ,'titles' => array('Name','Start','Status')
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
    
} //end of class