<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

$user = WPF()->current_object['user'];
$posts = (array) wpfval( WPF()->current_object, 'favored_posts' );
?>

<div class="wpf-activities">
    <?php
    wpforo_template_profile_board_panel();
    wpforo_template_profile_favored_panel();
    if( $posts ) : ?>
			<?php foreach( $posts as $post ) : ?>
                <?php $forum = wpforo_forum( $post['forumid']); ?>
                <?php $topic = wpforo_topic( $post['topicid']); ?>

                <div class="wpf-activity wpfa-reply">
                    <div class="wpf-activity-icon">
                        <?php
                        switch( wpfval( WPF()->current_object, 'filter' ) ){
                            case 'likes':
                                printf(
                                    '<div class="wpf-activity-tlabel">%1$s</div>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g id="_01_align_center" data-name="01 align center"><path d="M15.021,7l.336-2.041a3.044,3.044,0,0,0-4.208-3.287A3.139,3.139,0,0,0,9.582,3.225L7.717,7H3a3,3,0,0,0-3,3v9a3,3,0,0,0,3,3H22.018L24,10.963,24.016,7ZM2,19V10A1,1,0,0,1,3,9H7V20H3A1,1,0,0,1,2,19Zm20-8.3L20.33,20H9V8.909l2.419-4.9A1.07,1.07,0,0,1,13.141,3.8a1.024,1.024,0,0,1,.233.84L12.655,9H22Z"></path></g></svg>',
                                    wpforo_phrase( 'Like', false )
                                );
                            break;
                            case 'dislikes':
	                            printf(
		                            '<div class="wpf-activity-tlabel">%1$s</div>
                                    <svg style="transform: rotate(180deg); vertical-align: bottom;" viewBox="0 0 24 24"><g id="_01_align_center" data-name="01 align center"><path d="M15.021,7l.336-2.041a3.044,3.044,0,0,0-4.208-3.287A3.139,3.139,0,0,0,9.582,3.225L7.717,7H3a3,3,0,0,0-3,3v9a3,3,0,0,0,3,3H22.018L24,10.963,24.016,7ZM2,19V10A1,1,0,0,1,3,9H7V20H3A1,1,0,0,1,2,19Zm20-8.3L20.33,20H9V8.909l2.419-4.9A1.07,1.07,0,0,1,13.141,3.8a1.024,1.024,0,0,1,.233.84L12.655,9H22Z"></path></g></svg>',
		                            wpforo_phrase( 'Dislike', false )
	                            );
                            break;
                            default:
	                            printf(
		                            '<div class="wpf-activity-tlabel">%1$s</div>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40"><path d="M20.137,24a2.8,2.8,0,0,1-1.987-.835L12,17.051,5.85,23.169a2.8,2.8,0,0,1-3.095.609A2.8,2.8,0,0,1,1,21.154V5A5,5,0,0,1,6,0H18a5,5,0,0,1,5,5V21.154a2.8,2.8,0,0,1-1.751,2.624A2.867,2.867,0,0,1,20.137,24ZM6,2A3,3,0,0,0,3,5V21.154a.843.843,0,0,0,1.437.6h0L11.3,14.933a1,1,0,0,1,1.41,0l6.855,6.819a.843.843,0,0,0,1.437-.6V5a3,3,0,0,0-3-3Z"/></svg>',
		                            wpforo_phrase( 'Bookmark', false )
	                            );
                            break;
                        }
                        ?>
                    </div>
                    <div class="wpf-activity-data">
                        <div class="wpf-activity-top">
                            <div class="wpf-activity-title">
                                <a href="<?php echo esc_url_raw( WPF()->post->get_post_url( $post['postid'] ) ) ?>">
                                    <?php echo $post['title'] ?>
                                </a>
                                <p><?php echo wpforo_text( $post['body'], 150, false ) ?></p>
                            </div>
                            <div class="wpf-activity-date">
                                <?php wpforo_date($post['created']); ?>
                            </div>
                        </div>
                        <div class="wpf-activity-bottom">
                            <div class="wpf-activity-flabel"><?php wpforo_phrase('Forum') ?></div>
                            <div class="wpf-activity-forum">
                                <?php $forum_icon = ( isset($forum['icon']) && $forum['icon']) ? $forum['icon'] : 'fas fa-comments'; ?>
                                <i class="<?php echo esc_attr($forum_icon) ?>" style="color: <?php echo esc_attr($forum['color']) ?>"></i>
                                <a href="<?php echo esc_url_raw($forum['url']) ?>"><?php echo $forum['title'] ?></a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach ?>
        <div class="activity-foot">
			<?php wpforo_template_pagenavi() ?>
        </div>
	<?php else : ?>
        <p class="wpf-p-error"> <?php wpforo_phrase( 'No posts found for this member.' ) ?> </p>
	<?php endif ?>
</div>
