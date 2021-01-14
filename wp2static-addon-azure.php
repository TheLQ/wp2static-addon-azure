<?php

/**
 * Plugin Name:       WP2Static Add-on: Azure
 * Plugin URI:        https://wp2static.com
 * Description:       Microsoft Azure Cloud Storage as a deployment option for WP2Static.
 * Version:           0.2
 * Author:            Leon Stafford
 * Author URI:        https://leonstafford.github.io
 * License:           Unlicense
 * License URI:       http://unlicense.org
 * Text Domain:       wp2static-addon-azure
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WP2STATIC_AZURE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP2STATIC_AZURE_VERSION', '0.2' );

if ( file_exists( WP2STATIC_AZURE_PATH . 'vendor/autoload.php' ) ) {
    require_once WP2STATIC_AZURE_PATH . 'vendor/autoload.php';
}

function run_wp2static_addon_azure() {
    $controller = new WP2StaticAzure\Controller();
    $controller->run();
}

register_activation_hook(
    __FILE__,
    [ 'WP2StaticAzure\Controller', 'activate' ]
);

register_deactivation_hook(
    __FILE__,
    [ 'WP2StaticAzure\Controller', 'deactivate' ]
);

run_wp2static_addon_azure();
