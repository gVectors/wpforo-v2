<?php if( ! defined( "ABSPATH" ) ) exit();
$options = WPF()->settings->info->core['social']['options'];
?>

<input type="hidden" name="wpfaction" value="social_settings_save">

<?php WPF()->settings->header( 'social' ); ?>

<div class="wpf-subtitle">
    <span class="dashicons dashicons-share"></span> <?php _e( 'Share Buttons', 'wpforo' ) ?>
</div>

<!-- Option start -->
<div class="wpf-opt-row" data-wpf-opt="sb">
    <div class="wpf-opt-name" style="width: 40%">
        <label><?php echo esc_html( $options['sb']["label"] ) ?></label>
        <p class="wpf-desc">
			<?php echo esc_html( $options['sb']["description"] ) ?><br/>
        </p>
    </div>
    <div class="wpf-opt-input" style="width: 60%; display: flex; flex-wrap: wrap;">
        <div style="width: 15%; text-align: center; background: #3B5A9A; padding: 1px 18px 3px 18px; margin: 1%;">
            <label for="sb_fb"><img src="<?php echo WPFORO_URL . '/assets/images/sn/fb-m.png' ?>" alt="fb-m.png" align="middle" style="width: 30px"></label><br>
            <input id="sb_fb" type="checkbox" name="social[sb][fb]" value="1" <?php checked( (bool) wpforo_setting( 'social', 'sb', 'fb' ) ) ?>>
        </div>
        <div style="width: 15%; text-align: center; background: #00A3F5; padding: 1px 18px 3px 18px;  margin: 1%;">
            <label for="sb_tw"><img src="<?php echo WPFORO_URL . '/assets/images/sn/tw-m.png' ?>" alt="tw-m.png" align="middle" style="width: 30px"></label><br>
            &nbsp;<input id="sb_tw" type="checkbox" name="social[sb][tw]" value="1" <?php checked( (bool) wpforo_setting( 'social', 'sb', 'tw' ) ) ?>>
        </div>
        <div style="width: 15%; text-align: center; background: #1BD741; padding: 1px 18px 3px 18px;  margin: 1%;">
            <label for="sb_wapp"><img src="<?php echo WPFORO_URL . '/assets/images/sn/wapp-m.png' ?>" alt=wapp-m.png"" align="middle" style="width: 30px"></label><br>
            &nbsp;&nbsp;<input id="sb_wapp" type="checkbox" name="social[sb][wapp]" value="1" <?php checked( (bool) wpforo_setting( 'social', 'sb', 'wapp' ) ) ?>>
        </div>
        <div style="width: 15%; text-align: center; background: #0A75B5; padding: 1px 18px 3px 18px;  margin: 1%;">
            <label for="sb_lin"><img src="<?php echo WPFORO_URL . '/assets/images/sn/lin-m.png' ?>" alt="lin-m.png" align="middle" style="width: 28px"></label><br>
            &nbsp;&nbsp;<input id="sb_lin" type="checkbox" name="social[sb][lin]" value="1" <?php checked( (bool) wpforo_setting( 'social', 'sb', 'lin' ) ) ?>>
        </div>
        <div style="width: 15%; text-align: center; background: #2D76A6; padding: 1px 18px 3px 18px;  margin: 1%;">
            <label for="sb_vk"><img src="<?php echo WPFORO_URL . '/assets/images/sn/vk-m.png' ?>" alt="vk-m.png" align="middle" style="width: 30px"></label><br>
            &nbsp;&nbsp;<input id="sb_vk" type="checkbox" name="social[sb][vk]" value="1" <?php checked( (bool) wpforo_setting( 'social', 'sb', 'vk' ) ) ?>>
        </div>
        <div style="width: 15%; text-align: center; background: #FF7800; padding: 1px 18px 3px 18px;  margin: 1%;">
            <label for="sb_ok"><img src="<?php echo WPFORO_URL . '/assets/images/sn/ok-m.png' ?>" alt="ok-m.png" align="middle" style="width: 30px"></label><br>
            &nbsp;&nbsp;<input id="sb_ok" type="checkbox" name="social[sb][ok]" value="1" <?php checked( (bool) wpforo_setting( 'social', 'sb', 'ok' ) ) ?>>
        </div>
    </div>
	<?php echo WPF()->settings->get_doc_link( $options['sb'] ) ?>
</div>
<!-- Option end -->

<?php
WPF()->settings->form_field( 'social', 'sb_on' );
WPF()->settings->form_field( 'social', 'sb_style' );
WPF()->settings->form_field( 'social', 'sb_type' );
WPF()->settings->form_field( 'social', 'sb_toggle_on' );
?>

<!-- Option start -->
<div class="wpf-opt-row" data-wpf-opt="sb_toggle">
    <div class="wpf-opt-name">
        <label><?php echo esc_html( $options['sb_toggle']["label"] ) ?></label>
        <p class="wpf-desc">
			<?php echo esc_html( $options['sb_toggle']["description"] ) ?>
        </p>
    </div>
    <div class="wpf-opt-input" style="display: flex; justify-content: flex-start; flex-wrap: wrap;">
        <div style="background: #fff; width: 32%; text-align: center; padding: 1px 18px 3px 18px; margin: 1%; border: 1px solid #ddd;">
            <label for="sb_toggle_1"><img src="<?php echo WPFORO_URL . '/assets/images/sn/toggle-1.png' ?>" alt="toggle-1.png" align="middle"></label><br>
            &nbsp;&nbsp;<input id="sb_toggle_1" type="radio" name="social[sb_toggle]" value="1" <?php checked( wpforo_setting( 'social', 'sb_toggle' ), 1 ); ?>>
        </div>
        <div style="background: #fff; width: 32%; text-align: center; padding: 1px 18px 3px 18px; margin: 1%; border: 1px solid #ddd;">
            <label for="sb_toggle_2"><img src="<?php echo WPFORO_URL . '/assets/images/sn/toggle-2.png' ?>" align="middle" alt="toggle-2.png"></label><br>
            &nbsp;&nbsp;<input id="sb_toggle_2" type="radio" name="social[sb_toggle]" value="2" <?php checked( wpforo_setting( 'social', 'sb_toggle' ), 2 ); ?>>
        </div>
        <div style="background: #fff; width: 32%; text-align: center; padding: 1px 18px 3px 18px; margin: 1%; border: 1px solid #ddd;">
            <label for="sb_toggle_3"><img src="<?php echo WPFORO_URL . '/assets/images/sn/toggle-3.png' ?>" align="middle" alt="toggle-3.png"></label><br>
            &nbsp;&nbsp;<input id="sb_toggle_3" type="radio" name="social[sb_toggle]" value="3" <?php checked( wpforo_setting( 'social', 'sb_toggle' ), 3 ); ?>>
        </div>
        <div style="background: #fff; width: 32%; text-align: center; padding: 1px 18px 3px 18px; margin: 1%; border: 1px solid #ddd;">
            <label for="sb_toggle_4"><img src="<?php echo WPFORO_URL . '/assets/images/sn/toggle-4.png' ?>" align="middle" alt="toggle-4.png"></label><br>
            &nbsp;&nbsp;<input id="sb_toggle_4" type="radio" name="social[sb_toggle]" value="4" <?php checked( wpforo_setting( 'social', 'sb_toggle' ), 4 ); ?>>
        </div>
    </div>
	<?php echo WPF()->settings->get_doc_link( $options['sb_toggle'] ) ?>
</div>
<!-- Option end -->
<?php
WPF()->settings->form_field( 'social', 'sb_toggle_type' );
WPF()->settings->form_field( 'social', 'sb_icon' );
?>

<table class="wpforo_settings_table" data-wpf-opt="sb_location">
    <tr>
        <td style="padding: 0;"></td>
    </tr>
    <tr>
        <th style="padding: 15px 0" colspan="2">
            <label style="font-size: 15px; color: #555;font-weight: 600; margin-bottom: 5px; display: block;"><?php _e( 'Share Button Locations', 'wpforo' ); ?></label>
            <p class="wpf-info" style="margin-bottom: 10px;"><?php _e( 'The post sharing toggle can be displayed either on the left side or on the top of each post. The general share buttons can be displayed on both (top and bottom) locations.', 'wpforo' ); ?></p>
            <div style="padding-right: 10px; display: inline-block; width: auto; border-right: 1px solid #ccc; ">
                <p style="text-align: center; margin: 0; font-weight: normal; font-size: 14px;"><?php _e( 'General Share Buttons', 'wpforo' ); ?></p>
                <div style="float: left; background: #fff; display: inline-block; text-align: center; padding: 1px 5px 3px 5px; margin: 10px 5px 10px 0;">
                    <label for="sb_location_4"><img src="<?php echo WPFORO_URL . '/assets/images/sn/location-3.png' ?>" alt="location-3.png" align="middle" style="width: 130px"></label><br>
                    &nbsp;&nbsp;<input id="sb_location_4" type="checkbox" name="social[sb_location][top]" value="1" <?php checked( (bool) wpforo_setting( 'social', 'sb_location', 'top' ) ) ?>>
                </div>
                <div style="float: left; background: #fff; display: inline-block; text-align: center; padding: 1px 5px 3px 5px; margin: 10px 5px 10px 0;">
                    <label for="sb_location_5"><img src="<?php echo WPFORO_URL . '/assets/images/sn/location-5.png' ?>" alt="location-5.png" align="middle" style="width: 130px"></label><br>
                    &nbsp;&nbsp;<input id="sb_location_5" type="checkbox" name="social[sb_location][bottom]" value="1" <?php checked( (bool) wpforo_setting( 'social', 'sb_location', 'bottom' ) ) ?>>
                </div>
                <div style="clear: both;"></div>
            </div>
            <div style="padding-left: 10px; display: inline-block; width: auto;">
                <p style="text-align: center; margin: 0; font-weight: normal; font-size: 14px;"><?php _e( 'Post Sharing Toggle', 'wpforo' ); ?></p>
                <div style="float: left; background: #fff; display: inline-block; text-align: center; padding: 1px 5px 3px 5px; margin: 10px 5px 10px 0;">
                    <label for="sb_location_1"><img src="<?php echo WPFORO_URL . '/assets/images/sn/location-1.png' ?>" alt="location-1.png" align="middle" style="width: 130px"></label><br>
                    &nbsp;&nbsp;<input id="sb_location_1" type="radio" name="social[sb_location_toggle]" value="left" <?php checked( wpforo_setting( 'social', 'sb_location_toggle' ), 'left' ); ?>>
                </div>
                <div style="float: left; background: #fff; display: inline-block; text-align: center; padding: 1px 5px 3px 5px; margin: 10px 5px 10px 0;">
                    <label for="sb_location_3"><img src="<?php echo WPFORO_URL . '/assets/images/sn/location-6.png' ?>" align="middle" style="width: 130px" alt="location-6.png"></label><br>
                    &nbsp;&nbsp;<input id="sb_location_3" type="radio" name="social[sb_location_toggle]" value="right" <?php checked( wpforo_setting( 'social', 'sb_location_toggle' ), 'right' ); ?>>
                </div>
                <div style="float: left; background: #fff; display: inline-block; text-align: center; padding: 1px 5px 3px 5px; margin: 10px 5px 10px 0;">
                    <label for="sb_location_2"><img src="<?php echo WPFORO_URL . '/assets/images/sn/location-2.png' ?>" alt="location-2.png" align="middle" style="width: 130px"></label><br>
                    &nbsp;&nbsp;<input id="sb_location_2" type="radio" name="social[sb_location_toggle]" value="top" <?php checked( wpforo_setting( 'social', 'sb_location_toggle' ), 'top' ); ?>>
                </div>
                <div style="clear: both;"></div>
            </div>
        </th>
    </tr>
</table>
