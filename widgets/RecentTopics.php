<?php

namespace wpforo\widgets;

use WP_Widget;

class RecentTopics extends WP_Widget {
	private $default_instance = [];
	private $orderby_fields   = [];
	private $order_fields     = [];

	function __construct() {
		parent::__construct( 'wpforo_recent_topics', 'wpForo Recent Topics', [ 'description' => 'Your forum\'s recent topics.' ] );
		$this->init_local_vars();
	}

	private function init_local_vars() {
		$this->default_instance = [
			'title'                  => 'Recent Topics',
			'forumids'               => [],
			'orderby'                => 'created',
			'order'                  => 'DESC',
			'count'                  => 9,
			'display_avatar'         => false,
			'forumids_filter'        => false,
			'current_forumid_filter' => false,
			'goto_unread'            => false,
		];
		$this->orderby_fields   = [
			'created'  => __( 'Created Date', 'wpforo' ),
			'modified' => __( 'Modified Date', 'wpforo' ),
			'posts'    => __( 'Posts Count', 'wpforo' ),
			'views'    => __( 'Views Count', 'wpforo' ),
		];
		$this->order_fields     = [
			'DESC' => __( 'DESC', 'wpforo' ),
			'ASC'  => __( 'ASC', 'wpforo' ),
		];
	}

	public function widget( $args, $instance ) {
		//		wp_enqueue_script('wpforo-widgets-js');

		$instance = wpforo_parse_args( $instance, $this->default_instance );
		if( $instance['current_forumid_filter'] && $current_forumid = wpfval( WPF()->current_object, 'forumid' ) ) {
			$instance['forumids'] = (array) $current_forumid;
		}
		echo $args['before_widget'];//This is an HTML content//
		echo '<div id="wpf-widget-recent-replies" class="wpforo-widget-wrap">';
		if( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];//This is an HTML content//
		}
		// widget content from front end
		$private    = ( ! is_user_logged_in() || ! WPF()->usergroup->can( 'aum' ) ) ? 0 : null;
		$status     = ( ! is_user_logged_in() || ! WPF()->usergroup->can( 'aum' ) ) ? 0 : null;
		$topic_args = [
			'forumids'  => ( $instance['forumids'] ?: $this->default_instance['forumids'] ),
			'orderby'   => ( key_exists( $instance['orderby'], $this->orderby_fields ) ? $instance['orderby'] : $this->default_instance['orderby'] ),
			'order'     => ( key_exists( $instance['order'], $this->order_fields ) ? $instance['order'] : $this->default_instance['order'] ),
			'row_count' => ( ( $count = intval( $instance['count'] ) ) ? $count : $this->default_instance['count'] ),
			'private'   => $private,
			'status'    => $status,
		];
		$topics     = WPF()->topic->get_topics( $topic_args );
		$ug_can_va  = WPF()->usergroup->can( 'va' );
		$is_avatar  = wpforo_setting( 'profiles', 'avatars' );
		echo '<div class="wpforo-widget-content"><ul>';
		foreach( $topics as $topic ) {
			$topic_url = wpforo_topic( $topic['topicid'], 'url' );
			$member    = wpforo_member( $topic );
			?>
            <li>
                <div class="wpforo-list-item">
					<?php if( $instance['display_avatar'] ): ?>
						<?php if( $ug_can_va && $is_avatar ): ?>
                            <div class="wpforo-list-item-left">
								<?php echo WPF()->member->avatar( $member ); ?>
                            </div>
						<?php endif; ?>
					<?php endif; ?>
                    <div class="wpforo-list-item-right" <?php if( ! $instance['display_avatar'] ): ?> style="width:100%"<?php endif; ?>>
                        <p class="posttitle">
							<?php if( wpfval( $instance, 'goto_unread' ) ): ?>
								<?php wpforo_topic_title( $topic, $topic_url, '{p}{au}{t}{/a}' ) ?>
								<?php if( $topic['topicid'] != wpfval( WPF()->current_object, 'topicid' ) ): ?>
									<?php wpforo_unread_button( $topic['topicid'], $topic_url ); ?>
								<?php endif; ?>
							<?php else: ?>
								<?php wpforo_topic_title( $topic, $topic_url, '{p}{a}{t}{/a}' ) ?>
							<?php endif; ?>
                        </p>
                        <p class="postuser"><?php wpforo_phrase( 'by' ) ?> <?php wpforo_member_link( $member ) ?>, <span
                                    style="white-space:nowrap;"><?php esc_html( wpforo_date( $topic['created'] ) ) ?></span>
                        </p>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
            </li>
			<?php
		}
		echo '</ul></div>';
		echo '</div>';
		echo $args['after_widget'];//This is an HTML content//
	}

	public function form( $instance ) {
		$instance               = wpforo_parse_args( $instance, $this->default_instance );
		$title                  = (string) $instance['title'];
		$selected               = array_unique( array_filter( array_map( 'intval', (array) $instance['forumids'] ) ) );
		$orderby                = (string) $instance['orderby'];
		$order                  = (string) $instance['order'];
		$count                  = (int) $instance['count'];
		$display_avatar         = (bool) $instance['display_avatar'];
		$forumids_filter        = (bool) $instance['forumids_filter'];
		$current_forumid_filter = (bool) $instance['current_forumid_filter'];
		$goto_unread            = (bool) $instance['goto_unread'];
		?>
        <style>
            select.wpf_wdg_forumids {
                display: none;
                width: 100%;
                min-height: 170px;
            }

            input.wpf_wdg_forumids_filter:checked ~ select.wpf_wdg_forumids {
                display: block;
            }
        </style>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title', 'wpforo' ); ?>:</label>
            <input id="<?php echo $this->get_field_id( 'title' ) ?>" class="widefat"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                   value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'forumids_filter' ) ?>"><?php _e( 'Filter by forums', 'wpforo' ); ?>
                :</label>
            <input id="<?php echo $this->get_field_id( 'forumids_filter' ) ?>" class="wpf_wdg_forumids_filter"
                   name="<?php echo esc_attr( $this->get_field_name( 'forumids_filter' ) ); ?>" <?php checked( $forumids_filter ); ?>
                   type="checkbox">
            <label for="<?php echo $this->get_field_id( 'forumids' ) ?>"></label><select id="<?php echo $this->get_field_id( 'forumids' ) ?>" class="wpf_wdg_forumids"
                                                                                         name="<?php echo esc_attr( $this->get_field_name( 'forumids' ) ); ?>[]" multiple>
				<?php WPF()->forum->tree( 'select_box', false, $selected ) ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'current_forumid_filter' ) ?>"><?php _e( 'Autofilter by current forum', 'wpforo' ); ?>
                :</label>
            <input id="<?php echo $this->get_field_id( 'current_forumid_filter' ) ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'current_forumid_filter' ) ); ?>" <?php checked( $current_forumid_filter ); ?>
                   type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'orderby' ) ?>"><?php _e( 'Order by', 'wpforo' ); ?>:</label>
            <select name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>"
                    id="<?php echo $this->get_field_id( 'orderby' ) ?>">
				<?php foreach( $this->orderby_fields as $orderby_key => $orderby_field ) : ?>
                    <option value="<?php echo $orderby_key; ?>"<?php echo( $orderby_key == $orderby ? ' selected' : '' ); ?>><?php echo $orderby_field; ?></option>
				<?php endforeach; ?>
            </select>
            <label>
                <select name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>">
					<?php foreach( $this->order_fields as $order_key => $order_field ) : ?>
                        <option value="<?php echo $order_key; ?>"<?php echo( $order_key == $order ? ' selected' : '' ); ?>><?php echo $order_field; ?></option>
					<?php endforeach; ?>
                </select>
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'count' ) ?>"><?php _e( 'Number of Items', 'wpforo' ); ?></label>&nbsp;
            <input id="<?php echo $this->get_field_id( 'count' ) ?>" type="number" min="1" style="width: 53px;"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
                   value="<?php echo esc_attr( $count ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'display_avatar' ) ?>">
				<?php _e( 'Display with avatars', 'wpforo' ); ?>
                <input id="<?php echo $this->get_field_id( 'display_avatar' ) ?>" <?php checked( $display_avatar ); ?>
                       type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'display_avatar' ) ); ?>">
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'goto_unread' ) ?>"><?php _e( 'Refer topics to first unread post', 'wpforo' ); ?></label>
            <input id="<?php echo $this->get_field_id( 'goto_unread' ) ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'goto_unread' ) ); ?>" <?php checked( $goto_unread ); ?>
                   type="checkbox">
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$new_instance                       = wpforo_parse_args( $new_instance, $this->default_instance );
		$instance                           = [];
		$instance['title']                  = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['forumids_filter']        = isset( $new_instance['forumids_filter'] ) ? (bool) $new_instance['forumids_filter'] : $this->default_instance['forumids_filter'];
		$instance['forumids']               = ( $instance['forumids_filter'] ? array_unique( array_filter( array_map( 'intval', (array) wpfval( $new_instance, 'forumids' ) ) ) ) : [] );
		$instance['orderby']                = ( ! empty( $new_instance['orderby'] ) && key_exists( $new_instance['orderby'], $this->orderby_fields ) ) ? $new_instance['orderby'] : $this->default_instance['orderby'];
		$instance['order']                  = ( ! empty( $new_instance['order'] ) && key_exists( $new_instance['order'], $this->order_fields ) ) ? $new_instance['order'] : $this->default_instance['order'];
		$instance['count']                  = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : $this->default_instance['count'];
		$instance['display_avatar']         = isset( $new_instance['display_avatar'] ) ? (bool) $new_instance['display_avatar'] : $this->default_instance['display_avatar'];
		$instance['current_forumid_filter'] = isset( $new_instance['current_forumid_filter'] ) ? (bool) $new_instance['current_forumid_filter'] : $this->default_instance['current_forumid_filter'];
		$instance['goto_unread']            = isset( $new_instance['goto_unread'] ) ? (bool) $new_instance['goto_unread'] : $this->default_instance['goto_unread'];

		return $instance;
	}
}
