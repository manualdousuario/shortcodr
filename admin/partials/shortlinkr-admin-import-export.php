<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}
?>

<?php require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-header.php'; ?>

<div class="wrap shortlinkr-admin-content">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="shortlinkr-import-export-container">
        <div class="shortlinkr-card">
            <h2><?php _e('Export URLs', 'shortlinkr'); ?></h2>
            <p><?php _e('Export all your short URLs to a JSON file for backup or migration purposes.', 'shortlinkr'); ?></p>
            
            <button type="button" id="export-json-btn" class="button button-primary">
                <?php _e('Export to JSON', 'shortlinkr'); ?>
            </button>
            
            <div id="export-status" class="shortlinkr-status-message"></div>
        </div>

        <div class="shortlinkr-card">
            <h2><?php _e('Import from JSON', 'shortlinkr'); ?></h2>
            <p><?php _e('Import short URLs from a JSON backup file.', 'shortlinkr'); ?></p>
            
            <form method="post" enctype="multipart/form-data" id="import-json-form">
                <?php wp_nonce_field('shortlinkr_action', 'shortlinkr_nonce'); ?>
                <input type="hidden" name="shortlinkr_action" value="import_json">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="json_file"><?php _e('JSON File', 'shortlinkr'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="json_file" id="json_file" accept=".json" required>
                            <p class="description"><?php _e('Select a JSON file exported from Shortlinkr', 'shortlinkr'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="skip_duplicates_json"><?php _e('Skip Duplicates', 'shortlinkr'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="skip_duplicates" id="skip_duplicates_json" value="1" checked>
                                <?php _e('Skip URLs with slugs that already exist', 'shortlinkr'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Import JSON', 'shortlinkr'), 'primary', 'submit', false); ?>
            </form>
        </div>

        <div class="shortlinkr-card">
            <h2><?php _e('Import from CSV', 'shortlinkr'); ?></h2>
            <p><?php _e('Import short URLs from a CSV file. The CSV should have two columns: slug and target URL.', 'shortlinkr'); ?></p>
            
            <div class="shortlinkr-csv-example">
                <strong><?php _e('CSV Format Example:', 'shortlinkr'); ?></strong>
                <pre>slug,url
product,https://example.com/products/amazing-product
promo,https://example.com/summer-sale</pre>
            </div>
            
            <form method="post" enctype="multipart/form-data" id="import-csv-form">
                <?php wp_nonce_field('shortlinkr_action', 'shortlinkr_nonce'); ?>
                <input type="hidden" name="shortlinkr_action" value="import_csv">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="csv_file"><?php _e('CSV File', 'shortlinkr'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required>
                            <p class="description"><?php _e('Select a CSV file with slug,url format', 'shortlinkr'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="skip_duplicates_csv"><?php _e('Skip Duplicates', 'shortlinkr'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="skip_duplicates" id="skip_duplicates_csv" value="1" checked>
                                <?php _e('Skip URLs with slugs that already exist', 'shortlinkr'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Import CSV', 'shortlinkr'), 'primary', 'submit', false); ?>
            </form>
        </div>
    </div>
</div>

<?php require_once SHORTLINKR_PLUGIN_PATH . 'admin/partials/shortlinkr-admin-footer.php'; ?>