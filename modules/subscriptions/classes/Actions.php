<?php

namespace wpforo\modules\subscriptions\classes;

class Actions {
	public function __construct() {
		$this->init_hooks();
	}

	public function init_hooks(  ) {
		add_action( 'wpforo_action_subscriptions_settings_save', [ $this, 'settings_save' ] );

		add_action( 'wpforo_action_sbscrbconfirm',               [ $this, 'confirmation' ] );
		add_action( 'wpforo_action_unsbscrb',                    [ $this, 'delete' ] );
		add_action( 'wpforo_action_subscribe_manager',           [ $this, 'manager' ] );

		add_action( 'wpforo_after_add_topic',                    [ $this, 'topic_auto_subscribe' ] );
		add_action( 'wpforo_after_add_post',                     [ $this, 'topic_auto_subscribe' ] );
		add_action( 'wpforo_post_approve',                       [ $this, 'after_post_approve' ] );
		add_action( 'wpforo_after_delete_topic',                 [ $this, 'after_delete_topic' ] );
		add_action( 'wpforo_after_delete_user',                  [ $this, 'after_delete_user' ] );

		add_action( 'wpforo_after_add_topic',                    [ $this, 'after_add_topic' ], 10, 2 );
		add_action( 'wpforo_after_add_post',                     [ $this, 'after_add_post'  ], 10, 2 );

		### AJAX ACTIONS ###
		add_action( 'wp_ajax_wpforo_subscribe_ajax',             [ $this, 'subscribe' ] );
		add_action( 'wp_ajax_nopriv_wpforo_subscribe_ajax',      [ $this, 'subscribe' ] );
	}

	/**
	 * subscriptions_settings_save after submit action
	 *
	 * @return void
	 */
	public function settings_save() {
		check_admin_referer( 'wpforo_settings_save_subscriptions' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'subscriptions' );
		} else {
			$subscriptions                                         = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['subscriptions'] ), WPF()->settings->_subscriptions );
			$subscriptions['confirmation_email_subject']           = sanitize_text_field( $subscriptions['confirmation_email_subject'] );
			$subscriptions['confirmation_email_message']           = wpforo_kses( $subscriptions['confirmation_email_message'], 'email' );
			$subscriptions['new_topic_notification_email_subject'] = sanitize_text_field( $subscriptions['new_topic_notification_email_subject'] );
			$subscriptions['new_topic_notification_email_message'] = wpforo_kses( $subscriptions['new_topic_notification_email_message'], 'email' );
			$subscriptions['new_post_notification_email_subject']  = sanitize_text_field( $subscriptions['new_post_notification_email_subject'] );
			$subscriptions['new_post_notification_email_message']  = wpforo_kses( $subscriptions['new_post_notification_email_message'], 'email' );
			$subscriptions['user_mention_email_subject']           = sanitize_text_field( $subscriptions['user_mention_email_subject'] );
			$subscriptions['user_mention_email_message']           = wpforo_kses( $subscriptions['user_mention_email_message'], 'email' );
			$subscriptions['user_following_email_subject']         = sanitize_text_field( $subscriptions['user_following_email_subject'] );
			$subscriptions['user_following_email_message']         = wpforo_kses( $subscriptions['user_following_email_message'], 'email' );
			foreach( WPF()->action->generate_option_names( 'subscriptions' ) as $option_name ) {
				wpforo_update_option( $option_name, $subscriptions );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * subscription_confirmation form submit action
	 */
	public function confirmation() {
		if( $sbs_key = wpfval( WPF()->GET, 'key' ) ) {
			$sbs_key = sanitize_text_field( $sbs_key );
			WPF()->sbscrb->edit( $sbs_key );
			wp_safe_redirect( wpforo_setting( 'authorization', 'redirect_url_after_confirm_sbscrb' ) ?: wpforo_home_url( preg_replace( '#\?.*$#is', '', WPF()->current_url ) ) );
			exit();
		}
	}

	/**
	 * subscription_delete form submit action
	 */
	public function delete() {
		if( $sbs_key = wpfval( WPF()->GET, 'key' ) ) {
			$sbs_key = sanitize_text_field( $sbs_key );
			WPF()->sbscrb->delete( $sbs_key );
			wp_safe_redirect( wpforo_setting( 'authorization', 'redirect_url_after_confirm_sbscrb' ) ?: wpforo_home_url( preg_replace( '#\?.*$#is', '', WPF()->current_url ) ) );
			exit();
		}
	}

	/**
	 * subscribe_manager form submit action
	 */
	public function manager() {
		wpforo_verify_form();
		$data = ( ! empty( $_POST['wpforo']['forums'] ) ? array_map( 'sanitize_title', $_POST['wpforo']['forums'] ) : [] );
		$all  = ( ! empty( $_POST['wpforo']['check_all'] ) ? sanitize_title( $_POST['wpforo']['check_all'] ) : '' );

		WPF()->sbscrb->reset( $data, $all, (int) wpfval( $_POST, 'wpforo', 'userid' ) );
		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	function topic_auto_subscribe( $item ) {
		if( ! isset( $_POST['wpforo_topic_subs'] ) || ! $_POST['wpforo_topic_subs'] ) return false;

		if( isset( $item['forumid'] ) && $item['forumid'] ) {
			if( isset( $item['private'] ) && $item['private'] && ! wpforo_is_owner( $item['userid'] ) ) {
				if( ! WPF()->perm->forum_can( 'vp', $item['forumid'] ) ) {
					WPF()->notice->add( 'You are not permitted to subscribe here', 'error' );
					return false;
				}
			}
		} else {
			WPF()->notice->add( 'Forum ID is not detected', 'error' );
			return false;
		}

		$args = [
			'itemid'     => wpforo_bigintval( $item['topicid'] ),
			'type'       => 'topic',
			'userid'     => WPF()->current_userid,
			'user_name'  => '',
			'user_email' => '',
		];

		if( ! WPF()->current_userid ) {
			if( WPF()->current_user_email )        $args['user_email'] = WPF()->current_user_email;
			if( WPF()->current_user_display_name ) $args['user_name']  = WPF()->current_user_display_name;
		}
		if( ! $args['userid'] && ! $args['user_email'] ) return false;

		$args['confirmkey'] = WPF()->sbscrb->get_confirm_key();

		if( wpforo_setting( 'subscriptions', 'subscribe_confirmation' ) ) {
			############### Sending Email  ##################
			$confirmlink = WPF()->sbscrb->get_confirm_link( $args );
			$member_name = ( WPF()->current_userid ? wpforo_user_dname( WPF()->current_user ) : ( $args['user_name'] ?: $args['user_email'] ) );
			$subject     = wpforo_setting( 'subscriptions', 'confirmation_email_subject' );
			$message     = wpforo_setting( 'subscriptions', 'confirmation_email_message' );
			$topic       = WPF()->topic->get_topic( $item['topicid'], false );
			$from_tags   = [ "[user_display_name]", "[entry_title]", "[confirm_link]" ];
			$to_words    = [
				sanitize_text_field( $member_name ),
				'<strong>' . sanitize_text_field( $topic['title'] ) . '</strong>',
				'<br><br><a target="_blank" href="' . esc_url( $confirmlink ) . '"> ' . wpforo_phrase( 'Confirm my subscription', false ) . ' </a>',
			];
			$subject     = stripslashes( str_replace( $from_tags, $to_words, $subject ) );
			$message     = stripslashes( str_replace( $from_tags, $to_words, $message ) );
			$message     = wpforo_kses( $message, 'email' );

			add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
			$headers = wpforo_mail_headers();

			if( wp_mail( WPF()->current_user_email, sanitize_text_field( $subject ), $message, $headers ) ) {
				if( $response = WPF()->sbscrb->add( $args ) ) return $response;
			} else {
				WPF()->notice->add( 'Can\'t send confirmation email', 'error' );
				return false;
			}
			remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
			############### Sending Email end  ##############
		} else {
			$args['active'] = 1;
			if( $response = WPF()->sbscrb->add( $args ) ) return $response;
		}

		return false;
	}

	public function after_post_approve( $post ) {
		if( (int) wpfval($post, 'is_first_post') ){
			if( ($topicid = wpforo_bigintval(wpfval($post, 'topicid')))
				&& ($topic = WPF()->topic->get_topic( $topicid ))
			    && ($forumid = (int) wpfval($post, 'forumid'))
				&& ($forum = WPF()->forum->get_forum( $forumid ))
			) {
				$this->after_add_topic( $topic, $forum );
				WPF()->sbscrb->Mentioning->Actions->send_mail_to_mentioned_users( $topic, $forum );
			}
		}else{
			if( ($topicid = wpforo_bigintval(wpfval($post, 'topicid')))
			    && ($topic = WPF()->topic->get_topic( $topicid ))
			) {
				$this->after_add_post( $post, $topic );
				WPF()->sbscrb->Mentioning->Actions->send_mail_to_mentioned_users( $post, $topic );
			}
		}
	}

	public function after_delete_topic( $topic ) {
		if( wpfval( $topic, 'topicid' ) ) WPF()->sbscrb->delete( [ 'type' => 'topic', 'itemid' => $topic['topicid'] ] );
	}

	public function after_delete_user( $userid ) {
		WPF()->sbscrb->delete_for_all_active_boards( [ 'userid' => $userid ] );
	}

	public function after_add_topic( $topic, $forum ) {
		if( ! wpfval( $topic, 'url' ) )       $topic['url'] = wpforo_topic( $topic['topicid'], 'url' );
		if( ! wpfkey( $topic, 'body' ) ) $topic['body'] = wpforo_post( $topic['first_postid'], 'body' );
		$owner = wpforo_member( $topic );

		$forums_sbs  = WPF()->sbscrb->get_subscribes( [ 'itemid' => 0,                 'type' => [ 'forums', 'forums-topics' ] ] );
		$forum_sbs   = WPF()->sbscrb->get_subscribes( [ 'itemid' => $topic['forumid'], 'type' => [ 'forum' , 'forum-topic'   ] ] );
		$subscribers = array_merge( $forums_sbs, $forum_sbs );
		if( wpforo_setting( 'email', 'new_topic_notify' ) ) {
			foreach( wpforo_setting( 'email', 'admin_emails' ) as $admin_email ) $subscribers[] = $admin_email;
		}
		$subscribers = apply_filters( 'wpforo_forum_subscribers', $subscribers, $topic, $forum );

		foreach( $subscribers as $subscriber ) {
			if( is_array( $subscriber ) ) {
				if( $subscriber['userid'] ) {
					$user = WPF()->member->get_member( $subscriber['userid'] );
				} elseif( $subscriber['user_email'] ) {
					$user = [ 'groupid' => 4, 'groupids' => [4], 'display_name' => ( $subscriber['user_name'] ?: $subscriber['user_email'] ), 'user_email' => $subscriber['user_email'] ];
				} else {
					continue;
				}

				if( ! WPF()->topic->view_access( $topic, $user ) ) continue;

				$unsubscribe_link = WPF()->sbscrb->get_unsubscribe_link( $subscriber['confirmkey'] );
			} else {
				$user           = [ 'display_name' => $subscriber, 'user_email' => $subscriber ];
				$unsubscribe_link = '';
			}

			if( wpforo_is_users_same( $owner, $user ) ) continue;

			$sbj_msg = WPF()->sbscrb->get_sbj_msg( 'new_topic', $forum, $topic, $owner, $user, $unsubscribe_link );
			wpforo_send_email( $user['user_email'], $sbj_msg['sbj'], $sbj_msg['msg'] );
		}
	}

	public function after_add_post( $post, $topic ) {
		$owner       = wpforo_member( $post );
		$forums_sbs  = WPF()->sbscrb->get_subscribes( [ 'itemid' => 0, 'type' => 'forums-topics' ] );
		$forum_sbs   = WPF()->sbscrb->get_subscribes( [ 'itemid' => $post['forumid'], 'type' => 'forum-topic' ] );
		$topic_sbs   = WPF()->sbscrb->get_subscribes( [ 'itemid' => $post['topicid'], 'type' => 'topic' ] );
		$subscribers = array_merge( $forums_sbs, $forum_sbs, $topic_sbs );
		if( wpforo_setting( 'email', 'new_reply_notify' ) ) {
			foreach( wpforo_setting( 'email', 'admin_emails' ) as $admin_email ) $subscribers[] = $admin_email;
		}
		$subscribers = apply_filters( 'wpforo_topic_subscribers', $subscribers, $post, $topic );

		foreach( $subscribers as $subscriber ) {
			if( is_array( $subscriber ) ) {
				if( $subscriber['userid'] ) {
					$user = WPF()->member->get_member( $subscriber['userid'] );
				} elseif( $subscriber['user_email'] ) {
					$user = [ 'groupid' => 4, 'groupids' => [4], 'display_name' => ( $subscriber['user_name'] ?: $subscriber['user_email'] ), 'user_email' => $subscriber['user_email'] ];
				} else {
					continue;
				}

				if( ! WPF()->post->view_access( $post, $user ) ) continue;

				$unsubscribe_link = WPF()->sbscrb->get_unsubscribe_link( $subscriber['confirmkey'] );
			} else {
				$user           = [ 'display_name' => $subscriber, 'user_email' => $subscriber ];
				$unsubscribe_link = '';
			}

			if( wpforo_is_users_same( $owner, $user ) ) continue;

			$sbj_msg = WPF()->sbscrb->get_sbj_msg( 'new_post', $topic, $post, $owner, $user, $unsubscribe_link );
			wpforo_send_email( $user['user_email'], $sbj_msg['sbj'], $sbj_msg['msg'] );
		}
	}

	############### -- AJAX ACTIONS -- ###############

	public function subscribe() {
		$return = 0;
		$resp   = [ 'notice' => WPF()->notice->get_notices() ];
		$args   = [
			'itemid'     => wpforo_bigintval( wpfval( $_POST, 'itemid' ) ),
			'type'       => sanitize_text_field( wpfval( $_POST, 'type' ) ),
			'userid'     => WPF()->current_userid,
			'active'     => 0,
			'user_name'  => '',
			'user_email' => '',
		];

		if( ! WPF()->current_userid ) {
			if( WPF()->current_user_email )        $args['user_email'] = WPF()->current_user_email;
			if( WPF()->current_user_display_name ) $args['user_name']  = WPF()->current_user_display_name;
		}
		if( ! $args['userid'] && ! $args['user_email'] ) wp_send_json_error( $resp );

		if( wpfval( $_POST, 'status' ) === 'subscribe' ) {
			$item_title = '';
			if( $args['type'] === 'forum' ) {
				$forum      = WPF()->forum->get_forum( $args['itemid'] );
				$item_title = wpfval( $forum, 'title' );
				if( wpfval( $forum, 'forumid' ) && ! WPF()->perm->forum_can( 'vf', $forum['forumid'] ) ) {
					WPF()->notice->add( 'You are not permitted to subscribe here', 'error' );
					$resp['notice'] = WPF()->notice->get_notices();
					wp_send_json_error( $resp );
				}
			} elseif( $args['type'] === 'topic' ) {
				$topic      = WPF()->topic->get_topic( $args['itemid'], false );
				$item_title = wpfval( $topic, 'title' );
				if( wpfval( $topic, 'forumid' ) ) {
					if( wpfval( $topic, 'private' ) && ! wpforo_is_owner( $topic['userid'], $topic['email'] ) && ! WPF()->perm->forum_can( 'vp', $topic['forumid'] ) ) {
						WPF()->notice->add( 'You are not permitted to subscribe here', 'error' );
						$resp['notice'] = WPF()->notice->get_notices();
						wp_send_json_error( $resp );
					}
				}
			} elseif( $args['type'] === 'user' ) {
				$member     = WPF()->member->get_member( $args['itemid'] );
				$item_title = wpforo_user_dname( $member );
				if( $args['itemid'] === WPF()->current_userid ) {
					WPF()->notice->add( 'You are not permitted to subscribe with you', 'error' );
					$resp['notice'] = WPF()->notice->get_notices();
					wp_send_json_error( $resp );
				}
			}

			$args['confirmkey'] = WPF()->sbscrb->get_confirm_key();

			if( wpforo_setting( 'subscriptions', 'subscribe_confirmation' ) ) {
				############### Sending Email  ##################
				$confirmlink = WPF()->sbscrb->get_confirm_link( $args );
				$member_name = ( WPF()->current_userid ? wpforo_user_dname( WPF()->current_user ) : ( $args['user_name'] ?: $args['user_email'] ) );
				$subject     = wpforo_setting( 'subscriptions', 'confirmation_email_subject' );
				$message     = wpforo_setting( 'subscriptions', 'confirmation_email_message' );
				$from_tags   = [ "[user_display_name]", "[entry_title]", "[confirm_link]" ];
				$to_words    = [
					sanitize_text_field( $member_name ),
					sprintf( '<strong>%1$s</strong>', sanitize_text_field( $item_title ) ),
					sprintf( '<br><br><a target="_blank" href="%1$s"> %2$s </a>', esc_url( $confirmlink ), wpforo_phrase( 'Confirm my subscription', false ) ),
				];
				$subject     = stripslashes( strip_tags( str_replace( $from_tags, $to_words, $subject ) ) );
				$message     = stripslashes( str_replace( $from_tags, $to_words, $message ) );
				$message     = wpforo_kses( $message, 'email' );

				add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
				$headers = wpforo_mail_headers();

				if( wp_mail( WPF()->current_user_email, sanitize_text_field( $subject ), $message, $headers ) ) {
					if( WPF()->sbscrb->add( $args ) ) $return = 1;
				} else {
					WPF()->notice->add( 'Can\'t send confirmation email', 'error' );
				}
				remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
				############### Sending Email end  ##############
			} else {
				$args['active'] = 1;
				if( WPF()->sbscrb->add( $args ) ) $return = 1;
			}
		} elseif( wpfval( $_POST, 'status' ) === 'unsubscribe' ) {
			$subscribe = WPF()->sbscrb->get_subscribe( $args );
			$return    = (int) WPF()->sbscrb->delete( $subscribe['confirmkey'] );
		}

		$resp['notice'] = WPF()->notice->get_notices();
		if( $return ) {
			wp_send_json_success( $resp );
		} else {
			wp_send_json_error( $resp );
		}
	}
}
