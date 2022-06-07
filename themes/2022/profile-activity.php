<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

$user = WPF()->current_object['user'];
$activities = (array) wpfval( WPF()->current_object, 'activities' );
?>

<div class="wpf-activities">
    <?php
        wpforo_template_profile_board_panel();
        wpforo_template_profile_activity_panel();
    ?>

	<?php if( $activities ) : ?>
			<?php foreach( $activities as $activity ) : ?>
                <?php $forum = wpforo_forum( $activity['forumid']); ?>
                <?php $topic = wpforo_topic( $activity['topicid']); ?>
                <?php if( $activity['is_first_post'] ) : ?>
                    <div class="wpf-activity wpfa-topic">
                        <div class="wpf-activity-icon">
                            <div class="wpf-activity-tlabel"><?php wpforo_phrase('Topic') ?></div>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M1,6H23a1,1,0,0,0,0-2H1A1,1,0,0,0,1,6Z"/><path d="M1,11H15a1,1,0,0,0,0-2H1a1,1,0,0,0,0,2Z"/><path d="M15,19H1a1,1,0,0,0,0,2H15a1,1,0,0,0,0-2Z"/><path d="M23,14H1a1,1,0,0,0,0,2H23a1,1,0,0,0,0-2Z"/></svg>
                            <?php wpforo_topic_icon( $activity['topicid'], 'mixed' ); ?>
                        </div>
                        <div class="wpf-activity-data">
                            <div class="wpf-activity-top">
                                <div class="wpf-activity-title">
                                    <a href="<?php echo esc_url_raw( WPF()->topic->get_topic_url( $activity['topicid'] )) ?>">
                                        <?php echo $activity['title'] ?>
                                    </a>
                                </div>
                                <div class="wpf-activity-date">
                                    <?php wpforo_date($activity['created']); ?>
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
                            <div class="wpf-activity-stat">
                                <div>
                                    <?php wpforo_phrase('Replies:'); ?>
                                    <?php echo $topic['posts'] ?>
                                </div>
                                <div>
                                    <?php wpforo_phrase('Views:'); ?>
                                    <?php echo $topic['views'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="wpf-activity wpfa-reply">
                        <div class="wpf-activity-icon">
                            <div class="wpf-activity-tlabel"><?php wpforo_phrase('Reply') ?></div>
                            <svg style="transform: rotate(180deg); vertical-align: bottom;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23,24a1,1,0,0,1-1-1,6.006,6.006,0,0,0-6-6H10.17v1.586A2,2,0,0,1,6.756,20L.877,14.121a3,3,0,0,1,0-4.242L6.756,4A2,2,0,0,1,10.17,5.414V7H15a9.01,9.01,0,0,1,9,9v7A1,1,0,0,1,23,24ZM8.17,5.414,2.291,11.293a1,1,0,0,0,0,1.414L8.17,18.586V16a1,1,0,0,1,1-1H16a7.984,7.984,0,0,1,6,2.714V16a7.008,7.008,0,0,0-7-7H9.17a1,1,0,0,1-1-1Z"/></svg>
                        </div>
                        <div class="wpf-activity-data">
                            <div class="wpf-activity-top">
                                <div class="wpf-activity-title">
                                    <a href="<?php echo esc_url_raw( WPF()->post->get_post_url( $activity['postid'] ) ) ?>">
                                        <?php echo $activity['title'] ?>
                                    </a>
                                    <p><?php echo wpforo_text( $activity['body'], 150, false ) ?></p>
                                </div>
                                <div class="wpf-activity-date">
                                    <?php wpforo_date($activity['created']); ?>
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
                <?php endif; ?>




            <?php endforeach ?>



        <div class="activity-foot">
			<?php wpforo_template_pagenavi() ?>
        </div>
	<?php else : ?>
        <p class="wpf-p-error"> <?php wpforo_phrase( 'No activity found for this member.' ) ?> </p>
	<?php endif ?>
</div>
