<?php

/**
 * Provide a admin area view for adding/editing campaigns
 *
 * @package    shortcodr
 * @subpackage shortcodr/admin/partials
 */

if (!defined('WPINC')) {
    die;
}

// Check if we're editing an existing campaign
$edit_mode = isset($_GET['edit']) && !empty($_GET['edit']);
$campaign_data = null;

if ($edit_mode) {
    $campaign_id = intval($_GET['edit']);
    $campaign_data = shortcodr_Database::get_campaign($campaign_id);
    
    if (!$campaign_data) {
        wp_die(__('Campaign not found.', 'shortcodr'));
    }
}

// Default values
$name = $edit_mode ? $campaign_data->name : '';

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo $edit_mode ? esc_html__('Edit Campaign', 'shortcodr') : esc_html__('Add New Campaign', 'shortcodr'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=shortcodr-campaigns'); ?>" class="page-title-action button button-secondary">
        <?php echo esc_html__('Back to Campaigns', 'shortcodr'); ?>
    </a>
    
    <hr class="wp-header-end">

    <form method="post" action="" class="shortcodr-form">
        <?php wp_nonce_field('shortcodr_action', 'shortcodr_nonce'); ?>
        <input type="hidden" name="shortcodr_action" value="<?php echo $edit_mode ? 'edit_campaign' : 'add_campaign'; ?>">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign_data->id); ?>">
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="campaign_name"><?php echo esc_html__('Campaign Name', 'shortcodr'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" name="campaign_name" id="campaign_name" value="<?php echo esc_attr($name); ?>" class="regular-text" required>
                        <p class="description">
                            <?php echo esc_html__('Enter a descriptive name for this campaign.', 'shortcodr'); ?>
                        </p>
                    </td>
                </tr>

                <?php if ($edit_mode): ?>
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Campaign Statistics', 'shortcodr'); ?>
                    </th>
                    <td>
                        <?php
                        global $wpdb;
                        $urls_table = $wpdb->prefix . 'shortcodr_urls';
                        $stats = $wpdb->get_row($wpdb->prepare("
                            SELECT 
                                COUNT(*) as total_urls,
                                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_urls
                            FROM $urls_table 
                            WHERE campaign_id = %d
                        ", $campaign_data->id));
                        ?>
                        <p><strong><?php echo esc_html__('Total URLs:', 'shortcodr'); ?></strong> <?php echo esc_html($stats->total_urls); ?></p>
                        <p><strong><?php echo esc_html__('Active URLs:', 'shortcodr'); ?></strong> <?php echo esc_html($stats->active_urls); ?></p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=shortcodr&campaign=' . $campaign_data->id); ?>" class="button">
                                <?php echo esc_html__('View Campaign URLs', 'shortcodr'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Created', 'shortcodr'); ?>
                    </th>
                    <td>
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($campaign_data->created_at))); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php submit_button($edit_mode ? __('Update Campaign', 'shortcodr') : __('Create Campaign', 'shortcodr'), 'primary', 'submit', false); ?>
        
        <a href="<?php echo admin_url('admin.php?page=shortcodr-campaigns'); ?>" class="button button-secondary">
            <?php echo esc_html__('Cancel', 'shortcodr'); ?>
        </a>
    </form>
</div>