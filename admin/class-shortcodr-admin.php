<?php

// Admin functionality
class shortcodr_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'shortcodr') !== false) {
            wp_enqueue_style($this->plugin_name, SHORTCODR_PLUGIN_URL . 'admin/dist/css/shortcodr-admin.min.css', array(), $this->version, 'all');
            wp_enqueue_style('wp-color-picker');
        }
    }

    public function enqueue_scripts() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'shortcodr') !== false) {
            wp_enqueue_script($this->plugin_name, SHORTCODR_PLUGIN_URL . 'admin/dist/js/shortcodr-admin.min.js', array(), $this->version, false);
            wp_localize_script($this->plugin_name, 'shortcodr_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shortcodr_nonce'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'shortcodr'),
                'confirm_delete_campaign' => __('Are you sure you want to delete this campaign? URLs using this campaign will be unassigned.', 'shortcodr')
            ));
        }
    }

    public function add_admin_menu() {
        if (!current_user_can($this->get_required_capability())) {
            return;
        }

        add_menu_page(
            __('Shortcodr', 'shortcodr'),
            __('Shortcodr', 'shortcodr'),
            $this->get_required_capability(),
            'shortcodr',
            array($this, 'display_urls_page'),
            'dashicons-admin-links',
            90
        );

        add_submenu_page(
            'shortcodr',
            __('All Short URLs', 'shortcodr'),
            __('All Short URLs', 'shortcodr'),
            $this->get_required_capability(),
            'shortcodr',
            array($this, 'display_urls_page')
        );

        add_submenu_page(
            'shortcodr',
            __('Add New', 'shortcodr'),
            __('Add New', 'shortcodr'),
            $this->get_required_capability(),
            'shortcodr-add-new',
            array($this, 'display_add_url_page')
        );

        add_submenu_page(
            'shortcodr',
            __('Campaigns', 'shortcodr'),
            __('Campaigns', 'shortcodr'),
            $this->get_required_capability(),
            'shortcodr-campaigns',
            array($this, 'display_campaigns_page')
        );

        add_submenu_page(
            'shortcodr',
            __('Import/Export', 'shortcodr'),
            __('Import/Export', 'shortcodr'),
            $this->get_required_capability(),
            'shortcodr-import-export',
            array($this, 'display_import_export_page')
        );

        add_submenu_page(
            'shortcodr',
            __('Settings', 'shortcodr'),
            __('Settings', 'shortcodr'),
            $this->get_required_capability(),
            'shortcodr-settings',
            array($this, 'display_settings_page')
        );
    }

    public function admin_init() {
        register_setting('shortcodr_settings', 'shortcodr_base_url_pattern');
        register_setting('shortcodr_settings', 'shortcodr_default_redirect_type');
        register_setting('shortcodr_settings', 'shortcodr_user_capabilities');
        
        $this->handle_form_submissions();
    }

    private function get_required_capability() {
        $capabilities = get_option('shortcodr_user_capabilities', array('administrator'));
        $user = wp_get_current_user();
        
        foreach ($capabilities as $cap) {
            if (in_array($cap, $user->roles) || user_can($user, $cap)) {
                return $cap;
            }
        }
        
        return 'manage_options';
    }

    private function handle_form_submissions() {
        if (!isset($_POST['shortcodr_nonce']) || !wp_verify_nonce($_POST['shortcodr_nonce'], 'shortcodr_action')) {
            return;
        }

        if (isset($_POST['shortcodr_action'])) {
            switch ($_POST['shortcodr_action']) {
                case 'add_url':
                    $this->handle_add_url();
                    break;
                case 'edit_url':
                    $this->handle_edit_url();
                    break;
                case 'add_campaign':
                    $this->handle_add_campaign();
                    break;
                case 'edit_campaign':
                    $this->handle_edit_campaign();
                    break;
                case 'import_json':
                    $this->handle_import_json();
                    break;
                case 'import_csv':
                    $this->handle_import_csv();
                    break;
            }
        }
    }

    private function handle_add_url() {
        $slug = sanitize_text_field($_POST['slug']);
        $target_url = esc_url_raw($_POST['target_url']);
        $campaign_id = !empty($_POST['campaign_id']) ? intval($_POST['campaign_id']) : null;
        $redirect_type = intval($_POST['redirect_type']);

        if (empty($slug)) {
            $slug = shortcodr_Database::generate_random_slug();
        }

        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $slug)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Slug can only contain letters, numbers, hyphens and underscores.', 'shortcodr') . '</p></div>';
            });
            return;
        }

        $result = shortcodr_Database::create_url($slug, $target_url, $campaign_id, $redirect_type);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Short URL created successfully!', 'shortcodr') . '</p></div>';
            });
            
            wp_redirect(admin_url('admin.php?page=shortcodr'));
            exit;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error creating short URL. Slug may already exist.', 'shortcodr') . '</p></div>';
            });
        }
    }

    private function handle_edit_url() {
        $id = intval($_POST['url_id']);
        $slug = sanitize_text_field($_POST['slug']);
        $target_url = esc_url_raw($_POST['target_url']);
        $campaign_id = !empty($_POST['campaign_id']) ? intval($_POST['campaign_id']) : null;
        $redirect_type = intval($_POST['redirect_type']);
        $status = sanitize_text_field($_POST['status']);

        $data = array(
            'slug' => $slug,
            'target_url' => $target_url,
            'campaign_id' => $campaign_id,
            'redirect_type' => $redirect_type,
            'status' => $status
        );

        $result = shortcodr_Database::update_url($id, $data);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Short URL updated successfully!', 'shortcodr') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error updating short URL.', 'shortcodr') . '</p></div>';
            });
        }
    }

    private function handle_add_campaign() {
        $name = sanitize_text_field($_POST['campaign_name']);

        $result = shortcodr_Database::create_campaign($name);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Campaign created successfully!', 'shortcodr') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error creating campaign.', 'shortcodr') . '</p></div>';
            });
        }
    }

    private function handle_edit_campaign() {
        $id = intval($_POST['campaign_id']);
        $name = sanitize_text_field($_POST['campaign_name']);

        $result = shortcodr_Database::update_campaign($id, $name);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Campaign updated successfully!', 'shortcodr') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error updating campaign.', 'shortcodr') . '</p></div>';
            });
        }
    }

    public function display_urls_page() {
        require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-list-urls.php';
    }

    public function display_add_url_page() {
        require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-add-url.php';
    }

    public function display_campaigns_page() {
        require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-campaigns.php';
    }


    public function display_import_export_page() {
        require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-import-export.php';
    }

    public function display_settings_page() {
        require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-settings.php';
    }

    private function handle_import_json() {
        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error uploading file.', 'shortcodr') . '</p></div>';
            });
            return;
        }

        $json_data = file_get_contents($_FILES['json_file']['tmp_name']);
        $skip_duplicates = isset($_POST['skip_duplicates']);

        $result = shortcodr_Database::import_urls_from_json($json_data, $skip_duplicates);

        if ($result['success']) {
            add_action('admin_notices', function() use ($result) {
                $message = sprintf(
                    __('Import completed! Imported: %d, Skipped: %d, Errors: %d', 'shortcodr'),
                    $result['imported'],
                    $result['skipped'],
                    $result['errors']
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['error']) . '</p></div>';
            });
        }
    }

    private function handle_import_csv() {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error uploading file.', 'shortcodr') . '</p></div>';
            });
            return;
        }

        $csv_data = file_get_contents($_FILES['csv_file']['tmp_name']);
        $skip_duplicates = isset($_POST['skip_duplicates']);

        $result = shortcodr_Database::import_urls_from_csv($csv_data, $skip_duplicates);

        if ($result['success']) {
            add_action('admin_notices', function() use ($result) {
                $message = sprintf(
                    __('Import completed! Imported: %d, Skipped: %d, Errors: %d', 'shortcodr'),
                    $result['imported'],
                    $result['skipped'],
                    $result['errors']
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['error']) . '</p></div>';
            });
        }
    }

    public function ajax_toggle_status() {
        check_ajax_referer('shortcodr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortcodr'));
        }

        $id = intval($_POST['id']);
        $current_status = sanitize_text_field($_POST['current_status']);
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';

        $result = shortcodr_Database::update_url($id, array('status' => $new_status));

        if ($result) {
            wp_send_json_success(array('new_status' => $new_status));
        } else {
            wp_send_json_error(__('Error updating status.', 'shortcodr'));
        }
    }

    public function ajax_delete_url() {
        check_ajax_referer('shortcodr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortcodr'));
        }

        $id = intval($_POST['id']);
        $result = shortcodr_Database::delete_url($id);

        if ($result) {
            wp_send_json_success(__('URL deleted successfully.', 'shortcodr'));
        } else {
            wp_send_json_error(__('Error deleting URL.', 'shortcodr'));
        }
    }

    public function ajax_get_analytics() {
        check_ajax_referer('shortcodr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortcodr'));
        }

        $id = intval($_POST['id']);
        $days = intval($_POST['days']) ?: 30;

        $analytics = shortcodr_Database::get_url_analytics($id, $days);
        $total_views = shortcodr_Database::get_url_total_views($id);

        wp_send_json_success(array(
            'analytics' => $analytics,
            'total_views' => $total_views
        ));
    }

    public function ajax_generate_slug() {
        check_ajax_referer('shortcodr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortcodr'));
        }

        $slug = shortcodr_Database::generate_random_slug();
        wp_send_json_success(array('slug' => $slug));
    }

    public function ajax_export_json() {
        check_ajax_referer('shortcodr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortcodr'));
        }

        $json = shortcodr_Database::export_urls_to_json();
        
        wp_send_json_success(array('json' => $json));
    }

    public function ajax_validate_pattern() {
        check_ajax_referer('shortcodr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortcodr'));
        }

        $pattern = sanitize_text_field($_POST['pattern']);
        $result = shortcodr_Database::validate_base_pattern($pattern);

        if ($result['valid']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}