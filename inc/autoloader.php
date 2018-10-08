
<?php
/**
* Simple autoloader, so we don't need Composer just for this.
*/
class Autoloader
{
    public static function register()
    {
        spl_autoload_register( function ( $class ) {
                $class_filename = __DIR__ . "\\class-$class" . ".php";
                $file = str_replace('\\', DIRECTORY_SEPARATOR, $class_filename);
                if ( file_exists( $file ) ) {
                    //error_log( "Care Register Data class - loading: $class_filename" );
                    require $file;
                    return true;
                }
                else {
                    return false; 
                }
        } );
    }
}
Autoloader::register();