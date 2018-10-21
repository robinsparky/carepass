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
                 , 'PASS Settings' //menu name
                 , 'manage_options' //capabilities required of user
                 , 'carepass' //slug
                 , 'care_theme_create_page' // function to create page
                 , 'dashicons-admin-generic' //icon
                 , 5 //menu position
    );

    //Generate admin sub pages
    add_submenu_page( 'carepass' //parent slug
                    , 'PASS Admin Options' //page title
                    , 'Settings' // menu title
                    , 'manage_options' //capabilities
                    , 'carepass' // menu slug
                    , 'care_theme_create_page' //callback
    );
    
    //Generate admin sub pages -- first one must mirror the main menu page
    // add_submenu_page( 'carepass' //parent slug
    //                 , 'PASS Dashboard Options' //page title
    //                 , 'Dashboard' // menu title
    //                 , 'manage_options' //capabilities
    //                 , 'carepass_dashboard' // menu slug
    //                 , 'care_theme_dashboard_page' //callback
    //);

    add_action( 'admin_init', 'care_custom_settings' );
}

//Activate custom settings
add_action( 'admin_menu', 'care_admin_page' );

function care_theme_create_page() {
    //generation of our admin page
    require_once get_stylesheet_directory() . '/inc/templates/pass-settings.php';
}

// function care_theme_dashboard_page() {
//     //generation of our admin sub page
//     require_once get_stylesheet_directory() . '/inc/templates/pass-dashboard.php';
// }

function care_custom_settings() {
    register_setting( 'care-settings-group' //Options group
                    , 'pass_webinar_pct_complete' //Option name
                    //, '' //sanitize call back
                    );
    register_setting( 'care-settings-group' //Options group
                    , 'care_webinars_page_size' //Option name
                    , 'sanitize_page_size' //sanitize call back
                    );

    add_settings_section( 'care-course-options' //id
                        , 'Webinar Options' //title
                        , 'care_webinar_options' //callback to generate html
                        , 'carepass' //page
                    );
    
    add_settings_field( 'webinar-percent-complete' // id
                      , 'Webinar Completion Percentage' // title
                      , 'webinarPercentComplete' // callback
                      , 'carepass' // page
                      , 'care-course-options' // section
                      //,  array of args
                );
                
    add_settings_section( 'care-display-options' //id
                        , 'Display Options' //title
                        , 'care_webinars_page_size_option' //callback to generate html
                        , 'carepass' //page
                    );
    add_settings_field( 'posts-per-page' // id
                      , 'Webinars Per Page' // title
                      , 'webinarsPerPage' // callback
                      , 'carepass' // page
                      , 'care-display-options' // section
                      //,  array of args
                );
}

function care_webinar_options() {
    echo "Manage your Webinar Options";
}
function care_webinars_page_size_option() {
    echo "Manage your Display Options";
}

function webinarPercentComplete() {
    $pctCompleteFactor = esc_attr( get_option('pass_webinar_pct_complete', 85) );
    echo '<input type="number" placeholder="Min: 10.0, max: 100.0"
    min="10.0" max="100.0" step="1" name="pass_webinar_pct_complete" value="' . $pctCompleteFactor . '" /><p>Max 100 and at least 10. Default is 85</p>';
}

function webinarsPerPage() {
    $pagesize = esc_attr( get_option('care_webinars_page_size') );
    echo '<input type="number" min="1" max="1000" placeholder="Min: 1, max: 1000" step="1" name="care_webinars_page_size" value="' . $pagesize . '" /><p>Max 1000 and not negative</p>';
}

function sanitize_page_size( $input ) {
    $output = 1000;
    if( is_numeric( $input ) ) {
        if( $input < 0 || $input > 1000) $output = 1000;
        else $output = $input;
    }
    return $output;
}

