<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;
?>

<div id="wpf-admin-wrap" class="wrap">
    <h1 style="margin: 0; padding: 0; line-height: 10px;">&nbsp</h1>
	<?php WPF()->notice->show() ?>

    <div id="dashboard-widgets-wrap" style="padding-top:10px;">
        <div class="metabox-holder" id="dashboard-widgets">

            <div class="postbox-container" id="postbox-container-0" style="width:100%;">
                <div class="wpf-box-wrap" style="min-height:60px;">

                    <div class="postbox" id="wpforo_dashboard_widget_0" style="margin: 10px; padding: 20px 15px 10px 15px;">
                        <div class="inside">
                            <div class="main" style="padding:5px 15px 15px 15px;">
                                <div style="float:left; vertical-align:top; width:calc(100% - 150px);;">
                                    <p style="font-size:30px; margin:0 0 10px; font-family:Constantia, 'Lucida Bright', 'DejaVu Serif', Georgia, serif">
                                        <?php _e( 'Welcome to wpForo', 'wpforo' ); echo ' ' . esc_html( WPFORO_VERSION ) ?>
                                    </p>
                                    <p style="margin:0; font-size:14px; font-family:'Lucida Bright', 'DejaVu Serif', Georgia, serif">
										<?php _e( 'Thank you for using wpForo! wpForo is a professional bulletin board for WorPress, and the only forum software which comes with Multi-layout template system.
                                    The "Extended", "Simplified", "Q&A" and "Threaded" layouts fit almost all type of discussions needs. You can use wpForo for small and extremely large communities. If you found some issue or bug please open a support topic in wpForo Support forum at wpForo.com. If you liked wpForo please leave some good review for this plugin. We really need your good reviews. 
                                    If you didn\'t like wpForo please leave a list of issues and requirements you\'d like us to fix and add in our support forum. We\'re here to help you and improve wpForo as much as possible.',
											'wpforo'
										); ?>
                                    </p>
                                    <h3 style="font-size: 18px; margin-top: 20px; font: full-width:500;"><?php _e("What's New in wpForo 2", 'wpforo') ?></h3>
                                    <ul class="wpf-wn-list">
                                        <li><span class="wpf-wn-label"><?php _e("Multi-board system:", 'wpforo') ?></span> <?php _e("Allows to create multiple separate forum boards in the same WordPress website. You can create fully separate forums in different pages,", 'wpforo') ?></li>
                                        <li><span class="wpf-wn-label"><?php _e("Multi-language forums:", 'wpforo') ?></span> <?php _e("Using the multi-board system you can create multiple separate forums for each language of your website. The forums will have different pages, categories and threads,", 'wpforo') ?></li>
                                        <li><span class="wpf-wn-label"><?php _e("New Forum Theme:", 'wpforo') ?></span> <?php _e("The new version comes with completely redesigned forum layouts and style. It brings modern and clean look and feel for all 4 forum layouts (Extended, Simplified, Q&A and Threaded),", 'wpforo') ?></li>
                                        <li><span class="wpf-wn-label"><?php _e("New Member Profile System:", 'wpforo') ?></span> <?php _e("wpForo 2 comes with more powerful member profile system with awesome design, so in most cases you won't need to use other profile builder plugins,", 'wpforo') ?></li>
                                        <li><span class="wpf-wn-label"><?php _e("Topic Overview:", 'wpforo') ?></span> <?php _e("You can see a small panel on the top of all topics, which provides lots of quick information about the topic. Different statistic information and a shorthand tree of the whole topic with discussion threads and nested replies in one place. This makes it very quick find the posts you need in the topic,", 'wpforo') ?></li>
                                        <li><span class="wpf-wn-label"><?php _e("And lots of more:", 'wpforo') ?></span> <a href="https://wpforo.com/community/wpforo-announcements/wpforo-2-0-0-is-released/" target="_blank"><?php _e("wpForo 2 release summary", 'wpforo') ?> &raquo;</a></li>
                                    </ul>
                                </div>
                                <div style="float:right; vertical-align:top; padding-right:0; width:150px; text-align:right; padding-top:20px;">
                                    <img class="wpforo-dashboard-logo" src="<?php echo WPFORO_URL ?>/assets/images/wpforo-logo.png">
                                    <p style="font-size:11px; color:#B1B1B1; font-style:italic; text-align:right; line-height:14px; padding-top:15px; margin:0;">
										<?php _e( 'Thank you!<br> Sincerely yours,<br> gVectors Team', 'wpforo' ); ?>&nbsp;
                                    </p>
                                </div>
                                <div style="clear:both;"></div>
                            </div>
                        </div>
                    </div><!-- widget / postbox -->

                </div>
            </div>

			<?php if( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' ) ): ?>
                <div class="postbox-container" style="width: 100%;">
                    <div class="wpf-box-wrap">

                        <div class="postbox wpf-dash-box" id="wpforo_dashboard_widget_server">
                            <h2 class="wpf-box-header"><span><?php _e( 'Server Information', 'wpforo' ); ?></span></h2>
                            <div class="inside">
                                <div class="main">
                                    <table style="width:98%; margin:0 auto; text-align:left;">
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td">USER AGENT</td>
                                            <td class="wpf-dw-td-value"><?php echo esc_html( $_SERVER['HTTP_USER_AGENT'] ) ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td">Web Server</td>
                                            <td class="wpf-dw-td-value"><?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] ) ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td">PHP Version</td>
                                            <td class="wpf-dw-td-value"><?php echo phpversion(); ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td">MySQL Version</td>
                                            <td class="wpf-dw-td-value"><?php echo WPF()->db->get_var( "SELECT VERSION()" ); ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td">PHP Max Post Size</td>
                                            <td class="wpf-dw-td-value"><?php echo ini_get( 'post_max_size' ); ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td">PHP Max Upload Size</td>
                                            <td class="wpf-dw-td-value"><?php echo ini_get( 'upload_max_filesize' ); ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td">PHP Memory Limit</td>
                                            <td class="wpf-dw-td-value"><?php echo ini_get( 'memory_limit' ); ?></td>
                                        </tr>
                                        <tr class="wpf-dw-tr">
                                            <td class="wpf-dw-td">PHP DateTime Class</td>
                                            <td class="wpf-dw-td-value" style="line-height: 18px!important;">
                                                <?php echo ( class_exists( 'DateTime' ) && class_exists( 'DateTimeZone' ) && method_exists( 'DateTime', 'setTimestamp' ) ) ? '<span class="wpf-green">' . __( 'Available', 'wpforo' ) . '</span>' : '<span class="wpf-red">' . __(
                                                        'The setTimestamp() method of PHP DateTime class is not available. Please make sure you use PHP 5.4 and higher version on your hosting service.',
                                                        'wpforo'
                                                    ) . '</span> | <a href="http://php.net/manual/en/datetime.settimestamp.php" target="_blank">more info&raquo;</a>'; ?> </td>
                                        </tr>
                                        <?php do_action( 'wpforo_dashboard_widget_server' ) ?>
                                    </table>
                                </div>
                            </div>
                        </div><!-- widget / postbox -->

                        <?php if( wpforo_current_user_is( 'admin' ) ) : ?>
                            <div class="postbox wpf-dash-box" id="wpforo_dashboard_widget_0">
                                <h2 class="wpf-box-header"><span><?php _e( 'General Maintenance', 'wpforo' ); ?></span></h2>
                                <p class="wpf-info" style="padding:10px;"><?php _e( "This process may take a few seconds or dozens of minutes, please be patient and don't close this page. If you got 500 Server Error please don't worry, the data updating process is still working in MySQL server.", 'wpforo' ); ?></p>
                                <div class="inside">
                                    <div class="main">

                                        <div style="width:100%; padding:7px;">
                                            <?php
                                            $synch_user_profiles   = wp_nonce_url( admin_url( 'admin.php?page=wpforo-overview&wpfaction=synch_user_profiles' ), 'wpforo_synch_user_profiles' );
                                            $reset_users_stat_url  = wp_nonce_url( admin_url( 'admin.php?page=wpforo-overview&wpfaction=reset_users_stats' ), 'wpforo_reset_users_stat' );
                                            $reset_user_cache      = wp_nonce_url( admin_url( 'admin.php?page=wpforo-overview&wpfaction=reset_user_cache' ), 'wpforo_reset_user_cache' );
                                            ?>
                                            <a href="<?php echo esc_url( $reset_users_stat_url ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Update Users Statistic', 'wpforo' ); ?></a>&nbsp;
                                            <a href="<?php echo esc_url( $reset_user_cache ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Delete User Cache', 'wpforo' ); ?></a>&nbsp;
                                            <a href="<?php echo esc_url( $synch_user_profiles ); ?>" style="min-width:160px; margin-bottom:10px; text-align:center;" class="button button-secondary"><?php _e( 'Synch User Profiles', 'wpforo' ); ?></a>&nbsp;
                                        </div>

                                    </div>
                                </div>
                            </div><!-- widget / postbox -->
                        <?php endif ?>

                        <div class="postbox wpf-dash-box" id="wpforo_dashboard_widget_1">
                            <h2 class="wpf-box-header"><span><?php _e( 'General Information', 'wpforo' ); ?></span></h2>
                            <div class="inside">
                                <div class="main">
                                    <ul>
                                        <li class="post-count"><?php _e( 'You are currently running', 'wpforo' ); ?> wpForo <?php echo esc_html( WPFORO_VERSION ) ?></li>
                                        <li class="page-count"><?php _e( 'Current active theme', 'wpforo' ); ?>: <?php echo WPF()->tpl->theme ?></li>
                                        <li class="page-count"><?php _e( 'wpForo Community', 'wpforo' ); ?>: <a href="https://wpforo.com/community/">https://wpforo.com/community/</a></li>
                                        <li class="page-count"><?php _e( 'wpForo Documentation', 'wpforo' ); ?>: <a href="https://wpforo.com/documentation/">https://wpforo.com/documentation/</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div><!-- widget / postbox -->

						<?php do_action( 'wpforo_dashboard_widgets_col1' ); ?>

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

