<?php
/**
 * Plugin Name: Shortcodr
 * Plugin URI: https://github.com/manualdousuario/shortcodr/
 * Description: A WordPress URL shortener plugin with campaign management and analytics
 * Version: 1.0.0
 * Author: Butiá Labs
 * Author URI: https://butialabs.com
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Tested up to: 6.7
 * License: GPL v2 or later
 * Network: true
 * Text Domain: shortcodr
 * Domain Path: /languages
 */

if (!defined('WPINC')) {
    die;
}

define('SHORTCODR_VERSION', '1.0.2');
define('SHORTCODR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SHORTCODR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SHORTCODR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Activates the plugin
function activate_shortcodr($network_wide) {
    require_once SHORTCODR_PLUGIN_PATH . 'includes/class-shortcodr-activator.php';
    shortcodr_Activator::activate($network_wide);
}

// Deactivates the plugin
function deactivate_shortcodr($network_wide) {
    require_once SHORTCODR_PLUGIN_PATH . 'includes/class-shortcodr-deactivator.php';
    shortcodr_Deactivator::deactivate($network_wide);
}

// Activates on new multisite
function activate_shortcodr_new_site($site_id) {
    if (is_plugin_active_for_network(SHORTCODR_PLUGIN_BASENAME)) {
        require_once SHORTCODR_PLUGIN_PATH . 'includes/class-shortcodr-activator.php';
        switch_to_blog($site_id);
        shortcodr_Activator::activate_single_site();
        restore_current_blog();
    }
}

// Removes when deleting multisite
function deactivate_shortcodr_deleted_site($site_id) {
    require_once SHORTCODR_PLUGIN_PATH . 'includes/class-shortcodr-deactivator.php';
    switch_to_blog($site_id);
    shortcodr_Deactivator::deactivate_single_site();
    restore_current_blog();
}

register_activation_hook(__FILE__, 'activate_shortcodr');
register_deactivation_hook(__FILE__, 'deactivate_shortcodr');
add_action('wpmu_new_blog', 'activate_shortcodr_new_site');
add_action('delete_blog', 'deactivate_shortcodr_deleted_site');

require SHORTCODR_PLUGIN_PATH . 'includes/class-shortcodr.php';

function run_shortcodr() {
    $plugin = new shortcodr();
    $plugin->run();
}
run_shortcodr();
