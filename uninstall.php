<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

function shortlinkr_delete_single_site_data() {
    global $wpdb;
    
    // Delete plugin tables
    $tables = array(
        $wpdb->prefix . 'shortlinkr_urls',
        $wpdb->prefix . 'shortlinkr_campaigns',
        $wpdb->prefix . 'shortlinkr_analytics',
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Delete plugin options
    delete_option('shortlinkr_version');
    delete_option('shortlinkr_base_pattern');
    delete_option('shortlinkr_default_redirect_type');
    delete_option('shortlinkr_track_analytics');
    
    // Delete any transients
    delete_transient('shortlinkr_stats');
}

function shortlinkr_delete_network_data() {
    global $wpdb;
    
    // Get all blog IDs
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    
    // Delete data for each blog
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        shortlinkr_delete_single_site_data();
        restore_current_blog();
    }
    
    // Delete network-wide options if any
    delete_site_option('shortlinkr_network_version');
}

if (is_multisite()) {
    shortlinkr_delete_network_data();
} else {
    shortlinkr_delete_single_site_data();
}

flush_rewrite_rules();