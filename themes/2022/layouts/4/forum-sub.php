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
$childs = apply_filters('wpforo_sub_forum_list_threaded_layout', $childs);
?>

<div id="wpf-cat-<?php echo $cat['forumid'] ?>" class="wpf-subforums wpfl-4 wpforo-section">
    <div class="wpforo-category"></div>
    <div id="wpf-forums-<?php echo $cat['forumid'] ?>" class="wpf-cat-forums">
        <div class="wpf-cat-forum-list">
            <?php if(!empty($childs)): ?>
                <?php foreach($childs as $child): ?>
                    <?php if($child == $cat['forumid'] || !WPF()->perm->forum_can('vf', $child) ) continue; $forum = wpforo_forum( $child ); ?>
                    <div class="wpf-forum-item  <?php wpforo_unread($child, 'forum'); ?>">
                        <span class="wpf-circle wpf-s" style="color: <?php echo $forum['color'] ?>; display: inline-flex; margin-right: 8px;">
                            <i class="<?php echo $forum['icon'] ?>"></i>
                        </span>
                        <?php $forum_description = (wpfval($forum, 'description')) ? 'wpf-tooltip="' . esc_attr(strip_tags($forum['description'])) . '"  wpf-tooltip-size="long"' : ''; ?>
                        <a href="<?php echo esc_url($forum['url']); ?>" <?php echo $forum_description ?>>
                            <?php echo esc_html($forum['title']); ?>
                            <span class="wpf-forum-item-stat">&nbsp;<?php echo '<span wpf-tooltip="' . esc_attr(wpforo_phrase('Threads', false)) . '">' . wpforo_print_number($forum['topics']) . '</span> <sep>/</sep> <span wpf-tooltip="' . esc_attr(wpforo_phrase('Posts', false)) . '">' . wpforo_print_number($forum['posts']) . '</span>' ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p><?php wpforo_phrase('No forum found in this category') ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- wpfl-4 -->
