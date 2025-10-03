<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    Shortcodr
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete plugin data for a single site.
 */
function shortcodr_delete_single_site_data() {
    global $wpdb;
    
    // Delete plugin tables
    $tables = array(
        $wpdb->prefix . 'shortcodr_urls',
        $wpdb->prefix . 'shortcodr_campaigns',
        $wpdb->prefix . 'shortcodr_analytics',
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Delete plugin options
    delete_option('shortcodr_version');
    delete_option('shortcodr_base_pattern');
    delete_option('shortcodr_default_redirect_type');
    delete_option('shortcodr_track_analytics');
    
    // Delete any transients
    delete_transient('shortcodr_stats');
}

/**
 * Delete plugin data from all sites in a network.
 */
function shortcodr_delete_network_data() {
    global $wpdb;
    
    // Get all blog IDs
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    // Delete data for each blog
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        shortcodr_delete_single_site_data();
        restore_current_blog();
    }
    
    // Delete network-wide options if any
    delete_site_option('shortcodr_network_version');
}

// Check if this is a multisite uninstall
if (is_multisite()) {
    shortcodr_delete_network_data();
} else {
    shortcodr_delete_single_site_data();
}

// Flush rewrite rules
flush_rewrite_rules();