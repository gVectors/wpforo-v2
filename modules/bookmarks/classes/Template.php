<?php

namespace wpforo\modules\bookmarks\classes;

class Template {
	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks(  ) {
		add_filter( 'wpforo_template_buttons',
			function( $html, $button, $forum, $topic, $post ){
				if( $button === 'bookmark' ){
					$post['forumid'] = (int) wpfval( $forum, 'forumid' );
					$html .= $this->button( $post );
				}

				return $html;
			},
	        10, 5
		);
	}

	/**
	 * @param array $post
	 *
	 * @return string
	 */
	public function button( $post ) {
		if(
			( $postid  = wpforo_bigintval( wpfval( $post,  'postid' ) ) )
			&& ( $forumid = wpforo_bigintval( wpfval( $post, 'forumid' ) ) )
			&& WPF()->perm->forum_can( 'l', $forumid )
		) {
			$is_bookmarked = WPF()->bookmark->is_user_bookmarked_this( $postid );
			return $this->_button( ! $is_bookmarked );
		}

		return '';
	}

	/**
	 * @param bool $status
	 *
	 * @return string
	 */
	public function _button( $status, $onlyicon = true ) {
		return sprintf(
		'<span class="wpf-action %1$s" wpf-tooltip="%2$s">%3$s %4$s</span>',
			( $status ? 'wpforo-bookmark' : 'wpforo-unbookmark' ),
			( $status ? wpforo_phrase( 'Bookmark', false ) : wpforo_phrase( 'Unbookmark', false ) ),
			( $status ? '<i class="fa-regular fa-bookmark wpfsx"></i>' : '<i class="fa-solid fa-bookmark wpfsx"></i>' ),
			(
				! $onlyicon ?
				sprintf(
					'<span class="wpf-action-txt">%1$s</span>',
					( $status ? wpforo_phrase( 'Bookmark', false ) : wpforo_phrase( 'Unbookmark', false ) )
				) : ''
			)
		);
	}
}
