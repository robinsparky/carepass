<?php
//Register custom post types
require get_stylesheet_directory() . '/inc/class-Webinar.php';
require get_stylesheet_directory() . '/inc/class-Course.php';
require get_stylesheet_directory() . '/inc/class-CourseSession.php';
//require get_stylesheet_directory() . '/inc/class-CourseRegisterByEmail.php';
require get_stylesheet_directory() . '/inc/class-CourseRegisterByEvent.php';

/**
 * Register ajax-based classes
 * */
//ReportCourseProgress::register();
WatchWebinarProgress::register();
//RecordUserCourseProgress::register();
//CareMediaSelector::register();

/**
 * Customize Event Query using Post Meta
 * 
 * @author Bill Erickson
 * @link http://www.billerickson.net/customize-the-wordpress-query/
 * @param object $query data
 *
 */
function be_event_query( $query ) {
	
	if( $query->is_main_query() && !$query->is_feed() && !is_admin() && $query->is_post_type_archive( '////' ) ) {
		$meta_query = array(
			array(
				'key' => 'be_events_manager_end_date',
				'value' => time(),
				'compare' => '>'
			)
		);
		$query->set( 'meta_query', $meta_query );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'meta_key', 'be_events_manager_start_date' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', '4' );
	}
}
//add_action( 'pre_get_posts', 'be_event_query' );

/**
 * Customize the Query for Care Course Archives
 * @param object $query data
 *
 */
function archive_carecourse_query( $query ) {
    $loc = __FILE__ . '/' . __FUNCTION__;
    error_log("$loc");
	
	if( $query->is_main_query() && !$query->is_feed() && !is_admin() 
	&& $query->is_post_type_archive( 'carecourse' ) ) {
		$courses_per_page = get_option('care_courses_page_size', 10 );
		error_log("$loc --> courses per page=$courses_per_page");

		$tax_query = array('relation' => 'AND'
						, array( 'taxonomy' => 'coursecategory'
							, 'field' => 'slug'
							, 'terms' => array('workshop')
							, 'operator' => 'NOT IN'
							)
					);

		$query->set( 'tax_query', $tax_query );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', $courses_per_page );
	}
}
add_action( 'pre_get_posts', 'archive_carecourse_query' );

/**
 * Customize the Query for Care Course Taxonomy - Workshop
 * @param object $query data
 *
 */
function taxonomy_carecourse_query( $query ) {
    $loc = __FILE__ . '/' . __FUNCTION__;
    error_log("$loc");

	if( $query->is_main_query() && !$query->is_feed() && !is_admin() 
	&& is_tax( 'coursecategory', 'Workshop' ) ) {
		$workshops_per_page = get_option('care_courses_page_size', 10 );
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', $workshops_per_page );
	}
}
add_action( 'pre_get_posts', 'taxonomy_carecourse_query' );
