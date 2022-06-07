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

function wpforo_get_topic_overview_chunk( $topicid, $row_count = 5, $offset = 0 ){
	$roots = WPF()->post->get_posts( [ 'topicid' => $topicid, 'is_first_post' => 0, 'parentid' => 0, 'offset' => $offset, 'row_count' => $row_count ], $roots_count );
	$tree = [];
	foreach( $roots as $root ) {
		$tree[ wpforo_bigintval( $root['postid'] ) ] = wpforo_bigintval( $root['parentid'] );
		$posts = WPF()->post->get_posts( [ 'root' => $root['postid'] ] );
		foreach( $posts as $post ) $tree[ wpforo_bigintval( $post['postid'] ) ] = wpforo_bigintval( $post['parentid'] );
	}

    return [ 'html' => ($tree ? wpforo_thread_tree( 0, $tree ) : ''), 'roots_count' => intval( $roots_count ), 'nomore' => ! ($roots_count > ( $offset + $row_count)) ];
}

function wpforo_topic_head_top( $forum, $topic ){
    ?>
    <div class="wpf-post-info">
        <span class="wpf-post-info-forum"><i style="color: <?php echo $forum['color'] ?>;" class="<?php echo $forum['icon'] ?>"></i> <?php echo $forum['title'] ?></span>
        <div>
            <a href="<?php echo esc_url( wpforo_post( $topic['last_post'], 'url' ) ); ?>">
                <svg style="height: 14px; margin-right: 5px; vertical-align: text-top;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M18.656.93,6.464,13.122A4.966,4.966,0,0,0,5,16.657V18a1,1,0,0,0,1,1H7.343a4.966,4.966,0,0,0,3.535-1.464L23.07,5.344a3.125,3.125,0,0,0,0-4.414A3.194,3.194,0,0,0,18.656.93Zm3,3L9.464,16.122A3.02,3.02,0,0,1,7.343,17H7v-.343a3.02,3.02,0,0,1,.878-2.121L20.07,2.344a1.148,1.148,0,0,1,1.586,0A1.123,1.123,0,0,1,21.656,3.93Z"/>
                    <path d="M23,8.979a1,1,0,0,0-1,1V15H18a3,3,0,0,0-3,3v4H5a3,3,0,0,1-3-3V5A3,3,0,0,1,5,2h9.042a1,1,0,0,0,0-2H5A5.006,5.006,0,0,0,0,5V19a5.006,5.006,0,0,0,5,5H16.343a4.968,4.968,0,0,0,3.536-1.464l2.656-2.658A4.968,4.968,0,0,0,24,16.343V9.979A1,1,0,0,0,23,8.979ZM18.465,21.122a2.975,2.975,0,0,1-1.465.8V18a1,1,0,0,1,1-1h3.925a3.016,3.016,0,0,1-.8,1.464Z"/>
                </svg>
                <span class=""><?php wpforo_phrase( 'Last Post' ); ?></span>
            </a>
            <span><?php wpforo_phrase( 'by', true, 'lower' ) ?></span> <?php wpforo_member_link( wpforo_member( wpforo_post( $topic['last_post'], 'userid' ) ) ) ?> <?php wpforo_date( wpforo_post( $topic['last_post'], 'created' ) ) ?>
			<?php do_action( 'wpforo_topic_head_left', $forum, $topic ) ?>
        </div>
    </div>
    <div class="wpf-post-stat">
        <div class="wpf-post-stat-box">
                    <span class="wpf-tstat">
                        <svg style="height: 16px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g id="_01_align_center" data-name="01 align center"><path d="M24,24H12.018A12,12,0,1,1,24,11.246l0,.063ZM12.018,2a10,10,0,1,0,0,20H22V11.341A10.018,10.018,0,0,0,12.018,2Z"/><rect x="7" y="7" width="6" height="2"/><rect x="7" y="11" width="10" height="2"/><rect x="7" y="15" width="10" height="2"/></g></svg>
                        <?php echo wpforo_print_number( $topic['posts'] ) ?>
                    </span>
            <span class="wpf-tlabel"><?php wpforo_phrase( 'posts' ) ?></span>
        </div>
        <div class="wpf-post-stat-box">
                    <span class="wpf-tstat">
                        <svg style="height: 17px;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" data-name="Layer 1"><path d="m7.5 13a4.5 4.5 0 1 1 4.5-4.5 4.505 4.505 0 0 1 -4.5 4.5zm0-7a2.5 2.5 0 1 0 2.5 2.5 2.5 2.5 0 0 0 -2.5-2.5zm7.5 14a5.006 5.006 0 0 0 -5-5h-5a5.006 5.006 0 0 0 -5 5v4h2v-4a3 3 0 0 1 3-3h5a3 3 0 0 1 3 3v4h2zm2.5-11a4.5 4.5 0 1 1 4.5-4.5 4.505 4.505 0 0 1 -4.5 4.5zm0-7a2.5 2.5 0 1 0 2.5 2.5 2.5 2.5 0 0 0 -2.5-2.5zm6.5 14a5.006 5.006 0 0 0 -5-5h-4v2h4a3 3 0 0 1 3 3v4h2z"/></svg>
                        <?php echo wpforo_get_users_count_for_topic( $topic['topicid'] ) ?>
                    </span>
            <span class="wpf-tlabel"><?php wpforo_phrase( 'users', 'lower' ) ?></span>
        </div>
        <div class="wpf-post-stat-box">
                    <span class="wpf-tstat">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g id="_01_align_center" data-name="01 align center"><path d="M15.021,7l.336-2.041a3.044,3.044,0,0,0-4.208-3.287A3.139,3.139,0,0,0,9.582,3.225L7.717,7H3a3,3,0,0,0-3,3v9a3,3,0,0,0,3,3H22.018L24,10.963,24.016,7ZM2,19V10A1,1,0,0,1,3,9H7V20H3A1,1,0,0,1,2,19Zm20-8.3L20.33,20H9V8.909l2.419-4.9A1.07,1.07,0,0,1,13.141,3.8a1.024,1.024,0,0,1,.233.84L12.655,9H22Z"/></g></svg>
                        <?php echo wpforo_get_likes_for_topic( $topic['topicid'] ) ?>
                    </span>
            <span class="wpf-tlabel"><?php wpforo_phrase( 'likes', 'lower' ) ?></span>
        </div>
        <div class="wpf-post-stat-box">
                    <span class="wpf-tstat">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g id="_01_align_center" data-name="01 align center"><path d="M23.821,11.181v0C22.943,9.261,19.5,3,12,3S1.057,9.261.179,11.181a1.969,1.969,0,0,0,0,1.64C1.057,14.739,4.5,21,12,21s10.943-6.261,11.821-8.181A1.968,1.968,0,0,0,23.821,11.181ZM12,19c-6.307,0-9.25-5.366-10-6.989C2.75,10.366,5.693,5,12,5c6.292,0,9.236,5.343,10,7C21.236,13.657,18.292,19,12,19Z"/><path
                                        d="M12,7a5,5,0,1,0,5,5A5.006,5.006,0,0,0,12,7Zm0,8a3,3,0,1,1,3-3A3,3,0,0,1,12,15Z"/></g></svg>
                        <?php echo wpforo_print_number( $topic['views'] ) ?>
                    </span>
            <span class="wpf-tlabel"><?php wpforo_phrase( 'views' ) ?></span>
        </div>
        <div class="wpf-post-stat-box wpf-pb-more" wpf-tooltip="<?php wpforo_phrase('Topic overview and more...') ?>" wpf-tooltip-size="middle">
            <i class="fas fa-chevron-down" style="font-size: 18px;"></i>
            <span><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="30" height="30"><circle cx="21.517" cy="12.066" r="2.5"/><circle cx="12" cy="12" r="2.5"/><circle cx="2.5" cy="12" r="2.5"/></svg></span>
        </div>
    </div>
    <?php
}

function wpforo_topic_active_participants( $topicid ){
	if( !$users_stats_for_topic = WPF()->post->get_users_stats_for_topic( $topicid ) ) return '';
    $html = '';
	foreach( $users_stats_for_topic as $users_stats ){
		$user = wpforo_member( $users_stats['userid'] );
        if( $user && WPF()->usergroup->can( 'va' ) && wpforo_setting( 'profiles', 'avatars' ) ){
            $html .= sprintf(
                '<div class="wpf-tmi-user-avatar"><a href="%1$s" title="%2$s">%3$s</a><div class="wpf-tmi-user-posts">%4$d</div></div>',
                $user['profile_url'],
                wpforo_user_dname( $user ),
                WPF()->member->avatar( $user, 'alt="' . esc_attr( $user['display_name'] ) . '"', 48 ),
                $users_stats['posts']
            );
        }
    }

    return sprintf(
        '<div class="wpf-tmi wpf-tmi-users"><h3>%1$s</h3><div class="wpf-tmi-users-data">%2$s</div></div>',
        wpforo_phrase( 'Active Participants', false ),
        $html
    );
}

function wpforo_topic_overview( $topicid ){
    if( ! ( $first_postid = wpforo_bigintval( wpforo_topic( $topicid, 'first_postid' ) )) ) return '';
	$chunk_size = apply_filters( 'wpforo_topic_overview_chunk_size', 5 );
    $chunk = wpforo_get_topic_overview_chunk( $topicid, $chunk_size );

    return sprintf(
        '<div class="wpf-tmi wpf-tmi-overview">
            <h3>%1$s</h3>
            <ul class="wpf-topic-overview-tree" data-offset="0" data-chunksize="%2$s" data-nomore="%3$d">%4$s %5$s</ul>
            <div class="wpf-topic-overview-load-more wpf-action">%6$s</div>
        </div>',
        wpforo_phrase( 'Topic Overview', false ),
        $chunk_size,
        intval( $chunk['nomore'] ),
        wpforo_thread_tree_item( $first_postid, 0 ),
        $chunk['html'],
        wpforo_phrase( 'Load more replies...', false )
    );
}

function wpforo_topic_head( $forum, $topic ) {
    if( ! wpforo_setting( 'topics', 'topic_head' ) ) return;
	?>
    <div class="wpforo-topic-head-wrap"
         data-forumid="<?php echo intval( $forum['forumid'] ) ?>"
         data-topicid="<?php echo wpforo_bigintval( $topic['topicid'] ) ?>"
         data-userid="<?php echo wpforo_bigintval( $topic['userid'] ) ?>"
         data-isowner="<?php echo intval( ( wpforo_bigintval( $topic['userid'] ) === WPF()->current_userid ) ) ?>"
    >
        <div class="wpf-post-head-top"><?php wpforo_topic_head_top( $forum, $topic ); ?></div>
        <div class="wpf-topic-more-info" <?php echo ( wpforo_setting( 'topics', 'topic_head_expanded' ) ? 'style="display: block;"' : 'style="display: none;"' ) ?>></div>
        <div class="wpf-post-head-bottom">
            <div class="wpf-left">
                <div class="wpf-manage-link">
                    <?php wpforo_post_buttons( 'icon-text', [ 'solved', 'sticky', 'close', 'private' ], $forum, $topic, wpforo_post( $topic['first_postid'] ) ); ?>
                </div>
            </div>
            <div class="wpf-right">
                <?php do_action( 'wpforo_topic_head_right', $forum, $topic ) ?>
                <?php wpforo_post_buttons( 'icon-text', [ 'tools' ], $forum ); ?>
                <?php wpforo_feed_link(); ?>
            </div>
        </div>
    </div>
	<?php
}

function wpforo_thread_tree($root, $tree, $level = 1) {
    $html = '';
    if(!is_null($tree) && count($tree) > 0) {
        $html .= '<ul>';
        foreach($tree as $child => $parent) {
            if($parent === $root) {
                unset($tree[$child]);
	            $html .= wpforo_thread_tree_item($child, $level);
	            $html .= wpforo_thread_tree($child, $tree, $level+1);
	            $html .= '</li>';
            }
        }
	    $html .= '</ul>';
    }

    return $html;
}

function wpforo_thread_tree_item($postid, $level, $post = array(), $member = array()){
	$level = intval( $level );
	if( $level < 0 ) $level = 0;
    $repeate = $level - 1;
    if( $repeate < 0 ) $repeate = 0;
    if(empty($post)) $post = wpforo_post($postid);
    if(empty($member)) $member = wpforo_member($post['userid']);
    return '<li><div class="wpf-tmi-item">' . ( $level > 0 ? '<span class="wpf-tmi-boxh"> &boxur; &boxh;</span>' : '' )
        . str_repeat('<span class="wpf-tmi-boxh"> &boxhu; &boxh;</span>', $repeate)
        . WPF()->member->avatar( $member, 'alt="'.esc_attr( wpforo_user_dname($member)).'"', 20 )
        . ' ' . wpforo_member_link($member, '', 7, 'wpfto-author', false)
        . ' &nbsp;&nbsp;<span class="wpfto-date">' . wpforo_date($post['created'], 'ago-date', false) . ' &nbsp;&bullet;&nbsp; </span>'
        . '<span class="wpf-link wpf-tmi-item-body-excerpt" data-postid="'. $postid .'">' . wpforo_text($post['body'], 70, false) . '</span>'
        . '</div>';
}

function wpforo_l2_print_avatars( $userids ){
	$ulimit = (int) apply_filters( 'wpforo_item_participant_avatars_count', 7 );
	if( ! $ulimit ) $ulimit = 7;
	if( $ucount = count( $userids ) ){
		foreach( $userids as $k => $userid ){
			if( ! ( $k < $ulimit ) ) break;
			$user = wpforo_member( $userid );
			if( wpforo_setting( 'profiles', 'avatars' ) && WPF()->usergroup->can('va') ){
				echo WPF()->member->avatar( $user, 'alt="'.esc_attr( wpforo_user_dname( $user ) ).'"', 40 );
			}
		}
	}
	if( !$ucount || $ucount > $ulimit ){
		printf(
			'<div class="wpf-sbd-count">%1$s</div>',
			( $ucount ? "+" . ( $ucount - $ulimit ) : wpforo_phrase( 'No Participants', false ) )
		);
	}
}

function wpforo_l2_forum_users( $forumid ){
    wpforo_l2_print_avatars( WPF()->post->get_userids_for_forum( $forumid ) );
}

function wpforo_l2_topic_users( $topicid ){
    wpforo_l2_print_avatars( WPF()->post->get_userids_for_topic( $topicid ) );
}
