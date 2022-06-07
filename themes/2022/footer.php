<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;
?>


<?php do_action( 'wpforo_footer_hook' ) ?>

<!-- forum statistic -->
<div class="wpf-clear"></div>

<?php wpforo_share_buttons( 'bottom' ); ?>

<div id="wpforo-footer">
    <?php $stat = WPF()->statistic(); ?>
	<?php do_action( 'wpforo_stat_bar_start', WPF() ); ?>
	<?php if( wpforo_setting( 'components', 'footer_stat' ) ): ?>
        <div id="wpforo-stat-header">
            <div class="wpf-footer-title">
                <svg viewBox="0 0 2048 1792" xmlns="http://www.w3.org/2000/svg"><path d="M640 896v512h-256v-512h256zm384-512v1024h-256v-1024h256zm1024 1152v128h-2048v-1536h128v1408h1920zm-640-896v768h-256v-768h256zm384-384v1152h-256v-1152h256z"/></svg>
                <span><?php wpforo_phrase( 'Forum Information' ) ?></span>
            </div>
            <div class="wpf-footer-buttons">
                <div class="wpf-all-read"><?php wpforo_mark_all_read_link() ?></div>
                <?php if( isset( $stat['posts'] ) && $stat['posts'] ): ?><div class="wpf-stat-recent-posts"><a href="<?php echo esc_url( wpforo_home_url( wpforo_settings_get_slug( 'recent' ) ) ) ?>"><i class="fas fa-list-ul"></i> <?php wpforo_phrase( 'Recent Posts' ) ?></a></div><?php endif; ?>
                <?php if( isset( $stat['posts'] ) && $stat['posts'] ): ?><div class="wpf-stat-unread-posts"><a href="<?php echo esc_url( wpforo_home_url( wpforo_settings_get_slug( 'recent' ) . '?view=unread' ) ) ?>"><i class="fas fa-layer-group"></i> <?php wpforo_phrase( 'Unread Posts' ) ?></a></div><?php endif; ?>
                <?php if( !wpforo_is_module_enabled( 'tags' ) ): ?><div class="wpf-stat-tags"><a href="<?php echo esc_url( wpforo_home_url( wpforo_settings_get_slug( 'tags' ) ) ) ?>"><i class="fas fa-tag"></i> <?php wpforo_phrase( 'Tags' ) ?></a></div><?php endif; ?>
            </div>
        </div>
        <div id="wpforo-stat-body">

            <?php if( WPF()->usergroup->can( 'view_stat' ) ) : ?>
                <div class="wpf-footer-box">
                    <ul>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" data-name="Layer 1" viewBox="0 0 24 24"><path d="M24,16v5a3,3,0,0,1-3,3H16a8,8,0,0,1-6.92-4,10.968,10.968,0,0,0,2.242-.248A5.988,5.988,0,0,0,16,22h5a1,1,0,0,0,1-1V16a5.988,5.988,0,0,0-2.252-4.678A10.968,10.968,0,0,0,20,9.08,8,8,0,0,1,24,16ZM17.977,9.651A9,9,0,0,0,8.349.023,9.418,9.418,0,0,0,0,9.294v5.04C0,16.866,1.507,18,3,18H8.7A9.419,9.419,0,0,0,17.977,9.651Zm-4.027-5.6a7.018,7.018,0,0,1,2.032,5.46A7.364,7.364,0,0,1,8.7,16H3c-.928,0-1-1.275-1-1.666V9.294A7.362,7.362,0,0,1,8.49,2.018Q8.739,2,8.988,2A7.012,7.012,0,0,1,13.95,4.051Z"/></svg>
                            <span class="wpf-stat-value"><?php echo wpforo_print_number( $stat['forums'] ) ?></span>
                            <span class="wpf-stat-label"><?php wpforo_phrase( 'Forums' ) ?></span>
                        </li>
                        <li>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g id="_01_align_center" data-name="01 align center"><path d="M21,0H3A3,3,0,0,0,0,3V20H6.9l3.808,3.218a2,2,0,0,0,2.582,0L17.1,20H24V3A3,3,0,0,0,21,0Zm1,18H16.366L12,21.69,7.634,18H2V3A1,1,0,0,1,3,2H21a1,1,0,0,1,1,1Z"/><rect x="6" y="5" width="6" height="2"/><rect x="6" y="9" width="12" height="2"/><rect x="6" y="13" width="12" height="2"/></g></svg>
                            <span class="wpf-stat-value"><?php echo wpforo_print_number( $stat['topics'] ) ?></span>
                            <span class="wpf-stat-label"><?php wpforo_phrase( 'Topics' ) ?></span>
                        </li>
                        <li>
                            <svg style="transform: rotate(180deg); vertical-align: bottom;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23,24a1,1,0,0,1-1-1,6.006,6.006,0,0,0-6-6H10.17v1.586A2,2,0,0,1,6.756,20L.877,14.121a3,3,0,0,1,0-4.242L6.756,4A2,2,0,0,1,10.17,5.414V7H15a9.01,9.01,0,0,1,9,9v7A1,1,0,0,1,23,24ZM8.17,5.414,2.291,11.293a1,1,0,0,0,0,1.414L8.17,18.586V16a1,1,0,0,1,1-1H16a7.984,7.984,0,0,1,6,2.714V16a7.008,7.008,0,0,0-7-7H9.17a1,1,0,0,1-1-1Z"/></svg>
                            <span class="wpf-stat-value"><?php echo wpforo_print_number( $stat['posts'] ) ?></span>
                            <span class="wpf-stat-label"><?php wpforo_phrase( 'Posts' ) ?></span>
                        </li>
                        <li>
                            <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1120 576q0 13-9.5 22.5t-22.5 9.5-22.5-9.5-9.5-22.5q0-46-54-71t-106-25q-13 0-22.5-9.5t-9.5-22.5 9.5-22.5 22.5-9.5q50 0 99.5 16t87 54 37.5 90zm160 0q0-72-34.5-134t-90-101.5-123-62-136.5-22.5-136.5 22.5-123 62-90 101.5-34.5 134q0 101 68 180 10 11 30.5 33t30.5 33q128 153 141 298h228q13-145 141-298 10-11 30.5-33t30.5-33q68-79 68-180zm128 0q0 155-103 268-45 49-74.5 87t-59.5 95.5-34 107.5q47 28 47 82 0 37-25 64 25 27 25 64 0 52-45 81 13 23 13 47 0 46-31.5 71t-77.5 25q-20 44-60 70t-87 26-87-26-60-70q-46 0-77.5-25t-31.5-71q0-24 13-47-45-29-45-81 0-37 25-64-25-27-25-64 0-54 47-82-4-50-34-107.5t-59.5-95.5-74.5-87q-103-113-103-268 0-99 44.5-184.5t117-142 164-89 186.5-32.5 186.5 32.5 164 89 117 142 44.5 184.5z"/></svg>
                            <span class="wpf-stat-value"><?php echo wpforo_print_number( $stat['online_members_count'] ) ?></span>
                            <span class="wpf-stat-label"><?php wpforo_phrase( 'Online' ) ?></span>
                        </li>
                        <li>
                            <svg style="height: 16px; " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,12A6,6,0,1,0,6,6,6.006,6.006,0,0,0,12,12ZM12,2A4,4,0,1,1,8,6,4,4,0,0,1,12,2Z"/><path d="M12,14a9.01,9.01,0,0,0-9,9,1,1,0,0,0,2,0,7,7,0,0,1,14,0,1,1,0,0,0,2,0A9.01,9.01,0,0,0,12,14Z"/></svg>
                            <span class="wpf-stat-value"><?php echo wpforo_print_number( $stat['members'] ) ?></span>
                            <span class="wpf-stat-label"><?php wpforo_phrase( 'Members' ) ?></span>
                        </li>
                    </ul>
                </div>
            <?php endif ?>

            <div class="wpf-footer-box">
                <div class="wpf-newest-member">
                    <svg viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1329 784q47 14 89.5 38t89 73 79.5 115.5 55 172 22 236.5q0 154-100 263.5t-241 109.5h-854q-141 0-241-109.5t-100-263.5q0-131 22-236.5t55-172 79.5-115.5 89-73 89.5-38q-79-125-79-272 0-104 40.5-198.5t109.5-163.5 163.5-109.5 198.5-40.5 198.5 40.5 163.5 109.5 109.5 163.5 40.5 198.5q0 147-79 272zm-433-656q-159 0-271.5 112.5t-112.5 271.5 112.5 271.5 271.5 112.5 271.5-112.5 112.5-271.5-112.5-271.5-271.5-112.5zm427 1536q88 0 150.5-71.5t62.5-173.5q0-239-78.5-377t-225.5-145q-145 127-336 127t-336-127q-147 7-225.5 145t-78.5 377q0 102 62.5 173.5t150.5 71.5h854z"/></svg>
                    <?php wpforo_phrase( 'Our newest member' ) ?>: <?php wpforo_member_link( $stat['newest_member'] ); ?>
                </div>
                <?php if( $stat['last_post_title'] ): ?>
                    <div class="wpf-newest-post">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.656.93,6.464,13.122A4.966,4.966,0,0,0,5,16.657V18a1,1,0,0,0,1,1H7.343a4.966,4.966,0,0,0,3.535-1.464L23.07,5.344a3.125,3.125,0,0,0,0-4.414A3.194,3.194,0,0,0,18.656.93Zm3,3L9.464,16.122A3.02,3.02,0,0,1,7.343,17H7v-.343a3.02,3.02,0,0,1,.878-2.121L20.07,2.344a1.148,1.148,0,0,1,1.586,0A1.123,1.123,0,0,1,21.656,3.93Z"/><path d="M23,8.979a1,1,0,0,0-1,1V15H18a3,3,0,0,0-3,3v4H5a3,3,0,0,1-3-3V5A3,3,0,0,1,5,2h9.042a1,1,0,0,0,0-2H5A5.006,5.006,0,0,0,0,5V19a5.006,5.006,0,0,0,5,5H16.343a4.968,4.968,0,0,0,3.536-1.464l2.656-2.658A4.968,4.968,0,0,0,24,16.343V9.979A1,1,0,0,0,23,8.979ZM18.465,21.122a2.975,2.975,0,0,1-1.465.8V18a1,1,0,0,1,1-1h3.925a3.016,3.016,0,0,1-.8,1.464Z"/></svg>
                        <?php wpforo_phrase( 'Latest Post' ) ?>: <a href="<?php echo esc_url( $stat['last_post_url'] ) ?>"><?php echo esc_html( $stat['last_post_title'] ) ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="wpf-footer-box wpf-last-info">
                <div class="wpf-forum-icons">
                    <span class="wpf-stat-label"><?php wpforo_phrase( 'Forum Icons' ) ?>:</span>
                    <span class="wpf-no-new"><i class="fas fa-comments wpfcl-0"></i> <?php wpforo_phrase( 'Forum contains no unread posts' ) ?></span>
                    <span class="wpf-new"><i class="fas fa-comments"></i> <?php wpforo_phrase( 'Forum contains unread posts' ) ?></span>
                </div>
                <div class="wpf-topic-icons">
                    <span class="wpf-stat-label"><?php wpforo_phrase( 'Topic Icons' ) ?>:</span>
                    <span><i class="far fa-file wpfcl-2"></i> <?php wpforo_phrase( 'Not Replied' ) ?></span>
                    <span><i class="far fa-file-alt wpfcl-2"></i> <?php wpforo_phrase( 'Replied' ) ?></span>
                    <span><i class="fas fa-file-alt wpfcl-2"></i> <?php wpforo_phrase( 'Active' ) ?></span>
                    <span><i class="fa-brands fa-hotjar wpfcl-5"></i> <?php wpforo_phrase( 'Hot' ) ?></span>
                    <span><i class="fas fa-thumbtack wpfcl-10"></i> <?php wpforo_phrase( 'Sticky' ) ?></span>
                    <span><i class="fas fa-exclamation-circle wpfcl-5"></i> <?php wpforo_phrase( 'Unapproved' ) ?></span>
                    <span><i class="fas fa-check-circle wpfcl-8"></i> <?php wpforo_phrase( 'Solved' ) ?></span>
                    <span><i class="fas fa-eye-slash wpfcl-1"></i> <?php wpforo_phrase( 'Private' ) ?></span>
                    <span><i class="fas fa-lock wpfcl-1"></i> <?php wpforo_phrase( 'Closed' ) ?></span>
                </div>
            </div>

        </div>
	<?php endif; ?>
	<?php WPF()->tpl->copyright() ?>
	<?php do_action( 'wpforo_stat_bar_end' ); ?>
</div>    <!-- wpforo-footer -->
