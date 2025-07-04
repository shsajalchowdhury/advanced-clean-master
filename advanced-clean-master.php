<?php
/*
Plugin Name: Advanced Clean Master - Complete Site Cleanup & Database Optimizer
Description: A plugin to clean and optimize your WordPress site by removing drafts, auto-drafts, trashed posts, orphaned media, and more.
Version: 1.0.8
Author: SH Sajal Chowdhury
Author URI: https://easywptools.com
Requires at least: 5.4
Requires PHP: 7.2
Text Domain: advanced-clean-master
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit( esc_html__( 'Direct access is not allowed.', 'advanced-clean-master' ) );
}

// Define plugin constants
define( 'ACMT_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACMT_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once ACMT_PATH . 'includes/class-cleanup-tool.php';
require_once ACMT_PATH . 'includes/class-review-notice.php';

// Initialize the plugin
function acmt_init() {
    // Initialize main plugin
    $plugin = new ACMT_Cleanup();
    $plugin->init();

    // Initialize review notice
    if (class_exists('ACMT_Review_Notice')) {
        new ACMT_Review_Notice();
    }
}

function acmt_enqueue_admin_styles() {
    wp_enqueue_style('acmt-review-notice', ACMT_URL . 'assets/css/review-notice.css', array(), '1.0.8');
}
add_action( 'plugins_loaded', 'acmt_init' );
add_action('admin_enqueue_scripts', 'acmt_enqueue_admin_styles');

// Activation Hook - Create Logs Table
register_activation_hook( __FILE__, 'acmt_activate' );
function acmt_activate() { 
    global $wpdb;
    $table_name = $wpdb->prefix . 'acmt_logs'; // Shortened table prefix
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        cleanup_type varchar(100) NOT NULL,
        cleaned_count int(11) NOT NULL,
        cleaned_on datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Error handling
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) { //phpcs:ignore
        wp_die( esc_html__( 'Failed to create database table for Advanced Clean Master plugin.', 'advanced-clean-master' ) );
    }
}

// Deactivation Hook - Clear Scheduled Events
register_deactivation_hook( __FILE__, 'acmt_deactivate' );
function acmt_deactivate() {
    wp_clear_scheduled_hook( 'acmt_daily_event' );
    wp_clear_scheduled_hook( 'acmt_weekly_event' );
}
