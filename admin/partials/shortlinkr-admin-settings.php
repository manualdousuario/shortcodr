<?php

if (!defined('WPINC')) {
    die;
}

if (isset($_POST['submit']) && check_admin_referer('shortlinkr_settings', 'shortlinkr_settings_nonce')) {
    
    $base_url_pattern = sanitize_text_field($_POST['shortlinkr_base_url_pattern']);
    $base_url_pattern = trim($base_url_pattern, '/');
    
    if (empty($base_url_pattern)) {
        $base_url_pattern = 'go';
    }
    
    $validation = shortlinkr_Database::validate_base_pattern($base_url_pattern);
    
    if ($validation['valid']) {
        $base_url_pattern = '/' . $base_url_pattern . '/';
        update_option('shortlinkr_base_url_pattern', $base_url_pattern);
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($validation['message']) . '</p></div>';
        $base_url_pattern = get_option('shortlinkr_base_url_pattern', '/go/');
    }
    
    $default_redirect_type = intval($_POST['shortlinkr_default_redirect_type']);
    if (!in_array($default_redirect_type, array(301, 302))) {
        $default_redirect_type = 301;
    }
    update_option('shortlinkr_default_redirect_type', $default_redirect_type);
    
    $user_capabilities = isset($_POST['shortlinkr_user_capabilities']) ? $_POST['shortlinkr_user_capabilities'] : array();
    // Get all available roles dynamically from WordPress
    $allowed_roles = array_keys(wp_roles()->get_names());
    $user_capabilities = array_intersect($user_capabilities, $allowed_roles);
    if (empty($user_capabilities)) {
        $user_capabilities = array('administrator');
    }
    update_option('shortlinkr_user_capabilities', $user_capabilities);
    
    flush_rewrite_rules();
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'shortlinkr') . '</p></div>';
}

$base_url_pattern = get_option('shortlinkr_base_url_pattern', '/go/');
$default_redirect_type = get_option('shortlinkr_default_redirect_type', 301);
$user_capabilities = get_option('shortlinkr_user_capabilities', array('administrator'));

$base_url_pattern = trim($base_url_pattern, '/');

?>

<?php require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-header.php'; ?>

<div class="wrap shortlinkr-admin-content">
    <h1><?php echo esc_html__('Shortlinkr Settings', 'shortlinkr'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('shortlinkr_settings', 'shortlinkr_settings_nonce'); ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Base URL Pattern -->
                <tr>
                    <th scope="row">
                        <label for="shortlinkr_base_url_pattern"><?php echo esc_html__('Base URL Pattern', 'shortlinkr'); ?></label>
                    </th>
                    <td>
                        <div class="shortlinkr-url-pattern-input">
                            <span class="shortlinkr-site-url"><?php echo esc_html(home_url('/')); ?></span>
                            <input type="text" name="shortlinkr_base_url_pattern" id="shortlinkr_base_url_pattern"
                                   value="<?php echo esc_attr($base_url_pattern); ?>"
                                   class="regular-text" placeholder="go"
                                   data-base-url="<?php echo esc_attr(home_url('/')); ?>">
                            <span class="shortlinkr-url-suffix">/[slug]</span>
                        </div>
                        <div id="pattern-validation-message"></div>
                        <p class="description">
                            <?php echo esc_html__('The URL pattern for your short links. Examples: "go", "s", "link", "out"', 'shortlinkr'); ?>
                            <br>
                            <strong><?php echo esc_html__('Current preview:', 'shortlinkr'); ?></strong> 
                            <code id="shortlinkr-url-preview"><?php echo esc_html(home_url('/' . $base_url_pattern . '/example')); ?></code>
                        </p>
                        <p class="description">
                            <strong><?php echo esc_html__('Note:', 'shortlinkr'); ?></strong> 
                            <?php echo esc_html__('Changing this setting will require you to update any existing short URLs you\'ve shared.', 'shortlinkr'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="shortlinkr_default_redirect_type"><?php echo esc_html__('Default Redirect Type', 'shortlinkr'); ?></label>
                    </th>
                    <td>
                        <select name="shortlinkr_default_redirect_type" id="shortlinkr_default_redirect_type">
                            <option value="301" <?php selected($default_redirect_type, 301); ?>>
                                <?php echo esc_html__('301 - Permanent Redirect (Recommended)', 'shortlinkr'); ?>
                            </option>
                            <option value="302" <?php selected($default_redirect_type, 302); ?>>
                                <?php echo esc_html__('302 - Temporary Redirect', 'shortlinkr'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('The default redirect type for new short URLs. 301 redirects are better for SEO and are cached by browsers.', 'shortlinkr'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html__('User Access', 'shortlinkr'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php echo esc_html__('User roles that can access the URL shortener', 'shortlinkr'); ?></legend>
                            
                            <?php
                            // Get all available roles dynamically
                            $all_roles = wp_roles()->get_names();
                            foreach ($all_roles as $role_slug => $role_name) :
                            ?>
                                <label for="shortlinkr_capability_<?php echo esc_attr($role_slug); ?>">
                                    <input type="checkbox" name="shortlinkr_user_capabilities[]"
                                           id="shortlinkr_capability_<?php echo esc_attr($role_slug); ?>"
                                           value="<?php echo esc_attr($role_slug); ?>"
                                           <?php checked(in_array($role_slug, $user_capabilities)); ?>>
                                    <?php echo esc_html(translate_user_role($role_name)); ?>
                                </label><br>
                            <?php endforeach; ?>
                            
                            <p class="description">
                                <?php echo esc_html__('Select which user roles can access and use the URL shortener functionality.', 'shortlinkr'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

            </tbody>
        </table>

        <?php submit_button(__('Save Settings', 'shortlinkr')); ?>
    </form>
</div>

<?php require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-footer.php'; ?>