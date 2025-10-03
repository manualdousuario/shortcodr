<?php

// Main plugin class
class shortcodr {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('SHORTCODR_VERSION')) {
            $this->version = SHORTCODR_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'shortcodr';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    // Loads plugin dependencies
    private function load_dependencies() {
        require_once SHORTCODR_PLUGIN_PATH . 'includes/class-shortcodr-loader.php';
        require_once SHORTCODR_PLUGIN_PATH . 'includes/class-shortcodr-i18n.php';
        require_once SHORTCODR_PLUGIN_PATH . 'admin/class-shortcodr-admin.php';
        require_once SHORTCODR_PLUGIN_PATH . 'public/class-shortcodr-public.php';
        require_once SHORTCODR_PLUGIN_PATH . 'includes/class-shortcodr-database.php';
        $this->loader = new shortcodr_Loader();
    }

    // Sets plugin translation
    private function set_locale() {
        $plugin_i18n = new shortcodr_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    // Registers admin hooks
    private function define_admin_hooks() {
        $plugin_admin = new shortcodr_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
        
        $this->loader->add_action('wp_ajax_shortcodr_toggle_status', $plugin_admin, 'ajax_toggle_status');
        $this->loader->add_action('wp_ajax_shortcodr_delete_url', $plugin_admin, 'ajax_delete_url');
        $this->loader->add_action('wp_ajax_shortcodr_get_analytics', $plugin_admin, 'ajax_get_analytics');
        $this->loader->add_action('wp_ajax_shortcodr_generate_slug', $plugin_admin, 'ajax_generate_slug');
        $this->loader->add_action('wp_ajax_shortcodr_export_json', $plugin_admin, 'ajax_export_json');
        $this->loader->add_action('wp_ajax_shortcodr_validate_pattern', $plugin_admin, 'ajax_validate_pattern');
    }

    // Registers public hooks
    private function define_public_hooks() {
        $plugin_public = new shortcodr_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('init', $plugin_public, 'init');
        $this->loader->add_action('template_redirect', $plugin_public, 'handle_redirect');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}