<?php

// Public functionality
class shortcodr_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function init() {
        $this->add_rewrite_rules();
    }

    private function add_rewrite_rules() {
        $base_pattern = get_option('shortcodr_base_url_pattern', '/go/');
        $base_pattern = trim($base_pattern, '/');
        
        if (!empty($base_pattern)) {
            add_rewrite_rule(
                '^' . $base_pattern . '/([^/]+)/?$',
                'index.php?shortcodr_slug=$matches[1]',
                'top'
            );
            
            add_rewrite_tag('%shortcodr_slug%', '([^&]+)');
        }
    }

    public function handle_redirect() {
        global $wp_query;
        
        if (isset($wp_query->query_vars['shortcodr_slug'])) {
            $slug = sanitize_text_field($wp_query->query_vars['shortcodr_slug']);
            $this->process_redirect($slug);
        }
    }

    private function process_redirect($slug) {
        $url_data = shortcodr_Database::get_url_by_slug($slug);
        
        if (!$url_data) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit;
        }

        shortcodr_Database::record_view($url_data->id);

        $redirect_type = intval($url_data->redirect_type);
        if (!in_array($redirect_type, array(301, 302))) {
            $redirect_type = 301;
        }

        $target_url = esc_url_raw($url_data->target_url);
        
        if (empty($target_url)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit;
        }

        $query_params = $_GET;
        
        unset($query_params['shortcodr_slug']);
        
        if (!empty($query_params)) {
            $target_url = add_query_arg($query_params, $target_url);
        }

        wp_redirect($target_url, $redirect_type);
        exit;
    }

    public static function get_short_url($slug) {
        $base_pattern = get_option('shortcodr_base_url_pattern', '/go/');
        $base_pattern = trim($base_pattern, '/');
        
        return home_url($base_pattern . '/' . $slug);
    }

    public static function get_short_url_html($slug, $copy_button = true) {
        $short_url = self::get_short_url($slug);
        
        $html = '<code class="shortcodr-short-url">' . esc_html($short_url) . '</code>';
        
        if ($copy_button) {
            $html .= ' <button class="button button-small shortcodr-copy-btn" data-url="' . esc_attr($short_url) . '">';
            $html .= __('Copy', 'shortcodr');
            $html .= '</button>';
        }
        
        return $html;
    }
}