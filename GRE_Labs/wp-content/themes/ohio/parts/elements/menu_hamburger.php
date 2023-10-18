<?php
/**
 * Ohio WordPress Theme
 *
 * Hamburger menu template
 *
 * @author Colabrio
 * @link   https://ohio.clbthemes.com
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get theme options
$hamburger_caption_visibility = OhioOptions::get( 'page_hamburger_menu_caption', false );

?>

<?php if ( $hamburger_caption_visibility ) : ?>
    <a class="hamburger-outer" aria-controls="site-menu" aria-expanded="false">
<?php endif; ?>

    <button class="icon-button hamburger" <?php if ( !$hamburger_caption_visibility ) { echo esc_attr( 'aria-controls=site-menu aria-expanded=false'); } ?>>
        <i class="icon"></i>
    </button>

<?php if ( $hamburger_caption_visibility ) : ?>   
        <span class="hamburger-caption"><?php esc_html_e( 'Menu', 'ohio' ); ?></span>
    </a>
<?php endif; ?>