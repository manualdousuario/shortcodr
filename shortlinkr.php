<?php
/**
 * Plugin Name: Shortlinkr
 * Plugin URI: https://github.com/manualdousuario/shortlinkr/
 * Description: A WordPress URL shortener plugin with campaign management and analytics
 * Version: 1.0.0
 * Author: Butiá Labs
 * Author URI: https://butialabs.com
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Tested up to: 6.7
 * License: GPL v2 or later
 * Network: true
 * Text Domain: shortlinkr
 * Domain Path: /languages
 */

if (!defined('WPINC')) {
    die;
}

define('SHORTLINKR_VERSION', '1.0.2');
define('SHORTLINKR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SHORTLINKR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SHORTLINKR_PLUGIN_BASENAME', plugin_basename(__FILE__));

function activate_shortlinkr($network_wide) {
    require_once SHORTLINKR_PLUGIN_PATH . 'includes/class-shortlinkr-activator.php';
    shortlinkr_Activator::activate($network_wide);
}

function deactivate_shortlinkr($network_wide) {
    require_once SHORTLINKR_PLUGIN_PATH . 'includes/class-shortlinkr-deactivator.php';
    shortlinkr_Deactivator::deactivate($network_wide);
}

function activate_shortlinkr_new_site($site_id) {
    if (is_plugin_active_for_network(SHORTLINKR_PLUGIN_BASENAME)) {
        require_once SHORTLINKR_PLUGIN_PATH . 'includes/class-shortlinkr-activator.php';
        switch_to_blog($site_id);
        shortlinkr_Activator::activate_single_site();
        restore_current_blog();
    }
}

function deactivate_shortlinkr_deleted_site($site_id) {
    require_once SHORTLINKR_PLUGIN_PATH . 'includes/class-shortlinkr-deactivator.php';
    switch_to_blog($site_id);
    shortlinkr_Deactivator::deactivate_single_site();
    restore_current_blog();
}

register_activation_hook(__FILE__, 'activate_shortlinkr');
register_deactivation_hook(__FILE__, 'deactivate_shortlinkr');
add_action('wpmu_new_blog', 'activate_shortlinkr_new_site');
add_action('delete_blog', 'deactivate_shortlinkr_deleted_site');

require SHORTLINKR_PLUGIN_PATH . 'includes/class-shortlinkr.php';

function run_shortlinkr() {
    $plugin = new shortlinkr();
    $plugin->run();
}
run_shortlinkr();
