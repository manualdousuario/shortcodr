<?php

if (!defined('WPINC')) {
    die;
}

$edit_mode = isset($_GET['edit']) && !empty($_GET['edit']);
$campaign_data = null;

if ($edit_mode) {
    $campaign_id = intval($_GET['edit']);
    $campaign_data = shortlinkr_Database::get_campaign($campaign_id);

    if (!$campaign_data) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Campaign not found.', 'shortlinkr') . '</p></div>';
        });
        $edit_mode = false;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['campaign_id']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_campaign_' . $_GET['campaign_id'])) {
    $campaign_id = intval($_GET['campaign_id']);
    $result = shortlinkr_Database::delete_campaign($campaign_id);

    if ($result) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Campaign deleted successfully.', 'shortlinkr') . '</p></div>';
        });
    } else {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Error deleting campaign.', 'shortlinkr') . '</p></div>';
        });
    }

    wp_redirect(admin_url('admin.php?page=shortlinkr-campaigns'));
    exit;
}

$campaigns = shortlinkr_Database::get_campaigns('all');

global $wpdb;
$urls_table = $wpdb->prefix . 'shortlinkr_urls';
$campaign_stats = $wpdb->get_results("
    SELECT 
        campaign_id,
        COUNT(*) as url_count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count
    FROM $urls_table 
    WHERE campaign_id IS NOT NULL 
    GROUP BY campaign_id
", OBJECT_K);

?>

<?php require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-header.php'; ?>

<div class="wrap shortlinkr-admin-content">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Campaigns', 'shortlinkr'); ?>
    </h1>

    <?php if (!$edit_mode): ?>
        <a href="<?php echo admin_url('admin.php?page=shortlinkr-campaigns&edit=new'); ?>" class="page-title-action button button-secondary">
            <?php echo esc_html__('Add New Campaign', 'shortlinkr'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if ($edit_mode || (isset($_GET['edit']) && $_GET['edit'] === 'new')): ?>
        <div class="shortlinkr-card">
            <h2>
                <?php echo ($edit_mode && $campaign_data) ? esc_html__('Edit Campaign', 'shortlinkr') : esc_html__('Add New Campaign', 'shortlinkr'); ?>
            </h2>

            <form method="post" action="" class="shortlinkr-form">
                <?php wp_nonce_field('shortlinkr_action', 'shortlinkr_nonce'); ?>
                <input type="hidden" name="shortlinkr_action" value="<?php echo ($edit_mode && $campaign_data) ? 'edit_campaign' : 'add_campaign'; ?>">
                <?php if ($edit_mode && $campaign_data): ?>
                    <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign_data->id); ?>">
                <?php endif; ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="campaign_name"><?php echo esc_html__('Campaign Name', 'shortlinkr'); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input type="text" name="campaign_name" id="campaign_name" value="<?php echo $edit_mode && $campaign_data ? esc_attr($campaign_data->name) : ''; ?>" class="regular-text" required>
                                <p class="description">
                                    <?php echo esc_html__('Enter a descriptive name for this campaign.', 'shortlinkr'); ?>
                                </p>
                            </td>
                        </tr>

                        <?php if ($edit_mode && $campaign_data): ?>
                            <tr>
                                <th scope="row">
                                    <?php echo esc_html__('Campaign Statistics', 'shortlinkr'); ?>
                                </th>
                                <td>
                                    <?php
                                    global $wpdb;
                                    $urls_table = $wpdb->prefix . 'shortlinkr_urls';
                                    $stats = $wpdb->get_row($wpdb->prepare("
                                SELECT
                                    COUNT(*) as total_urls,
                                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_urls
                                FROM $urls_table
                                WHERE campaign_id = %d
                            ", $campaign_data->id));
                                    ?>
                                    <p><strong><?php echo esc_html__('Total URLs:', 'shortlinkr'); ?></strong> <?php echo esc_html($stats->total_urls); ?></p>
                                    <p><strong><?php echo esc_html__('Active URLs:', 'shortlinkr'); ?></strong> <?php echo esc_html($stats->active_urls); ?></p>
                                    <p>
                                        <a href="<?php echo admin_url('admin.php?page=shortlinkr&campaign=' . $campaign_data->id); ?>" class="button">
                                            <?php echo esc_html__('View Campaign URLs', 'shortlinkr'); ?>
                                        </a>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php echo esc_html__('Created', 'shortlinkr'); ?>
                                </th>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($campaign_data->created_at))); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <?php submit_button(($edit_mode && $campaign_data) ? __('Update Campaign', 'shortlinkr') : __('Create Campaign', 'shortlinkr'), 'primary', 'submit', false); ?>

                    <a href="<?php echo admin_url('admin.php?page=shortlinkr-campaigns'); ?>" class="button button-secondary">
                        <?php echo esc_html__('Cancel', 'shortlinkr'); ?>
                    </a>
                </p>
            </form>
        </div>

        <hr>
    <?php endif; ?>

    <div class="bl-wp-table-overflow">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-name">
                        <?php echo esc_html__('Name', 'shortlinkr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-urls">
                        <?php echo esc_html__('URLs', 'shortlinkr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-created">
                        <?php echo esc_html__('Created', 'shortlinkr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php echo esc_html__('Actions', 'shortlinkr'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="4">
                            <?php echo esc_html__('No campaigns found.', 'shortlinkr'); ?>
                            <a href="<?php echo admin_url('admin.php?page=shortlinkr-campaigns&edit=new'); ?>"><?php echo esc_html__('Create your first campaign', 'shortlinkr'); ?></a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $campaign): ?>
                        <?php
                        $stats = isset($campaign_stats[$campaign->id]) ? $campaign_stats[$campaign->id] : null;
                        $url_count = $stats ? $stats->url_count : 0;
                        $active_count = $stats ? $stats->active_count : 0;
                        ?>
                        <tr>
                            <td class="column-name">
                                <strong><?php echo esc_html($campaign->name); ?></strong>
                            </td>
                            <td class="column-urls">
                                <?php if ($url_count > 0): ?>
                                    <a href="<?php echo admin_url('admin.php?page=shortlinkr&campaign=' . $campaign->id); ?>">
                                        <?php printf(_n('%d URL', '%d URLs', $url_count, 'shortlinkr'), $url_count); ?>
                                    </a>
                                    <?php if ($active_count !== $url_count): ?>
                                        <br><small><?php printf(__('%d active', 'shortlinkr'), $active_count); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="shortlinkr-no-urls"><?php echo esc_html__('No URLs', 'shortlinkr'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-created">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($campaign->created_at))); ?>
                            </td>
                            <td class="column-actions">
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=shortlinkr-campaigns&edit=' . $campaign->id); ?>">
                                            <?php echo esc_html__('Edit', 'shortlinkr'); ?>
                                        </a> |
                                    </span>

                                    <?php if ($url_count > 0): ?>
                                        <span class="view-urls">
                                            <a href="<?php echo admin_url('admin.php?page=shortlinkr&campaign=' . $campaign->id); ?>">
                                                <?php echo esc_html__('View URLs', 'shortlinkr'); ?>
                                            </a> |
                                        </span>
                                    <?php endif; ?>

                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=shortlinkr-campaigns&action=delete&campaign_id=' . $campaign->id), 'delete_campaign_' . $campaign->id); ?>"
                                            class="shortlinkr-delete-campaign text-danger"
                                            data-url-count="<?php echo esc_attr($url_count); ?>">
                                            <?php echo esc_html__('Delete', 'shortlinkr'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-footer.php'; ?>