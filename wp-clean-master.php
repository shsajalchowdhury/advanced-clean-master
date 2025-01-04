<?php
/*
Plugin Name: Simple Cleanup Tool
Description: A plugin to clean and optimize your WordPress site by removing drafts, trashed posts, orphaned media, and more.
Version: 1.0.0
Author: SH Sajal Chowdhury
Author URI: https://easywptools.com
Text Domain: wp-clean-master
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'WP_CLEAN_MASTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_CLEAN_MASTER_URL', plugin_dir_url( __FILE__ ) );

// Include the main class
require_once WP_CLEAN_MASTER_PATH . 'includes/class-cleanup-tool.php';

// Initialize the plugin
function wp_clean_master_init() {
    $plugin = new WP_Clean_Master();
    $plugin->init();
}
add_action( 'plugins_loaded', 'wp_clean_master_init' );

// Activation Hook - Create Logs Table
register_activation_hook( __FILE__, 'wp_clean_master_activate' );
function wp_clean_master_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'clean_master_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cleanup_type varchar(100) NOT NULL,
        cleaned_count int(11) NOT NULL,
        cleaned_on datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
