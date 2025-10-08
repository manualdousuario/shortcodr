<?php

class shortlinkr_Database {

    public static function get_campaigns($status = 'active') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_campaigns';
        
        if ($status === 'all') {
            $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
        } else {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE status = %s ORDER BY name ASC", $status));
        }
        
        return $results;
    }

    public static function get_campaign($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_campaigns';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        
        return $result;
    }

    public static function create_campaign($name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_campaigns';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => sanitize_text_field($name),
                'created_by' => get_current_user_id(),
            ),
            array('%s', '%d')
        );
        
        if ($result !== false) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    public static function update_campaign($id, $name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_campaigns';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'name' => sanitize_text_field($name),
            ),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }

    public static function delete_campaign($id) {
        global $wpdb;
        
        $campaigns_table = $wpdb->prefix . 'shortlinkr_campaigns';
        $urls_table = $wpdb->prefix . 'shortlinkr_urls';
        
        $url_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $urls_table WHERE campaign_id = %d", $id));
        
        if ($url_count > 0) {
            $wpdb->update(
                $urls_table,
                array('campaign_id' => null),
                array('campaign_id' => $id),
                array('%s'),
                array('%d')
            );
        }
        
        $result = $wpdb->delete($campaigns_table, array('id' => $id), array('%d'));
        
        return $result !== false;
    }

    public static function get_urls($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'search' => '',
            'campaign_id' => '',
            'status' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $urls_table = $wpdb->prefix . 'shortlinkr_urls';
        $campaigns_table = $wpdb->prefix . 'shortlinkr_campaigns';
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($args['search'])) {
            $where_conditions[] = "(u.slug LIKE %s OR u.target_url LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($args['campaign_id'])) {
            $where_conditions[] = "u.campaign_id = %d";
            $where_values[] = $args['campaign_id'];
        }
        
        if (!empty($args['status'])) {
            $where_conditions[] = "u.status = %s";
            $where_values[] = $args['status'];
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT u.*, c.name as campaign_name
                FROM $urls_table u
                LEFT JOIN $campaigns_table c ON u.campaign_id = c.id
                $where_clause
                ORDER BY u.{$args['orderby']} {$args['order']}
                LIMIT %d OFFSET %d";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        if (!empty($where_values)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            $results = $wpdb->get_results($sql);
        }
        
        return $results;
    }

    public static function get_urls_count($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'search' => '',
            'campaign_id' => '',
            'status' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $urls_table = $wpdb->prefix . 'shortlinkr_urls';
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($args['search'])) {
            $where_conditions[] = "(slug LIKE %s OR target_url LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($args['campaign_id'])) {
            $where_conditions[] = "campaign_id = %d";
            $where_values[] = $args['campaign_id'];
        }
        
        if (!empty($args['status'])) {
            $where_conditions[] = "status = %s";
            $where_values[] = $args['status'];
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT COUNT(*) FROM $urls_table $where_clause";
        
        if (!empty($where_values)) {
            $count = $wpdb->get_var($wpdb->prepare($sql, $where_values));
        } else {
            $count = $wpdb->get_var($sql);
        }
        
        return intval($count);
    }

    public static function get_url($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_urls';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        
        return $result;
    }

    public static function get_url_by_slug($slug) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_urls';
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s AND status = 'active'", $slug));
        
        return $result;
    }

    public static function create_url($slug, $target_url, $campaign_id = null, $redirect_type = 301) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_urls';
        
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE slug = %s", $slug));
        if ($existing) {
            return false;
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'slug' => sanitize_text_field($slug),
                'target_url' => esc_url_raw($target_url),
                'campaign_id' => $campaign_id ? intval($campaign_id) : null,
                'redirect_type' => intval($redirect_type),
                'created_by' => get_current_user_id(),
            ),
            array('%s', '%s', '%d', '%d', '%d')
        );
        
        if ($result !== false) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    public static function update_url($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_urls';
        
        $allowed_fields = array('slug', 'target_url', 'campaign_id', 'redirect_type', 'status');
        $update_data = array();
        $format = array();
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                if ($field === 'slug') {
                    $update_data[$field] = sanitize_text_field($value);
                    $format[] = '%s';
                } elseif ($field === 'target_url') {
                    $update_data[$field] = esc_url_raw($value);
                    $format[] = '%s';
                } elseif ($field === 'status') {
                    $update_data[$field] = sanitize_text_field($value);
                    $format[] = '%s';
                } else {
                    $update_data[$field] = intval($value);
                    $format[] = '%d';
                }
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }

    public static function delete_url($id) {
        global $wpdb;
        
        $urls_table = $wpdb->prefix . 'shortlinkr_urls';
        $analytics_table = $wpdb->prefix . 'shortlinkr_analytics';
        
        $wpdb->delete($analytics_table, array('url_id' => $id), array('%d'));
        
        $result = $wpdb->delete($urls_table, array('id' => $id), array('%d'));
        
        return $result !== false;
    }

    public static function get_url_analytics($url_id, $days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_analytics';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT view_date, view_count 
             FROM $table_name 
             WHERE url_id = %d 
             AND view_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             ORDER BY view_date DESC",
            $url_id,
            $days
        ));
        
        return $results;
    }

    public static function get_url_total_views($url_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_analytics';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(view_count) FROM $table_name WHERE url_id = %d",
            $url_id
        ));
        
        return intval($total);
    }

    public static function record_view($url_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_analytics';
        $today = current_time('Y-m-d');
        
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_name (url_id, view_date, view_count) 
             VALUES (%d, %s, 1) 
             ON DUPLICATE KEY UPDATE view_count = view_count + 1",
            $url_id,
            $today
        ));
        
        return $result !== false;
    }

    public static function generate_random_slug($length = 6) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_urls';
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        do {
            $slug = '';
            for ($i = 0; $i < $length; $i++) {
                $slug .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE slug = %s", $slug));
        } while ($exists);
        
        return $slug;
    }

    public static function export_urls_to_json() {
        global $wpdb;
        
        $urls_table = $wpdb->prefix . 'shortlinkr_urls';
        $campaigns_table = $wpdb->prefix . 'shortlinkr_campaigns';
        
        $urls = $wpdb->get_results(
            "SELECT u.slug, u.target_url, u.redirect_type, u.status, c.name as campaign_name
             FROM $urls_table u
             LEFT JOIN $campaigns_table c ON u.campaign_id = c.id
             ORDER BY u.created_at ASC"
        );
        
        $export_data = array(
            'version' => SHORTLINKR_VERSION,
            'exported_at' => current_time('mysql'),
            'site_url' => get_site_url(),
            'urls' => $urls
        );
        
        return json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function import_urls_from_json($json_data, $skip_duplicates = true) {
        $data = json_decode($json_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => __('Invalid JSON format', 'shortlinkr'),
                'imported' => 0,
                'skipped' => 0,
                'errors' => 0
            );
        }
        
        if (!isset($data['urls']) || !is_array($data['urls'])) {
            return array(
                'success' => false,
                'error' => __('Invalid data structure', 'shortlinkr'),
                'imported' => 0,
                'skipped' => 0,
                'errors' => 0
            );
        }
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($data['urls'] as $url) {
            $campaign_id = null;
            if (!empty($url['campaign_name'])) {
                $campaign_id = self::get_campaign_id_by_name($url['campaign_name']);
            }
            
            $result = self::create_url(
                $url['slug'],
                $url['target_url'],
                $campaign_id,
                isset($url['redirect_type']) ? $url['redirect_type'] : 301
            );
            
            if ($result) {
                $imported++;
                
                if (isset($url['status']) && $url['status'] !== 'active') {
                    self::update_url($result, array('status' => $url['status']));
                }
            } else {
                if ($skip_duplicates) {
                    $skipped++;
                } else {
                    $errors++;
                }
            }
        }
        
        return array(
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }

    public static function import_urls_from_csv($csv_data, $skip_duplicates = true) {
        $lines = explode("\n", $csv_data);
        
        if (empty($lines)) {
            return array(
                'success' => false,
                'error' => __('Empty CSV file', 'shortlinkr'),
                'imported' => 0,
                'skipped' => 0,
                'errors' => 0
            );
        }
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $first_line = true;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            if ($first_line) {
                $first_line = false;
                if (stripos($line, 'slug') !== false && stripos($line, 'url') !== false) {
                    continue;
                }
            }
            
            $data = str_getcsv($line);
            
            if (count($data) < 2) {
                $errors++;
                continue;
            }
            
            $slug = sanitize_text_field(trim($data[0]));
            $target_url = esc_url_raw(trim($data[1]));
            
            if (empty($slug) || empty($target_url)) {
                $errors++;
                continue;
            }
            
            $result = self::create_url($slug, $target_url);
            
            if ($result) {
                $imported++;
            } else {
                if ($skip_duplicates) {
                    $skipped++;
                } else {
                    $errors++;
                }
            }
        }
        
        return array(
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }

    private static function get_campaign_id_by_name($name) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'shortlinkr_campaigns';
        $id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE name = %s LIMIT 1",
            $name
        ));
        
        if (!$id) {
            $id = self::create_campaign($name);
        }
        
        return $id;
    }

    public static function validate_base_pattern($pattern) {
        $pattern = trim($pattern, '/');
        
        if (empty($pattern)) {
            return array(
                'valid' => false,
                'message' => __('Base pattern cannot be empty', 'shortlinkr')
            );
        }
        
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $pattern)) {
            return array(
                'valid' => false,
                'message' => __('Base pattern can only contain letters, numbers, hyphens and underscores', 'shortlinkr')
            );
        }
        
        $post_types = get_post_types(array('public' => true), 'objects');
        
        foreach ($post_types as $post_type) {
            if (isset($post_type->rewrite['slug']) && $post_type->rewrite['slug'] === $pattern) {
                return array(
                    'valid' => false,
                    'message' => sprintf(
                        __('Pattern conflicts with post type "%s" (slug: %s)', 'shortlinkr'),
                        $post_type->label,
                        $post_type->rewrite['slug']
                    )
                );
            }
            
            if ($post_type->name === $pattern) {
                return array(
                    'valid' => false,
                    'message' => sprintf(
                        __('Pattern conflicts with post type "%s"', 'shortlinkr'),
                        $post_type->label
                    )
                );
            }
        }
        
        $reserved_slugs = array('wp-admin', 'wp-content', 'wp-includes', 'feed', 'rsd', 'robots', 'trackback', 'page', 'author', 'search', 'category', 'tag');
        
        if (in_array($pattern, $reserved_slugs)) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    __('Pattern "%s" is a reserved WordPress slug', 'shortlinkr'),
                    $pattern
                )
            );
        }
        
        return array(
            'valid' => true,
            'message' => __('Pattern is valid', 'shortlinkr')
        );
    }
}