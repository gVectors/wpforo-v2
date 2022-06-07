<?php

namespace wpforo\widgets;

use WP_Widget;

class OnlineMembers extends WP_Widget {
	function __construct() {
		parent::__construct( 'wpforo_online_members', 'wpForo Online Members', [ 'description' => 'Online members.' ] );
	}

	public function widget( $args, $instance ) {
		//		wp_enqueue_script('wpforo-widgets-js');

		echo $args['before_widget']; //This is an HTML content//
		echo '<div id="wpf-widget-online-users" class="wpforo-widget-wrap">';
		if( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		$groupids = ( ! empty( $instance['groupids'] ) ? array_filter( wpforo_parse_args( json_decode( $instance['groupids'], true ) ) ) : WPF()->usergroup->get_visible_usergroup_ids() );
		// widget content from front end
		$online_members = WPF()->member->get_online_members( $instance['count'], $groupids );
		echo '<div class="wpforo-widget-content">';
		if( ! empty( $online_members ) ) {
			echo '<ul>
					 <li>
						<div class="wpforo-list-item">';
			foreach( $online_members as $member ) {
				if( $instance['display_avatar'] ): ?>
                    <a href="<?php echo esc_url( WPF()->member->get_profile_url( $member['userid'] ) ) ?>"
                       class="onlineavatar">
						<?php echo WPF()->member->get_avatar( $member['userid'], 'style="width:95%;" class="avatar" title="' . esc_attr( $member['display_name'] ) . '"' ); ?>
                    </a>
				<?php else: ?>
                    <a href="<?php echo esc_url( WPF()->member->get_profile_url( $member['userid'] ) ) ?>"
                       class="onlineuser"><?php echo esc_html( $member['display_name'] ) ?></a>
				<?php endif; ?>
				<?php
			}
			echo '<div class="wpf-clear"></div>
							</div>
						</li>
					</ul>
				</div>';
		} else {
			echo '<p class="wpf-widget-note">&nbsp;' . wpforo_phrase( 'No online members at the moment', false ) . '</p>';
		}
		echo '</div>';
		echo $args['after_widget'];//This is an HTML content//
	}

	public function form( $instance ) {
		$title          = ! empty( $instance['title'] ) ? $instance['title'] : 'Online Members';
		$count          = ! empty( $instance['count'] ) ? $instance['count'] : '15';
		$display_avatar = isset( $instance['display_avatar'] ) && $instance['display_avatar'];
		$groupids       = ( ! empty( $instance['groupids'] ) ? array_filter( wpforo_parse_args( json_decode( $instance['groupids'], true ) ) ) : WPF()->usergroup->get_visible_usergroup_ids() );
		?>
        <p>
            <label><?php _e( 'Title', 'wpforo' ); ?>:</label>
            <label>
                <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                       value="<?php echo esc_attr( $title ); ?>">
            </label>
        </p>
        <p>
            <label><?php _e( 'User Groups', 'wpforo' ); ?></label>&nbsp;
            <label>
                <select name="<?php echo esc_attr( $this->get_field_name( 'groupids' ) ); ?>[]" multiple>
					<?php WPF()->usergroup->show_selectbox( $groupids ) ?>
                </select>
            </label>
        </p>
        <p>
            <label><?php _e( 'Number of Items', 'wpforo' ); ?></label>&nbsp;
            <label>
                <input type="number" min="1" style="width: 53px;"
                       name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
                       value="<?php echo esc_attr( $count ); ?>">
            </label>
        </p>
        <p>
            <label>
                <input<?php checked( $display_avatar ); ?> type="checkbox" value="1"
                                                           name="<?php echo esc_attr( $this->get_field_name( 'display_avatar' ) ); ?>"/>
				<?php _e( 'Display Avatars', 'wpforo' ); ?>
            </label>
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance                   = [];
		$instance['title']          = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['count']          = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : '';
		$instance['display_avatar'] = isset( $new_instance['display_avatar'] ) && $new_instance['display_avatar'];
		$instance['groupids']       = ( ! empty( $new_instance['groupids'] ) ? json_encode( array_filter( wpforo_parse_args( $new_instance['groupids'] ) ) ) : json_encode( WPF()->usergroup->get_visible_usergroup_ids() ) );

		return $instance;
	}
}
