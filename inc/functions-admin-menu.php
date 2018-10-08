<?php

/* WP Admin Menu positions
2 - Dashboard
4 - Separator
5 - Posts
10 - Media
20 - Pages
25 - Comments
59 - Separator
60 - Appearance
65 - Plugins
70 - Users
75 - Tools
80 - Settings
99 - Separator
*/

/* ================================
    Admin Menus
   ================================   
*/
function care_admin_page() {
    //Generate MCI admin page
    add_menu_page( 'PASS Admin Options' //title
                 , 'PASS' //menu name
                 , 'manage_options' //capabilities required of user
                 , 'carepass' //slug
                 , 'care_theme_create_page' // function to create page
                 , 'dashicons-admin-generic' //icon
                 , 5 //menu position
    );

    //Generate admin sub pages
    add_submenu_page( 'carepass' //parent slug
                    , 'Care Admin Options' //page title
                    , 'Settings' // menu title
                    , 'manage_options' //capabilities
                    , 'carepass' // menu slug
                    , 'care_theme_create_page' //callback
    );
    
    //Generate admin sub pages -- first one must mirror the main menu page
    add_submenu_page( 'carepass' //parent slug
                    , 'Care Dashboard Options' //page title
                    , 'Dashboard' // menu title
                    , 'manage_options' //capabilities
                    , 'carepass_dashboard' // menu slug
                    , 'care_theme_dashboard_page' //callback
    );

    add_action( 'admin_init', 'care_custom_settings' );
}

//Activate custom settings
add_action( 'admin_menu', 'care_admin_page' );

function care_theme_create_page() {
    //generation of our admin page
    require_once get_stylesheet_directory() . '/inc/templates/pass-settings.php';
}

function care_theme_dashboard_page() {
    //generation of our admin sub page
    require_once get_stylesheet_directory() . '/inc/templates/pass-dashboard.php';
}

function care_custom_settings() {
    register_setting( 'care-settings-group' //Options group
                    , 'care_courses_password' //Option name
                    //, '' //sanitize call back
                    );
    register_setting( 'care-settings-group' //Options group
                    , 'care_posts_page_size' //Option name
                    , 'sanitize_page_size' //sanitize call back
                    );

    add_settings_section( 'care-course-options' //id
                        , 'Course Options' //title
                        , 'care_course_options' //callback to generate html
                        , 'carepass' //page
                    );
    
    add_settings_field( 'courses-password' // id
                      , 'Course Lock Word' // title
                      , 'courses_password' // callback
                      , 'carepass' // page
                      , 'care-course-options' // section
                      //,  array of args
                );
                
    add_settings_section( 'care-display-options' //id
                        , 'Display Options' //title
                        , 'care_posts_page_size_option' //callback to generate html
                        , 'carepass' //page
                    );
    add_settings_field( 'posts-per-page' // id
                      , 'Posts Per Page' // title
                      , 'posts_per_page' // callback
                      , 'carepass' // page
                      , 'care-display-options' // section
                      //,  array of args
                );
}

function care_course_options() {
    echo "Manage your Course Options";
}
function care_posts_page_size_option() {
    echo "Manage your Display Options";
}

function courses_password() {
    $pass = esc_attr( get_option('care_courses_password') );
    echo '<input type="text" name="care_courses_password" value="' . $pass . '" />';
}

function posts_per_page() {
    $pagesize = esc_attr( get_option('care_posts_page_size') );
    echo '<input type="text" name="care_posts_page_size" value="' . $pagesize . '" /><p>Max 1000 and not negative</p>';
}

function sanitize_page_size( $input ) {
    $output = 1000;
    if( is_numeric( $input ) ) {
        if( $input < 0 || $input > 1000) $output = 1000;
        else $output = $input;
    }
    return $output;
}

