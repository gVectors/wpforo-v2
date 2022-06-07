<?php

namespace wpforo\modules\bookmarks\classes;

class Actions {
	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wpforo_after_delete_user',  [ $this, 'after_delete_user' ] );
		add_action( 'wpforo_after_delete_board', [ $this, 'after_delete_board' ] );
		add_action( 'wpforo_after_delete_post',  [ $this, 'after_delete_post' ] );

		#### -- AJAX ACTIONS -- ###
		add_action( 'wp_ajax_wpforo_bookmark',   [ $this, 'bookmark' ] );
		add_action( 'wp_ajax_wpforo_unbookmark', [ $this, 'unbookmark' ] );
	}

	public function after_delete_user( $userid ){
		WPF()->bookmark->delete( [ 'userid' => $userid ] );
	}

	public function after_delete_board( $boardid ) {
		WPF()->bookmark->delete( [ 'boardid' => $boardid ] );
	}

	public function after_delete_post( $post ) {
		$boardid = WPF()->board->get_current( 'boardid' );
		WPF()->bookmark->delete( [ 'boardid' => $boardid, 'postid' => $post['postid'] ] );
	}

	public function bookmark() {
		$r = false;
		if( WPF()->current_userid && ( $postid = wpforo_bigintval( wpfval( $_POST, 'postid' ) ) ) ){
			$boardid = WPF()->board->get_current( 'boardid' );

			$bookmark = WPF()->bookmark->get_user_bookmark( $postid, WPF()->current_userid, $boardid );

			if( $bookmark ){
				if( ! $bookmark['status'] ){
					$r = WPF()->bookmark->edit(
						[
							'status'  => true,
							'created' => current_time( 'timestamp', 1 )
						],
						$bookmark['bookmarkid']
					);
				}else{
					$r = true;
				}
			}else{
				$r = WPF()->bookmark->add(
					[
						'userid'  => WPF()->current_userid,
						'boardid' => $boardid,
						'postid'  => $postid,
					]
				);
			}
		}

		if( $r ){
			wp_send_json_success( [ 'button' => WPF()->bookmark->Template->_button( false ) ] );
		}else{
			wp_send_json_error();
		}
	}

	public function unbookmark() {
		$r = false;
		if( WPF()->current_userid && ( $postid = wpforo_bigintval( wpfval( $_POST, 'postid' ) ) ) ){
			$boardid = WPF()->board->get_current( 'boardid' );

			$r = WPF()->bookmark->delete(
				[
					'userid'  => WPF()->current_userid,
					'boardid' => $boardid,
					'postid'  => $postid,
				]
			);
		}

		if( $r ){
			wp_send_json_success( [ 'button' => WPF()->bookmark->Template->_button( true ) ] );
		}else{
			wp_send_json_error();
		}
	}
}
