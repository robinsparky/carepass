<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 
 * Data and functions for Recording Webinar Progress
 * @class  RecordUserMemberData
 * @package Care
 * @version 1.0.0
 * @since   0.1.0
*/
class RecordUserMemberData
{ 
    const META_KEY   = "joined_mentorship";
    const TABLE_CLASS = 'member-data';

    private $errobj = null;
    private $errcode = 0;

    private $hooks;
    private $roles;
    private $log;

    /**
     * Register the AJAX handler class with all the appropriate WordPress hooks.
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
        //Only emit on this page
        $this->hooks = array( 'profile.php', 'user-edit.php' );		
        $rolesWatch = esc_attr( get_option('care_roles_that_watch') );
        $this->roles = explode( ",", $rolesWatch );
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
            // wp_register_script( 'care-userprofile-webinarprogress'
            //                 , get_stylesheet_directory_uri() . '/js/care-record-user-webinar-status.js'
            //                 , array('jquery') );
    
            // wp_localize_script( 'care-userprofile-webinarprogress', 'care_userprofile_webinar', $this->get_data() );

            // wp_enqueue_script( 'care-userprofile-webinarprogress' );
        }
    }

    public function registerHandlers() {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc");

        //Show the data
        add_action( 'show_user_profile', array( $this, 'memberProfileFields' ), 10, 1  );
        //add_action( 'profile_personal_options', array( $this, 'webinarUserProfileFields' ), 10, 1  );
        add_action( 'edit_user_profile', array( $this, 'memberProfileFields' ), 10, 1  );
        add_action( 'user_new_form', array( $this, 'memberProfileFields' ) ); // creating a new user
        
        //Save the entered data
        add_action( 'personal_options_update', array( $this, 'memberDataProfileSave' ) );
        add_action( 'edit_user_profile_update', array( $this, 'memberDataProfileSave' ) );
        add_action( 'user_register', array( $this, 'memberDataProfileSave' ) );
    }

    public function memberProfileFields( $profileuser ) {	
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
    <h3>PASS Member Data</h3>
		<table class="form-table">
			<tr>
				<td>
                    <?php 
                        echo $this->emitJoinedMentorship( $profileuser );
                    ?>
				</td>
		</table>
    <?php
    }
    
	public function emitJoinedMentorship( $user )
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log( $loc );

		$user_id = $user->ID;
        $this->log->error_log( sprintf("%s: User id=%d and email=%s",$loc, $user_id, $user->user_email ));

        $dateJoined = get_user_meta( $user_id, self::META_KEY, true );
        if( $dateJoined == 'no' || $dateJoined == 'yes' ) {
            $joinedMentorship = $dateJoined;
        }
        elseif ($dateJoined == '') {
            $joinedMentorship = 'no';
        }
        else {
            $dateJoined = DateTime::createFromFormat("Y-m-d", $dateJoined );
            if( $dateJoined === false ) {
                $this->log->error_log( DateTime::getLastErrors(), "Error getting date joined mentorship");
                $joinedMentorship = 'no';
            }
            else {
                $joinedMentorship = 'yes';
            }
        }
        $this->log->error_log( $joinedMentorship, "$loc --> joined Mentorship" );
        $this->log->error_log( $dateJoined, "$loc --> date joined Mentorship" );


        $label = __( "Did member join the mentorship program?", CARE_TEXTDOMAIN );
        $checked = $joinedMentorship === "yes" ? "checked" : "";
        $templ = <<<EOT
            <div>
            <label for="mentorship">%s</label>
            <input id="pass_mentorship" name="pass_mentorship" type="checkbox" value="%s" %s/>
            </div>
EOT;

        $out = "<fieldset>";
        $out .= sprintf($templ, $label, $joinedMentorship, $checked);
        $out .= "</fieldset>";
        return $out;
    }

    public function memberDataProfileSave( $userId ) 
    {
        $loc = __CLASS__ . '::' . __FUNCTION__;
        $this->log->error_log("$loc: _POST:");
        $this->log->error_log( $_POST );
    
        if ( !current_user_can('edit_user', $userId)) {
            return;
        }
        
        //Get the mentorship flag
        $joinedMentorship = 'no';
        if ( !empty( $_POST['pass_mentorship'] )) {
            $joinedMentorship = 'yes'; //$_POST['pass_mentorship'];
        }
        elseif ( !empty( $_GET['pass_mentorship'] )) {
            $joinedMentorship = 'yes'; //$_GET['pass_mentorship'];
        }

        $dateJoined = '';
        if( 'yes' === $joinedMentorship ) {
            $dateJoined = (new DateTime())->format('Y-m-d');
        }

        if( 'yes' === $joinedMentorship ) {
            $meta_id = update_user_meta( $userId, self::META_KEY, $dateJoined );
            if( true === $meta_id ) {
                $mess = sprintf("Updated mentorship=%s; joined on '%s'", $joinedMentorship, $dateJoined);
            }
            elseif( is_numeric( $meta_id ) ) {
                $mess = sprintf("Added mentorship=%s; joined on '%s'. (Meta id=%d)", $joinedMentorship, $dateJoined, $meta_id );
            }
            elseif( false === $meta_id ) {
                $mess = "Mentorship was not added/updated.";
            }
        }
        else {
            delete_user_meta( $userId, self::META_KEY );
            $mess = 'deleted';
        }

        $this->log->error_log( $mess, $loc );

        return;
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
                   );
    }

    private function handleErrors( string $mess ) {
        wp_die( $mess );
    }
    
} //end of class