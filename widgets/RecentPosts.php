<?php

namespace wpforo\widgets;

use WP_Widget;

class RecentPosts extends WP_Widget {
	private $default_instance = [];
	private $orderby_fields   = [];
	private $order_fields     = [];

	function __construct() {
		parent::__construct( 'wpforo_recent_posts', 'wpForo Recent Posts', [ 'description' => 'Your forum\'s recent posts.' ] );
		$this->init_local_vars();
	}

	private function init_local_vars() {
		$this->default_instance = [
			'title'                  => 'Recent Posts',
			'forumids'               => [],
			'orderby'                => 'created',
			'order'                  => 'DESC',
			'count'                  => 9,
			'limit_per_topic'        => 0,
			'display_avatar'         => false,
			'forumids_filter'        => false,
			'current_forumid_filter' => false,
			'display_only_unread'    => false,
			'display_new_indicator'  => false,
		];
		$this->orderby_fields   = [
			'created'  => __( 'Created Date', 'wpforo' ),
			'modified' => __( 'Modified Date', 'wpforo' ),
		];
		$this->order_fields     = [
			'DESC' => __( 'DESC', 'wpforo' ),
			'ASC'  => __( 'ASC', 'wpforo' ),
		];
	}

	public function widget( $args, $instance ) {
		//		wp_enqueue_script('wpforo-widgets-js');
		$login    = is_user_logged_in();
		$instance = wpforo_parse_args( $instance, $this->default_instance );
		if( $instance['display_only_unread'] ) {
			$display_widget = $login;
			$display_widget = apply_filters( 'wpforo_widget_display_recent_posts', $display_widget );
		} else {
			$display_widget = true;
		}

		if( $display_widget ) {
			if( $instance['current_forumid_filter'] && $current_forumid = wpfval( WPF()->current_object, 'forumid' ) ) {
				$instance['forumids'] = (array) $current_forumid;
			}

			echo $args['before_widget'];//This is an HTML content//
			echo '<div id="wpf-widget-recent-replies" class="wpforo-widget-wrap">';

			if( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];//This is an HTML content//
			}
			$private = ( ! is_user_logged_in() || ! WPF()->usergroup->can( 'aum' ) ) ? 0 : null;
			$status  = ( ! is_user_logged_in() || ! WPF()->usergroup->can( 'aum' ) ) ? 0 : null;
			// widget content from front end
			$ug_can_va  = WPF()->usergroup->can( 'va' );
			$is_avatar  = wpforo_setting( 'profiles', 'avatars' );
			$posts_args = [
				'forumids'        => ( $instance['forumids'] ?: $this->default_instance['forumids'] ),
				'orderby'         => ( key_exists( $instance['orderby'], $this->orderby_fields ) ? $instance['orderby'] : $this->default_instance['orderby'] ),
				'order'           => ( key_exists( $instance['order'], $this->order_fields ) ? $instance['order'] : $this->default_instance['order'] ),
				'row_count'       => ( ( $count = intval( $instance['count'] ) ) ? $count : $this->default_instance['count'] ),
				'limit_per_topic' => ( ( $limit = intval( $instance['limit_per_topic'] ) ) ? $limit : $this->default_instance['limit_per_topic'] ),
				'private'         => $private,
				'status'          => $status,
				'check_private'   => true,
			];
			echo '<div class="wpforo-widget-content"><ul>';
			if( $posts_args['limit_per_topic'] ) {
				if( $instance['display_only_unread'] && $login ) {
					$grouped_postids = WPF()->post->get_unread_posts( $posts_args, $count );
				} else {
					$grouped_postids = WPF()->post->get_posts( $posts_args );
				}
				if( ! empty( $grouped_postids ) ) {
					$grouped_postids = implode( ',', $grouped_postids );
					$postids         = array_filter( array_map( 'wpforo_bigintval', explode( ',', $grouped_postids ) ) );
					rsort( $postids );
					foreach( $postids as $postid ) {
						$class = '';
						$post  = wpforo_post( $postid );
						if( ! WPF()->post->view_access( $post ) ) {
							continue;
						}
						$current = $post['topicid'] == wpfval( WPF()->current_object, 'topicid' );
						if( ! $current ) {
							$class = 'class="' . ( $instance['display_only_unread'] ? 'wpf-unread-post' : wpforo_unread( $post['topicid'], 'post', false, $post['postid'] ) ) . '"';
						}
						$member = wpforo_member( $post );
						?>
                        <li <?php echo $class; ?>>
                            <div class="wpforo-list-item ">
								<?php if( $instance['display_avatar'] ): ?>
									<?php if( $ug_can_va && $is_avatar ): ?>
                                        <div class="wpforo-list-item-left">
											<?php echo WPF()->member->avatar( $member ); ?>
                                        </div>
									<?php endif; ?>
								<?php endif; ?>
                                <div class="wpforo-list-item-right" <?php if( ! $instance['display_avatar'] ): ?> style="width:100%"<?php endif; ?>>
                                    <p class="posttitle">
                                        <a href="<?php echo esc_url( $post['url'] ) ?>"><?php
											if( $t = esc_html( trim( $post['title'] ) ) ) {
												echo $t;
											} else {
												echo wpforo_phrase( 'RE', false, 'default' ) . ': ' . esc_html( trim( wpforo_topic( $post['topicid'], 'title' ) ) );
											} ?>
                                        </a>
										<?php if( ! $current && $instance['display_new_indicator'] ) wpforo_unread_button( $post['topicid'], '', true, $post['postid'] ) ?>
                                    </p>
                                    <p class="posttext"><?php echo esc_html( wpforo_text( $post['body'], 55 ) ); ?></p>
                                    <p class="postuser"><?php wpforo_phrase( 'by' ) ?> <?php wpforo_member_link( $member ) ?>
                                        , <?php esc_html( wpforo_date( $post['created'] ) ) ?></p>
                                </div>
                                <div class="wpf-clear"></div>
                            </div>
                        </li>
						<?php
					}
				} else {
					$error_message = ( $instance['display_only_unread'] ) ? 'No new posts found' : 'No posts found';
					echo '<li class="wpf-no-post-found">' . wpforo_phrase( $error_message, false ) . '</li>';
				}
			} else {
				if( $instance['display_only_unread'] && $login ) {
					$recent_posts = WPF()->post->get_unread_posts( $posts_args, $count );
				} else {
					$recent_posts = WPF()->post->get_posts( $posts_args );
				}
				if( ! empty( $recent_posts ) ) {
					foreach( $recent_posts as $post ) {
						$class    = '';
						$post_url = wpforo_post( $post['postid'], 'url' );
						$member   = wpforo_member( $post );
						$current  = $post['topicid'] == wpfval( WPF()->current_object, 'topicid' );
						if( ! $current ) {
							$class = 'class="' . ( $instance['display_only_unread'] ? 'wpf-unread-post' : wpforo_unread( $post['topicid'], 'post', false, $post['postid'] ) ) . '"';
						}
						?>
                        <li <?php echo $class; ?>>
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
                                        <a href="<?php echo esc_url( $post_url ) ?>"><?php
											if( $t = esc_html( trim( $post['title'] ) ) ) {
												echo $t;
											} else {
												echo wpforo_phrase( 'RE', false, 'default' ) . ': ' . esc_html( trim( wpforo_topic( $post['topicid'], 'title' ) ) );
											} ?>
                                        </a>
										<?php if( ! $current && $instance['display_new_indicator'] ) wpforo_unread_button( $post['topicid'], '', true, $post['postid'] ) ?>
                                    </p>
                                    <p class="posttext"><?php echo esc_html( wpforo_text( $post['body'], 55 ) ); ?></p>
                                    <p class="postuser"><?php wpforo_phrase( 'by' ) ?> <?php wpforo_member_link( $member ) ?>
                                        , <?php esc_html( wpforo_date( $post['created'] ) ) ?></p>
                                </div>
                                <div class="wpf-clear"></div>
                            </div>
                        </li>
						<?php
					}
				} else {
					$error_message = ( $instance['display_only_unread'] ) ? 'No new posts found' : 'No posts found';
					echo '<li class="wpf-no-post-found">' . wpforo_phrase( $error_message, false ) . '</li>';
				}
			}
			echo '</ul></div>';
			echo '</div>';
			echo $args['after_widget'];//This is an HTML content//
		}
	}

	public function form( $instance ) {
		$instance               = wpforo_parse_args( $instance, $this->default_instance );
		$title                  = (string) $instance['title'];
		$selected               = array_unique( array_filter( array_map( 'intval', (array) $instance['forumids'] ) ) );
		$orderby                = (string) $instance['orderby'];
		$order                  = (string) $instance['order'];
		$count                  = (int) $instance['count'];
		$limit_per_topic        = (int) $instance['limit_per_topic'];
		$display_avatar         = (bool) $instance['display_avatar'];
		$forumids_filter        = (bool) $instance['forumids_filter'];
		$current_forumid_filter = (bool) $instance['current_forumid_filter'];
		$display_only_unread    = (bool) $instance['display_only_unread'];
		$display_new_indicator  = (bool) $instance['display_new_indicator'];
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

            .wpf_wdg_limit_per_topic {
                width: 53px;
            }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('.wpf_wdg_limit_per_topic').change(function () {
                    var wrap = $(this).parents('.wpf_wdg_form_wrap')
                    var disabled = $(this).val() > 0
                    $('.wpf_wdg_orderby', wrap).attr('disabled', disabled)
                    $('.wpf_wdg_order', wrap).attr('disabled', disabled)
                })
            })
        </script>
        <div class="wpf_wdg_form_wrap">
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
                <label for="<?php echo $this->get_field_id( 'forumids' ) ?>"></label>
                <select id="<?php echo $this->get_field_id( 'forumids' ) ?>" class="wpf_wdg_forumids">
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
                <label for="<?php echo $this->get_field_id( 'orderby' ) ?>"><?php _e( 'Order by', 'wpforo' ); ?>
                    :</label>
                <select class="wpf_wdg_orderby" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>"
                        id="<?php echo $this->get_field_id( 'orderby' ) ?>" <?php echo( $limit_per_topic ? 'disabled' : '' ) ?>>
					<?php foreach( $this->orderby_fields as $orderby_key => $orderby_field ) : ?>
                        <option value="<?php echo $orderby_key; ?>"<?php echo( $orderby_key == $orderby ? ' selected' : '' ); ?>><?php echo $orderby_field; ?></option>
					<?php endforeach; ?>
                </select>
                <label>
                    <select class="wpf_wdg_order"
                            name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" <?php echo( $limit_per_topic ? 'disabled' : '' ) ?>>
						<?php foreach( $this->order_fields as $order_key => $order_field ) : ?>
                            <option value="<?php echo $order_key; ?>"<?php echo( $order_key == $order ? ' selected' : '' ); ?>><?php echo $order_field; ?></option>
						<?php endforeach; ?>
                    </select>
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'limit_per_topic' ) ?>"><?php _e( 'Limit Per Topic', 'wpforo' ); ?></label>&nbsp;
                <input id="<?php echo $this->get_field_id( 'limit_per_topic' ) ?>" class="wpf_wdg_limit_per_topic"
                       type="number" min="0"
                       name="<?php echo esc_attr( $this->get_field_name( 'limit_per_topic' ) ); ?>"
                       value="<?php echo esc_attr( $limit_per_topic ); ?>">
                <span style="color: #aaa;"><?php _e( 'set 0 to remove this limit', 'wpforo' ) ?></span>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'count' ) ?>"><?php _e( 'Number of Items', 'wpforo' ); ?></label>&nbsp;
                <input id="<?php echo $this->get_field_id( 'count' ) ?>" type="number" min="1" style="width: 53px;"
                       name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
                       value="<?php echo esc_attr( $count ); ?>">
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'display_avatar' ) ?>">
                    <input id="<?php echo $this->get_field_id( 'display_avatar' ) ?>" <?php checked( $display_avatar ); ?>
                           type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'display_avatar' ) ); ?>">
					<?php _e( 'Display with Avatars', 'wpforo' ); ?></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'display_only_unread' ) ?>">
                    <input id="<?php echo $this->get_field_id( 'display_only_unread' ) ?>" <?php checked( $display_only_unread ); ?>
                           type="checkbox"
                           name="<?php echo esc_attr( $this->get_field_name( 'display_only_unread' ) ); ?>">
					<?php _e( 'Display Only Unread Posts', 'wpforo' ); ?></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'display_new_indicator' ) ?>">
                    <input id="<?php echo $this->get_field_id( 'display_new_indicator' ) ?>" <?php checked( $display_new_indicator ); ?>
                           type="checkbox"
                           name="<?php echo esc_attr( $this->get_field_name( 'display_new_indicator' ) ); ?>">
					<?php _e( 'Display [new] indicator', 'wpforo' ); ?></label>
            </p>
        </div>
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
		$instance['limit_per_topic']        = ( ! empty( $new_instance['limit_per_topic'] ) ) ? intval( $new_instance['limit_per_topic'] ) : $this->default_instance['limit_per_topic'];
		$instance['display_avatar']         = isset( $new_instance['display_avatar'] ) ? (bool) $new_instance['display_avatar'] : $this->default_instance['display_avatar'];
		$instance['current_forumid_filter'] = isset( $new_instance['current_forumid_filter'] ) ? (bool) $new_instance['current_forumid_filter'] : $this->default_instance['current_forumid_filter'];
		$instance['display_only_unread']    = isset( $new_instance['display_only_unread'] ) ? (bool) $new_instance['display_only_unread'] : $this->default_instance['display_only_unread'];
		$instance['display_new_indicator']  = isset( $new_instance['display_new_indicator'] ) ? (bool) $new_instance['display_new_indicator'] : $this->default_instance['display_new_indicator'];

		return $instance;
	}
}
