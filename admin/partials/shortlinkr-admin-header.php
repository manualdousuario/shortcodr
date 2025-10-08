<?php
/**
 * Admin header with navigation menu
 *
 * @package    shortlinkr
 * @subpackage shortlinkr/admin/partials
 */

if (!defined('WPINC')) {
    die;
}

// Get current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'shortlinkr';
?>

<div class="bl-admin-header">
    <div class="bl-admin-header-content">
        <div class="bl-branding">
            <span class="dashicons dashicons-admin-links"></span>
            <h1>Shortlinkr</h1>
        </div>
        
        <nav class="bl-admin-nav">
            <a href="<?php echo admin_url('admin.php?page=shortlinkr'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortlinkr') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-list-view"></span>
                <?php echo esc_html__('All Short URLs', 'shortlinkr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=shortlinkr-add-new'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortlinkr-add-new') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php echo esc_html__('Add New', 'shortlinkr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=shortlinkr-campaigns'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortlinkr-campaigns') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-tag"></span>
                <?php echo esc_html__('Campaigns', 'shortlinkr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=shortlinkr-import-export'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortlinkr-import-export') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-database-import"></span>
                <?php echo esc_html__('Import/Export', 'shortlinkr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=shortlinkr-settings'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortlinkr-settings') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Settings', 'shortlinkr'); ?>
            </a>
        </nav>
    </div>
</div>