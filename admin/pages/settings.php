<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;
if( ! WPF()->usergroup->can( 'ms' ) ) exit;
$tab = sanitize_key( (string) wpfval( $_GET, 'wpf_tab' ) );
WPF()->settings->init_info();
?>

<div class="wrap" style="margin:10px 20px 0 20px">
    <div id="wpf-setbox" class="wpf-dash">
        <!-- wpf-setbox-head start -->
        <div class="wpf-setbox-head">
            <div class="wpf-head-logo">
                <img src="<?php echo esc_url_raw( WPFORO_URL . "/assets/images/dashboard/wpforo-logo.png" ); ?>" alt="wpForo Logo">
            </div>
            <div class="wpf-head-title">
				<?php //esc_html_e("wpForo", "wpforo") ?>
            </div>
            <div class="wpf-head-info">
                <span><a href="https://wpforo.com/docs/wpforo-2/" target="_blank"><?php esc_html_e( "Documentation", "wpforo" ); ?></a></span>
                <span><a href="https://wpforo.com/support/" target="_blank"><?php esc_html_e( "Support", "wpforo" ); ?></a></span>
                <span><a href="https://gvectors.com/product-category/wpforo/" target="_blank"><?php esc_html_e( "Addons", "wpforo" ); ?></a></span>
            </div>
        </div>
        <h1 style="width:0;height:0;margin:0;padding:0;"></h1>
		<?php WPF()->notice->show(); ?>
		<?php do_action( 'wpforo_settings_page_top' ) ?>
		<?php do_action( "wpforo_option_page" ); ?>
		<?php settings_errors( "wpforo" ); ?>
        <!-- wpf-setbox-head end -->

        <!-- wpf-setbox-body start -->
        <div id="wpf-admin-wrap" class="wpf-setbox-body">
            <div class="wpf-section">
                <h3><?php esc_html_e( "Forum Settings", "wpforo" ) ?></h3>
                <div class="wpf-opt-search">
                    <input id="wpf-opt-search-field" type="text" name="" value="" placeholder="<?php esc_attr_e( "Find an option...", "wpforo" ) ?>"/>
                    <span class="dashicons dashicons-search"></span>
                    <div id="wpf-opt-search-results"></div>
                </div>
            </div>

			<?php if( ! $tab || ( ! isset( WPF()->settings->info->core[ $tab ] ) && ! isset( WPF()->settings->info->addons[ $tab ] ) ) ) : ?>
                <!-- wpf-box-wrap start -->
                <div class="wpf-box-wrap wpf-settings-home">
					<?php
					foreach( WPF()->settings->info->core as $tab_key => $setting ) {
						if( ( $_GET['page'] === 'wpforo-base-settings' && ! $setting['base'] && wpforo_is_multiboard() ) || ( $_GET['page'] !== 'wpforo-base-settings' && $setting['base'] && wpforo_is_multiboard() ) ) {
							continue;
						}
						?>
                        <!-- Settings start -->
                        <div class="wpf-box">
                            <div class="wpf-box-info wpf-<?php echo esc_attr( $setting["status"] ); ?>">
                                <img src="<?php echo esc_url_raw( WPFORO_URL . "/assets/images/dashboard/" . $setting["status"] . ".png" ); ?>">
                            </div>
                            <div class="wpf-box-head">
                                <a href="<?php echo esc_url_raw( admin_url( "admin.php?page=" . $_GET['page'] . "&wpf_tab=" . $tab_key ) ); ?>" title="<?php esc_attr_e( "Open Settings", "wpforo" ) ?>">
                                    <?php echo $setting["icon"]; ?>
                                </a>
                            </div>
                            <div class="wpf-box-foot">
                                <div class="wpf-box-title">
                                    <a href="<?php echo esc_url_raw( admin_url( "admin.php?page=" . $_GET['page'] . "&wpf_tab=" . $tab_key ) ); ?>" title="<?php esc_attr_e( "Open Settings", "wpforo" ) ?>">
                                        <?php echo $setting["title"] ?>
                                    </a>
                                </div>
                                <div class="wpf-box-arrow">
                                    <img src="<?php echo esc_url_raw( WPFORO_URL . "/assets/images/dashboard/arrow-right.png" ); ?>">
                                </div>
                            </div>
                        </div>
                        <!-- Settings end -->
						<?php
					}
					?>
                    <div class="wpf-clear"></div>
                </div>
                <!-- wpf-box-wrap end -->
				<?php if( WPF()->settings->info->addons ): ?>
                <div class="wpf-section">
                    <h3><?php esc_html_e( "Addons Settings", "wpforo" ) ?></h3>
                </div>
                <!-- wpf-box-wrap start -->
                <div class="wpf-box-wrap wpf-box-addons wpf-settings-home">
					<?php foreach( WPF()->settings->info->addons as $addon_key => $addon ) {
						if( ( $_GET['page'] === 'wpforo-base-settings' && ! $addon['base'] && wpforo_is_multiboard() ) || ( $_GET['page'] !== 'wpforo-base-settings' && $addon['base'] && wpforo_is_multiboard() ) ) {
							continue;
						}
						?>
                        <!-- Addon Settings start -->
                        <a href="<?php echo esc_url_raw( admin_url( "admin.php?page=" . $_GET['page'] . "&wpf_tab=" . $addon_key ) ); ?>">
                            <div class="wpf-box">
                                <div class="wpf-body">
                                    <div class="wpf-icon">
                                        <img src="<?php echo esc_url_raw( WPFORO_URL . "/assets/images/dashboard/addon.png" ); ?>" style="height: 20px; padding: 0px;"/>
                                    </div>
                                    <div class="wpf-title">
										<?php echo esc_html( $addon["title"] ); ?>
                                    </div>
                                    <div class="wpf-more">
                                        <img src="<?php echo esc_url_raw( WPFORO_URL . "/assets/images/dashboard/dots.png" ); ?>" style="height: 18px; padding-top: 2px;"/>
                                    </div>
                                </div>
                            </div>
                        </a>
                        <!-- Addon Settings end -->
					<?php } ?>
                </div>
                <!-- wpf-box-wrap end -->
                <div class="wpf-clear"></div>
			<?php endif; ?>
			<?php else: ?>
                <div id="wpf-settings-tab" class="wpf-box-wrap wpf-settings-tab_<?php echo esc_attr( $tab ); ?>" style="align-items:flex-start;">
                    <!-- Settings Content start -->
                    <div class="wpf-box wpf-setcon">
						<?php $setting = ( wpfkey( WPF()->settings->info->core, $tab ) ) ? WPF()->settings->info->core[ $tab ] : WPF()->settings->info->addons[ $tab ]; ?>
                        <div class="wpf-setcon-head">
							<?php echo esc_html( $setting["title"] ); ?>
                            <a class="wpf-back" href="<?php echo esc_url_raw( admin_url( "admin.php?page=" . wpfval( $_GET, 'page' ) ) ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span><?php esc_html_e( "Back", "wpforo" ) ?></a>
                        </div>
                        <div class="wpf-setcon-body">
                            <form method="POST" enctype="multipart/form-data">
								<?php
								wp_nonce_field( "wpforo_settings_save_" . sanitize_title( $tab ) );
								if( is_callable( $setting['callback_for_page'] ) ) call_user_func( $setting['callback_for_page'], $setting );
								do_action( "wpforo_settings_after", $tab, $setting );
								?>
                                <div class="wpf-opt-row" style="flex-wrap: wrap; margin-top: 50px; display: flex;">
                                    <div style="display: flex;">
                                        <input style="padding-left: 21px; padding-right: 21px;" type="submit" class="button button-primary" name="save" value="<?php esc_attr_e( "Save Options", "wpforo" ); ?>">
			                            <?php if( ! $setting['base'] && wpforo_is_multiboard() ) : ?>
                                            <input style="order: -1;" type="submit" class="button button-primary" name="save_for_all" value="<?php esc_attr_e( "Save Options for All Boards", "wpforo" ); ?>">
			                            <?php endif; ?>
                                    </div>
                                    <div style="order: -1;">
                                        <input type="submit" class="button" value="<?php _e( 'Reset Options', 'wpforo' ); ?>" name="reset" onclick="return confirm('<?php wpforo_phrase( 'Do you really want to reset options?' ) ?>')"/>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Settings Content end -->
                    <!-- Settings Sidebar start -->
                    <div class="wpf-setbar">
                        <ul class="wpf-box wpf-menu-group">
                            <li class="wpf-menu-head"><?php esc_html_e( "Settings", "wpforo" ) ?> <span class="dashicons dashicons-arrow-up"></span></li>
							<?php foreach( WPF()->settings->info->core as $tab_key => $setting ): ?>
                                <li<?php if( $tab === $tab_key ) echo " class='wpf-active'"; ?>><a href="<?php echo esc_url_raw( admin_url( "admin.php?page=" . ( ( $setting['base'] && wpforo_is_multiboard() ) ? 'wpforo-base-settings' : wpforo_prefix_slug( 'settings' ) ) . "&wpf_tab=" . $tab_key ) ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span> <?php echo esc_html( $setting["title"] ); ?></a></li>
							<?php endforeach; ?>
                        </ul>
						<?php if( WPF()->settings->info->addons ): ?>
                            <ul class="wpf-box wpf-menu-group">
                                <li class="wpf-menu-head"><?php esc_html_e( "Addons Settings", 'wpforo' ) ?> <span class="dashicons dashicons-arrow-up"></span></li>
								<?php foreach( WPF()->settings->info->addons as $addon_key => $addon ): ?>
                                    <li<?php if( $tab === $addon_key ) echo " class='wpf-active'"; ?>><a href="<?php echo esc_url_raw( admin_url( "admin.php?page=" . ( ( $addon['base'] && wpforo_is_multiboard() ) ? 'wpforo-base-settings' : wpforo_prefix_slug( 'settings' ) ) . "&wpf_tab=" . $addon_key ) ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span> <?php echo esc_html( $addon["title"] ); ?></a></li>
								<?php endforeach; ?>
                            </ul>
						<?php endif; ?>
                    </div>
                    <!-- Settings Sidebar end -->
                    <div class="wpf-clear"></div>
                </div>
                <script>
                    jQuery(document).ready(function ($) {
                        $(document).on('click', '.wpf-setbar .wpf-menu-head .dashicons-arrow-down, .wpf-setbar .wpf-menu-head .dashicons-arrow-up', function () {
                            var $this = $(this)
                            var up = $this.hasClass('dashicons-arrow-up')
                            $this.toggleClass('dashicons-arrow-down dashicons-arrow-up')
                            if (up) {
                                $this.parents('.wpf-menu-group').find('li:not(.wpf-menu-head)').hide()
                            } else {
                                $this.parents('.wpf-menu-group').find('li:not(.wpf-menu-head)').show()
                            }
                        })
                    })
                </script>
			<?php endif; ?>
        </div>
        <!-- wpf-setbox-body end -->

    </div>
</div>
