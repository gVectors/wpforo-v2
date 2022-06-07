<?php if( ! defined( "ABSPATH" ) ) exit(); ?>

<input type="hidden" name="wpfaction" value="styles_settings_save">

<?php
WPF()->settings->header( 'styles' );
WPF()->settings->form_field( 'styles', 'font_sizes' );
WPF()->settings->form_field( 'styles', 'custom_css' );
$colorids = apply_filters( 'wpforo_manageable_colorids', [ 1, 3, 11, 12, 14 ] );
?>

<h3 style="margin:20px 0 0; padding:10px 0; border-bottom:3px solid #F5F5F5; font-size: 15px;" data-wpf-opt="style"><?php _e( 'Forum Styles', 'wpforo' ); ?> &nbsp;<a href="https://wpforo.com/docs/root/wpforo-settings/style-settings/" title="<?php _e( 'Read the documentation', 'wpforo' ) ?>" target="_blank" style="font-size: 14px;"><i class="far fa-question-circle"></i></a> &nbsp;|&nbsp; <a href="https://wpforo.com/docs/root/forum-themes/theme-styles/" target="_blank"
                                                                                                                                                                                                                                                                                                                                                                                                         style="font-size:13px; text-decoration:none;"><?php _e( 'Colors Documentation', 'wpforo' ); ?> &raquo;</a>
</h3>
<table style="width:95%; border:none; padding:5px; margin-left:10px; margin-top:15px;">
    <tbody>
    <tr class="form-field form-required">
        <td class="wpf-dw-td-value-p">
            <table class="wpforo-style-color-wrapper" style="margin-right:10px; width:20px;">
                <tr>
                    <td class="wpfo-settings-style-colorid-hash">#</td>
                </tr>
				<?php
				foreach( $colorids as $colorid ) {
					printf( '<tr><td><div class="wpfo-settings-style-colorid">%1$d</div></td></tr>', $colorid );
				}
				?>
            </table>
			<?php
			foreach( wpforo_setting( 'styles', 'color_styles' ) as $color_style => $colors ): ?>
                <table class="wpforo-style-color-wrapper" style="border-right:2px solid #eee; margin-right:10px; padding-left:5px; <?php echo ( $color_style === wpforo_setting( 'styles', 'color_style' ) ) ? 'background: #E8FFE5; width: 130px; text-align: center;' : 'background: transparent'; ?>">
                    <tr>
                        <td>
                            <div style="float: left; text-align: center; width: 27px;">
                                <input style="margin: 0;" <?php checked( $color_style === wpforo_setting( 'styles', 'color_style' ) ) ?> type="radio" name="styles[color_style]" value="<?php wpfo( $color_style ) ?>" id="wpforo_stle_<?php wpfo( $color_style ) ?>">
                            </div>
                            <div style="text-transform: uppercase; text-align: left; float: left; font-weight: bold; font-size: 13px; padding-bottom: 5px;"><label for="wpforo_stle_<?php wpfo( $color_style ) ?>">&nbsp;<?php _e( $color_style, 'wpforo' ); ?></label></div>
                            <div style="clear: both;"></div>
                        </td>
                    </tr>
					<?php foreach( $colorids as $colorid ) : ?>
                        <tr>
                            <td style="border-bottom:1px solid #ddd;">
                                <div class="wpforo-style-field">
									<?php if( $color_style === wpforo_setting( 'styles', 'color_style' ) ): ?>
                                        <input class="wpforo-color-field" name="styles[color_styles][<?php wpfo( $color_style ) ?>][<?php wpfo( $colorid ) ?>]" type="text" value="<?php wpfo( strtoupper( $colors[ $colorid ] ) ); ?>" title="<?php wpfo( strtoupper( $colors[ $colorid ] ) ); ?>">
									<?php else: ?>
                                        <input style="width:90%; height: 23px; box-sizing: border-box; padding:0;" name="styles[color_styles][<?php wpfo( $color_style ) ?>][<?php wpfo( $colorid ); ?>]" type="color" value="<?php wpfo( strtoupper( $colors[ $colorid ] ) ); ?>" title="<?php wpfo( strtoupper( $colors[ $colorid ] ) ); ?>">
									<?php endif; ?>
                                </div>
                            </td>
                        </tr>
					<?php endforeach; ?>
                </table>
			<?php endforeach; ?>
            <div style="clear:both;"></div>
            <div class="wpf-color-desc">
                <ul>
                    <li><strong style="text-transform: uppercase;"><?php _e( 'Color', 'wpforo' ) ?> #1</strong>
                        <ul>
                            <li><?php _e( 'Wherever you see white background and white font color', 'wpforo' ) ?></li>
                        </ul>
                    </li>
                    <li><strong style="text-transform: uppercase;"><?php _e( 'Color', 'wpforo' ) ?> #3</strong>
                        <ul>
                            <li><?php _e( 'Post content font color', 'wpforo' ) ?></li>
                            <li><?php _e( 'Forum menu bar background color', 'wpforo' ) ?></li>
                            <li><?php _e( 'Footer top bar background color', 'wpforo' ) ?></li>
                            <li><?php _e( 'Footer bottom "powered by" bar background color', 'wpforo' ) ?></li>
                        </ul>
                    </li>
                    <li><span style="color: #3366ff; text-transform: uppercase;"><strong><?php _e( 'Color', 'wpforo' ) ?> #11</strong></span>
                        <ul>
                            <li><?php _e( "Links' hover/active font color", 'wpforo' ) ?></li>
                            <li><?php _e( 'Topic/post action link hover color (reply, quote, like, sticky, closed, move, delete)', 'wpforo' ) ?></li>
                        </ul>
                    </li>
                    <li><span style="color: #3366ff; text-transform: uppercase;"><strong><?php _e( 'Color', 'wpforo' ) ?> #12</strong></span> - <span style="color: #ff0000;"><strong>[ PRIMARY COLOR ]</strong></span>
                        <ul>
                            <li><?php _e( 'Buttons background color', 'wpforo' ) ?></li>
                            <li><?php _e( 'Active menu background color', 'wpforo' ) ?></li>
                            <li><?php _e( 'Category panel background color', 'wpforo' ) ?></li>
                            <li><?php _e( 'Topic list head panel background color', 'wpforo' ) ?></li>
                            <li><?php _e( 'Post list head panel background color', 'wpforo' ) ?></li>
                            <li><?php _e( 'Top right pop-up message background color', 'wpforo' ) ?></li>
                        </ul>
                    </li>
                    <li><span style="color: #3366ff; text-transform: uppercase;"><strong><?php _e( 'Color', 'wpforo' ) ?> #14</strong></span>
                        <ul>
                            <li><?php _e( 'Button border color and button hover background color', 'wpforo' ) ?></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </td>
    </tr>
    </tbody>
</table>
<div style="clear: both;"></div>
<hr>

<?php if( $dynamic_css = WPF()->tpl->generate_dynamic_css() ) : ?>
    <script type="text/javascript">
        function wpforo_input_select_all_and_copy (t) {
            t.select()
            if (document.execCommand('copy')) {
                jQuery('#dynamic-css-code-wrap').addClass('wpf_copy_animate')
                setTimeout(function () {
                    jQuery('#dynamic-css-code-wrap').removeClass('wpf_copy_animate')
                }, 1000)
            }
        }
    </script>
    <div id="dynamic-css-notice-wrap">
        <div id="dynamic-css-help-steps-wrap">
            <p style="font-size: 15px;">
                <b><i class="fas fa-info-circle" aria-hidden="true"></i> <?php _e( 'Problems with colors?', 'wpforo' ); ?></b><br>
				<?php printf( __( 'After changing and saving colors, go to the forum front-end and press %s twice. If you don\'t see any change, please follow to the instruction below.', 'wpforo' ), '<b>CTRL+F5</b>' ); ?>
            </p>
            <p style="font-size: 15px;"><?php _e( 'In most cases, this problem comes from your server file writing permissions. Files are not permitted to change, thus the forum color provider colors.css file is not updated with your changes. If you cannot fix this issue in hosting server, then the following easy steps can solve your problem:', 'wpforo' ) ?></p>
            <ol>
                <li style="font-size: 14px; margin-bottom: 1px; line-height: 1.5"><?php printf( __( 'Create colors.css file or simply download %s file with the CSS code provided in the textarea below,', 'wpforo' ), '<code>colors.css</code>' ) ?></li>
                <li style="font-size: 14px; margin-bottom: 1px; line-height: 1.5"><?php printf( __( 'Upload and replace %s file in %s directory,', 'wpforo' ), '<code>colors.css</code>', '<code>' . WPF()->tpl->template_dir . DIRECTORY_SEPARATOR . '</code>' ) ?></li>
                <li style="font-size: 14px; margin-bottom: 1px; line-height: 1.5"><?php printf( __( 'Delete website cache, reset CSS file optimizer and minifier plugins caches, purge CDN data (if you have), then go to the forum front-end and press %s twice.', 'wpforo' ), '<b>CTRL+F5</b>' ) ?></li>
            </ol>
        </div>
        <div id="dynamic-css-code-wrap">
            <label for="colors_css" class="dynamic-css-fname"><i class="fas fa-file-code"></i>&nbsp;colors.css</label>
            <textarea id="colors_css" readonly class="dynamic-css-code" rows="10" onclick="wpforo_input_select_all_and_copy(this)"><?php echo $dynamic_css ?></textarea>
            <div class="wpf_copied_txt"><span><?php _e( 'Copied', 'wpforo' ) ?></span></div>
        </div>
        <a style="font-size: 14px; text-decoration: none;" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'settings' ) . '&tab=styles&wpfaction=colors_css_download' ), 'dynamic_css_download' ) ?>"><i class="fas fa-file-download"></i> <?php _e( 'Download', 'wpforo' ) ?> colors.css</a>
        <br style="clear: both">
    </div>
<?php endif; ?>

<script>jQuery(document).ready(function ($) {$(function () { $('.wpforo-color-field').wpColorPicker() })})</script>
