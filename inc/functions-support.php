<?php

$care_queued_js = '';
/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code
 */
function care_enqueue_js( $code ) {
	global $care_queued_js;

	if ( empty( $care_queued_js ) ) {
		$care_queued_js = '';
	}

	$care_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function care_emit_js() {
	global $care_queued_js;

	if ( ! empty( $care_queued_js ) ) {
		// Sanitize.
		$care_queued_js = wp_check_invalid_utf8( $care_queued_js );
		$care_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $care_queued_js );
		$care_queued_js = str_replace( "\r", '', $care_queued_js );

		$emit = "<!-- CARE Support JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $care_queued_js });\n</script>\n";

		//dump($emit,'EMIT');
		echo $emit;
		
		unset( $care_queued_js );
	}
}
add_action('wp_footer','care_emit_js');

function Care_GetCallingMethodName(){
	$e = new Exception();
	$trace = $e->getTrace(); // or use debug_trace
	//position 0 would be the line that called this function so we ignore it
	$last_call = $trace[1];
	return $last_call;
}

////////////////////////////////////////////////////////
// Function:         dump
// Inspired from:     PHP.net Contributions
// Description: Helps with php debugging

function dump(&$var, $info = FALSE)
{
	$scope = false;
	$prefix = 'unique';
	$suffix = 'value';

	if($scope) $vals = $scope;
	else $vals = $GLOBALS;

	$old = $var;
	$var = $new = $prefix . rand() . $suffix; $vname = FALSE;
	foreach($vals as $key => $val) if($val === $new) $vname = $key;
	$var = $old;

	echo "<pre style='margin: 0px 0px 10px 0px; display: block; background: white; color: black; font-family: Verdana; border: 1px solid #cccccc; padding: 5px; font-size: 10px; line-height: 13px;'>";
	if($info != FALSE) echo "<b style='color: red;'>$info:</b><br>";
	do_dump($var, '$'.$vname);
	echo "</pre>";
}

////////////////////////////////////////////////////////
// Function:         do_dump
// Inspired from:     PHP.net Contributions
// Description: Better GI than print_r or var_dump

function do_dump(&$var, $var_name = NULL, $indent = NULL, $reference = NULL)
{
	$do_dump_indent = "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
	$reference = $reference.$var_name;
	$keyvar = 'the_do_dump_recursion_protection_scheme'; $keyname = 'referenced_object_name';

	if (is_array($var) && isset($var[$keyvar]))
	{
		$real_var = &$var[$keyvar];
		$real_name = &$var[$keyname];
		$type = ucfirst(gettype($real_var));
		echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
	}
	else
	{
		$var = array($keyvar => $var, $keyname => $reference);
		$avar = &$var[$keyvar];

		$type = ucfirst(gettype($avar));
		if($type == "String") $type_color = "<span style='color:green'>";
		elseif($type == "Integer") $type_color = "<span style='color:red'>";
		elseif($type == "Double"){ $type_color = "<span style='color:#0099c5'>"; $type = "Float"; }
		elseif($type == "Boolean") $type_color = "<span style='color:#92008d'>";
		elseif($type == "NULL") $type_color = "<span style='color:black'>";

		if(is_array($avar))
		{
			$count = count($avar);
			echo "$indent" . ($var_name ? "$var_name => ":"") . "<span style='color:#a2a2a2'>$type ($count)</span><br>$indent(<br>";
			$keys = array_keys($avar);
			foreach($keys as $name)
			{
				$value = &$avar[$name];
				do_dump($value, "['$name']", $indent.$do_dump_indent, $reference);
			}
			echo "$indent)<br>";
		}
		elseif(is_object($avar))
		{
			echo "$indent$var_name <span style='color:#a2a2a2'>$type</span><br>$indent(<br>";
			foreach($avar as $name=>$value) do_dump($value, "$name", $indent.$do_dump_indent, $reference);
			echo "$indent)<br>";
		}
		elseif(is_int($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
		elseif(is_string($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color\"$avar\"</span><br>";
		elseif(is_float($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
		elseif(is_bool($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color".($avar == 1 ? "TRUE":"FALSE")."</span><br>";
		elseif(is_null($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> {$type_color}NULL</span><br>";
		else echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $avar<br>";

		$var = $var[$keyvar];
	}
}

function display_marker( $label ) {

	date_default_timezone_set( "America/Toronto" );
	$datetime = date( 'l F j, Y \a\t g:i:s a' );

	list( $usec, $sec ) = explode( " ", microtime() );

	echo "<div><span><strong>$label</strong> $datetime $usec</span></div>";
}

