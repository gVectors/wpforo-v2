<?php
add_action( 'wp_enqueue_scripts', function() {
	if( is_rtl() ) {
		wp_register_style( 'wpforo-bp-rtl', WPF()->tpl->template_url . '/integration/buddypress/style-rtl.css', false, WPFORO_VERSION );
		wp_enqueue_style( 'wpforo-bp-rtl' );
	} else {
		wp_register_style( 'wpforo-bp', WPF()->tpl->template_url . '/integration/buddypress/style.css', false, WPFORO_VERSION );
		wp_enqueue_style( 'wpforo-bp' );
	}
	if( ! is_wpforo_page() ) {
		wp_enqueue_style( 'dashicons' );
	}
} );

/**
 * Insert BuddyPress Activity
 *
 * @param array $args
 *
 * @return bool|int
 */
function wpforo_bp_activity( $args = [] ) {
	if( ! function_exists( 'bp_activity_add' ) || ! is_user_logged_in() ) return false;
	$default = [
		'action'        => '',
		'title'         => '',
		'content'       => '',
		'component'     => 'community',
		'type'          => false,
		'primary_link'  => '',
		'user_id'       => '',
		'item_id'       => false,
		'hide_sitewide' => false,
		'is_spam'       => false,
	];

	$args = wpforo_parse_args( $args, $default );
	if( function_exists( 'bp_activity_add' ) ) {
		if( function_exists( 'bp_loggedin_user_domain' ) ) {
			$user_url = bp_loggedin_user_domain( $args['user_id'] );
			if( function_exists( 'bp_core_get_user_displayname' ) ) {
				$user_name = bp_core_get_user_displayname( $args['user_id'] );
				if( $user_url && $user_name ) {
					$user_link    = '<a href="' . esc_url( $user_url ) . '">' . esc_html( $user_name ) . '</a>';
					$content_link = ( $args['primary_link'] && $args['title'] ) ? '<a href="' . esc_url( $args['primary_link'] ) . '">' . esc_html( $args['title'] ) . '</a> - ' : $args['title'] . ' - ';
					if( $args['type'] == 'wpforo_topic' ) {
						$args['action'] = sprintf( wpforo_phrase( '%s posted a new topic %s', false ), $user_link, $content_link );
					} elseif( $args['type'] == 'wpforo_post' ) {
						$args['action'] = sprintf( wpforo_phrase( '%s replied to the topic %s', false ), $user_link, $content_link );
					} elseif( $args['type'] == 'wpforo_like' ) {
						$args['action'] = sprintf( wpforo_phrase( '%s liked forum post %s', false ), $user_link, $content_link );
					}
				}
			}
		}

		return $activity_id = bp_activity_add( $args );
	}

	return false;
}

/**
 * Delete BuddyPress Activity
 *
 * @param array $args
 */
function wpforo_bp_activity_delete( $args = [] ) {
	if( ! function_exists( 'bp_activity_delete' ) || ! is_user_logged_in() ) return;
	$default = [
		'action'        => '',
		'title'         => '',
		'content'       => '',
		'component'     => 'community',
		'type'          => false,
		'primary_link'  => '',
		'user_id'       => '',
		'item_id'       => false,
		'hide_sitewide' => false,
		'is_spam'       => false,
	];

	$args = wpforo_parse_args( $args, $default );
	if( function_exists( 'bp_activity_delete' ) ) {
		bp_activity_delete( $args );
	}
}

/**
 * Disable comment button for wpForo activity
 *
 * @param bool $can_comment
 *
 * @return bool
 */
function wpforo_bp_activity_disable_comment( $can_comment = true ) {
	if( false === $can_comment ) return $can_comment;
	if( function_exists( 'bp_get_activity_action_name' ) ) {
		$action_name      = bp_get_activity_type();
		$disabled_actions = [ 'wpforo_topic', 'wpforo_post', 'wpforo_like' ];
		$disabled_actions = apply_filters( 'wpforo_bp_activity_disable_comment', $disabled_actions );
		if( in_array( $action_name, $disabled_actions ) ) {
			$can_comment = false;
		}
	}

	return $can_comment;
}

/**
 * Register BuddyPress Activities
 */

add_action( 'bp_register_activity_actions', function() {
	bp_activity_set_action( 'community', 'wpforo_topic', wpforo_phrase( 'Forum topic', false ), '', wpforo_phrase( 'Forum topic', false ), [ 'member' ] );
	bp_activity_set_action( 'community', 'wpforo_post', wpforo_phrase( 'Forum post', false ), '', wpforo_phrase( 'Forum post', false ), [ 'member' ] );
	bp_activity_set_action( 'community', 'wpforo_like', wpforo_phrase( 'Forum post like', false ), '', wpforo_phrase( 'Forum post like', false ), [ 'member' ] );
} );
add_filter( 'bp_activity_can_comment', 'wpforo_bp_activity_disable_comment' );

function wpforo_bp_forums_screen_topics() {
	add_action( 'bp_template_content', function() {
		if( isset( $_GET['wpfpaged'] ) && intval( $_GET['wpfpaged'] ) ) $paged = intval( $_GET['wpfpaged'] );
		$paged      = ( isset( $paged ) && $paged ) ? $paged : 1;
		$args       = [
			'offset'        => ( $paged - 1 ) * WPF()->current_object['items_per_page'],
			'row_count'     => WPF()->current_object['items_per_page'],
			'userid'        => bp_displayed_user_id(),
			'orderby'       => 'modified',
			'check_private' => true,
		];
		$activities = WPF()->topic->get_topics( $args, $items_count );
		?>
        <div id="wpforo-topics" class="wpforo-activity">
            <h2 class="entry-title"><?php wpforo_phrase( 'Forum Topics Started' ); ?></h2>
			<?php if( empty( $activities ) ) : ?>
                <p class="wpf-p-error"> <?php wpforo_phrase( 'No activity found for this member.' ) ?> </p>
			<?php else: ?>
                <table>
					<?php $bg = false;
					foreach( $activities as $activity ) : ?>
                        <tr>
                            <td class="wpf-activity-title">
                                <span class="dashicons dashicons-admin-comments"></span>
								<?php
								$topic = wpforo_topic( $activity['topicid'] );
								if( ! empty( $topic ) ) {
									$topic_url   = $topic['url'];
									$topic_title = $topic['title'];
									if( ! $topic_url ) $topic_url = '#';
									if( ! $topic_title ) $topic_title = wpforo_phrase( 'Topic link' );
									?><a href="<?php echo esc_url( $topic_url ) ?>" class="wpf-item-title"><?php echo $topic_title ?></a><?php
								}
								if( wpfval( $topic, 'forumid' ) ) {
									$forum       = wpforo_forum( $topic['forumid'] );
									$forum_url   = $forum['url'];
									$forum_title = $forum['title'];
									if( ! $forum_url ) $forum_url = '#';
									if( ! $forum_title ) $forum_url = wpforo_phrase( 'Forum link' );
									?><p style="font-style: italic"><span><?php echo wpforo_phrase( 'in forum', false ) ?></span> <a href="<?php echo esc_url( $forum_url ) ?>"><?php echo $forum_title ?></a></p><?php
								}
								?>
                            </td>
                            <td class="wpf-activity-users">
								<?php $members = WPF()->topic->members( $topic['topicid'], 3 ); ?>
								<?php if( ! empty( $members ) ): foreach( $members as $member ): ?>
									<?php if( ! empty( $member ) ): ?>
                                        <a href="<?php echo bp_core_get_user_domain( $member['userid'] ) ?>" title="<?php echo esc_attr( bp_core_get_user_displayname( $member['userid'] ) ); ?>"><?php echo WPF()->member->avatar( $member, 'alt="' . esc_attr( $member['display_name'] ) . '"', 30 ) ?></a>
									<?php endif; ?>
								<?php endforeach; endif; ?>
                            </td>
                            <td class="wpf-activity-posts">
								<?php echo $activity['posts']; ?><?php wpforo_phrase( 'posts' ); ?>
                            </td>
                            <td class="wpf-activity-date"><?php wpforo_date( $topic['created'] ); ?></td>
                        </tr>
					<?php endforeach ?>
                </table>
                <div class="wpf-activity-foot"><?php WPF()->tpl->pagenavi( $paged, $items_count, null, false ); ?></div>
                <div style="clear: both"></div>
			<?php endif; ?>
        </div>
		<?php
	} );
	bp_core_load_template( apply_filters( 'wpforo_bp_forums_screen_topics', 'members/single/plugins' ) );
}

function wpforo_bp_forums_screen_replies() {
	add_action( 'bp_template_content', function() {
		if( isset( $_GET['wpfpaged'] ) && intval( $_GET['wpfpaged'] ) ) $paged = intval( $_GET['wpfpaged'] );
		$paged      = ( isset( $paged ) && $paged ) ? $paged : 1;
		$args       = [
			'offset'        => ( $paged - 1 ) * WPF()->current_object['items_per_page'],
			'row_count'     => WPF()->current_object['items_per_page'],
			'userid'        => bp_displayed_user_id(),
			'orderby'       => '`created` DESC',
			'check_private' => true,
		];
		$activities = WPF()->post->get_posts( $args, $items_count );
		?>
        <div id="wpforo-posts" class="wpforo-activity">
            <h2 class="entry-title"><?php wpforo_phrase( 'Forum Replies Created' ); ?></h2>
			<?php if( empty( $activities ) ) : ?>
                <p class="wpf-p-error"> <?php wpforo_phrase( 'No activity found for this member.' ) ?> </p>
			<?php else: ?>
                <table>
					<?php foreach( $activities as $activity ) : ?>
                        <tr>
                            <td class="wpf-activity-title">
                                <span class="dashicons dashicons-format-chat"></span>
								<?php
								$post = wpforo_post( $activity['postid'] );
								if( ! empty( $post ) ) {
									$post_url   = $post['url'];
									$post_title = $post['title'];
									if( ! $post_url ) $post_url = '#';
									if( ! $post_title ) $post_title = wpforo_phrase( 'Post link' );
									?><a href="<?php echo esc_url( $post_url ) ?>" class="wpf-item-title"><?php echo $post_title ?></a><?php
								}
								?>
								<?php if( wpfval( $post, 'body' ) ): ?>
                                    <p class="wpf-post-excerpt" style="font-style: italic">
										<?php
										$body = wpforo_content_filter( $post['body'] );
										$body = preg_replace( '#\[attach\][^\[\]]*\[\/attach\]#is', '', strip_shortcodes( strip_tags( $body ) ) );
										wpforo_text( $body, 200 );
										?>
                                    </p>
								<?php endif; ?>
                            </td>
                            <td class="wpf-activity-forum">
								<?php
								if( wpfval( $post, 'forumid' ) ) {
									$forum       = wpforo_forum( $post['forumid'] );
									$forum_url   = $forum['url'];
									$forum_title = $forum['title'];
									if( ! $forum_url ) $forum_url = '#';
									if( ! $forum_title ) $forum_url = wpforo_phrase( 'Forum link' );
									?><p style="font-style: italic"><span><?php echo wpforo_phrase( 'in forum', false ) ?></span> <a href="<?php echo esc_url( $forum_url ) ?>"><?php echo $forum_title ?></a></p><?php
								}
								?>
                            </td>
                            <td class="wpf-activity-date"><?php wpforo_date( $post['created'] ); ?></td>
                        </tr>
					<?php endforeach ?>
                </table>
                <div class="wpf-activity-foot"><?php WPF()->tpl->pagenavi( $paged, $items_count, null, false ); ?></div>
                <div style="clear: both"></div>
			<?php endif; ?>
        </div>
		<?php
	} );
	bp_core_load_template( apply_filters( 'wpforo_bp_forums_screen_replies', 'members/single/plugins' ) );
}

function wpforo_bp_forums_screen_likes() {
	add_action( 'bp_template_content', function() {
		if( isset( $_GET['wpfpaged'] ) && intval( $_GET['wpfpaged'] ) ) $paged = intval( $_GET['wpfpaged'] );
		$paged      = ( isset( $paged ) && $paged ) ? $paged : 1;
		$args       = [
			'userid'    => bp_displayed_user_id(),
			'offset'    => ( $paged - 1 ) * WPF()->current_object['items_per_page'],
			'row_count' => WPF()->current_object['items_per_page'],
			'var'       => 'postid',
		];
		$activities = WPF()->post->get_liked_posts( $args, $items_count );
		?>
        <div id="wpforo-liked-posts" class="wpforo-activity">
            <h2 class="entry-title"><?php wpforo_phrase( 'Liked Forum Posts' ); ?></h2>
			<?php if( empty( $activities ) ) : ?>
                <p class="wpf-p-error"> <?php wpforo_phrase( 'No activity found for this member.' ) ?> </p>
			<?php else: ?>
                <table>
					<?php $bg = false;
					foreach( $activities as $postid ) : ?>
                        <tr>
                            <td class="wpf-activity-title">
                                <span class="dashicons dashicons-thumbs-up"></span>
								<?php
								$post = wpforo_post( $postid );
								if( ! empty( $post ) ) {
									$post_url   = $post['url'];
									$post_title = $post['title'];
									if( ! $post_url ) $post_url = '#';
									if( ! $post_title ) $post_title = wpforo_phrase( 'Post link' );
									?><a href="<?php echo esc_url( $post_url ) ?>" class="wpf-item-title"><?php echo $post_title ?></a><?php
								}
								?>
								<?php if( wpfval( $post, 'body' ) ): ?>
                                    <p class="wpf-post-excerpt" style="font-style: italic">
										<?php
										$body = wpforo_content_filter( $post['body'] );
										$body = preg_replace( '#\[attach\][^\[\]]*\[\/attach\]#is', '', strip_shortcodes( strip_tags( $body ) ) );
										wpforo_text( $body, 200 );
										?>
                                    </p>
								<?php endif; ?>
                            </td>
                            <td class="wpf-activity-forum">
								<?php
								if( wpfval( $post, 'forumid' ) ) {
									$forum       = wpforo_forum( $post['forumid'] );
									$forum_url   = $forum['url'];
									$forum_title = $forum['title'];
									if( ! $forum_url ) $forum_url = '#';
									if( ! $forum_title ) $forum_url = wpforo_phrase( 'Forum link' );
									?><p style="font-style: italic"><span><?php echo wpforo_phrase( 'in forum', false ) ?></span> <a href="<?php echo esc_url( $forum_url ) ?>"><?php echo $forum_title ?></a></p><?php
								}
								?>
                            </td>
                            <td class="wpf-activity-date"><?php wpforo_date( $post['created'] ); ?></td>
                        </tr>
					<?php endforeach ?>
                </table>
                <div class="wpf-activity-foot"><?php WPF()->tpl->pagenavi( $paged, $items_count, null, false ); ?></div>
                <div style="clear: both"></div>
			<?php endif; ?>
        </div>
		<?php
	} );
	bp_core_load_template( apply_filters( 'wpforo_bp_forums_screen_likes', 'members/single/plugins' ) );
}

function wpforo_bp_forums_screen_subscriptions() {
	add_action( 'bp_template_content', 'wpforo_bp_member_forums_subscriptions_content' );
	bp_core_load_template( apply_filters( 'wpforo_bp_forums_screen_subscriptions', 'members/single/plugins' ) );
}

function wpforo_bp_member_forums_subscriptions_content() {
    if( !WPF()->sbscrb ) return;
	if( isset( $_GET['wpfpaged'] ) && intval( $_GET['wpfpaged'] ) ) $paged = intval( $_GET['wpfpaged'] );
	$user_id    = bp_displayed_user_id();
	$paged      = ( isset( $paged ) && $paged ) ? $paged : 1;
	$args       = [
		'offset'    => ( $paged - 1 ) * WPF()->current_object['items_per_page'],
		'row_count' => WPF()->current_object['items_per_page'],
		'userid'    => $user_id,
		'order'     => 'DESC',
	];
	$activities = WPF()->sbscrb->get_subscribes( $args, $items_count );
	?>
    <div id="wpforo-subscriptions" class="wpforo-activity">
        <h2 class="entry-title"><?php wpforo_phrase( 'Forum Subscriptions' ); ?></h2>
		<?php if( empty( $activities ) ) : ?>
            <p class="wpf-p-error"> <?php wpforo_phrase( 'No activity found for this member.' ) ?> </p>
		<?php else: ?>
            <table>
				<?php $bg = false;
				foreach( $activities as $activity ) : ?>
                    <tr>
                        <td class="wpf-activity-title">
                            <span class="dashicons <?php echo ( $activity['type'] == 'forum' ) ? 'dashicons-category' : 'dashicons-admin-comments'; ?>"></span>
							<?php
							$item_url = '#';
							if( in_array( $activity['type'], [ 'forum', 'forum-topic' ] ) ) {
								$item     = wpforo_forum( $activity['itemid'] );
								$item_url = $item['url'];
							} elseif( $activity['type'] == 'topic' ) {
								$item     = wpforo_topic( $activity['itemid'] );
								$item_url = $item['url'];
							} elseif( in_array( $activity['type'], [ 'forums', 'forums-topics' ] ) ) {
								$item     = [ 'title' => wpforo_phrase( 'All ' . $activity['type'], false ) ];
								$item_url = '#';
							}
							if( empty( $item ) ) continue;
							?>
                            <a href="<?php echo esc_url( $item_url ) ?>" class="wpf-item-title"><?php echo esc_html( $item['title'] ) ?></a>
                        </td>
						<?php if( wpforo_is_owner( $user_id ) ) : ?>
                            <td class="wpf-activity-unsb"><a href="<?php echo esc_url( WPF()->sbscrb->get_unsubscribe_link( $activity['confirmkey'] ) ) ?>"><?php wpforo_phrase( 'Unsubscribe' ); ?></a></td>
						<?php else : ?>
                            <td></td>
						<?php endif; ?>
                    </tr>
				<?php endforeach ?>
            </table>
            <div class="wpf-activity-foot"><?php WPF()->tpl->pagenavi( $paged, $items_count, null, false ); ?></div>
            <div style="clear: both"></div>
		<?php endif; ?>
    </div>
	<?php
}

/**
 * Filter registered notifications components, and add 'community' to the queried 'component_name' array.
 *
 * @param array $component_names
 *
 * @return array
 * @since wpForo (1.4.8)
 *
 */
add_filter( 'bp_notifications_get_registered_components', function( $component_names = [] ) {
	if( ! is_array( $component_names ) ) $component_names = [];
	array_push( $component_names, 'community' );

	return $component_names;
},          11 );

/**
 * Format the BuddyBar/Toolbar notifications
 *
 * @param string $action The kind of notification being rendered
 * @param int $item_id The primary item id
 * @param int $secondary_item_id The secondary item id
 * @param int $total_items The total number of messaging-related notifications waiting for the user
 * @param string $format 'string' for BuddyBar-compatible notifications; 'array' for WP Toolbar
 *
 * @since wpForo (1.4.8)
 *
 */
function wpforo_bp_format_buddypress_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {
	// New reply notifications

	if( 'wpforo_new_reply' === $action ) {

		$post = wpforo_post( $item_id );
		if( ! wpfval( $post, 'postid' ) ) return false;
		$topic = wpforo_topic( $post['topicid'] );
		if( ! wpfval( $topic, 'topicid' ) ) return false;

		$reply_id    = $post['postid'];
		$reply_url   = $post['url'];
		$topic_title = $topic['title'];
		$reply_link  = wp_nonce_url( add_query_arg( [ 'action' => 'wpforo_mark_read', 'itemid' => $reply_id ], $reply_url ), 'wpforo_mark_topic_' . $reply_id );
		$title_attr  = __( 'Topic reply', 'wpforo' );

		if( (int) $total_items > 1 ) {
			$text   = sprintf( __( 'You have %d new replies', 'wpforo' ), (int) $total_items );
			$filter = 'wpforo_bp_multiple_new_subscription_notification';
		} else {
			if( ! empty( $secondary_item_id ) ) {
				$text = sprintf( __( 'You have %d new reply to %2$s from %3$s', 'wpforo' ), (int) $total_items, $topic_title, bp_core_get_user_displayname( $secondary_item_id ) );
			} else {
				$text = sprintf( __( 'You have %d new reply to %s', 'wpforo' ), (int) $total_items, $topic_title );
			}
			$filter = 'wpforo_bp_single_new_subscription_notification';
		}
		// WordPress Toolbar
		if( 'string' === $format ) {
			$return = apply_filters( $filter, '<a href="' . esc_url( $reply_link ) . '" title="' . esc_attr( $title_attr ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $text, $reply_link );
		} else {
			$return = apply_filters( $filter, [ 'text' => $text, 'link' => $reply_link ], $reply_link, (int) $total_items, $text, $topic_title );
		}
		do_action( 'wpforo_bp_format_buddypress_notifications', $action, $item_id, $secondary_item_id, $total_items );

		return $return;
	}

	return '';
}

add_filter( 'bp_notifications_get_notifications_for_user', 'wpforo_bp_format_buddypress_notifications', 11, 5 );

/**
 * Hooked into the new reply function, this notification action is responsible
 * for notifying topic and hierarchical reply authors of topic replies.
 *
 * @param array $post
 * @param array $topic
 *
 * @since wpForo (1.4.8)
 *
 */
function wpforo_bp_add_notification( $post = [], $topic = [] ) {
	if( ! wpforo_setting( 'buddypress', 'notification' ) ) return;

	//Get reply data
	if( ! wpfval( $post, 'postid' ) ) return;
	if( ! wpfval( $topic, 'topicid' ) ) return;

	//Don't notify if a new reply is unapproved
	if( wpfval( $post, 'status' ) ) return;
	if( wpfval( $post, 'is_first_post' ) ) return;

	//Get author information
	$author_id       = $post['userid'];
	$topic_author_id = $topic['userid'];

	// Hierarchical replies
	if( wpfval( $post, 'parentid' ) ) {
		$reply_to_item_author_id = wpforo_post( $post['parentid'], 'userid' );
	}

	// Notify the topic author if not the current reply author
	if( $author_id != $topic_author_id ) {
		$args = [
			'user_id'           => $topic_author_id,
			'item_id'           => $post['postid'],
			'component_name'    => 'community',
			'component_action'  => 'wpforo_new_reply',
			'date_notified'     => $post['created'],
			'secondary_item_id' => $author_id,
		];
		bp_notifications_add_notification( $args );
	}

	// Notify the immediate reply author if not the current reply author
	if( isset( $reply_to_item_author_id ) && wpfval( $post, 'parentid' ) && $topic_author_id != $reply_to_item_author_id && $author_id != $reply_to_item_author_id ) {
		$args = [
			'user_id'           => $reply_to_item_author_id,
			'item_id'           => $post['postid'],
			'component_name'    => 'community',
			'component_action'  => 'wpforo_new_reply',
			'date_notified'     => $post['created'],
			'secondary_item_id' => $author_id,
		];
		bp_notifications_add_notification( $args );
	}
}

add_action( 'wpforo_after_add_post', 'wpforo_bp_add_notification', 10, 2 );

/**
 * Remove notification when reply is set unapproved
 *
 * @param array $post
 * @param array $topic
 *
 * @since wpForo (1.4.8)
 *
 */
function wpforo_bp_delete_notification( $post = [], $topic = [] ) {

	if( ! wpforo_setting( 'buddypress', 'notification' ) ) return;

	//Get reply data
	if( ! wpfval( $post, 'postid' ) ) return;
	if( ! wpfval( $topic, 'topicid' ) && wpfval( $post, 'topicid' ) ) {
		$topic = wpforo_topic( $post['topicid'] );
	}

	$reply_to_item_author_id = 0;
	if( wpfval( $post, 'parentid' ) ) {
		$reply_to_item_author_id = wpforo_post( $post['parentid'], 'userid' );
	}

	if( wpfval( $topic, 'userid' ) ) {
		bp_notifications_delete_notifications_by_item_id( $topic['userid'], $post['postid'], 'community', 'wpforo_new_reply' );
	}

	if( $reply_to_item_author_id && $topic['userid'] !== $reply_to_item_author_id ) {
		bp_notifications_delete_notifications_by_item_id( $reply_to_item_author_id, $post['postid'], 'community', 'wpforo_new_reply' );
	}
}

add_action( 'wpforo_after_delete_post', 'wpforo_bp_delete_notification', 10 );

/**
 * Add / Remove buddypress notification based on post status (approve/unapprove)
 *
 * @param int $reply_id
 * @param int $status | 0 is approved, 1 is unapproved
 *
 * @since wpForo (1.4.8)
 *
 */
add_action( 'wpforo_post_status_update',
    function( $post, $status = 0 ) {
        if( !$post || ! wpforo_setting( 'buddypress', 'notification' ) ) return;
        $post['status'] = $status = intval( $status );
        if( wpfval( $post, 'topicid' ) ) {
            $topic = WPF()->topic->get_topic( $post['topicid'] );
        } else {
            return;
        }
        if( $status ) {
            wpforo_bp_delete_notification( $post, $topic );
        } else {
            wpforo_bp_add_notification( $post, $topic );
        }
    }, 10, 2
);

/**
 * Mark notifications as read when reading a topic
 *
 * @since wpForo (1.4.8)
 *
 * If not trying to mark a notification as read
 */
add_action( 'wpforo_actions_end', function( $action = '' ) {
	if( empty( $_GET['itemid'] ) || empty( $_GET['action'] ) ) return;
	if( 'wpforo_mark_read' !== $_GET['action'] ) return;

	// Get required data
	$action   = $action ?: $_GET['action'];
	$user_id  = bp_loggedin_user_id();
	$reply_id = intval( $_GET['itemid'] );

	// Check nonce
	$result = isset( $_REQUEST['_wpnonce'] ) ? wp_verify_nonce( $_REQUEST['_wpnonce'], 'wpforo_mark_topic_' . $reply_id ) : false;

	if( ! $result ) {
		$wp_error = new WP_Error();
		$wp_error->add( 'wpforo_bp_notification_error', __( 'Are you sure you wanted to do that?', 'wpforo' ) );
		// Check current user's ability to edit the user
	} elseif( ! current_user_can( 'edit_user', $user_id ) ) {
		$wp_error = new WP_Error();
		$wp_error->add( 'wpforo_bp_notification_permissions', __( 'You do not have permission to mark notifications for that user.', 'wpforo' ) );
	}

	if( ! isset( $wp_error ) ) {
		$success = bp_notifications_mark_notifications_by_item_id( $user_id, $reply_id, 'community', 'wpforo_new_reply' );
		do_action( 'wpforo_bp_notifications_handler', $success, $user_id, $reply_id, $action );
	}

	// Redirect to the topic
	$redirect = wpforo_post( $reply_id, 'url' );

	// Redirect
	wp_safe_redirect( $redirect );

	// For good measure
	exit();
},          9 );

function wpforo_bp_profile_url( $url = '', $member = [], $template = 'profile' ) {
	if( wpfval( $member, 'userid' ) ) {
		$user_domain = wpforo_bp_profile_domain( $member );
		$tabs        = wpforo_setting( 'buddypress', 'forum_tab' );
		if( $user_domain && $tabs ) {
			if( $template === 'account' ) {
				$url = rtrim( $user_domain, '/' ) . '/profile/';
			} elseif( $template === 'activity' ) {
				$url = rtrim( $user_domain, '/' ) . '/community/';
			} elseif( $template === 'subscriptions' ) {
				$url = rtrim( $user_domain, '/' ) . '/community/subscriptions/';
			} elseif( $template === 'profile' ) {
				$url = $user_domain;
			}
		} else {
			$url = $user_domain;
		}
	}

	return apply_filters( 'wpforo_bp_member_profile_url', $url, $member, $template );
}

function wpforo_bp_profile_domain( $member ) {
	$user_domain = trim( bp_core_get_user_domain( $member['userid'] ), '/' );
	// Get profile root slug and build current login user url //////////////////
	$root_slug = 'members';
	if( strpos( $user_domain, '//' ) !== false ) {
		if( $pages = bp_core_get_directory_page_ids() ) {
			if( wpfval( $pages, 'members' ) ) {
				$root      = get_post_field( 'post_name', intval( $pages['members'] ) );
				$root_slug = $root ?: $root_slug;
			}
			$username = bp_core_get_username( $member['userid'], $member['user_nicename'], $member['user_login'] );
			if( bp_is_username_compatibility_mode() ) $username = rawurlencode( $username );
			$after_domain = bp_core_enable_root_profiles() ? $username : $root_slug . '/' . $username;
			$domain       = trailingslashit( bp_get_root_domain() . '/' . $after_domain );
			$user_domain  = apply_filters( 'bp_core_get_user_domain', $domain, $member['userid'], $member['user_nicename'], $member['user_login'] );
		}
	}

	//////////////////////////////////////////////////////////////////////////
	return strtok( $user_domain, '?' );
}

add_action( 'profile_update', function( $userid ) {
	WPF()->member->reset( $userid );
});

add_filter( 'bp_get_displayed_user_avatar', function( $avatar, $r ) {
	$replace_buddypress_avatar = apply_filters( 'wpforo_replace_buddypress_avatar', false );
	if( $replace_buddypress_avatar ) {
		if( ! $r['width'] ) $r['width'] = 150;

		return wpforo_avatar( $avatar, $r['item_id'], $r['width'], '', $r['alt'] );
	}

	return $avatar;
},          10, 2 );

add_filter( 'bp_get_activity_content_body', function( $body ) { return wpforo_strip_shortcodes( $body ); }, 4 );
