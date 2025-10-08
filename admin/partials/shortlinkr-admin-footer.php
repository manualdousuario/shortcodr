<?php
/**
 * Admin footer with version, GitHub link and credits
 *
 * @package    shortlinkr
 * @subpackage shortlinkr/admin/partials
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="bl-admin-footer">
    <div class="bl-admin-footer-content">
        <div class="bl-footer-info">
            <span class="bl-version">
                <strong><?php echo esc_html__('Version:', 'shortlinkr'); ?></strong> 
                <?php echo esc_html(SHORTLINKR_VERSION); ?>
            </span>
            
            <span class="bl-separator">|</span>
            
            <a href="https://github.com/manualdousuario/shortlinkr" target="_blank" rel="noopener noreferrer" class="bl-github-link">
                <span class="dashicons dashicons-editor-code"></span>
                <?php echo esc_html__('GitHub', 'shortlinkr'); ?>
            </a>
        </div>
        
        <div class="bl-footer-credits">
            <?php 
            printf(
                esc_html__('Made in partnership with %1$s and %2$s', 'shortlinkr'),
                '<a href="https://butialabs.com" target="_blank" rel="noopener noreferrer"><strong>Butiá Labs</strong></a>',
                '<a href="https://manualdousuario.net" target="_blank" rel="noopener noreferrer"><strong>Manual do Usuário</strong></a>'
            );
            ?>
        </div>
    </div>
</div>