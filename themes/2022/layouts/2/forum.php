<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

/**
*
* @layout: Simplified
* @url: http://gvectors.com/
* @version: 1.0.0
* @author: gVectors Team
* @description: Simplified layout looks simple and clean.
*
*/

$cover_styles = wpforo_get_forum_cover_styles( $cat );
?>

<div class="wpfl-2 wpforo-section">
	<div class="wpforo-category" <?php echo $cover_styles['cover'] ?>>
	    <div class="cat-title" title="<?php echo esc_attr($cat['description']); ?>">
            <span class="cat-name" <?php echo $cover_styles['title'] ?>><?php echo esc_html($cat['title']); ?></span>
        </div>
	    <?php if( WPF()->current_object['template'] === 'forum' ) wpforo_template_add_topic_button($cat['forumid']); ?>
	</div><!-- wpforo-category -->

    <?php if( WPF()->current_object['template'] === 'forum' ) wpforo_template_topic_portable_form($cat['forumid']); ?>

  	<?php foreach($forums as $key => $forum) :
  		if( !WPF()->perm->forum_can( 'vf', $forum['forumid'] ) ) continue;
        if( !empty($forum['icon']) ){
            $forum['icon'] = trim($forum['icon']);
            if( strpos($forum['icon'], ' ') === false ) $forum['icon'] = 'fas ' . $forum['icon'];
        }
		$forum_icon = ( isset($forum['icon']) && $forum['icon']) ? $forum['icon'] : 'fas fa-comments';
		?>

	  	<div class="forum-wrap <?php wpforo_unread($forum['forumid'], 'forum') ?>">
	      	<div class="wpforo-forum">
		        <div class="wpforo-forum-icon" style="<?php echo ( !is_rtl() ) ? 'border-left: 3px solid' : 'border-right: 3px solid'; ?> <?php echo esc_attr($forum['color']) ?>"><i class="<?php echo esc_attr($forum_icon) ?> wpfcl-0"></i></div>
		        <div class="wpforo-forum-info">
		        	<h3 class="wpforo-forum-title"><a href="<?php echo esc_url( wpforo_forum($forum['forumid'],'url') ) ?>"><?php echo esc_html($forum['title']); ?></a> <?php wpforo_viewing( $forum ); ?></h3>
		        	<p class="wpforo-forum-description"><?php echo $forum['description'] ?></p>
					<?php $sub_forums = WPF()->forum->get_forums( array( "parentid" => $forum['forumid'], "type" => 'forum' ) ); ?>
		            <?php if(is_array($sub_forums) && !empty($sub_forums)) : ?>

			            <div class="wpforo-subforum">
			                <ul>
			                    <li class="first wpfcl-0"><?php wpforo_phrase('Subforums'); ?>: </li>

								<?php foreach($sub_forums as $sub_forum) :
									if( !WPF()->perm->forum_can( 'vf', $sub_forum['forumid'] ) ) continue;
                                    if( !empty($sub_forum['icon']) ){
                                        $sub_forum['icon'] = trim($sub_forum['icon']);
                                        if( strpos($sub_forum['icon'], ' ') === false ) $sub_forum['icon'] = 'fas ' . $sub_forum['icon'];
                                    }
									$sub_forum_icon = ( isset($sub_forum['icon']) && $sub_forum['icon']) ? $sub_forum['icon'] : 'fas fa-comments'; ?>

			                      	<li class="<?php wpforo_unread($sub_forum['forumid'], 'forum') ?>"><i class="<?php echo esc_attr($sub_forum_icon) ?> wpfcl-0"></i>&nbsp;<a href="<?php echo esc_url( wpforo_forum($sub_forum['forumid'],'url') ) ?>"><?php echo esc_html($sub_forum['title']); ?></a> <?php wpforo_viewing( $sub_forum ); ?></li>

		                     	<?php endforeach; ?>

			                </ul>
			                <br class="wpf-clear" />
			            </div><!-- wpforo-subforum -->

					<?php endif; ?>

		    	</div><!-- wpforo-forum-info -->

                <div class="wpforo-forum-data">
                    <?php if($forum['last_postid']) : ?>
                        <div class="wpforo-forum-details">
                            <div class="wpf-stat-box">
                                <div class="wpf-sbl"><?php wpforo_phrase('Topics') ?></div>
                                <div class="wpf-sbd"><?php echo wpforo_print_number($forum['topics']) ?></div>
                            </div>
                            <div class="wpf-stat-box">
                                <div class="wpf-sbl"><?php wpforo_phrase('Posts'); ?></div>
                                <div class="wpf-sbd"><?php echo wpforo_print_number($forum['posts']) ?></div>
                            </div>
                            <div class="wpf-stat-box">
                                <div class="wpf-sbl"><?php wpforo_phrase('Forum Participants'); ?></div>
                                <div class="wpf-sbd wpf-sbd-avatar"><?php wpforo_l2_forum_users( $forum['forumid'] ) ?></div>
                            </div>
                        </div>
                        <div class="wpforo-last-post-info">
                            <?php $last_post = wpforo_post($forum['last_postid']); ?>
                            <?php $last_post_topic = wpforo_topic($last_post['topicid']) ?>
                            <?php $member = wpforo_member($last_post) ?>
                            <?php if( WPF()->usergroup->can('va') && wpforo_setting( 'profiles', 'avatars' ) ): ?>
                                <div class="wpforo-last-post-avatar">
                                    <?php if( isset($member['profile_url']) && $member['profile_url'] ): ?>
                                        <a href="<?php echo esc_url($member['profile_url']) ?>">
                                            <?php echo WPF()->member->get_avatar($forum['last_userid'], 'alt="'.esc_attr($member['display_name']).'" title="'.esc_attr($member['display_name']).'"', 38) ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo WPF()->member->avatar($member, 'alt="'.esc_attr($member['display_name']).'" title="'.esc_attr($member['display_name']).'"', 38) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="wpforo-last-post">
                                <p class="wpforo-last-post-title">
                                    <?php wpforo_topic_title($last_post_topic, $last_post['url'], '{p}{au}{tc}{/a}{n}', true, '', 100) ?>
                                </p>
                                <p class="wpforo-last-post-author"><?php wpforo_member_link($member, 'by'); ?>, <?php wpforo_date($forum['last_post_date']) ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="wpforo-last-post">
                            <p class="wpforo-last-post-title"><?php wpforo_phrase('Forum is empty'); ?></p>
                        </div>
                    <?php endif ?>
                </div>

	      	</div><!-- wpforo-forum -->
	  	</div><!-- forum-wrap -->

      	<?php do_action( 'wpforo_loop_hook', $key ) ?>

    <?php endforeach; ?> <!-- $forums as $forum -->
</div><!-- wpfl-2 -->

