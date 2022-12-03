<?php
/**
 * Plugin Name: Jorgel Custom Tax Calculator & Extra user fields
 * Version:           2.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Kaveh Movahedi
 * Author URI:        https://bluehillside.com/
 */

define( 'BHS_TAX_DIR', plugin_dir_path( __FILE__ ));
define( 'BHS_TAX_URL', plugin_dir_url( __FILE__ ));

spl_autoload_register( function ($class_name) {
    $name = explode( '\\', $class_name );
    if( $name[0] == 'BHS' ) {
        $name = $name[count($name) - 1];
        if( file_exists( BHS_TAX_DIR . '/controllers/' . $name . '.php' ) )
            require_once( BHS_TAX_DIR . '/controllers/' . $name . '.php' );
    }
});


require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );

new \BHS\Tax\Controllers\AdminController();
new \BHS\Tax\Controllers\WooController();
new \BHS\Jorgel\RegistrationController();