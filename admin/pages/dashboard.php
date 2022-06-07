<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;
?>

<div id="wpf-admin-wrap" class="wrap">
    <h1 style="padding:30px 10px 10px;"><?php _e( 'Forum Dashboard', 'wpforo' ); ?></h1>
	<?php WPF()->notice->show() ?>

    <div id="dashboard-widgets-wrap">
        <div class="metabox-holder" id="dashboard-widgets">

			<?php if( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' ) ): ?>

                <div class="postbox-container" style="width: 100%;">
                    <div class="wpf-box-wrap">

                        <div class="postbox wpf-dash-box" id="wpforo_dashboard_widget_statistic">
                            <h2 class="wpf-box-header"><span><?php _e( 'Board Statistic', 'wpforo' ); ?></span></h2>
                            <div class="inside">
                                <div class="main">
                                    <table style="width:98%; margin:0 auto; text-align:left;">
										<?php $statistic = WPF()->statistic(); ?>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td"><?php _e( 'Forums', 'wpforo' ); ?></td>
                                            <td class="wpf-dw-td-value"><?php echo intval( $statistic['forums'] ) ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td"><?php _e( 'Topics', 'wpforo' ); ?></td>
                                            <td class="wpf-dw-td-value"><?php echo intval( $statistic['topics'] ) ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td"><?php _e( 'Posts', 'wpforo' ); ?></td>
                                            <td class="wpf-dw-td-value"><?php echo intval( $statistic['posts'] ) ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td"><?php _e( 'Members', 'wpforo' ); ?></td>
                                            <td class="wpf-dw-td-value"><?php echo intval( $statistic['members'] ) ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td"><?php _e( 'Members Online', 'wpforo' ); ?></td>
                                            <td class="wpf-dw-td-value"><?php echo intval( $statistic['online_members_count'] ) ?></td>
                                        </tr>
										<?php
										$size_avatar = wpforo_dir_size( WPF()->folders['avatars']['dir'] );
										$size_da     = wpforo_dir_size( WPF()->folders['default_attachments']['dir'] );
										$size_aa     = wpforo_dir_size( WPF()->folders['attachments']['dir'] );
										?>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td"><?php _e( 'Avatars Size', 'wpforo' ); ?></td>
                                            <td class="wpf-dw-td-value"><?php echo wpforo_human_filesize( $size_avatar ); ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td"><?php _e( 'Default Attachments Size', 'wpforo' ); ?></td>
                                            <td class="wpf-dw-td-value"><?php echo wpforo_human_filesize( $size_da ); ?></td>
                                        </tr>
										<?php if( isset( $statistic['attachments'] ) && $statistic['attachment_sizes'] ) : ?>
                                            <tr class="wpf-dw-tr">
                                                <td class="wpf-dw-td"><?php _e( 'Advanced Attachments', 'wpforo' ); ?></td>
                                                <td class="wpf-dw-td-value"><?php echo esc_html( $statistic['attachments'] ) ?><?php _e( 'file(s)', 'wpforo' ); ?></td>
                                            </tr>
                                            <tr class="wpf-dw-tr">
                                                <td class="wpf-dw-td"><?php _e( 'Advanced Attachments Size', 'wpforo' ); ?></td>
                                                <td class="wpf-dw-td-value"><?php echo wpforo_human_filesize( $size_aa ); ?></td>
                                            </tr>
										<?php endif ?>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td"><?php _e( 'Total Size', 'wpforo' ); ?></td>
                                            <td class="wpf-dw-td-value">
                                                <strong style="font-size:14px;">
													<?php
													$total = $size_avatar + $size_da + $size_aa;
													echo wpforo_human_filesize( $total );
													?>
                                                </strong>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div><!-- widget / postbox -->

                        <?php if( wpforo_current_user_is( 'admin' ) ) : ?>
                            <div class="postbox wpf-dash-box" id="wpforo_dashboard_widget_0">
                                <h2 class="wpf-box-header">
                                    <span><?php _e( 'Forum Maintenance', 'wpforo' ); ?></span>
                                </h2>
                                <p class="wpf-info" style="padding:10px;"><?php _e( "This process may take a few seconds or dozens of minutes, please be patient and don't close this page. If you got 500 Server Error please don't worry, the data updating process is still working in MySQL server.", 'wpforo' ); ?></p>
                                <div class="inside">
                                    <div class="main">
                                        <div style="width:100%; padding:7px 0;">
                                            <?php
                                            $reset_cache           = wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) . '&wpfaction=reset_all_caches' ), 'wpforo_reset_cache' );
                                            $clean_up              = wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) . '&wpfaction=clean_up' ), 'wpforo_clean_up' );
                                            $reset_forums_stat_url = wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) . '&wpfaction=reset_forums_stats' ), 'wpforo_reset_forums_stat' );
                                            $reset_topics_stat_url = wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) . '&wpfaction=reset_topics_stats' ), 'wpforo_reset_topics_stat' );
                                            $reset_phrase_cache    = wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) . '&wpfaction=reset_phrase_cache' ), 'wpforo_reset_phrase_cache' );
                                            $recrawl_phrases       = wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) . '&wpfaction=recrawl_phrases' ), 'wpforo_recrawl_phrases' );
                                            $rebuild_threads       = wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) . '&wpfaction=rebuild_threads' ), 'wpforo_rebuild_threads' );
                                            ?>
                                            <a href="<?php echo esc_url( $reset_cache ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Delete All Caches', 'wpforo' ); ?></a>&nbsp;
                                            <a href="<?php echo esc_url( $clean_up ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Clean Up', 'wpforo' ); ?></a>&nbsp;
                                            <a href="<?php echo esc_url( $reset_forums_stat_url ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Update Forums Statistic', 'wpforo' ); ?></a>&nbsp;
                                            <a href="<?php echo esc_url( $reset_topics_stat_url ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Update Topics Statistic', 'wpforo' ); ?></a>&nbsp;
                                            <a href="<?php echo esc_url( $recrawl_phrases ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Rebuild Phrases', 'wpforo' ); ?></a>&nbsp;
                                            <a href="<?php echo esc_url( $reset_phrase_cache ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Delete Phrase Cache', 'wpforo' ); ?></a>&nbsp;
                                            <a href="<?php echo esc_url( $rebuild_threads ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Rebuild Threads', 'wpforo' ); ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- widget / postbox -->
                        <?php endif ?>

						<?php do_action( 'wpforo_dashboard_widgets_col2', WPF() ); ?>

                    </div><!-- normal-sortables -->
                </div><!-- wpforo_postbox_container -->
			<?php endif; ?>

            <div class="postbox-container" id="postbox-container-3">
                <div class="wpf-box-wrap">

					<?php do_action( 'wpforo_dashboard_widgets_col3', WPF() ); ?>

                </div><!-- normal-sortables -->
            </div><!-- wpforo_postbox_container -->

        </div><!-- dashboard-widgets -->
    </div><!-- dashboard-widgets-wrap -->

</div><!-- wpwrap -->

