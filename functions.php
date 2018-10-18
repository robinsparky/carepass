<?php
define( "CARE_SERVICE", "PASS" );
define( 'CARE_TEXTDOMAIN', CARE_SERVICE . '_ien_text' );

//Class autoload
require get_stylesheet_directory() . '/inc/autoloader.php';

//Custom post types, taxonomies and ajax-based classes
require get_stylesheet_directory() . '/inc/functions-cpt.php';

//Custom admin menus
require get_stylesheet_directory() . '/inc/functions-admin-menu.php';	

//Support functions
require get_stylesheet_directory() . '/inc/functions-support.php';

/*
* MCI styles
*/
function care_pass_theme_css() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'bootstrap-style', get_template_directory_uri() . '/css/bootstrap.css' );
	wp_enqueue_style( 'theme-menu', get_template_directory_uri() . '/css/theme-menu.css' );
	wp_enqueue_style( 'element-style', get_template_directory_uri() . '/css/element.css' );
	wp_enqueue_style( 'media-responsive', get_template_directory_uri(). '/css/media-responsive.css');
	wp_dequeue_style( 'appointment-default',get_template_directory_uri() .'/css/default.css');
	wp_enqueue_style( 'default-css', get_stylesheet_directory_uri()."/css/default.css" );

	//javascript
	wp_enqueue_script('care-common-js', get_stylesheet_directory_uri()."/js/care-message-window.js");
}
add_action( 'wp_enqueue_scripts', 'care_pass_theme_css',999);

/*
* PASS Setup
*/
function care_pass_setup() {
   add_theme_support( 'title-tag' ); //Let WordPress manage the document title.
   add_filter( 'template_include', 'care_template_include', 1000 );
}

/* 
   =============================================
	Function to setup the current theme template
   =============================================
*/
function care_template_include( $templatepath ){
	$loc = __FUNCTION__;
	error_log( "$loc-->template path= $templatepath" );
	$GLOBALS['care_current_theme_template'] = basename( $templatepath );
    return $templatepath;
}
add_action( 'after_setup_theme', 'care_pass_setup' );

/* 
   ===========================================
	Function to get the current template
   ===========================================
*/
function care_get_current_template( $echo = false ) {
    if( !isset( $GLOBALS['care_current_theme_template'] ) )
        return false;
    if( $echo )
        echo $GLOBALS['care_current_theme_template'];
    else
        return $GLOBALS['care_current_theme_template'];
}

/* 
   ===========================================
	Function to display taxonomy/tag links
   ===========================================
*/
function care_mci_get_term_links( $postID, $termname ) {
	$term_list = wp_get_post_terms( $postID, $termname ); 
	$i = 0;
	$len = count($term_list);
	foreach($term_list as $term ) {
		if( $i++ >= 0 && $i < $len) {
			$sep = ',';
		}
		else if( $i >= $len ) {
			$sep = '';
		}
		$lnk = get_term_link( $term );
		if( is_wp_error( $lnk ) ) {
			$mess = $lnk->get_error_message();
			echo "<span>$mess</span>$sep";
		}
		else {
			echo "<a href='$lnk'>$term->name</a>$sep";
		}
	}
}

//Just for dev
add_filter( 'auth_cookie_expiration', 'keep_me_logged_in_for_1_year' );

function keep_me_logged_in_for_1_year( $expirein ) {
    return YEAR_IN_SECONDS; //31556926; // 1 year in seconds
}
