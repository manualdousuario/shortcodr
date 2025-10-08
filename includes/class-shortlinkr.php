<?php

class shortlinkr {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('SHORTLINKR_VERSION')) {
            $this->version = SHORTLINKR_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'shortlinkr';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once SHORTLINKR_PLUGIN_PATH . 'includes/class-shortlinkr-loader.php';
        require_once SHORTLINKR_PLUGIN_PATH . 'includes/class-shortlinkr-i18n.php';
        require_once SHORTLINKR_PLUGIN_PATH . 'admin/class-shortlinkr-admin.php';
        require_once SHORTLINKR_PLUGIN_PATH . 'public/class-shortlinkr-public.php';
        require_once SHORTLINKR_PLUGIN_PATH . 'includes/class-shortlinkr-database.php';
        $this->loader = new shortlinkr_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new shortlinkr_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new shortlinkr_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
        
        $this->loader->add_action('wp_ajax_shortlinkr_toggle_status', $plugin_admin, 'ajax_toggle_status');
        $this->loader->add_action('wp_ajax_shortlinkr_delete_url', $plugin_admin, 'ajax_delete_url');
        $this->loader->add_action('wp_ajax_shortlinkr_get_analytics', $plugin_admin, 'ajax_get_analytics');
        $this->loader->add_action('wp_ajax_shortlinkr_generate_slug', $plugin_admin, 'ajax_generate_slug');
        $this->loader->add_action('wp_ajax_shortlinkr_export_json', $plugin_admin, 'ajax_export_json');
        $this->loader->add_action('wp_ajax_shortlinkr_validate_pattern', $plugin_admin, 'ajax_validate_pattern');
    }

    private function define_public_hooks() {
        $plugin_public = new shortlinkr_Public($this->get_plugin_name(), $this->get_version());

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