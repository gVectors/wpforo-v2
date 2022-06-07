<?php

namespace wpforo\widgets;

use WP_Widget;

class Profile extends WP_Widget {
	/**
	 * @var array
	 */
	private $default_instance;

	function __construct() {
		parent::__construct( 'wpforo_profile', 'wpForo User Profile & Notifications', [ 'description' => 'wpForo profile data and notifications' ] );
		$this->init_local_vars();
	}

	private function init_local_vars() {
		$this->default_instance = [
			'title'             => __( 'My Profile', 'wpforo' ),
			'title_guest'       => __( 'Join Us!', 'wpforo' ),
			'hide_avatar'       => false,
			'hide_name'         => false,
			'hide_notification' => false,
			'hide_data'         => false,
			'hide_buttons'      => false,
			'hide_for_guests'   => false,
		];
	}

	public function widget( $args, $instance ) {
		$display_widget = ! ( ! is_user_logged_in() ) || ! wpfval( $instance, 'hide_for_guests' );
		if( $display_widget ) {
            $class = 'wpf-' . wpforo_setting( 'styles', 'color_style' );
			echo $args['before_widget'];
			echo '<div id="wpf-widget-profile" class="wpforo-widget-wrap ' . esc_attr( $class ) . '">';
			if( wpfval( $instance, 'title' ) && is_user_logged_in() ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
			} elseif( ! is_user_logged_in() ) {
				$title_guest = wpfval( $instance, 'title_guest' ) ? wpfval( $instance, 'title_guest' ) : apply_filters( 'wpforo_profile_widget_guest_title', __( 'Join Us!', 'wpforo' ) );
				echo $args['before_title'] . apply_filters( 'widget_title', $title_guest ) . $args['after_title'];
			}
			echo '<div class="wpforo-widget-content">';
			$member = WPF()->current_user;
			?>
            <div class="wpf-prof-wrap">
				<?php if( is_user_logged_in() ): wp_enqueue_script( 'wpforo-widgets-js' ); ?>
                    <div class="wpf-prof-header">
						<?php if( ! wpfval( $instance, 'hide_avatar' ) ): ?>
                            <div class="wpf-prof-avatar">
								<?php echo wpforo_user_avatar( $member['userid'], 80 ); ?>
                            </div>
						<?php endif; ?>
						<?php if( ! wpfval( $instance, 'hide_name' ) ): ?>
                            <div class="wpf-prof-info">
                                <div class="wpf-prof-name">
									<?php WPF()->member->show_online_indicator( $member['userid'] ) ?>
									<?php echo wpfval( $member, 'display_name' ) ? esc_html( $member['display_name'] ) : esc_html( urldecode( $member['nicename'] ) ) ?>
									<?php wpforo_member_nicename( $member, '@' ); ?>
                                </div>
                            </div>
						<?php endif; ?>
						<?php if( ! wpfval( $instance, 'hide_notification' ) && wpforo_setting( 'notifications', 'notifications' ) ): ?>
                            <div class="wpf-prof-alerts">
								<?php WPF()->activity->bell( 'wpf-widget-alerts' ); ?>
                            </div>
						<?php endif; ?>
                    </div>
					<?php if( ! wpfval( $instance, 'hide_notification' ) && wpforo_setting( 'notifications', 'notifications' ) ): ?>
                        <div class="wpf-prof-notifications" style="flex-basis: 100%;">
							<?php wpforo_notifications() ?>
                        </div>
					<?php endif; ?>
					<?php if( ! wpfval( $instance, 'hide_data' ) ): ?>
                        <div class="wpf-prof-content">
							<?php do_action( 'wpforo_wiget_profile_content_before', $member ); ?>
                            <div class="wpf-prof-data">
                                <div class="wpf-prof-rating">
									<?php echo '<span class="wpf-member-title wpfrt">' . esc_html( $member['rating']['title'] ) . '</span>' ?>
									<?php wpforo_member_badge( $member ); ?>
                                </div>
								<?php wpforo_member_title( $member, true, '', '', [ 'rating-title' ] ); ?>
                            </div>
							<?php do_action( 'wpforo_wiget_profile_content_after', $member ); ?>
                        </div>
					<?php endif; ?>
				<?php endif; ?>
                <div class="wpf-prof-footer">
					<?php do_action( 'wpforo_wiget_profile_footer_before', $member ); ?>
					<?php if( is_user_logged_in() ): ?>
						<?php if( ! wpfval( $instance, 'hide_buttons' ) ): ?>
                            <div class="wpf-prof-buttons">
								<?php WPF()->tpl->member_buttons( $member ) ?>
								<?php if( ! wpforo_is_bot() ) : ?>
                                    <a href="<?php echo wpforo_logout_url() ?>" class="wpf-logout"
                                       title="<?php wpforo_phrase( 'Logout' ) ?>"><i
                                                class="fas fa-sign-out-alt"></i></a>
								<?php endif ?>
                            </div>
						<?php endif; ?>
					<?php elseif( ! wpforo_is_bot() ): ?>
                        <div class="wpf-prof-loginout">
                            <a href="<?php echo wpforo_login_url(); ?>" class="wpf-button"><i
                                        class="fas fa-sign-in-alt"></i> <?php wpforo_phrase( 'Login' ) ?></a> &nbsp;
                            <a href="<?php echo wpforo_register_url(); ?>" class="wpf-button"><i
                                        class="fas fa-user-plus"></i> <?php wpforo_phrase( 'Register' ) ?></a>
                        </div>
					<?php endif; ?>
					<?php do_action( 'wpforo_wiget_profile_footer_after', $member ); ?>
                </div>
            </div>
			<?php
			echo '</div></div>';
			echo $args['after_widget'];
		}
	}

	public function form( $instance ) {
		$title             = isset( $instance['title'] ) ? $instance['title'] : __( 'My Profile', 'wpforo' );
		$title_guest       = isset( $instance['title_guest'] ) ? $instance['title_guest'] : __( 'Join Us!', 'wpforo' );
		$hide_avatar       = isset( $instance['hide_avatar'] ) && $instance['hide_avatar'];
		$hide_name         = isset( $instance['hide_name'] ) && $instance['hide_name'];
		$hide_notification = isset( $instance['hide_notification'] ) && $instance['hide_notification'];
		$hide_data         = isset( $instance['hide_data'] ) && $instance['hide_data'];
		$hide_buttons      = isset( $instance['hide_buttons'] ) && $instance['hide_buttons'];
		$hide_for_guests   = isset( $instance['hide_for_guests'] ) && $instance['hide_for_guests'];
		?>
        <p>
            <label><?php _e( 'Title for Users', 'wpforo' ); ?>:</label>
            <label>
                <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                       value="<?php echo esc_attr( $title ); ?>">
            </label>
        </p>
        <p>
            <label><?php _e( 'Title for Guests', 'wpforo' ); ?>:</label>
            <label>
                <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title_guest' ) ); ?>" type="text"
                       value="<?php echo esc_attr( $title_guest ); ?>">
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'hide_avatar' ) ?>"><?php _e( 'Hide avatar', 'wpforo' ); ?>
                &nbsp;</label>
            <input id="<?php echo $this->get_field_id( 'hide_avatar' ) ?>" class="wpf_wdg_hide_avatar"
                   name="<?php echo esc_attr( $this->get_field_name( 'hide_avatar' ) ); ?>" <?php checked( $hide_avatar ); ?>
                   type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'hide_name' ) ?>"><?php _e( 'Hide user name', 'wpforo' ); ?>
                &nbsp;</label>
            <input id="<?php echo $this->get_field_id( 'hide_name' ) ?>" class="wpf_wdg_hide_name"
                   name="<?php echo esc_attr( $this->get_field_name( 'hide_name' ) ); ?>" <?php checked( $hide_name ); ?>
                   type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'hide_notification' ) ?>"><?php _e( 'Hide notification bell', 'wpforo' ); ?>
                &nbsp;</label>
            <input id="<?php echo $this->get_field_id( 'hide_notification' ) ?>" class="wpf_wdg_hide_notification"
                   name="<?php echo esc_attr( $this->get_field_name( 'hide_notification' ) ); ?>" <?php checked( $hide_notification ); ?>
                   type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'hide_data' ) ?>"><?php _e( 'Hide user data', 'wpforo' ); ?>
                &nbsp;</label>
            <input id="<?php echo $this->get_field_id( 'hide_data' ) ?>" class="wpf_wdg_hide_data"
                   name="<?php echo esc_attr( $this->get_field_name( 'hide_data' ) ); ?>" <?php checked( $hide_data ); ?>
                   type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'hide_buttons' ) ?>"><?php _e( 'Hide buttons', 'wpforo' ); ?>
                &nbsp;</label>
            <input id="<?php echo $this->get_field_id( 'hide_buttons' ) ?>" class="wpf_wdg_hide_buttons"
                   name="<?php echo esc_attr( $this->get_field_name( 'hide_buttons' ) ); ?>" <?php checked( $hide_buttons ); ?>
                   type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'hide_for_guests' ) ?>"><?php _e( 'Hide this widget for guests', 'wpforo' ); ?>
                &nbsp;</label>
            <input id="<?php echo $this->get_field_id( 'hide_for_guests' ) ?>" class="wpf_wdg_hide_for_guests"
                   name="<?php echo esc_attr( $this->get_field_name( 'hide_for_guests' ) ); ?>" <?php checked( $hide_for_guests ); ?>
                   type="checkbox">
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance                      = [];
		$instance['title']             = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['title_guest']       = ( ! empty( $new_instance['title_guest'] ) ) ? strip_tags( $new_instance['title_guest'] ) : '';
		$instance['hide_avatar']       = isset( $new_instance['hide_avatar'] ) ? (bool) $new_instance['hide_avatar'] : $this->default_instance['hide_avatar'];
		$instance['hide_name']         = isset( $new_instance['hide_name'] ) ? (bool) $new_instance['hide_name'] : $this->default_instance['hide_name'];
		$instance['hide_notification'] = isset( $new_instance['hide_notification'] ) ? (bool) $new_instance['hide_notification'] : $this->default_instance['hide_notification'];
		$instance['hide_data']         = isset( $new_instance['hide_data'] ) ? (bool) $new_instance['hide_data'] : $this->default_instance['hide_data'];
		$instance['hide_buttons']      = isset( $new_instance['hide_buttons'] ) ? (bool) $new_instance['hide_buttons'] : $this->default_instance['hide_buttons'];
		$instance['hide_for_guests']   = isset( $new_instance['hide_for_guests'] ) ? (bool) $new_instance['hide_for_guests'] : $this->default_instance['hide_for_guests'];

		return $instance;
	}
}
