<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
?>

<div class="wpfl-3 wpforo-section">
    <div class="wpforo-post-head">
        <?php wpforo_topic_head($forum, $topic); ?>
    </div>
    <?php wpforo_moderation_tools(); ?>
    <?php wpforo_check_threads($posts); ?>

	<?php foreach($posts as $key => $post ) : $is_topic = !$key; ?>

		<div class="wpforo-qa-item-wrap">
            <?php if($post['parentid'] == 0): ?>
                <?php $member = wpforo_member($post); $post_url = wpforo_post($post['postid'],'url'); ?>
                <div id="post-<?php echo wpforo_bigintval($post['postid']) ?>" data-postid="<?php echo wpforo_bigintval($post['postid']) ?>" data-userid="<?php echo wpforo_bigintval($member['userid']) ?>" data-mention="<?php echo esc_attr( ( wpforo_setting( 'profiles', 'mention_nicknames' ) ? $member['user_nicename'] : '') ) ?>" data-isowner="<?php echo esc_attr( (int) (bool) wpforo_is_owner($member['userid']) ) ?>" class="post-wrap wpfn-<?php echo ($key+1); ?><?php if( !$post['is_first_post'] ) echo ' wpf-answer-wrap'; else echo ' wpfp-first'; ?>" <?php echo ($key == 1) ? ' style="border-top:none;" ' : ''; ?>>
                    <?php wpforo_share_toggle($post_url, $post['body']); ?>
                    <div class="wpforo-post wpfcl-1">
                        <div class="wpf-left">
                            <div class="wpforo-post-voting">
                                <div class="wpf-positive">
                                    <?php wpforo_post_buttons( 'icon-text', 'positivevote', $forum, $topic, $post ); ?>
                                </div>
                                    <div class="wpf-vote-number">
                                        <?php WPF()->tpl->vote_count($post) ?>
                                    </div>
                                <div class="wpf-negative">
                                    <?php wpforo_post_buttons( 'icon-text', 'negativevote', $forum, $topic, $post ); ?>
                                </div>
                                <?php wpforo_post_buttons( 'icon-text', 'isanswer', $forum, $topic, $post ); ?>
                            </div>
                        </div><!-- left -->
                        <div class="wpf-right">
                            <div class="wpforo-post-content-top">
                                <?php if($post['status']): ?>
                                    <span class="wpf-mod-message">
                                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php wpforo_phrase('Awaiting moderation') ?></span>
                                <?php endif; ?>
                                <?php wpforo_share_toggle($post_url, $post['body'], 'top'); ?>
                                <div class="wpforo-post-link wpf-post-link">
                                    <?php wpforo_post_buttons( 'icon', ['bookmark', 'report', 'link'], $forum, $topic, $post ); ?>
                                </div>
                                <div class="wpforo-post-date"><?php wpforo_date($post['created'], 'd/m/Y g:i a'); ?></div>
	                            <?php wpforo_topic_starter($topic, $post) ?>
                                <div class="wpf-clear-right"></div>
                            </div>
                            <div class="wpforo-post-author">
                                <?php if(  WPF()->usergroup->can('va') && wpforo_setting( 'profiles', 'avatars' ) ) : ?>
                                    <div class="wpforo-post-avatar"><?php echo WPF()->member->avatar($member, 'alt="'.esc_attr($member['display_name']).'"', 96) ?></div>
                                <?php endif; ?>
                                <div class="wpforo-post-author-details">
                                    <div class="wpforo-post-author-name">
                                        <?php wpforo_member_link($member, '', 0, 'wpf-pa-name'); ?>&nbsp;<span class="wpf-pa-online"><?php WPF()->member->show_online_indicator($member['userid']) ?></span>
                                        <?php wpforo_member_nicename($member, '@'); ?>
                                    </div>
                                    <div class="wpforo-post-author-data">
                                        <div>
                                            <?php wpforo_member_title($member, true,'', '', ['rating-title', 'custom-title']) ?>
                                        </div>
                                        <div>
                                            <?php wpforo_member_title($member, true,'', '', ['usergroup']) ?>
                                            <?php wpforo_member_badge($member) ?>
                                        </div>
                                    </div>
                                    <div class="wpforo-post-author-stat">
                                        <span class="author-posts"><?php echo intval($member['posts']) ?> <?php wpforo_phrase('Posts') ?></span><br />
                                        <span class="author-stat-item"><i class="fas fa-question-circle wpfcl-6" title="<?php wpforo_phrase('Questions') ?>"></i><?php echo intval($member['questions']) ?></span>
                                        <span class="author-stat-item"><i class="fas fa-check-square wpfcl-5" title="<?php wpforo_phrase('Answers') ?>"></i><?php echo intval($member['answers']) ?></span>
                                        <span class="author-stat-item"><i class="fas fa-comment wpfcl-0" title="<?php wpforo_phrase('Comments') ?>"></i><?php echo intval($member['comments']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="wpforo-post-content">
                                <?php wpforo_content($post); ?>
                                <?php wpforo_post_edited($post); ?>
                                <?php do_action( 'wpforo_tpl_post_loop_after_content', $post, $member ) ?>
                                <?php if( wpforo_setting( 'profiles', 'signature' ) ): ?>
                                    <?php if( trim($member['signature'])): ?><div class="wpforo-post-signature"><?php wpforo_signature( $member ) ?></div><?php endif ?>
                                <?php endif; ?>
                            </div>
                            <div class="wpforo-post-author">
                                <div class="wpforo-post-lb-box">
                                    <?php
                                        if( $post['is_first_post'] ){
                                            $answer_button = wpforo_setting( 'posting', 'qa_display_answer_editor' ) ? 'style="display:none;"' : '';
                                            echo '<div class="wpf-answer-button" ' . $answer_button . '>';
                                            wpforo_post_buttons( 'icon-text', 'answer', $forum, $topic, $post );
                                            echo '</div>';
                                        }
                                        echo '<div class="wpf-add-comment-button" style="display:none;">';
                                        wpforo_post_buttons( 'icon-text', 'comment', $forum, $topic, $post );
                                        echo '</div>';
                                    ?>
                                </div>
                            </div><!-- wpforo-post-author -->
                            <div class="wpforo-post-tool-bar">
                                <?php
                                $buttons = [ 'edit', 'approved', 'delete' ];
                                wpforo_post_buttons( 'icon-text', $buttons, $forum, $topic, $post );
                                ?>
                            </div>
                        </div><!-- right -->
                        <div class="wpf-clear"></div>
                    </div><!-- wpforo-post -->
                </div><!-- post-wrap -->
                <?php
                $comment_count = 0;
                $row_count = ( wpforo_setting( 'topics', 'layout_qa_comments_limit_count' ) ?: null );
	            $args = array(
		            'root'      => $post['postid'],
		            'row_count' => $row_count
	            );
                $comments = WPF()->post->get_posts( $args, $comment_count );
                if(is_array($comments) && !empty($comments)): ?>
                    <div class="wpforo-qa-comments">
                        <?php foreach($comments as $comment) wpforo_qa_comment_template($comment, $forum, $topic); ?>
                    </div>
                <?php endif; ?>

                <div class="wpforo-comment">
                    <div class="wpf-left"></div>
                    <div class="wpf-right" style="background: none;">
                        <div class="wpforo-qa-comments-footer">
                            <div class="wpforo-qa-comment-loadrest">
					            <?php if( $comment_count > ($count_comments = count($comments)) ) : ?>
                                    <a class="wpforo-qa-show-rest-comments" title="<?php wpforo_phrase('expand to show all comments on this post') ?>">
							            <?php printf( wpforo_phrase('show %d more comments', false), ($comment_count - $count_comments ) ) ?>
                                    </a>
					            <?php endif; ?>
                            </div>
                            <div class="wpf-add-comment-button">
					            <?php wpforo_post_buttons( 'icon-text', 'comment', $forum, $topic, $post ); ?>
                            </div>
                        </div>
                    </div>
                    <div class="wpf-clear"></div>
                </div>

                <div class="wpforo-comment">
                    <div class="wpf-left"></div>
                    <div class="wpf-right" style="background: none;">
                        <div class="wpforo-portable-form-wrap"></div>
                    </div>
                    <div class="wpf-clear"></div>
                </div>

                <?php if( $post['is_first_post'] ): ?>
                    <div class="wpforo-topic-meta">
                        <?php wpforo_tags( $topic ); ?>
                    </div>
                    <?php if($topic['answers']): ?>
                        <div class="wpf-answer-sep">
                            <div class="wpf-answer-title">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12.0867962,18 L6,21.8042476 L6,18 L4,18 C2.8954305,18 2,17.1045695 2,16 L2,4 C2,2.8954305 2.8954305,2 4,2 L20,2 C21.1045695,2 22,2.8954305 22,4 L22,16 C22,17.1045695 21.1045695,18 20,18 L12.0867962,18 Z M8,18.1957524 L11.5132038,16 L20,16 L20,4 L4,4 L4,16 L8,16 L8,18.1957524 Z M11,10.5857864 L15.2928932,6.29289322 L16.7071068,7.70710678 L11,13.4142136 L7.29289322,9.70710678 L8.70710678,8.29289322 L11,10.5857864 Z" fill-rule="evenodd"/></svg>
                                <?php ( $topic['answers'] > 1 ) ? printf( wpforo_phrase('%d Answers', false), $topic['answers'] ) : printf( wpforo_phrase('%d Answer', false), $topic['answers'] ) ; ?>
                            </div>
                            <div class="wpf-answer-filter">
                                <?php wpforo_posts_ordering_dropdown() ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php do_action( 'wpforo_loop_hook', $key ) ?>
            <?php endif; ?>
        </div>
   <?php endforeach; ?>

    <?php if( $topic['answers'] && ! wpforo_setting( 'posting', 'qa_display_answer_editor' ) ): ?>
    <div class="wpf-bottom-bar">
        <div class="wpf-answer-button">
            <?php wpforo_post_buttons( 'icon-text', 'answer', $forum, $topic ); ?>
        </div>
    </div>
    <?php endif; ?>
</div><!-- wpfl-3 -->
