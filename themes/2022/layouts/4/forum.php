<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

/**
*
* @layout: Threaded
* @url: http://gvectors.com/
* @version: 1.0.0
* @author: gVectors Team
* @description: Threaded layout turns your forum to a threads list accented on discussion tree view.
*
*/
?>

<?php
$items_count = 0;
$childs = WPF()->forum->get_childs( $cat['forumid'] );
$childs[] = $cat['forumid'];
$args = array( 'row_count' => wpforo_setting( 'forums', 'layout_threaded_intro_topics_count' ), 'forumids' => $childs, 'orderby' => 'type, modified', 'order' => 'DESC' );
$topics = WPF()->topic->get_topics( $args, $items_count );
$load_more = $items_count >= wpforo_setting( 'forums', 'layout_threaded_intro_topics_count' );
if( ! wpforo_setting( 'forums', 'layout_threaded_display_subforums' ) ){
	$args = array('parentid' => $cat['forumid']);
    $childs = WPF()->forum->get_child_forums($cat['forumid']);
}
$childs = apply_filters('wpforo_forum_list_threaded_layout', $childs);

$cover_styles = wpforo_get_forum_cover_styles( $cat );
?>

<div id="wpf-cat-<?php echo $cat['forumid'] ?>" class="wpfl-4 wpforo-section <?php if(wpfval(WPF()->current_object, 'forumid')) echo 'wpf-category-page' ?>">
    <div class="wpforo-category" <?php echo $cover_styles['cover'] ?>>
        <div class="cat-title" title="<?php echo esc_attr($cat['description']); ?>" <?php echo $cover_styles['title'] ?>>
            <span class="cat-icon">
                <i class="<?php echo $cat['icon'] ?>"></i>
            </span>
            <span class="cat-name">
                <?php echo esc_html($cat['title']); ?>
            </span>
        </div>
        <div id="wpf-buttons-<?php echo $cat['forumid'] ?>" class="wpf-head-bar-left wpf-load-threads">
            <span class="wpf-forums" <?php echo $cover_styles['button'] ?>><i class="fas <?php echo ( wpforo_setting( 'forums', 'layout_threaded_intro_topics_toggle' ) ? 'fa-chevron-up' : 'fa-chevron-down' ) ?>"></i></span>
        </div>
        <?php wpforo_template_add_topic_button($cat['forumid']); ?>
    </div>

    <?php wpforo_template_topic_portable_form($cat['forumid']); ?>

    <div id="wpf-forums-<?php echo $cat['forumid'] ?>" class="wpf-cat-forums" style="display: <?php echo ( wpforo_setting( 'forums', 'layout_threaded_intro_topics_toggle' ) ? 'block' : 'none') ?>;">
        <div class="wpf-cat-forum-list">
            <?php if(!empty($childs)): ?>
                <?php foreach($childs as $child) : ?>
                    <?php if( $child == $cat['forumid'] || !WPF()->perm->forum_can('vf', $child) ) continue; $forum = wpforo_forum( $child ); ?>
                    <div class="wpf-forum-item <?php wpforo_unread($child, 'forum'); ?>">
                        <span class="wpf-circle wpf-s" style="color: <?php echo $forum['color'] ?>; display: inline-flex;">
                            <i class="<?php echo $forum['icon'] ?>"></i>
                        </span>
                        <?php $forum_description = (wpfval($forum, 'description')) ? 'wpf-tooltip="' . esc_attr(strip_tags($forum['description'])) . '"  wpf-tooltip-size="long"' : ''; ?>
                        <a href="<?php echo esc_url($forum['url']); ?>" <?php echo $forum_description ?>>
                            <?php echo esc_html($forum['title']); ?>
                        </a>
                        <span class="wpf-forum-item-stat">&nbsp;<?php echo '<span wpf-tooltip="' . esc_attr(wpforo_phrase('Threads', false)) . '">' . wpforo_print_number($forum['topics']) . '</span> <sep>/</sep> <span wpf-tooltip="' . esc_attr(wpforo_phrase('Posts', false)) . '">' . wpforo_print_number($forum['posts']) . '</span>' ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php wpforo_phrase('No forum found in this category') ?></p>
            <?php endif; ?>
        </div>
    </div>
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
        <div class="wpf-thread-list" data-forumid="<?php echo intval($cat['forumid']) ?>" data-filter="newest" data-paged="1">
            <?php foreach( $topics as $key => $topic ): ?>
                <?php wpforo_thread_forum_template( $topic['topicid'] ); ?>
                <?php do_action( 'wpforo_loop_hook', $key ) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="wpf-more-topics" style="text-align: center; margin-bottom: 17px; font-size: 13px; <?php echo ( !$load_more ? 'display: none;' : '' ) ?>">
        <a>
            <i class="fas fa-angle-double-down" style="font-size: 12px; padding: 0 5px;"></i>
            <?php wpforo_phrase('Load More Topics') ?>
        </a>
    </div>
</div>
<!-- wpfl-4 -->
