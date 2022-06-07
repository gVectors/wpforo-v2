<?php

namespace wpforo\modules\mentioning\classes;

class Actions {
	public function __construct() {
		$this->init_hooks();
	}

	public function init_hooks() {
		add_filter( 'wpforo_body_text_filter', [ $this, 'mentioned_code_to_link' ] );
		add_action( 'wpforo_after_add_topic',  [ $this, 'send_mail_to_mentioned_users' ], 10, 2 );
		add_action( 'wpforo_after_add_post',   [ $this, 'send_mail_to_mentioned_users' ], 10, 2 );

		#### -- AJAX ACTIONS -- ###
		add_action( 'wp_ajax_wpforo_mute_mentions', [ $this, 'mute' ] );
	}

	public function mentioned_code_to_link( $text ) {
		return preg_replace_callback(
			'#@([^\s\0<>\[\]!,.()\'\"|?@]+)($|[\s\0<>\[\]!,.()\'\"|?@])#iu',
			function( $match ) {
				$return = $match[0];
				if( $member = WPF()->member->get_member( $match[1] ) ) {
					$href   = WPF()->member->get_profile_url( $member );
					$dname  = wpforo_user_dname( $member );
					$return = sprintf( '<a href="%s" title="%s">@%s</a>%s', $href, $dname, $match[1], $match[2] );
				}

				return $return;
			},
			$text
		);
	}

	public function send_mail_to_mentioned_users( $item, $pitem ) {
		if( preg_match_all( '#@([^\s\0<>\[\]!,.()\'\"|?@]+)(?:$|[\s\0<>\[\]!,.()\'\"|?@])#iu', $item['body'], $matches, PREG_SET_ORDER ) ) {
			$topic = wpfkey( $item, 'first_postid' ) ? $item : $pitem;
			$owner = wpforo_member( $item );
			foreach( $matches as $match ) {
				$user = WPF()->member->get_member( $match[1] );
				if( ! empty( $user['user_email'] ) && ! $user['is_mention_muted'] ) {
					if( wpforo_is_users_same( $owner, $user ) ) continue;
					if( wpfkey( $item, 'first_postid' ) ){
						if( ! WPF()->topic->view_access( $item, $user ) ) continue;
					}else{
						if( ! WPF()->post->view_access( $item, $user ) ) continue;
					}
					//Adding notification if user doesn't mention yourself
					if( apply_filters( 'wpforo_user_mentioning_notification', true ) ) {
						$notify = false;
						//If current post is a reply check the parent post
						if( wpfval( $item, 'parentid' ) ) {
							$parent_post = wpforo_post( $item['parentid'] );
							//Parent post author has already received a "new_reply" notification,
							//so the mentioning is not neccessary. It only adds a new notification
							//if the parent post author is not the mentioned user.
							if( ! empty( $parent_post ) && $user['userid'] != wpfval( $parent_post, 'userid' ) ) $notify = true;
						} else {
							//This post is probably a first topic post or a single post (not a reply)
							//The topic author has already received a "new_reply" notification,
							//so the mentioning is not neccessary. It only adds a new notification
							//if the topic author is not the mentioned user.
							if( ! empty( $topic ) && $user['userid'] != wpfval( $topic, 'userid' ) ) $notify = true;
						}
						if( $notify ) {
							$args = [
								'itemid'    => ( wpfval( $item, 'postid' ) ? $item['postid'] : $item['first_postid'] ),
								'userid'    => $user['userid'],
								'content'   => ( wpfval( $item, 'body' ) ? $item['body'] : $item['title'] ),
								'permalink' => ( wpfval( $item, 'posturl' ) ? $item['posturl'] : $item['topicurl'] ),
							];
							WPF()->activity->add_notification( 'new_mention', $args );
						}
					}

					//Sending Email Notification
					if( wpforo_setting( 'subscriptions', 'user_mention_notify' ) ) {
						$sbj_msg = WPF()->sbscrb->get_sbj_msg( 'user_mention', $pitem, $item, $owner, $user, '' );
						wpforo_send_email( $user['user_email'], $sbj_msg['sbj'], $sbj_msg['msg'] );
					}
				}
			}

		}
	}

	/**
	 * Mute Mentions, do not send mention emails
	 */
	public function mute() {
		if( WPF()->current_userid && WPF()->current_object['user_is_same_current_user'] ) {
			$is_mention_muted = ! (int) wpfval( $_POST, 'currentstate' );
			$r = WPF()->member->update_profile_field( WPF()->current_userid, 'is_mention_muted', $is_mention_muted );
			if( $r !== false ) wp_send_json_success( [
				'ico'          => WPF()->sbscrb->Mentioning->Template->get_currentstate_ico( $is_mention_muted ),
				'currentstate' => (int) $is_mention_muted,
			] );
		}
		wp_send_json_error();
	}
}
