<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

function wpforo_thread_forum_template( $topicid ){
    $thread = wpforo_thread( $topicid );
    if(empty($thread)) return;
    ?>
    <div class="wpf-thread <?php wpforo_unread($topicid, 'topic'); ?>">
        <div class="wpf-thread-body">
            <div class="wpf-thread-box wpf-thread-status">
                <div class="wpf-thread-statuses" <?php echo $thread['wrap']; ?>><?php echo $thread['status_html']; ?></div>
            </div>
            <div class="wpf-thread-box wpf-thread-author">
                <?php echo $thread['author_html']; ?>
            </div>
            <div class="wpf-thread-box wpf-thread-title">
                <div class="wpf-thread-head">
                    <span class="wpf-thread-status-mobile"><?php wpforo_topic_icon($thread); ?> </span>
                    <?php wpforo_topic_title($thread, $thread['url'], '{p}{au}{tc}{/a}{n}{v}', true, '', wpforo_setting( 'forums', 'layout_threaded_intro_topics_length' )) ?>
                </div>
                <div class="wpf-thread-author-name">
                    <span><?php wpforo_phrase('by') ?></span> <?php wpforo_member_link( $thread['user'] ) ?>, <?php wpforo_date($thread['created'],'ago-date'); ?> <?php wpforo_tags($thread, true, 'text') ?>
                </div>
                <div class="wpf-thread-forum-mobile"><i class="<?php echo $thread['forum']['icon'] ?>" style="color: <?php echo $thread['forum']['color'] ?>"></i>&nbsp; <?php echo esc_attr($thread['forum']['title'])?></div>
            </div>
            <div class="wpf-thread-box wpf-thread-forum" style="border-left: 2px solid <?php echo $thread['forum']['color'] ?>">
                <span class="wpf-circle wpf-m" wpf-tooltip="<?php echo esc_attr($thread['forum']['title'])?>" wpf-tooltip-position="left" wpf-tooltip-size="long"><i class="<?php echo $thread['forum']['icon'] ?>" style="color: <?php echo $thread['forum']['color'] ?>"></i></span>
            </div>
            <div class="wpf-thread-box wpf-thread-posts">
                <span><?php echo wpforo_print_number((intval($thread['posts']) - 1)) ?></span>
            </div>
            <div class="wpf-thread-box wpf-thread-views">
                <span><?php echo wpforo_print_number($thread['views']) ?></span>
            </div>
            <div class="wpf-thread-box wpf-thread-last-reply">
                <div class="wpf-thread-last-info">
                    <div class="wpf-thread-last-user">
                        <div class="wpf-thread-last-avatar"><?php echo $thread['reply_html'] ?></div>
                        <?php wpforo_member_link( $thread['last_user'], 'by', 14) ?>
                    </div>
                    <div class="wpf-thread-last-date"><?php wpforo_date($thread['modified']) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
