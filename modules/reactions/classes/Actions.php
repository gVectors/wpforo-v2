<?php

namespace wpforo\modules\reactions\classes;

use wpforo\modules\reactions\Reactions;

class Actions {
	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wpforo_after_delete_user', [ $this, 'after_delete_user' ], 10, 2 );
		add_action( 'wpforo_after_delete_post', [ $this, 'after_delete_post' ] );

		#### -- AJAX ACTIONS -- ###
		add_action( 'wp_ajax_wpforo_like_ajax', [ $this, 'like' ] );
		add_action( 'wp_ajax_wpforo_vote_ajax', [ $this, 'vote' ] );

		add_action( 'wp_ajax_wpforo_react',     [ $this, 'react' ] );
		add_action( 'wp_ajax_wpforo_unreact',   [ $this, 'unreact' ] );
	}

	public function after_delete_user( $userid, $reassign ){
		if( $reassign ) WPF()->reaction->edit_for_all_active_boards( [ 'post_userid' => $reassign ], [ 'post_userid' => $userid ] );
		WPF()->reaction->delete_for_all_active_boards( ['userid' => $userid] );
	}

	public function after_delete_post( $post ) {
		WPF()->reaction->delete( ['postid' => $post['postid']] );
	}

	public function like() {
		$response = [ 'count' => 0, 'likers' => '', 'notice' => WPF()->notice->get_notices() ];
		$postid = wpforo_bigintval( wpfval( $_POST, 'postid' ) );
		if( ! is_user_logged_in() ) {
			WPF()->notice->add( wpforo_get_login_or_register_notice_text() );
			$response['notice'] = WPF()->notice->get_notices();
			wp_send_json_error( $response );
		}
		if( ! isset( $_POST['likestatus'] ) || !$postid ) {
			WPF()->notice->add( 'action error', 'error' );
			$response['notice'] = WPF()->notice->get_notices();
			wp_send_json_error( $response );
		}
		if( ! $post = WPF()->post->get_post( $postid ) ) {
			WPF()->notice->add( 'post not found', 'error' );
			$response['notice'] = WPF()->notice->get_notices();
			wp_send_json_error( $response );
		}
		if( ! WPF()->perm->forum_can( 'l', $post['forumid'] ) ) {
			WPF()->notice->add( 'You don\'t have permission to like posts from this forum', 'error' );
			$response['notice'] = WPF()->notice->get_notices();
			wp_send_json_error( $response );
		}
		$is_exists = WPF()->reaction->is_reacted( $postid, WPF()->current_userid );
		if( $_POST['likestatus'] ) {
			if( ! $is_exists ) {
				if( WPF()->reaction->add(
					[
                      'postid'      => $postid,
                      'userid'      => WPF()->current_userid,
                      'post_userid' => $post['userid']
                    ]
				) ) {
					wpforo_clean_cache( 'post-soft', $postid );
					do_action( 'wpforo_like', $post, WPF()->current_userid );
					WPF()->notice->add( 'done', 'success' );
					$response['notice'] = WPF()->notice->get_notices();
					$response['likers'] = WPF()->reaction->Template->likers( $postid );
					$response['count']  = WPF()->reaction->get_post_reactions_count( $postid );
					wp_send_json_success( $response );
				}
			} else {
				WPF()->notice->add( 'You\'re already liked this post', 'success' );
				$response['notice'] = WPF()->notice->get_notices();
				$response['likers'] = WPF()->reaction->Template->likers( $postid );
				$response['count']  = WPF()->reaction->get_post_reactions_count( $postid );
				wp_send_json_success( $response );
			}
		} else {
			if( $is_exists ) {
				if( WPF()->reaction->delete( ['postid' => $postid, 'userid' => WPF()->current_userid] ) ) {
					wpforo_clean_cache( 'post-soft', $postid );
					do_action( 'wpforo_dislike', $post, WPF()->current_userid );
					WPF()->notice->add( 'done', 'success' );
					$response['notice'] = WPF()->notice->get_notices();
					$response['likers'] = WPF()->reaction->Template->likers( $postid );
					$response['count']  = WPF()->reaction->get_post_reactions_count( $postid );
					wp_send_json_success( $response );
				}
			} else {
				WPF()->notice->add( 'You\'re already disliked this post', 'success' );
				$response['notice'] = WPF()->notice->get_notices();
				$response['likers'] = WPF()->reaction->Template->likers( $postid );
				$response['count']  = WPF()->reaction->get_post_reactions_count( $postid );
				wp_send_json_success( $response );
			}
		}

		wp_send_json_error( $response );
	}

	public function vote() {
		if( ! WPF()->current_userid ) {
			WPF()->notice->add( wpforo_get_login_or_register_notice_text() );
			wp_send_json_error( [ 'notice' => WPF()->notice->get_notices() ] );
		}

		if( ! $postid = wpforo_bigintval( wpfval( $_POST, 'postid' ) ) ) {
			WPF()->notice->add( 'Wrong post data', 'error' );
			wp_send_json_error( [ 'notice' => WPF()->notice->get_notices() ] );
		}

		$votestatus = wpfval( $_POST, 'votestatus' );
		if( $votestatus === 'clear' ) {
			$votes = WPF()->post->clear_user_post_votes( $postid );
			WPF()->notice->add( 'You have removed your vote', 'success' );
			wp_send_json_success( [ 'votes' => $votes, 'notice' => WPF()->notice->get_notices() ] );
		} else {
			$reaction = $votestatus === 'down' ? - 1 : 1;

			if( WPF()->post->has_user_voted_post( $postid, $reaction ) ) {
				WPF()->notice->add( 'You are already voted this post' );
				wp_send_json_error( [ 'notice' => WPF()->notice->get_notices() ] );
			} else {
				WPF()->post->clear_user_post_votes( $postid );
			}

			if( false !== ( $votes = WPF()->post->vote_post( $postid, $reaction ) ) ) {
				wp_send_json_success( [ 'votes' => $votes, 'notice' => WPF()->notice->get_notices() ] );
			}
		}

		WPF()->notice->add( 'Wrong post data', 'error' );
		wp_send_json_error( [ 'notice' => WPF()->notice->get_notices() ] );
	}

	public function react() {
		if( ($userid = WPF()->current_userid) && ( $postid = wpfval( $_POST, 'postid' ) ) ){
			$types = Reactions::get_types();
			if( !($type = wpfval( $_POST, 'type' )) || !wpfkey( $types, $type ) ) $type = 'up';

			$is_reacted = WPF()->reaction->is_reacted( $postid, $userid, $type );
			if( !$is_reacted ){
				$post = wpforo_post( $postid );
				$_type = wpfval( $types, $type);
				WPF()->reaction->delete(
					[
						'postid' => $postid,
						'userid' => $userid,
					]
				);
				if( WPF()->reaction->add(
					[
						'postid'      => $postid,
						'post_userid' => wpforo_bigintval( wpfval( $post, 'userid' ) ),
						'userid'      => $userid,
						'type'        => $type,
						'reaction'    => (int) wpfval( $_type, 'reaction' ),
					]
				) ){
					wpforo_clean_cache( 'post-soft', $postid );
					do_action( 'wpforo_like', $post, WPF()->current_userid );
					wp_send_json_success(
						[
							'like_button' => WPF()->reaction->Template->like_button( $post, $userid ),
							'likers' => WPF()->reaction->Template->likers( $postid ),
						]
					);
				}
			}
		}

		wp_send_json_error();
	}

	public function unreact() {
		if( ($userid = WPF()->current_userid) && ( $postid = wpfval( $_POST, 'postid' ) ) ){
			$post = wpforo_post( $postid );
			WPF()->reaction->delete(
				[
					'postid' => $postid,
					'userid' => $userid,
				]
			);
			wpforo_clean_cache( 'post-soft', $postid );
			do_action( 'wpforo_dislike', $post, WPF()->current_userid );
			wp_send_json_success(
				[
					'like_button' => WPF()->reaction->Template->like_button( $post, $userid ),
					'likers' => WPF()->reaction->Template->likers( $postid ),
				]
			);
		}

		wp_send_json_error();
	}
}
