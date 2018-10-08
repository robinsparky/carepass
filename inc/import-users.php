<?php
include 'wp-blog-header.php';
include 'wp-includes/registration.php';
include 'wp-includes/pluggable.php';
ini_set("memory_limit","1024M");
ini_set("max_execution_time", "240");
global $wpdb;
?>
 
<h1>Import Users</h1>
 
<?php
$connection = mysql_connect("localhost", "robin", "ras/5381") or die("Unable to connect to MySQL");
mysql_select_db("dev_care4nurses_ca", $connection) or die("Unable to connect to the database");
$result = mysql_query("SELECT * FROM source_users ;");
    while ($row = mysql_fetch_object($result)) {
        echo "<strong>ID:</strong>".$row->id." <strong>login:</strong>".$row->user_name." <strong>password:</strong> ".$row->password." <strong>e-mail:</strong>".$row->email_address." <strong>name:</strong> ".$row->name." <strong>surname:</strong> ".$row->surname."<br/>";
        // Add the ID to trick WP
        $add_id = "INSERT INTO ".$wpdb->users." (id, user_login) VALUES (".$row->id.",'"."$row->user_name"."' ); ";
         mysql_query($add_id) or die(mysql_error());
        // Add the rest
        $userdata = array(
         'ID' => $row->id,
         'user_login' => $row->user_name,
         'user_pass' => wp_hash_password($row->password),
         'user_nicename' => $row->user_name,
         'user_email' => $row->email_address,
         'first_name'  => $row->name,
         'last_name'  => $row->surname,
         'role' => 'subscriber'
        );
        wp_insert_user($userdata) ;
    }
mysql_close($connection);