<?php

if (!defined('WPINC')) {
    die;
}

$edit_mode = isset($_GET['edit']) && !empty($_GET['edit']);
$url_data = null;

if ($edit_mode) {
    $url_id = intval($_GET['edit']);
    $url_data = shortlinkr_Database::get_url($url_id);
    
    if (!$url_data) {
        wp_die(__('URL not found.', 'shortlinkr'));
    }
}

$campaigns = shortlinkr_Database::get_campaigns('active');

$slug = $edit_mode ? $url_data->slug : '';
$target_url = $edit_mode ? $url_data->target_url : '';
$campaign_id = $edit_mode ? $url_data->campaign_id : '';
$redirect_type = $edit_mode ? $url_data->redirect_type : get_option('shortlinkr_default_redirect_type', 301);
$status = $edit_mode ? $url_data->status : 'active';

?>

<?php require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-header.php'; ?>

<div class="wrap shortlinkr-admin-content">
    <h1 class="wp-heading-inline">
        <?php echo $edit_mode ? esc_html__('Edit Short URL', 'shortlinkr') : esc_html__('Add New Short URL', 'shortlinkr'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=shortlinkr'); ?>" class="page-title-action">
        <?php echo esc_html__('Back to List', 'shortlinkr'); ?>
    </a>
    
    <hr class="wp-header-end">

    <form method="post" action="" class="shortlinkr-form">
        <?php wp_nonce_field('shortlinkr_action', 'shortlinkr_nonce'); ?>
        <input type="hidden" name="shortlinkr_action" value="<?php echo $edit_mode ? 'edit_url' : 'add_url'; ?>">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="url_id" value="<?php echo esc_attr($url_data->id); ?>">
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="target_url"><?php echo esc_html__('Target URL', 'shortlinkr'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="url" name="target_url" id="target_url" value="<?php echo esc_attr($target_url); ?>" class="regular-text" required>
                        <p class="description">
                            <?php echo esc_html__('The URL where visitors will be redirected.', 'shortlinkr'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="slug"><?php echo esc_html__('Custom Slug', 'shortlinkr'); ?></label>
                    </th>
                    <td>
                        <div class="shortlinkr-slug-input">
                            <input type="text" name="slug" id="slug" value="<?php echo esc_attr($slug); ?>" class="regular-text" pattern="[a-zA-Z0-9\-_]+" title="<?php echo esc_attr__('Only letters, numbers, hyphens and underscores allowed', 'shortlinkr'); ?>">
                            <button type="button" id="generate-slug" class="button">
                                <?php echo esc_html__('Generate Random', 'shortlinkr'); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php echo esc_html__('Leave empty to generate a random slug. Only letters, numbers, hyphens and underscores allowed.', 'shortlinkr'); ?>
                        </p>
                        
                        <div id="short-url-preview">
                            <strong><?php echo esc_html__('Short URL Preview:', 'shortlinkr'); ?></strong>
                            <code id="preview-url"><?php echo esc_html(shortlinkr_Public::get_short_url($slug ?: '[slug]')); ?></code>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="campaign_id"><?php echo esc_html__('Campaign', 'shortlinkr'); ?></label>
                    </th>
                    <td>
                        <select name="campaign_id" id="campaign_id" class="regular-text">
                            <option value=""><?php echo esc_html__('No Campaign', 'shortlinkr'); ?></option>
                            <?php foreach ($campaigns as $campaign): ?>
                                <option value="<?php echo esc_attr($campaign->id); ?>" <?php selected($campaign_id, $campaign->id); ?> data-color="<?php echo esc_attr($campaign->color); ?>">
                                    <?php echo esc_html($campaign->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Optional: Assign this URL to a campaign for better organization.', 'shortlinkr'); ?>
                            <a href="<?php echo admin_url('admin.php?page=shortlinkr-campaigns&edit=new'); ?>" target="_blank">
                                <?php echo esc_html__('Add New Campaign', 'shortlinkr'); ?>
                            </a> |
                            <a href="<?php echo admin_url('admin.php?page=shortlinkr-campaigns'); ?>" target="_blank">
                                <?php echo esc_html__('Manage Campaigns', 'shortlinkr'); ?>
                            </a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="redirect_type"><?php echo esc_html__('Redirect Type', 'shortlinkr'); ?></label>
                    </th>
                    <td>
                        <select name="redirect_type" id="redirect_type">
                            <option value="301" <?php selected($redirect_type, 301); ?>>
                                <?php echo esc_html__('301 - Permanent Redirect (Recommended)', 'shortlinkr'); ?>
                            </option>
                            <option value="302" <?php selected($redirect_type, 302); ?>>
                                <?php echo esc_html__('302 - Temporary Redirect', 'shortlinkr'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('301 redirects are better for SEO and are cached by browsers. Use 302 for temporary links.', 'shortlinkr'); ?>
                        </p>
                    </td>
                </tr>

                <?php if ($edit_mode): ?>
                <tr>
                    <th scope="row">
                        <label for="status"><?php echo esc_html__('Status', 'shortlinkr'); ?></label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value="active" <?php selected($status, 'active'); ?>>
                                <?php echo esc_html__('Active', 'shortlinkr'); ?>
                            </option>
                            <option value="inactive" <?php selected($status, 'inactive'); ?>>
                                <?php echo esc_html__('Inactive', 'shortlinkr'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Inactive URLs will show a 404 error when accessed.', 'shortlinkr'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Analytics', 'shortlinkr'); ?>
                    </th>
                    <td>
                        <?php
                        $total_views = shortlinkr_Database::get_url_total_views($url_data->id);
                        $analytics = shortlinkr_Database::get_url_analytics($url_data->id, 7); // Last 7 days
                        ?>
                        <p><strong><?php echo esc_html__('Total Views:', 'shortlinkr'); ?></strong> <?php echo esc_html(number_format_i18n($total_views)); ?></p>
                        
                        <?php if (!empty($analytics)): ?>
                            <p><strong><?php echo esc_html__('Last 7 Days:', 'shortlinkr'); ?></strong></p>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php echo esc_html__('Date', 'shortlinkr'); ?></th>
                                        <th><?php echo esc_html__('Views', 'shortlinkr'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics as $day): ?>
                                        <tr>
                                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($day->view_date))); ?></td>
                                            <td><?php echo esc_html(number_format_i18n($day->view_count)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        
                        <p>
                            <button type="button" class="button shortlinkr-view-analytics" data-id="<?php echo esc_attr($url_data->id); ?>" data-slug="<?php echo esc_attr($url_data->slug); ?>">
                                <?php echo esc_html__('View Detailed Analytics', 'shortlinkr'); ?>
                            </button>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php submit_button($edit_mode ? __('Update Short URL', 'shortlinkr') : __('Create Short URL', 'shortlinkr'), 'primary', 'submit', false); ?>
        
        <a href="<?php echo admin_url('admin.php?page=shortlinkr'); ?>" class="page-title-action button button-secondary">
            <?php echo esc_html__('Cancel', 'shortlinkr'); ?>
        </a>
    </form>
</div>

<?php require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-footer.php'; ?>