<?php

if (!defined('WPINC')) {
    die;
}

if (isset($_POST['submit']) && check_admin_referer('shortcodr_settings', 'shortcodr_settings_nonce')) {
    
    $base_url_pattern = sanitize_text_field($_POST['shortcodr_base_url_pattern']);
    $base_url_pattern = trim($base_url_pattern, '/');
    
    if (empty($base_url_pattern)) {
        $base_url_pattern = 'go';
    }
    
    $validation = shortcodr_Database::validate_base_pattern($base_url_pattern);
    
    if ($validation['valid']) {
        $base_url_pattern = '/' . $base_url_pattern . '/';
        update_option('shortcodr_base_url_pattern', $base_url_pattern);
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($validation['message']) . '</p></div>';
        $base_url_pattern = get_option('shortcodr_base_url_pattern', '/go/');
    }
    
    $default_redirect_type = intval($_POST['shortcodr_default_redirect_type']);
    if (!in_array($default_redirect_type, array(301, 302))) {
        $default_redirect_type = 301;
    }
    update_option('shortcodr_default_redirect_type', $default_redirect_type);
    
    $user_capabilities = isset($_POST['shortcodr_user_capabilities']) ? $_POST['shortcodr_user_capabilities'] : array();
    // Get all available roles dynamically from WordPress
    $allowed_roles = array_keys(wp_roles()->get_names());
    $user_capabilities = array_intersect($user_capabilities, $allowed_roles);
    if (empty($user_capabilities)) {
        $user_capabilities = array('administrator');
    }
    update_option('shortcodr_user_capabilities', $user_capabilities);
    
    flush_rewrite_rules();
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'shortcodr') . '</p></div>';
}

$base_url_pattern = get_option('shortcodr_base_url_pattern', '/go/');
$default_redirect_type = get_option('shortcodr_default_redirect_type', 301);
$user_capabilities = get_option('shortcodr_user_capabilities', array('administrator'));

$base_url_pattern = trim($base_url_pattern, '/');

?>

<?php require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-header.php'; ?>

<div class="wrap shortcodr-admin-content">
    <h1><?php echo esc_html__('Shortcodr Settings', 'shortcodr'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('shortcodr_settings', 'shortcodr_settings_nonce'); ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <!-- Base URL Pattern -->
                <tr>
                    <th scope="row">
                        <label for="shortcodr_base_url_pattern"><?php echo esc_html__('Base URL Pattern', 'shortcodr'); ?></label>
                    </th>
                    <td>
                        <div class="shortcodr-url-pattern-input">
                            <span class="shortcodr-site-url"><?php echo esc_html(home_url('/')); ?></span>
                            <input type="text" name="shortcodr_base_url_pattern" id="shortcodr_base_url_pattern"
                                   value="<?php echo esc_attr($base_url_pattern); ?>"
                                   class="regular-text" placeholder="go"
                                   data-base-url="<?php echo esc_attr(home_url('/')); ?>">
                            <span class="shortcodr-url-suffix">/[slug]</span>
                        </div>
                        <div id="pattern-validation-message"></div>
                        <p class="description">
                            <?php echo esc_html__('The URL pattern for your short links. Examples: "go", "s", "link", "out"', 'shortcodr'); ?>
                            <br>
                            <strong><?php echo esc_html__('Current preview:', 'shortcodr'); ?></strong> 
                            <code id="shortcodr-url-preview"><?php echo esc_html(home_url('/' . $base_url_pattern . '/example')); ?></code>
                        </p>
                        <p class="description">
                            <strong><?php echo esc_html__('Note:', 'shortcodr'); ?></strong> 
                            <?php echo esc_html__('Changing this setting will require you to update any existing short URLs you\'ve shared.', 'shortcodr'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="shortcodr_default_redirect_type"><?php echo esc_html__('Default Redirect Type', 'shortcodr'); ?></label>
                    </th>
                    <td>
                        <select name="shortcodr_default_redirect_type" id="shortcodr_default_redirect_type">
                            <option value="301" <?php selected($default_redirect_type, 301); ?>>
                                <?php echo esc_html__('301 - Permanent Redirect (Recommended)', 'shortcodr'); ?>
                            </option>
                            <option value="302" <?php selected($default_redirect_type, 302); ?>>
                                <?php echo esc_html__('302 - Temporary Redirect', 'shortcodr'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('The default redirect type for new short URLs. 301 redirects are better for SEO and are cached by browsers.', 'shortcodr'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html__('User Access', 'shortcodr'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php echo esc_html__('User roles that can access the URL shortener', 'shortcodr'); ?></legend>
                            
                            <?php
                            // Get all available roles dynamically
                            $all_roles = wp_roles()->get_names();
                            foreach ($all_roles as $role_slug => $role_name) :
                            ?>
                                <label for="shortcodr_capability_<?php echo esc_attr($role_slug); ?>">
                                    <input type="checkbox" name="shortcodr_user_capabilities[]"
                                           id="shortcodr_capability_<?php echo esc_attr($role_slug); ?>"
                                           value="<?php echo esc_attr($role_slug); ?>"
                                           <?php checked(in_array($role_slug, $user_capabilities)); ?>>
                                    <?php echo esc_html(translate_user_role($role_name)); ?>
                                </label><br>
                            <?php endforeach; ?>
                            
                            <p class="description">
                                <?php echo esc_html__('Select which user roles can access and use the URL shortener functionality.', 'shortcodr'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>

            </tbody>
        </table>

        <?php submit_button(__('Save Settings', 'shortcodr')); ?>
    </form>
</div>

<?php require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-footer.php'; ?>