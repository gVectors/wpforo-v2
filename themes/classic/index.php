<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Template Name:  Forum Index (Forums List)
 */

if( WPF()->board->get_current( 'is_standalone' ) ) get_header(); ?>

<div id="wpforo">
    <div id="wpforo-wrap" class="<?php do_action( 'wpforo_wrap_class' ); ?>">

        <?php if( wpforo_display_header() ) include( wpftpl( 'header.php' ) ); ?>

        <div class="wpforo-main">
            <div class="wpforo-content <?php if( wpforo_setting( 'social', 'sb_location_toggle' ) === 'right' ) echo 'wpfrt' ?>" <?php echo is_active_sidebar( wpforo_prefix( 'sidebar' ) ) ? '' : 'style="width:100%"' ?>>
                <?php do_action( 'wpforo_content_start' ); ?>
                <?php if( ! in_array( WPF()->current_user_status, [ 'banned', 'trashed' ] ) ) :

                    if( ! WPF()->current_object['is_404'] ) {
                        if( WPF()->current_object['template'] === 'page' ) {
                            wpforo_page();
                        } elseif( wpforo_is_member_template() ) {
                            wpforo_template( 'member' );
                        } elseif( in_array( WPF()->current_object['template'], [ 'forum', 'topic' ] ) ) {
                            wpforo_template( 'forum' );
                            if( WPF()->current_object['template'] === 'topic' ) {
                                wpforo_template( 'topic' );
                            } else {
                                wpforo_admin_cpanel();
                            }
                        } else {
                            wpforo_template();
                        }
                    } else {
                        wpforo_template( '404' );
                    }

                else : ?>
                    <p class="wpf-p-error">
                        <?php wpforo_phrase( 'You have been banned. Please contact the forum administrator for more information.' ) ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php if( is_active_sidebar( wpforo_prefix( 'sidebar' ) ) ) : ?>
                <div class="wpforo-right-sidebar">
                    <?php dynamic_sidebar( wpforo_prefix( 'sidebar' ) ) ?>
                </div>
            <?php endif; ?>
            <div class="wpf-clear"></div>
        </div>

        <?php
        if( wpforo_display_footer() ) include( wpftpl( 'footer.php' ) );
        do_action( 'wpforo_bottom_hook' );
        ?>

    </div><!-- wpforo-wrap -->
</div>

<?php if( WPF()->board->get_current( 'is_standalone' ) ) get_footer(); ?>
