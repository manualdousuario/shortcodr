<?php

class shortlinkr_Activator {

    public static function activate($network_wide = false) {
        
        if (is_multisite() && $network_wide) {
            global $wpdb;
            
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
            
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::activate_single_site();
                restore_current_blog();
            }
        } else {
            self::activate_single_site();
        }
    }
    
    public static function activate_single_site() {
        self::create_tables();
        self::set_default_options();
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $campaigns_table = $wpdb->prefix . 'shortlinkr_campaigns';
        $campaigns_sql = "CREATE TABLE $campaigns_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active', 'inactive') DEFAULT 'active',
            PRIMARY KEY (id),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        $urls_table = $wpdb->prefix . 'shortlinkr_urls';
        $urls_sql = "CREATE TABLE $urls_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slug varchar(255) NOT NULL,
            target_url text NOT NULL,
            campaign_id bigint(20),
            redirect_type int(3) DEFAULT 301,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active', 'inactive') DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY campaign_id (campaign_id),
            KEY created_by (created_by),
            KEY status (status)
        ) $charset_collate;";
        
        $analytics_table = $wpdb->prefix . 'shortlinkr_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url_id bigint(20) NOT NULL,
            view_date date NOT NULL,
            view_count int(11) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY unique_url_date (url_id, view_date),
            KEY url_id (url_id),
            KEY view_date (view_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($campaigns_sql);
        dbDelta($urls_sql);
        dbDelta($analytics_sql);
    }
    
    private static function set_default_options() {
        if (!get_option('shortlinkr_base_url_pattern')) {
            add_option('shortlinkr_base_url_pattern', '/go/');
        }
        
        if (!get_option('shortlinkr_default_redirect_type')) {
            add_option('shortlinkr_default_redirect_type', '301');
        }
        
        if (!get_option('shortlinkr_user_capabilities')) {
            add_option('shortlinkr_user_capabilities', array('administrator'));
        }
        
        add_option('SHORTLINKR_VERSION', SHORTLINKR_VERSION);
    }
}