<?php

class shortlinkr_Deactivator {

    public static function deactivate($network_wide = false) {
        
        if (is_multisite() && $network_wide) {
            global $wpdb;
            
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
            
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::deactivate_single_site();
                restore_current_blog();
            }
        } else {
            self::deactivate_single_site();
        }
    }
    
    public static function deactivate_single_site() {
        flush_rewrite_rules();
    }
}