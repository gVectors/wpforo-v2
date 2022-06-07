<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
?>

<div class="wpfl-4 wpforo-section">
    <div class="wpf-threads">
        <div class="wpf-threads-head">
            <div class="wpf-head-box wpf-thead-status"><?php wpforo_phrase( 'Status' ) ?></div>
            <div class="wpf-head-box wpf-thead-author"><?php wpforo_phrase( 'Author' ) ?></div>
            <div class="wpf-head-box wpf-thead-title"><?php wpforo_phrase( 'Topics' ) ?></div>
            <div class="wpf-head-box wpf-thead-forum"><?php wpforo_phrase( 'Forum' ) ?></div>
            <div class="wpf-head-box wpf-thead-posts"><?php wpforo_phrase( 'Replies' ) ?></div>
            <div class="wpf-head-box wpf-thead-views"><?php wpforo_phrase( 'Views' ) ?></div>
            <div class="wpf-head-box wpf-thead-last-reply"><?php wpforo_phrase( 'Last post' ) ?>&nbsp;</div>
        </div>
        <div class="wpf-thread-list" data-forumid="<?php echo intval($forum['forumid']) ?>" data-filter="newest" data-paged="1">
            <?php foreach( $topics as $key => $topic ): ?>
                <?php wpforo_thread_topic_template( $topic['topicid'] ); ?>
                <?php do_action( 'wpforo_loop_hook', $key ) ?>
            <?php endforeach; ?>
        </div>
    </div>
</div> <!-- wpfl-4 -->
