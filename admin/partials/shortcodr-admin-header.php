<?php
/**
 * Admin header with navigation menu
 *
 * @package    shortcodr
 * @subpackage shortcodr/admin/partials
 */

if (!defined('WPINC')) {
    die;
}

// Get current page
$current_page = isset($_GET['page']) ? $_GET['page'] : 'shortcodr';
?>

<div class="bl-admin-header">
    <div class="bl-admin-header-content">
        <div class="bl-branding">
            <span class="dashicons dashicons-admin-links"></span>
            <h1>Shortcodr</h1>
        </div>
        
        <nav class="bl-admin-nav">
            <a href="<?php echo admin_url('admin.php?page=shortcodr'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortcodr') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-list-view"></span>
                <?php echo esc_html__('All Short URLs', 'shortcodr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=shortcodr-add-new'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortcodr-add-new') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php echo esc_html__('Add New', 'shortcodr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=shortcodr-campaigns'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortcodr-campaigns') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-tag"></span>
                <?php echo esc_html__('Campaigns', 'shortcodr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=shortcodr-import-export'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortcodr-import-export') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-database-import"></span>
                <?php echo esc_html__('Import/Export', 'shortcodr'); ?>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=shortcodr-settings'); ?>" 
               class="bl-nav-item <?php echo ($current_page === 'shortcodr-settings') ? 'active' : ''; ?>">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Settings', 'shortcodr'); ?>
            </a>
        </nav>
    </div>
</div>