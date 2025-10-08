<?php

class shortlinkr_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'shortlinkr') !== false) {
            wp_enqueue_style($this->plugin_name, SHORTLINKR_PLUGIN_URL . 'admin/dist/css/shortlinkr-admin.min.css', array(), $this->version, 'all');
            wp_enqueue_style('wp-color-picker');
        }
    }

    public function enqueue_scripts() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'shortlinkr') !== false) {
            wp_enqueue_script($this->plugin_name, SHORTLINKR_PLUGIN_URL . 'admin/dist/js/shortlinkr-admin.min.js', array(), $this->version, false);
            wp_localize_script($this->plugin_name, 'shortlinkr_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('shortlinkr_nonce'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'shortlinkr'),
                'confirm_delete_campaign' => __('Are you sure you want to delete this campaign? URLs using this campaign will be unassigned.', 'shortlinkr')
            ));
        }
    }

    public function add_admin_menu() {
        if (!current_user_can($this->get_required_capability())) {
            return;
        }

        add_menu_page(
            __('Shortlinkr', 'shortlinkr'),
            __('Shortlinkr', 'shortlinkr'),
            $this->get_required_capability(),
            'shortlinkr',
            array($this, 'display_urls_page'),
            'dashicons-admin-links',
            90
        );

        add_submenu_page(
            'shortlinkr',
            __('All Short URLs', 'shortlinkr'),
            __('All Short URLs', 'shortlinkr'),
            $this->get_required_capability(),
            'shortlinkr',
            array($this, 'display_urls_page')
        );

        add_submenu_page(
            'shortlinkr',
            __('Add New', 'shortlinkr'),
            __('Add New', 'shortlinkr'),
            $this->get_required_capability(),
            'shortlinkr-add-new',
            array($this, 'display_add_url_page')
        );

        add_submenu_page(
            'shortlinkr',
            __('Campaigns', 'shortlinkr'),
            __('Campaigns', 'shortlinkr'),
            $this->get_required_capability(),
            'shortlinkr-campaigns',
            array($this, 'display_campaigns_page')
        );

        add_submenu_page(
            'shortlinkr',
            __('Import/Export', 'shortlinkr'),
            __('Import/Export', 'shortlinkr'),
            $this->get_required_capability(),
            'shortlinkr-import-export',
            array($this, 'display_import_export_page')
        );

        add_submenu_page(
            'shortlinkr',
            __('Settings', 'shortlinkr'),
            __('Settings', 'shortlinkr'),
            $this->get_required_capability(),
            'shortlinkr-settings',
            array($this, 'display_settings_page')
        );
    }

    public function admin_init() {
        register_setting('shortlinkr_settings', 'shortlinkr_base_url_pattern');
        register_setting('shortlinkr_settings', 'shortlinkr_default_redirect_type');
        register_setting('shortlinkr_settings', 'shortlinkr_user_capabilities');
        
        $this->handle_form_submissions();
    }

    private function get_required_capability() {
        $capabilities = get_option('shortlinkr_user_capabilities', array('administrator'));
        $user = wp_get_current_user();
        
        foreach ($capabilities as $cap) {
            if (in_array($cap, $user->roles) || user_can($user, $cap)) {
                return $cap;
            }
        }
        
        return 'manage_options';
    }

    private function handle_form_submissions() {
        if (!isset($_POST['shortlinkr_nonce']) || !wp_verify_nonce($_POST['shortlinkr_nonce'], 'shortlinkr_action')) {
            return;
        }

        if (isset($_POST['shortlinkr_action'])) {
            switch ($_POST['shortlinkr_action']) {
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
            $slug = shortlinkr_Database::generate_random_slug();
        }

        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $slug)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Slug can only contain letters, numbers, hyphens and underscores.', 'shortlinkr') . '</p></div>';
            });
            return;
        }

        $result = shortlinkr_Database::create_url($slug, $target_url, $campaign_id, $redirect_type);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Short URL created successfully!', 'shortlinkr') . '</p></div>';
            });
            
            wp_redirect(admin_url('admin.php?page=shortlinkr'));
            exit;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error creating short URL. Slug may already exist.', 'shortlinkr') . '</p></div>';
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

        $result = shortlinkr_Database::update_url($id, $data);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Short URL updated successfully!', 'shortlinkr') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error updating short URL.', 'shortlinkr') . '</p></div>';
            });
        }
    }

    private function handle_add_campaign() {
        $name = sanitize_text_field($_POST['campaign_name']);

        $result = shortlinkr_Database::create_campaign($name);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Campaign created successfully!', 'shortlinkr') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error creating campaign.', 'shortlinkr') . '</p></div>';
            });
        }
    }

    private function handle_edit_campaign() {
        $id = intval($_POST['campaign_id']);
        $name = sanitize_text_field($_POST['campaign_name']);

        $result = shortlinkr_Database::update_campaign($id, $name);

        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Campaign updated successfully!', 'shortlinkr') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error updating campaign.', 'shortlinkr') . '</p></div>';
            });
        }
    }

    public function display_urls_page() {
        require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-list-urls.php';
    }

    public function display_add_url_page() {
        require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-add-url.php';
    }

    public function display_campaigns_page() {
        require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-campaigns.php';
    }


    public function display_import_export_page() {
        require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-import-export.php';
    }

    public function display_settings_page() {
        require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-settings.php';
    }

    private function handle_import_json() {
        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error uploading file.', 'shortlinkr') . '</p></div>';
            });
            return;
        }

        $json_data = file_get_contents($_FILES['json_file']['tmp_name']);
        $skip_duplicates = isset($_POST['skip_duplicates']);

        $result = shortlinkr_Database::import_urls_from_json($json_data, $skip_duplicates);

        if ($result['success']) {
            add_action('admin_notices', function() use ($result) {
                $message = sprintf(
                    __('Import completed! Imported: %d, Skipped: %d, Errors: %d', 'shortlinkr'),
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
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error uploading file.', 'shortlinkr') . '</p></div>';
            });
            return;
        }

        $csv_data = file_get_contents($_FILES['csv_file']['tmp_name']);
        $skip_duplicates = isset($_POST['skip_duplicates']);

        $result = shortlinkr_Database::import_urls_from_csv($csv_data, $skip_duplicates);

        if ($result['success']) {
            add_action('admin_notices', function() use ($result) {
                $message = sprintf(
                    __('Import completed! Imported: %d, Skipped: %d, Errors: %d', 'shortlinkr'),
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
        check_ajax_referer('shortlinkr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortlinkr'));
        }

        $id = intval($_POST['id']);
        $current_status = sanitize_text_field($_POST['current_status']);
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';

        $result = shortlinkr_Database::update_url($id, array('status' => $new_status));

        if ($result) {
            wp_send_json_success(array('new_status' => $new_status));
        } else {
            wp_send_json_error(__('Error updating status.', 'shortlinkr'));
        }
    }

    public function ajax_delete_url() {
        check_ajax_referer('shortlinkr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortlinkr'));
        }

        $id = intval($_POST['id']);
        $result = shortlinkr_Database::delete_url($id);

        if ($result) {
            wp_send_json_success(__('URL deleted successfully.', 'shortlinkr'));
        } else {
            wp_send_json_error(__('Error deleting URL.', 'shortlinkr'));
        }
    }

    public function ajax_get_analytics() {
        check_ajax_referer('shortlinkr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortlinkr'));
        }

        $id = intval($_POST['id']);
        $days = intval($_POST['days']) ?: 30;

        $analytics = shortlinkr_Database::get_url_analytics($id, $days);
        $total_views = shortlinkr_Database::get_url_total_views($id);

        wp_send_json_success(array(
            'analytics' => $analytics,
            'total_views' => $total_views
        ));
    }

    public function ajax_generate_slug() {
        check_ajax_referer('shortlinkr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortlinkr'));
        }

        $slug = shortlinkr_Database::generate_random_slug();
        wp_send_json_success(array('slug' => $slug));
    }

    public function ajax_export_json() {
        check_ajax_referer('shortlinkr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortlinkr'));
        }

        $json = shortlinkr_Database::export_urls_to_json();
        
        wp_send_json_success(array('json' => $json));
    }

    public function ajax_validate_pattern() {
        check_ajax_referer('shortlinkr_nonce', 'nonce');
        
        if (!current_user_can($this->get_required_capability())) {
            wp_die(__('You do not have permission to perform this action.', 'shortlinkr'));
        }

        $pattern = sanitize_text_field($_POST['pattern']);
        $result = shortlinkr_Database::validate_base_pattern($pattern);

        if ($result['valid']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}