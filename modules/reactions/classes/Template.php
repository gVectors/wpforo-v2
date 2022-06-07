<?php

namespace wpforo\modules\reactions\classes;

use wpforo\modules\reactions\Reactions;

class Template {
	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks(  ) {
		add_action( 'wpforo_post_footer_start', function( $post ){
			echo $this->like_button( $post );
		} );
		add_action( 'wpforo_post_content_end', function( $post ){
			echo $this->like_button( $post );
		} );
		add_action( 'wpforo_post_left_end', function( $post ){
			echo $this->like_button( $post );
		});
		add_action( 'wpforo_post_bottom_start', function( $post ){
			echo '<div class="reacted-users">' . $this->likers( $post['postid'] ) . '</div>';
		} );
		add_action( 'wpforo_post_bottom_end', function( $post ){
			echo '<div class="reacted-users">' . $this->likers( $post['postid'] ) . '</div>';
		} );
		add_action( 'wpforo_post_footer_bottom_start', function( $post ){
			echo '<div class="reacted-users">' . $this->likers( $post['postid'] ) . '</div>';
		} );
	}

	private function get_button_by_type( $type ) {
		$types = Reactions::get_types();
		if( $_type = wpfval( $types, $type ) ) return $_type['icon'];
		return '<i class="far fa-thumbs-up"></i>';
	}

	public function like_button( $post, $userid = 0 ) {
		if( wpforo_is_owner( $post['userid'], $post['email'] ) ) return '';
		$reaction = WPF()->reaction->get_user_reaction( $post['postid'], $userid );
		$type = wpfval($reaction, 'type');
		$all = [];
		foreach( Reactions::get_types() as $key => $_type ){
			$all[$key] = sprintf(
				'<span class="%1$s wpf-react-%2$s" data-type="%2$s">%3$s</span>',
				( $type !== $key ? 'wpf-react' : '' ),
				$key,
				$_type['icon']
			);
		}
		$up = $all['up']; unset( $all['up'] ); $all['up'] = $up;
		return sprintf(
			'<div class="wpf-reaction-wrap"><div class="wpforo-reaction wpf-popover" aria-haspopup="true" data-currentstate="%1$s">
				<span class="wpf-current-reaction %2$s" data-type="%1$s">%3$s</span>
				<div class="wpf-popover-content">%4$s</div>
			</div></div>',
			$type,
			( !$type ? 'wpf-react wpf-unreacted' : 'wpf-unreact wpf-react-' . $type ),
			$this->get_button_by_type( $type ),
			implode( '', $all )
		);
	}

	public function _like_button( $post = [], $type = 'icon-count' ) {
		$login       = is_user_logged_in();
		$button_html = '';
		$forumid     = ( isset( $post['forumid'] ) ) ? $post['forumid'] : 0;
		$postid      = ( isset( $post['postid'] ) ) ? $post['postid'] : 0;
		if( WPF()->perm->forum_can( 'l', $forumid ) && $login && WPF()->current_userid != $post['userid'] ) {
			$like_status = ( WPF()->reaction->is_reacted(
				$postid,
				WPF()->current_userid
			) === false ? 'wpforo-like' : 'wpforo-unlike' );
			$like_icon   = ( $like_status === 'wpforo-like' ) ? 'far' : 'fas';
			$icon        = ( in_array( $type, ['icon','icon-text','icon-count'], true ) ) ? '<i class="' . esc_attr(
					$like_icon
				) . ' fa-thumbs-up wpfsx wpforo-like-ico"></i>' : '';
			$number      = ( $type === 'icon-count' ) ? '<span class="wpf-like-count">' . intval(
					$post['likes_count']
				) . '</span>' : '';
			$phrase      = ( $type === 'text' || $type == 'icon-text' ) ? '<span class="wpforo-like-txt">' . wpforo_phrase(
					str_replace( 'wpforo-', '', $like_status ),
					false
				) . '</span>' : '';
			$button_html = '<span class="wpf-action ' . $like_status . '" data-postid="' . wpforo_bigintval(
					$postid
				) . '">' . '<span class="wpf-like-icon" wpf-tooltip="' . esc_attr(
				               wpforo_phrase( str_replace( 'wpforo-', '', $like_status ), false )
			               ) . '">' . $icon . '</span>' . $phrase . $number . '</span>';
		}
		return $button_html;
	}

	public 	function likers( $postid ) {
		if( ! $postid ) return '';

		$l_count     = wpforo_post( $postid, 'likes_count' );
		$l_usernames = wpforo_post( $postid, 'likers_usernames' );
		$return      = '';

		if( $l_count ) {
			if( $l_usernames[0]['userid'] == WPF()->current_userid ) $l_usernames[0]['display_name'] = wpforo_phrase( 'You', false );
			if( $l_count == 1 ) {
				$return = sprintf( wpforo_phrase( '%s reacted', false ), '<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[0]['userid'] ) ) . '">' . esc_html( $l_usernames[0]['display_name'] ) . '</a>' );
			} elseif( $l_count == 2 ) {
				$return = sprintf( wpforo_phrase( '%s and %s reacted', false ), '<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[0]['userid'] ) ) . '">' . esc_html( $l_usernames[0]['display_name'] ) . '</a>', '<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[1]['userid'] ) ) . '">' . esc_html( $l_usernames[1]['display_name'] ) . '</a>' );
			} elseif( $l_count == 3 ) {
				$return = sprintf(
					wpforo_phrase( '%s, %s and %s reacted', false ),
					'<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[0]['userid'] ) ) . '">' . esc_html( $l_usernames[0]['display_name'] ) . '</a>',
					'<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[1]['userid'] ) ) . '">' . esc_html( $l_usernames[1]['display_name'] ) . '</a>',
					'<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[2]['userid'] ) ) . '">' . esc_html( $l_usernames[2]['display_name'] ) . '</a>'
				);
			} elseif( $l_count >= 4 ) {
				$l_count = $l_count - 3;
				$return  = sprintf(
					wpforo_phrase( '%s, %s, %s and %d people reacted', false ),
					'<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[0]['userid'] ) ) . '">' . esc_html( $l_usernames[0]['display_name'] ) . '</a>',
					'<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[1]['userid'] ) ) . '">' . esc_html( $l_usernames[1]['display_name'] ) . '</a>',
					'<a href="' . esc_url( WPF()->member->get_profile_url( $l_usernames[2]['userid'] ) ) . '">' . esc_html( $l_usernames[2]['display_name'] ) . '</a>',
					$l_count
				);
			}
		}

		return $return;
	}
}
