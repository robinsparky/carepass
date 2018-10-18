<?php
/**
 * This file could be used to catch submitted form data. When using a non-configuration
 * view to save form data, remember to use some kind of identifying field in your form.
 */
    $startingDate = ( isset( $_POST['starting_date'] ) ) ? stripslashes( $_POST['starting_date'] ) : '';
    self::update_dashboard_widget_options(
            self::wid,                                  //The  widget id
            array(                                      //Associative array of options & default values
                'starting_date' => $startingDate,
            )
    );
?>
<label for="startDate">Enter starting date in Y-m-d format</label>
<input id="startDate" type="date" name="starting_date" />