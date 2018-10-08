<h1> Options for <?php bloginfo('name');?></h1>
<?php settings_errors(); ?>
<form method="post" action="options.php">
    <?php settings_fields('care-settings-group'); ?>
    <?php do_settings_sections('caremci'); ?>
    <?php submit_button(
        //  '' // some text
        // ,'' // type
        // ,'' // name
        // ,'' // wrap
        // ,'' // other attributes
    ); ?>
</form>