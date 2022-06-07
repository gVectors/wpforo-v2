<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * wpForo Classic Theme Functions
 * @hook: action - 'init'
 * @description: only for wpForo theme functions. For WordPress theme functions use functions-wp.php file.
 * @theme: Classic
 */

include_once( wpftpl( 'layouts/4/forum-thread.php' ) );
include_once( wpftpl( 'layouts/4/topic-thread.php' ) );
include_once( wpftpl( 'layouts/4/post-thread.php' ) );

function wpforo_thread_reply( $post, $topic = [], $forum = [], $level = 0, $parents = [] ) {
	if( wpfval( $post, 'postid' ) ) {
		$post = wpforo_post( $post['postid'] );
		wpforo_thread_reply_template( $post, $topic, $forum, $level, $parents );
	}
}

function wpforo_classic_reply_form_head( $string, $args ) {
	if( $args['layout'] === 3 && WPF()->tpl->layout_exists( 3 ) ) {
		$string = wpforo_phrase( 'Your Answer', false, 'default' );
	}

	return $string;
}

add_filter( 'wpforo_reply_form_head', 'wpforo_classic_reply_form_head', 1, 2 );

function wpforo_forum_layout_editors( $settings, $editor ) {
	if( $editor === 'post' && 4 === wpfval( WPF()->current_object, 'layout' ) ) {
		$settings['tinymce']['toolbar1'] = 'fontsizeselect,bold,italic,underline,forecolor,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,blockquote,pre,wpf_spoil,pastetext,source_code,emoticons';
		$settings['editor_height']       = 100;
	}

	return $settings;
}

add_filter( 'wpforo_editor_settings', 'wpforo_forum_layout_editors', 1, 2 );
