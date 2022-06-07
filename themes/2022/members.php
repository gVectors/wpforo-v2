<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

$members = WPF()->current_object['members'];
?>
<h2 id="wpforo-title"><?php wpforo_phrase( 'Forum Members' ) ?></h2>
<div class="wpforo-members-wrap">
	<?php if( WPF()->usergroup->can( 'vmem' ) ): ?>
        <div class="wpf-table wpforo-members-search wpfbg-9"><?php wpforo_member_search_form() ?></div>
        <p>&nbsp;</p>
        <div class="wpforo-members">

            <?php if( ! empty( $members ) ) : ?>
                <?php foreach( $members as $member ) : ?>

                <div class="wpforo-member">
                    <div class="wpforo-member-bg wpfbg-9" <?php wpforo_profile_head_attrs( $member ) ?>>&nbsp</div>
                    <div class="wpforo-member-head">
                        <?php if( WPF()->usergroup->can( 'va' ) && wpforo_setting( 'profiles', 'avatars' ) ): ?>
                            <div class="wpf-member-avatar">
                                <?php echo WPF()->member->avatar( $member ); ?>
                                <div class="wpf-member-online"><?php WPF()->member->show_online_indicator( $member['userid'] ) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php wpforo_member_link( $member, '', 50, ' wpf-member-name ' ); ?>
                    </div>

                    <div class="wpf-members-info">
                        <?php $ug = wpforo_member_title($member, false, '', '', ['rating-title', 'custom-title']) ?>
                        <?php if( $ug ): ?>
                            <div class="wpforo-member-ug">
                                <?php echo $ug ?>
                            </div>
                        <?php endif; ?>
                        <div class="wpforo-member-reputation">
                            <?php wpforo_member_title($member, true, '', '', ['usergroup']) ?>
                            <?php wpforo_member_badge($member) ?>
                            <?php do_action( 'wpforo_after_member_badge', $member ) ?>
                        </div>
                        <div class="wpforo-member-joined">
                            <?php wpforo_phrase('Joined:') ?> <?php wpforo_date( $member['user_registered'], 'date' ) ?>
                        </div>
                        <div class="wpforo-member-stat">
                            <span class="wpfm-sb"><?php wpforo_phrase('Topics') ?>: <?php echo intval($member['topics']) ?></span>
                             &nbsp;
                            <span class="wpfm-sb"><?php wpforo_phrase('Posts') ?>: <?php echo intval($member['posts']) ?></span>
                        </div>
                        <div class="wpforo-member-social">
                            <?php if( wpfval($member, 'fields') ): ?>
                                <?php foreach ($member['fields'] as $key => $value ): if(!$value) continue; ?>
                                    <?php
                                    switch ($key){
                                        case 'facebook': echo '<a href="' . esc_url_raw($value) . '"><i class="fa-brands fa-facebook-square"></i></a>'; break;
                                        case 'twitter': echo '<a href="' . esc_url_raw($value) . '"><i class="fa-brands fa-twitter-square"></i>'; break;
                                        case 'youtube': echo '<a href="' . esc_url_raw($value) . '"><i class="fa-brands fa-youtube"></i></a>'; break;
                                        case 'vkontakte': echo '<a href="' . esc_url_raw($value) . '"><i class="fa-brands fa-vk"></i></a>'; break;
                                        case 'linkedin': echo '<a href="' . esc_url_raw($value) . '"><i class="fa-brands fa-linkedin"></i></a>'; break;
                                        case 'telegram': echo '<a href="' . esc_url_raw($value) . '"><i class="fa-brands fa-telegram"></i></a>'; break;
                                        case 'instagram': echo '<a href="' . esc_url_raw($value) . '"><i class="fa-brands fa-instagram-square"></i></a>'; break;
                                        case 'skype': echo '<a href="skype:' . esc_attr($value) . '"><i class="fa-brands fa-skype"></i></a>'; break;
                                    }
                                    ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php do_action( 'wpforo_after_member_details', $member ) ?>
                        </div>
                    </div>

                </div>

                <?php endforeach; ?>
            <?php else : ?>
                <div>
                    <p class="wpf-p-error"> <?php wpforo_phrase( 'Members not found' ) ?> </p>
                </div>
            <?php endif ?>


        </div>
        <div class="wpf-members-foot">
			<?php wpforo_template_pagenavi() ?>
        </div>
	<?php else : ?>
        <p class="wpf-p-error"> <?php wpforo_phrase( 'You do not have permission to view this page' ) ?> </p>
	<?php endif; ?>
</div>
