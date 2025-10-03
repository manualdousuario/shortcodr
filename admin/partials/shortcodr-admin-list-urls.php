<?php

if (!defined('WPINC')) {
    die;
}

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$campaign_filter = isset($_GET['campaign']) ? intval($_GET['campaign']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

$args = array(
    'search' => $search,
    'campaign_id' => $campaign_filter,
    'status' => $status_filter,
    'limit' => $per_page,
    'offset' => $offset,
    'orderby' => 'created_at',
    'order' => 'DESC'
);

$urls = shortcodr_Database::get_urls($args);
$total_items = shortcodr_Database::get_urls_count($args);
$total_pages = ceil($total_items / $per_page);

$campaigns = shortcodr_Database::get_campaigns('all');

?>

<?php require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-header.php'; ?>

<div class="wrap shortcodr-admin-content">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Short URLs', 'shortcodr'); ?>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=shortcodr-add-new'); ?>" class="page-title-action button button-secondary">
        <?php echo esc_html__('Add New', 'shortcodr'); ?>
    </a>

    <hr class="wp-header-end">

    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="shortcodr">

                <label class="screen-reader-text" for="shortcodr-search-input"><?php echo esc_html__('Search URLs', 'shortcodr'); ?></label>
                <input type="search" id="shortcodr-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Search URLs or slugs...', 'shortcodr'); ?>">

                <select name="campaign" id="shortcodr-campaign-filter">
                    <option value=""><?php echo esc_html__('All Campaigns', 'shortcodr'); ?></option>
                    <?php foreach ($campaigns as $campaign): ?>
                        <option value="<?php echo esc_attr($campaign->id); ?>" <?php selected($campaign_filter, $campaign->id); ?>>
                            <?php echo esc_html($campaign->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status" id="shortcodr-status-filter">
                    <option value=""><?php echo esc_html__('All Statuses', 'shortcodr'); ?></option>
                    <option value="active" <?php selected($status_filter, 'active'); ?>><?php echo esc_html__('Active', 'shortcodr'); ?></option>
                    <option value="inactive" <?php selected($status_filter, 'inactive'); ?>><?php echo esc_html__('Inactive', 'shortcodr'); ?></option>
                </select>

                <input type="submit" class="button" value="<?php echo esc_attr__('Filter', 'shortcodr'); ?>">

                <?php if ($search || $campaign_filter || $status_filter): ?>
                    <a href="<?php echo admin_url('admin.php?page=shortcodr'); ?>" class="button">
                        <?php echo esc_html__('Clear', 'shortcodr'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($total_items > 0): ?>
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_items, 'shortcodr'), number_format_i18n($total_items)); ?>
                </span>

                <?php if ($total_pages > 1): ?>
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    );
                    echo '<span class="pagination-links">' . paginate_links($pagination_args) . '</span>';
                    ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bl-wp-table-overflow">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-slug">
                        <?php echo esc_html__('Slug', 'shortcodr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-target">
                        <?php echo esc_html__('Target URL', 'shortcodr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-campaign">
                        <?php echo esc_html__('Campaign', 'shortcodr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-views">
                        <?php echo esc_html__('Views', 'shortcodr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <?php echo esc_html__('Status', 'shortcodr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-date">
                        <?php echo esc_html__('Created', 'shortcodr'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php echo esc_html__('Actions', 'shortcodr'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($urls)): ?>
                    <tr class="no-items">
                        <td class="colspanchange" colspan="8">
                            <?php echo esc_html__('No short URLs found.', 'shortcodr'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($urls as $url): ?>
                        <?php
                        $total_views = shortcodr_Database::get_url_total_views($url->id);
                        $short_url = shortcodr_Public::get_short_url($url->slug);
                        ?>
                        <tr id="url-<?php echo esc_attr($url->id); ?>">
                            <td class="column-slug">
                                <strong>
                                    <a href="<?php echo esc_url($short_url); ?>" target="_blank" title="<?php echo esc_attr__('Open short URL', 'shortcodr'); ?>">
                                        <?php echo esc_html($url->slug); ?>
                                    </a>
                                </strong>
                                <div class="shortcodr-short-url-display">
                                    <small>
                                        <code><?php echo esc_html($short_url); ?></code>
                                        <button type="button" class="button-link shortcodr-copy-url" data-url="<?php echo esc_attr($short_url); ?>" title="<?php echo esc_attr__('Copy to clipboard', 'shortcodr'); ?>">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                    </small>
                                </div>
                            </td>
                            <td class="column-target">
                                <a href="<?php echo esc_url($url->target_url); ?>" target="_blank" title="<?php echo esc_attr__('Open target URL', 'shortcodr'); ?>">
                                    <?php echo esc_html(wp_trim_words($url->target_url, 8, '...')); ?>
                                </a>
                            </td>
                            <td class="column-campaign">
                                <?php if ($url->campaign_name): ?>
                                    <?php echo esc_html($url->campaign_name); ?>
                                <?php else: ?>
                                    <span class="shortcodr-no-campaign"><?php echo esc_html__('No Campaign', 'shortcodr'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-views">
                                <span class="shortcodr-view-count"><?php echo esc_html(number_format_i18n($total_views)); ?></span>
                            </td>
                            <td class="column-status">
                                <?php if ($url->status === 'active'): ?>
                                    <span class="shortcodr-status shortcodr-status-active"><?php echo esc_html__('Active', 'shortcodr'); ?></span>
                                <?php else: ?>
                                    <span class="shortcodr-status shortcodr-status-inactive"><?php echo esc_html__('Inactive', 'shortcodr'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-date">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($url->created_at))); ?>
                            </td>
                            <td class="column-actions">
                                <div class="row-actions">
                                    <span class="toggle-status">
                                        <button type="button" class="button-link shortcodr-toggle-status" data-id="<?php echo esc_attr($url->id); ?>" data-status="<?php echo esc_attr($url->status); ?>">
                                            <?php echo $url->status === 'active' ? esc_html__('Deactivate', 'shortcodr') : esc_html__('Activate', 'shortcodr'); ?>
                                        </button> |
                                    </span>

                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=shortcodr-add-new&edit=' . $url->id); ?>">
                                            <?php echo esc_html__('Edit', 'shortcodr'); ?>
                                        </a> |
                                    </span>

                                    <span class="delete">
                                        <button type="button" class="button-link shortcodr-delete-url text-danger" data-id="<?php echo esc_attr($url->id); ?>">
                                            <?php echo esc_html__('Delete', 'shortcodr'); ?>
                                        </button>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_items, 'shortcodr'), number_format_i18n($total_items)); ?>
                </span>
                <span class="pagination-links"><?php echo paginate_links($pagination_args); ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once SHORTCODR_PLUGIN_PATH . 'admin/partials/shortcodr-admin-footer.php'; ?>