<?php
/*
Plugin Name: Ancaster Tennis Club Administration Plugin
Plugin URI: ancastertennis.ca
Description: Administrative tasks such as emailing the membership, viewing stats.
Version: 1.0
Author: Robin Smith
Author URI: ancastertennis.ca
*/

/*  Copyright 2016  Ancaster Tennis Club  (email : robin.sparky@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
$atc_memberTypes = array();

// Call function when plugin is activated
register_activation_hook(__FILE__, 'atc_adminInstall');

register_deactivation_hook( __FILE__, 'atc_admin_deactivate' );

//Use this to add other actions
// because at this point WP is ready to go
add_action('plugins_loaded', 'atc_admin_dostuff');

// Action hook to initialize the plugin
add_action('admin_init', 'atc_adminInit');
add_action('template_redirect', 'atc_admin_setup');

// Ajax handlers
// 1. Email
if(is_admin()) {
	add_action('wp_ajax_atc_admin_email', 'atc_admin_emailhandler');
	add_action('wp_ajax_nopriv_atc_admin', 'atc_general_ajaxhandler');
}

/*
 * Enqueue scripts and css
 * Add javascript "globals" that are needed 
 * by this plugin's javascript
 */
function atc_admin_setup() {

	// guess current plugin directory URL
	$plugin_url = plugin_dir_url(__FILE__);
	wp_enqueue_script( 'atc_admin', $plugin_url . 'js/atc-admin.js', array('jquery') );
	wp_enqueue_style('atc_admin',$plugin_url . 'css/atc-admin.css');
	//Get current page protocol 
	$protocol = isset( $_SERVER["HTTPS"] ) ? 'https://' : 'http://';
	$params = array( 'ajaxurl' => admin_url( 'admin-ajax.php', $protocol ) ); 
	wp_localize_script( 'atc_admin', 'atc_admin', $params );
}



function atc_admin_emailhandler() {

	$errobj = new WP_Error();
	$errcode = 0;
	$sendto = array();

	//Must have more privileges than a subscriber
	//TODO: need to find a better check of privileges
	if ( current_user_can( 'Administrator' ) ) {
		$errobj->add($errcode++,'You are not authorized to use this function.');
		wp_send_json_error($errobj);
		exit;
	}
	
	//Get the email addresses
	if ( !empty( $_REQUEST['sendto'] )) {
		$addresses = $_REQUEST['sendto'];
	}
	else {
		$errobj->add($errcode++,'Who do I send this email to?');
	}
	
	//Get the email subject
	if ( !empty( $_REQUEST['subject'] )) {
		$subject = $_REQUEST['subject'];
	}
    else {
		$errobj->add($errcode++,'I need a subject for this email.');
    }
    
    //Get the email messsage
	if ( !empty( $_REQUEST['message'] )) {
		$message = $_REQUEST['message'];
	}
    else {
		$errobj->add($errcode++,'I need a message for this email.');
    }
    if(count($errobj->errors) > 0) {
    	wp_send_json_error($errobj);
    	exit;
    }

    //No errors so far...
    $addr = explode(',',$addresses);
    $ct = count($addr);
    for($i=0; $i < $ct; ++$i) {
    	$addr[$i] = ltrim(rtrim($addr[$i]));
    }
    $who = array();
    $memberTypes = getMemberTypes();
    //$strMemberTypes = implode(",",$memberTypes);
    //$test = implode(",",$addr);
    //$errobj->add($errcode++,"Searching for $ct Member Type(s): $test in $strMemberTypes");
    foreach($addr as $idx => $mt) {
        //$errobj->add($errcode++,"Searching for Member Type: $mt at $idx");
    	if(in_array( $mt, $memberTypes)){ 		
            //$errobj->add($errcode++,"Found member type: $mt at index $idx");
    		if(!in_array($mt, $who)) {
	    		$who[] = $mt; 
    		}
    		unset($addr[$idx]); //remove membertypes from array of email addresses
    	}
    }

    unset($mt);  //get rid of the dangling $mt
    $addr = array_values($addr); // reindex the array
    
    //Get the email addresses related to the member types
    $memtypeaddr = getEmailAddressesByMemberType($who);
    if(is_wp_error($memtypeaddr)) {
    	wp_send_json_error($memtypeaddr);
    	exit;
    }
    
    //TODO Allow new member email addresses
    $newmemaddr = array();
    $includeNewMems = false;
    if($includeNewMems) {
	    $newmemaddr = getEmailAddressesForNewMembers();
	    if(is_wp_error($newmemaddr)) {
	    	wp_send_json_error($newmemaddr);
	    	exit;
	    }
    }

    //Merge the membertype addresses with the other real email addresses
    $sendto = array_merge($addr, $memtypeaddr, $newmemaddr);
    /*
	$allEmails = implode(",",$sendto);   		
    $errobj->add($errcode++,"Final list of emails: $allEmails");
    */
    if(count($errobj->errors) > 0) {
    	wp_send_json_error($errobj);
    	exit;
    }
    
    //Attempt sending the emails
	$res = atc_sendmail( $sendto, $subject, $message );
	//$res = 1; 
	if(is_wp_error($res)) {
		wp_send_json_error($res);
		exit;
	}
	elseif(!$res) {
		$errobj = new WP_Error();
		$errobj->add($errcode++, "Something went wrong in WP emailer! To=$sendto[0] Subject=$subject");
		wp_send_json_error($errobj);
		exit;
	}

	$ct = count($sendto);
	$response = array();

	$word = 'email was';
	if($ct > 1) {
		$word = 'emails were';
	}
    $response['result'] = "Your $ct $word sent.";
    wp_send_json_success( $response );
    exit();
}

function atc_general_ajaxhandler(){
    //ob_clean();
	$errobj = new WP_Error();
	$errobj->add(0, "You are not authorized to use this function!!!!!!!!!");
	wp_send_json_error( $errobj );

    exit();
}


// Action hook to create the administration shortcode
add_shortcode('atc-admin', 'atc_adminShortcode');

//Use this to add other actions 
// because at this point WP is ready to go
function atc_admin_dostuff() {
	//add other actions here
}

function atc_adminInstall() {
    if ( version_compare( get_bloginfo( 'version' ), '4.4', '<' ) ) { 
	    // Deactivate our plugin
		deactivate_plugins( basename( __FILE__ ) ); 
    }
    
    atc_admin_rewrite();
	flush_rewrite_rules();
    
    //setup our default option values
    $admin_options_arr=array();

    //save our default option values
    update_option('atc-admin-options', $admin_options_arr);
}

//De-activation
function atc_admin_deactivate() {
	
	flush_rewrite_rules();
	// Delete option from options table
	//delete_option( 'atc_cr_options' );
}

//Initialize atc administration
function atc_adminInit()
{
	//atc_admin_rewrite();
}

// Add the atcadmin var so that WP recognizes it 
//NOTE: this should not be necessary as 'add_rewrite_tag' should do this
//add_filter( 'query_vars', 'atc_add_query_var' ); 
function atc_add_query_var( $vars ) { 
	$vars[] = 'tennisadmin'; 
	return $vars;
}


function atc_admin_rewrite()
{
	//Setup rewrite tag and permalink structure
	add_rewrite_tag( '%tennisadmin%', '(stats)' );
	add_permastruct( 'tennisadmin', 'atcadmin' . '/%tennisadmin%' );
	
}

//create shortcode
function atc_adminShortcode($atts, $content = null)
{
    global $post;
    if (!is_user_logged_in() || (is_user_logged_in() && current_user_can( 'subscriber' )) ) {
    	$out = errorOut();
    	return $out;
    }
    
    $myshorts = shortcode_atts(array("showstats" => 1, "email" => 0),$atts,'atc-admin');
    //print_r($atts);
    extract($myshorts);
    //print_r($myshorts);

    //load options array
    $admin_options = get_option('atc-admin-options');
    if(!($showstats | $email)) {
    	$showstats = 1;
    }
    
    $out = "";
    if($showstats) {
    	$out = includeStats();
    }
    if($email) {
    	$out .= includeEmail();
    }
    $out .= "<div><span id='atc-resultmessage'></span></div>";
    
    return $out;
}

function atc_admin_display() {
	global $wp_rewrite;
	global $wp_version;
	global $wp_db_version;
	global $wp;
	global $_REQUEST, $_SERVER, $_GET, $_POST;
	display_my_datetime('Template Redirect');
	$func = get_query_var('tennisadmin');
	//echo '<p>WP Version=' . $wp_version . '</p>';
	//echo '<p>DB Version=' . $wp_db_version . '</p>';
	echo '<p>func=' . $func . '</p>';
	$src = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'Unknown REQUEST_URI';
	echo "<p>REQUEST_URI='$src'</p>";
	$src = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : 'Unknown PATH_INFO';
	echo "<p>PATH_INFO='$src'</p>";
	$src = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'Unknown PHP_SELF';
	echo "<p>PHP_SELF='$src'</p>";

	echo "<p>Rewrite Permalink Structure</p><p>";
	print_r($wp_rewrite->permalink_structure);
	echo "</p>";
	
	echo "<p>Rewrite Rules</p><p>";
	print_r($wp_rewrite->rules);
	echo "</p>";

	$out = "";
	switch($func) {
		case 'stats':
		    $out = includeStats();
		    echo $out;
			exit;
		case 'email':
			$out = includeEmailProcessing();
			echo $out;
			exit;
		default:
			break;
	}
}

function includeEmail() {
    global $wpdb;
    
    $atc_memberTypes = getMemberTypes();
	$actionUrl = plugin_dir_url(__FILE__);
    $out = "<hr><h2>ATC Email</h2>"
    	  ."<form id='atc-email' action=$actionUrl>"
    	  ."<label>To: <input id='atc-addresses' list='emaildata' type='text' placeholder='Addresses' size='50' maxlength='240'></label>";
    $out .= "<datalist id='emaildata'>";
    $tmpl = '<option value="%s" %s>%s</option>';
    $out .= sprintf($tmpl,"ALL","selected","all");
    foreach ($atc_memberTypes as $mt) {
    	if($mt == '0') continue;
    	$out .= sprintf($tmpl,$mt,"",$mt);
    }
    $out .= "</datalist>";
    $out .= "<br>";
    $out .= "<label> Enter Subject: <input class='atc-subject' type='text' size='50' maxlength='240' placeholder='Subject'></label>";
    $out .= "<br>";
    $out .= "<label> Enter Message: <textarea class='atc-message' name='message' rows=5 cols=100 ></textarea></label>";
    $out .= "<br>";
    $out .= "<button id='sendmail' name='sendmail' type='submit'>Send</button> ";
    $out .= "<button type='reset'>Reset</button>";
    $out .= "</form>";	
    return $out; 
}

function includeStats() {
    global $wpdb;
    $users = $wpdb->get_results( "select case  when m.`meta_value` > 7 then 'Administrator'
    		when m.`meta_value` > 2 and m.`meta_value` <= 7 then 'Editor'
    		when m.`meta_value` = 2 then 'Author'
    		when m.`meta_value` = 1 then 'Contributor'
    		else 'Subscriber' end as 'Role', count(m.`meta_value`) as 'Count' from $wpdb->users u"
    		." inner join $wpdb->usermeta m"
    		." on u.`ID` = m.`user_id`"
    		." and m.`meta_key` = 'wp_user_level'"
    		." group by m.`meta_value`"
    		." order by m.`meta_value` DESC;", ARRAY_A );
    
    $members = $wpdb->get_results( "select `type` as 'Member Type', count(`type`) as 'Count'"
    		." from `memberships_members` "
    		." group by `type`"
    		." order by `type`", ARRAY_A);
    
    
    /* New Member Count*/
    $newmems = $wpdb->get_var("select count(*) from memberships_members thisyr "
    		." where lower(concat(trim(thisyr.last_name),trim(thisyr.first_name)))"
    		." NOT IN (select lower(concat(trim(last_name),trim(first_name))) from memberships_members_2015)");
    
    /* Returning Member Count */
    $oldmems = $wpdb->get_var("select count(*) from memberships_members thisyr "
    		." inner join memberships_members_2015 lastyr "
    		." on (lower(concat(trim(thisyr.last_name),trim(thisyr.first_name))) = lower(concat(trim(lastyr.last_name),trim(lastyr.first_name))))");
    $totmems = 0;
    date_default_timezone_set("America/Toronto");
    $datetime = date('l F j, Y g:i:s a');
    
    $out = "<h2 class='atc-stats'>ATC Statistics</h2>
	<table class='atc-user-stats'>
	<caption>Users</caption>
	<tr><th>Role</th><th>Count</th></tr>";

    foreach($users as $row) {
    	$role = $row['Role'];
    	$count = $row['Count'];
    	$out .= "<tr class='atc-user-role'><td>$role</td><td>$count</td></tr>";
    } //foreach
    $out .= "
	</table>
	<br>
	<table class='atc-member-stats'>
	<caption>Members</caption>
	<tr><th>Member Type</th><th>Count</th></tr>";

    foreach($members as $row) {
    	$role = $row['Member Type'];
    	$count = $row['Count'];
    	$totmems += $count;
    	$out .= "<tr class='atc-member-role'><td>$role</td><td>$count</td></tr>";
    } //foreach
    $out .= "<tr class='atc-member-role'><td>Total Members</td><td>$totmems</td></tr>";
    $out .= "<tr class='atc-member-role'><td>New Members</td><td>$newmems</td></tr>";
    $out .= "<tr class='atc-member-role'><td>Returning Members</td><td>$oldmems</td></tr>";

    $out .= "</table>
    <div><span>These statistics are as of $datetime</span></div>";

    return $out;
}

function errorOut() {
	$out = "<div><span class='atc-error'>";
	$out .= "You are not authorized for admininistration activities.";
	$out .= "</span></div>";
	return $out;
}

function atc_sendmail( $sendto, $subject, $message ) {

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	$num = 1;
	if(is_array($sendto)) {
	  $num = count($sendto);
	  $addressees = implode(",",$sendto);
	}
	// Additional headers
	$headers .= 'To: Robin Smith <robin.sparky@gmail.com>' . "\r\n";
	//$headers .= 'From: info <info@care4nurses.org>' . "\r\n";

	$sent_message = false;
	try {
		// send message using wp_mail function.
		//throw new Exception("On Purpose");
		$sent_message = wp_mail( $sendto, $subject, $message, $headers );
		//$sent_message = true;
	}
	catch(Exception $ex) {
		$data = "Headers=$headers; Count=$num; To=$addressees; Subject=$subject; Message=$message";
		$err = new WP_Error('send',$data,$ex->getMessage());
		return $err;
	}

	return $sent_message;
}

// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
//remove_filter( 'wp_mail_content_type', 'atc_set_html_content_type' );

function atc_set_html_content_type() {
	return 'text/html';
}

function getEmailAddressesByMemberType($arrTypes) {
	global $wpdb;
	$emails = array();

	if(is_array($arrTypes) && count($arrTypes) > 0) {
		$wantall = false;
		foreach($arrTypes as $mt) {
			$mt = strtoupper($mt);
			if($mt === 'ALL') {
				$wantall = true;
				break;
			}
		}
		$where = "";
		$sql = "";
		try {
			if($wantall) {
				$emails = $wpdb->get_col("select email from `memberdata`");
			}
			else {
				foreach($arrTypes as $mt) {
					$where .= "'$mt',";
				}
				$where = '(' . rtrim($where,",") . ')';
				$sql = "select email from `memberdata` where `member_type` in $where";
				$emails = $wpdb->get_col($sql,0);
			}
		}
		catch(Exception $ex) {
			$errobj = new WP_Error('emails',"Cannot find emails in db using: $sql",$ex->getMessage());
			return $errobj;
		}
	}
	
	return $emails;
}

function getEmailAddressesForNewMembers() {
	global $wpdb;

	$newmems = array();
	try {
	/* New Member Email Addresses*/	
	$newmems = $wpdb->get_col("select concat(thisyr.first_name, ' ', thisyr.last_name, ' <',u.user_email,'>') from memberships_members thisyr "
                             ." inner join wp_users u"
                             ." on thisyr.ID = u.ID"
                             ." where lower(concat(trim(thisyr.last_name),trim(thisyr.first_name)))"
                             ." NOT IN (select lower(concat(trim(last_name),trim(first_name))) from memberships_members_2015)",0);
	}
	catch(Exception $ex) {
		$errobj = new WP_Error('emails',"Cannot find new memeber emails in db using: $sql",$ex->getMessage());
		return $errobj;
	}
	return $newmems;
}

function getMemberTypes() {
	global $wpdb;
	$atc_memberType = array();
	$mt = $wpdb->get_results( "select `member_type` "
			." from `memberdata` "
			." group by `member_type`"
			." order by `member_type`", ARRAY_A);
	foreach($mt as $row) {
		$atc_memberTypes[] = $row['member_type'];
	}
	return $atc_memberTypes;
}
/*
 add_action('wp_loaded','catch_wp_loaded');
 function catch_wp_loaded() {
 display_my_datetime('WP loaded');
 }

 add_action('wp','catch_wp');
 function catch_wp($wp) {
 display_my_datetime('WP');
 }

 add_action('parse_request','catch_parse_request');
 function catch_parse_request($wp) {
 display_my_datetime('Parse Request');
 if($wp->did_permalink) {
 print_r($wp->request);
 echo "<p>Matched Rule</p><p>";
 print_r($wp->matched_rule);
 echo "</p>";
 echo "<p>Matched Query</p><p>";
 print_r($wp->matched_query);
 echo "</p>";
 echo "<p>Query Vars</p><p>";
 print_r($wp->queryvars);
 echo "</p>";
 }
 else {
 echo "<p>No PermaLinks!</p>";
 }
 }

 add_action('send_headers','catch_send_headers');
 function catch_send_headers($wp) {
 display_my_datetime('Send Headers');
 }

 add_filter('redirect_canonical', 'atcadmin_redirect_canonical', 10, 2);
 function atcadmin_redirect_canonical($redirect_url, $requested_url) {
 display_my_datetime('Redirect Canonical');
 echo "<p>Redirect Url='$redirect_url'</p>";
 echo "<p>Requested Url='$requested_url'</p>";

 //return $redirect_url;
 }

 function display_my_datetime($label) {

 date_default_timezone_set("America/Toronto");
 $datetime = date('l F j, Y \a\t g:i:s a');

 list($usec, $sec) = explode(" ", microtime());

 echo "<div><span><strong>$label</strong> $datetime $usec</span></div>";
 }
 */
