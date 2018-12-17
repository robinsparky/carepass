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
* PASS styles
*/
function care_pass_theme_css() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'bootstrap-style', get_template_directory_uri() . '/css/bootstrap.css' );
	wp_enqueue_style( 'theme-menu', get_template_directory_uri() . '/css/theme-menu.css' );
	wp_enqueue_style( 'element-style', get_template_directory_uri() . '/css/element.css' );
	wp_enqueue_style( 'media-responsive', get_template_directory_uri(). '/css/media-responsive.css');
	
	//wp_dequeue_style( 'appointment-default',get_template_directory_uri() .'/css/default.css');
	wp_enqueue_style( 'default-css', get_stylesheet_directory_uri()."/css/default.css" );

	//javascript
	wp_enqueue_script('care-common-js', get_stylesheet_directory_uri()."/js/care-message-window.js");
}
add_action( 'wp_enqueue_scripts', 'care_pass_theme_css',999);


function change_default_css( ) {
    wp_dequeue_style( 'appointment-default');	
    wp_deregister_style( 'appointment-default');   
}
add_filter( 'wp_enqueue_styles', 'change_default_css', PHP_INT_MAX );

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
   ===================================================
   Functions to add sortable ID and registration date
   to list of users
   ===================================================
 */
function care_modify_user_table( $column ) {
    // $column['id'] = 'ID';
    // return $column;
    
    $new = array();
    foreach($column as $key => $title) {
        if ($key == 'username') { 
            $new['user_id'] = 'ID'; // Our custom column’s identifier and text
            $new['registered'] = 'Registered';
        }
        $new[$key] = $title;
    }
    return $new;
}
add_filter( 'manage_users_columns', 'care_modify_user_table' );


function care_modify_user_sortable( $columns ) {
    $columns['user_id'] = 'ID';
    //$columns['registered'] = 'Registered';

    return $columns;
}
add_filter( 'manage_users_sortable_columns', 'care_modify_user_sortable' );

function new_modify_user_table_row( $val, $column_name, $user_id ) {
    
	$date_format = 'j M, Y H:i';
    switch ($column_name) {
        case 'user_id' :
            return $user_id;
            break;
        case 'registered':
            $udata = get_userdata( $user_id );
            $registered = $udata->user_registered;
            return  date( $date_format, strtotime( $registered ) );
            break;
        default:
    }
    return $val;
}
add_filter( 'manage_users_custom_column', 'new_modify_user_table_row', 10, 3 );

/**
 * Set column width
 */
function care_user_id_column_style() {
	echo "<style>.column-user_id{width: 5%}</style>";
}
add_action('admin_head-users.php', 'care_user_id_column_style');

/*
   ====================
   Just for dev
   REMOVE when live
   ====================
*/
add_filter( 'auth_cookie_expiration', 'keep_me_logged_in_for_1_year' );

function keep_me_logged_in_for_1_year( $expirein ) {
    return YEAR_IN_SECONDS; //31556926; // 1 year in seconds
}

