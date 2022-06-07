<?php
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 *
 * @layout: QA
 * @url: http://gvectors.com/
 * @version: 1.0.0
 * @author: gVectors Team
 * @description: Q&A Layout turns your forum to a powerful question and answer discussion board.
 *
 */

$cover_styles = wpforo_get_forum_cover_styles( $cat );
?>

<div class="wpfl-3 wpforo-section">
    <div class="wpforo-category" <?php echo $cover_styles['cover'] ?>>
        <div class="cat-title" title="<?php echo esc_attr($cat['description']); ?>">
            <span class="cat-name" <?php echo $cover_styles['title'] ?>><?php echo esc_html($cat['title']); ?></span>
        </div>
        <?php if( WPF()->current_object['template'] === 'forum' ) wpforo_template_add_topic_button($cat['forumid']); ?>
    </div>

    <?php if( WPF()->current_object['template'] === 'forum' ) wpforo_template_topic_portable_form($cat['forumid']); ?>

    <?php foreach($forums as $key =>$forum) :
        if( !WPF()->perm->forum_can( 'vf', $forum['forumid'] ) ) continue;

        $sub_forums = WPF()->forum->get_forums( array( "parentid" => $forum['forumid'], "type" => 'forum' ) );
        $has_sub_forums = is_array($sub_forums) && !empty($sub_forums);

        $topics = WPF()->topic->get_topics( array("forumid" => $forum['forumid'], "orderby" => "type, modified", "order" => "DESC", "row_count" => wpforo_setting( 'forums', 'layout_qa_intro_topics_count' ) ) );
        $has_topics = is_array($topics) && !empty($topics);

        $data = wpforo_forum($forum['forumid'], 'childs');
        $counts = wpforo_forum($forum['forumid'], 'counts');

        $forum_url = wpforo_forum($forum['forumid'],'url');
        $topic_toglle = wpforo_setting( 'forums', 'layout_qa_intro_topics_toggle' );

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
                    <h3 class="wpforo-forum-title"><a href="<?php echo esc_url($forum_url) ?>"><?php echo esc_html($forum['title']); ?></a> <?php wpforo_viewing( $forum ); ?></h3>
                    <p class="wpforo-forum-description"><?php echo $forum['description'] ?></p>

                    <?php if($has_sub_forums) : ?>

                        <div class="wpforo-subforum">
                            <ul>
                                <li class="first"><?php wpforo_phrase('Subforums') ?>:</li>

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

                    <?php if($has_topics) : ?>

                        <div class="wpforo-forum-footer">
                            <span class="wpfcl-5"><?php wpforo_phrase('Recent Questions') ?></span> &nbsp;
                            <i id="img-arrow-<?php echo intval($forum['forumid']) ?>" class="topictoggle fas fa-chevron-<?php echo ( $topic_toglle == 1 ? 'up' : 'down' ); ?>" style="color: rgb(67, 166, 223);font-size: 14px; cursor: pointer;"></i> &nbsp;&nbsp;
                        </div>

                    <?php endif ?>

                </div><!-- wpforo-forum-info -->

                <div class="wpforo-forum-stat">
                    <div class="wpft-row">
                        <div class="wpft-cell-left"><?php wpforo_phrase('Questions') ?></div>
                        <div class="wpft-cell-right"><?php echo wpforo_print_number($counts['topics']) ?></div>
                    </div>
                    <div class="wpft-row">
                        <div class="wpft-cell-left"><?php wpforo_phrase('Answers') ?></div>
                        <div class="wpft-cell-right"><?php echo wpforo_print_number(WPF()->topic->get_sum_answer($data)) ?></div>
                    </div>
                    <div class="wpft-row">
                        <div class="wpft-cell-left"><?php wpforo_phrase('Posts') ?></div>
                        <div class="wpft-cell-right"><?php echo wpforo_print_number($counts['posts']) ?></div>
                    </div>
                </div>
            </div><!-- wpforo-forum -->

            <?php if($has_topics) : ?>

                <div class="wpforo-last-topics-<?php echo intval($forum['forumid']) ?>" style="display: <?php echo ( $topic_toglle ? 'block' : 'none' ); ?>;">
                    <div class="wpforo-last-topics-tab"></div>
                    <div class="wpforo-last-topics-list">
                        <ul>
                            <?php foreach($topics as $topic) : ?>
                                <?php $member = wpforo_member($topic); ?>
                                <?php $topic_url = wpforo_topic($topic['topicid'], 'url'); ?>
                                <?php if($topic_url && !empty($member)): ?>
                                    <li class="<?php wpforo_unread($topic['topicid'], 'topic') ?>">
                                        <div class="wpforo-last-topic wpfcl-2">
                                            <div class="wpf-tbox votes <?php if( $topic['answers'] > 0) echo "wpfcl-4" ?>" wpf-tooltip="<?php wpforo_phrase('Votes') ?>" wpf-tooltip-position="top" wpf-tooltip-size="small">
                                                <div class="wpforo-label">
                                                    <svg class="<?php if( $topic['answers'] > 0) echo "wpfcl-4" ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.873,11.021H5.127a2.126,2.126,0,0,1-1.568-3.56L10.046.872a2.669,2.669,0,0,1,3.939.034l6.431,6.528a2.126,2.126,0,0,1-1.543,3.587ZM5.011,8.837a.115.115,0,0,0,0,.109.111.111,0,0,0,.114.075H18.873a.111.111,0,0,0,.114-.075.109.109,0,0,0-.022-.135L12.528,2.276A.7.7,0,0,0,12,2.021a.664.664,0,0,0-.5.221L5.01,8.838ZM12,24.011a2.667,2.667,0,0,1-1.985-.887L3.584,16.6a2.125,2.125,0,0,1,1.543-3.586H18.873a2.125,2.125,0,0,1,1.568,3.558l-6.487,6.589A2.641,2.641,0,0,1,12,24.011Zm-6.873-9a.125.125,0,0,0-.092.209l6.437,6.534a.7.7,0,0,0,.528.257.665.665,0,0,0,.5-.223l6.493-6.6h0a.112.112,0,0,0,0-.108.111.111,0,0,0-.114-.074Z"/></svg>
                                                </div>
                                                <div class="count"><?php echo intval($topic['votes']) ?></div>
                                            </div>
                                            <div class="wpf-tbox answers <?php if( $topic['answers'] > 0) echo "wpfcl-5" ?>" wpf-tooltip="<?php wpforo_phrase('Answers') ?>" wpf-tooltip-position="top" wpf-tooltip-size="small">
                                                <div class="wpforo-label">
                                                    <svg  class="<?php if( $topic['answers'] > 0) echo "wpfcl-5" ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12.0867962,18 L6,21.8042476 L6,18 L4,18 C2.8954305,18 2,17.1045695 2,16 L2,4 C2,2.8954305 2.8954305,2 4,2 L20,2 C21.1045695,2 22,2.8954305 22,4 L22,16 C22,17.1045695 21.1045695,18 20,18 L12.0867962,18 Z M8,18.1957524 L11.5132038,16 L20,16 L20,4 L4,4 L4,16 L8,16 L8,18.1957524 Z M11,10.5857864 L15.2928932,6.29289322 L16.7071068,7.70710678 L11,13.4142136 L7.29289322,9.70710678 L8.70710678,8.29289322 L11,10.5857864 Z" fill-rule="evenodd"/></svg>
                                                </div>
                                                <div class="count">
                                                    <?php echo intval($topic['answers']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="wpforo-last-topic-title">
                                            <?php wpforo_topic_title($topic, $topic_url, '{p}{au}{tc}{/a}{n}', true, '', wpforo_setting( 'forums', 'layout_qa_intro_topics_length' )) ?>
                                            <div class="wpforo-topic-author">
                                                <?php if( WPF()->usergroup->can('va') && wpforo_setting( 'profiles', 'avatars' ) ): ?>
                                                    <?php echo WPF()->member->avatar($member, 'alt="'.esc_attr($member['display_name']).'"', 40) ?>
                                                <?php endif; ?>
                                                <span class="wpforo-last-topic-info wpfcl-2">
                                                    <?php wpforo_member_link($member, 'by'); ?>, <?php wpforo_date($topic['modified']); ?>
                                                </span>
                                            </div>
                                            <div class="wpforo-last-topic-bottom">
                                                <span class="wpforo-last-topic-replies wpfcl-2">
                                                    <i class="fas fa-reply fa-rotate-180"></i> <?php wpforo_phrase('replies', true, 'lower') ?> <?php echo intval($topic['posts']) ?>
                                                </span>
                                                <?php wpforo_tags($topic, true, 'text') ?>
                                            </div>
                                        </div>
                                        <div class="wpforo-last-topic-status wpfcl-2 <?php if($topic['type']) echo "wpfbr-l-10" ?> <?php if( $topic['solved'] && !$topic['type']) echo "wpfbr-l-8" ?>">
                                            <?php echo str_replace( 'fa-check-circle', 'fas fa-square-check', wpforo_topic_icon($topic, 'all', true, false)) ?>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if( intval($forum['topics']) > wpforo_setting( 'forums', 'layout_qa_intro_topics_count' ) ): ?>
                                <li>
                                    <div class="wpforo-last-topic-title wpf-vat">
                                        <a href="<?php echo esc_url($forum_url) ?>"><?php wpforo_phrase('view all questions', true, 'lower');  ?> <i class="fas fa-angle-right" aria-hidden="true"></i></a>
                                    </div>
                                </li>
                            <?php endif ?>
                        </ul>
                    </div><!-- wpforo-last-topics-list -->
                    <br class="wpf-clear" />
                </div><!-- wpforo-last-topics -->

            <?php endif; ?>

        </div><!-- forum-wrap -->

        <?php do_action( 'wpforo_loop_hook', $key ) ?>

    <?php endforeach; ?> <!-- $forums as $forum -->
</div>
