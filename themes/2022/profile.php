<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

$user = wpforo_get_current_object_user();
?>

<div class="wpforo-profile">
	<?php if( $user && ( WPF()->current_userid == $user['userid'] || WPF()->usergroup->can( 'vprf' ) ) ) :
		$rating_enabled = wpforo_setting( 'rating', 'rating' ) && in_array( $user['groupid'], wpforo_setting( 'rating', 'rating_badge_ug' ), true );
		$secondary_group_names = ( $user['secondary_groupids'] ) ? WPF()->usergroup->get_secondary_group_names( $user['secondary_groupids'] ) : [];
    ?>

    <div class="wpforo-profile-head" <?php wpforo_profile_head_attrs( $user ) ?>>
        <div class="wpforo-profile-head-panel">
            <?php if( WPF()->usergroup->can( 'va' ) && wpforo_setting( 'profiles', 'avatars' ) ): ?>
                <div class="wpf-profile-avatar">
                    <?php echo wpforo_user_avatar( $user, 150, 'alt="' . esc_attr( wpforo_user_dname( $user ) ) . '"', true ); ?>
                    <div class="wpf-profile-online"><?php WPF()->member->show_online_indicator( $user['userid'] ) ?></div>
                </div>
            <?php endif; ?>
            <div class="wpforo-profile-head-data">
                <div class="wpforo-profile-head-top">
                    <div class="wpf-profile-details">
                        <div class="wpfp-name"><?php wpforo_user_dname( $user, true ) ?></div>
                    </div>
                    <div class="wpf-profile-head-right">
                        <?php do_action( 'wpforo_profile_head_right', $user ) ?>
                    </div>
                </div>
                <div class="wpforo-profile-head-bottom">
                    <div class="wpfp-box wpfp-ug">
                        <?php wpforo_member_title($user, true,'', '', ['rating-title', 'custom-title']) ?>
                    </div>
                    <div class="wpfp-box wpfp-reputation">
                        <?php wpforo_member_title($user, true,'', '', ['usergroup']) ?>
                        <?php wpforo_member_badge($user) ?>
                        <?php do_action( 'wpforo_after_member_badge', $user ); ?>
                    </div>
                    <div class="wpfp-box wpfp-joined">
                        <?php wpforo_phrase( 'Joined' ) ?>: <?php wpforo_date( $user['user_registered'], 'date' ) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="wpforo-profile-back">
            <?php
                $profile_head_menu = [
                        '<a href="' . wpforo_home_url() . '"><i class="fas fa-home"></i> ' . wpforo_phrase('Forum Home', false) . '</a>',
                        '<a href="' . wpforo_home_url( wpforo_settings_get_slug( 'recent' ) ) . '"><i class="fa-solid fa-reply"></i> ' . wpforo_phrase('Recent Posts', false) . '</a>'
                    ];
                $profile_head_menu = apply_filters('wpforo_profile_head_menu', $profile_head_menu);
                if( !wpforo_setting('profiles', 'profile_header') ) echo implode(' &nbsp; | &nbsp; ', $profile_head_menu);
            ?>
        </div>
    </div>

    <div class="wpforo-user-actions"><?php wpforo_template_profile_action_buttons( $user ) ?></div>

    <div class="wpforo-profile-menu">
        <?php wpforo_member_tabs() ?>
        <div class="wpf-clear"></div>
    </div>

    <div class="wpforo-profile-content">
        <?php wpforo_member_template() ?>
    </div>
	<?php elseif( $user ) : ?>
        <div class="wpforo-profile-content wpfbg-7">
            <div class="wpfbg-7 wpf-page-message-wrap">
                <div class="wpf-page-message-text">
					<?php wpforo_phrase( 'You do not have permission to view this page' ) ?>
                </div>
            </div>
        </div>
	<?php else : ?>
        <div class="wpforo-profile-content wpfbg-7">
            <div class="wpfbg-7 wpf-page-message-wrap">
                <div class="wpf-page-message-text">
					<?php WPF()->tpl->member_error() ?>
                </div>
            </div>
        </div>
	<?php endif; ?>
</div>
