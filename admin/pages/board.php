<?php
if( wpfkey( $_GET, 'boardid' ) ) {
	$board = WPF()->board->get_current();
} else {
	$board          = WPF()->board->decode( [] );
	$board['title'] = $board['slug'] = '';
}
$action  = wpfval( $_GET, 'wpfaction' );
$boardid = (int) wpfval( $_GET, 'boardid' );
?>

<div id="icon-edit" class="icon32 icon32-posts-post"></div>

<div id="wpf-admin-wrap" class="wrap" style="padding-right: 50px;">
    <h2 style="padding:30px 0 10px; line-height: 20px;">
		<?php _e( 'Boards', 'wpforo' ); ?> &nbsp;
        <a href="<?php echo admin_url( 'admin.php?page=wpforo-boards&wpfaction=wpforo_board_save_form' ) ?>" class="add-new-h2"><?php _e( 'Add New', 'wpforo' ); ?></a>
    </h2>
	<?php WPF()->notice->show();
	if( $action ) : ?>

    <div style="background: #fff; width: 100%; font-size: 16px; padding: 20px; margin-top: 20px; line-height: 24px; box-sizing: border-box;">
        <div style="font-size: 20px; font-weight: 500; margin-bottom: 5px; margin-top: 0">
            <svg style="height: 33px; vertical-align: text-bottom; margin-bottom: -3px; margin-right: 3px; fill: #f07d02;" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><defs><style>.cls-1{fill:none;}</style></defs><title/><g data-name="Layer 2" id="Layer_2"><path d="M22.7,28H9.3a6.25,6.25,0,0,1-5.47-3.15,6.15,6.15,0,0,1,0-6.22L10.56,7.12a6.3,6.3,0,0,1,10.88,0l6.71,11.51h0a6.15,6.15,0,0,1,0,6.22A6.25,6.25,0,0,1,22.7,28ZM16,6a4.24,4.24,0,0,0-3.71,2.12L5.58,19.64a4.15,4.15,0,0,0,0,4.21A4.23,4.23,0,0,0,9.3,26H22.7a4.23,4.23,0,0,0,3.73-2.15,4.15,4.15,0,0,0,0-4.21L19.71,8.12A4.24,4.24,0,0,0,16,6Z"/><path class="cls-1" d="M16,12a.54.54,0,0,0-.44.22.52.52,0,0,0-.1.48L16,14.88l.54-2.18a.52.52,0,0,0-.1-.48A.54.54,0,0,0,16,12Z"/><path d="M18,11a2.56,2.56,0,0,0-4,0,2.5,2.5,0,0,0-.46,2.19L15,19.24a1,1,0,0,0,1.94,0l1.51-6.06A2.5,2.5,0,0,0,18,11ZM16.54,12.7,16,14.88l-.54-2.18a.52.52,0,0,1,.1-.48.55.55,0,0,1,.88,0A.52.52,0,0,1,16.54,12.7Z"/><circle cx="16" cy="22.5" r="1.5"/></g><g id="frame"><rect class="cls-1" height="32" width="32"/></g></svg>
            <span style="color: #d5740f"><?php _e( 'Boards are not Forum Categories', 'wpforo' ) ?></span>
        </div>
        <div style="padding: 5px 2px 0 2px; line-height: 23px;">
            <?php if( ! $board['slug'] ) printf( '<span style="font-weight: bold;">%1$s</span>', __( 'You\'re about to create a new separate discussion board in your website.', 'wpforo' ) ); ?>
            <?php _e( 'Please note that boards are separate forum pages, you can use them if you want to have more than one forum in your website. Also, you can use boards to create separate forums for different languages. If you want to add a new category or forum, please use the Forums menu under the menu section of existing discussion boards.', 'wpforo' ) ?>
        </div>
    </div>

    <form method="post">

        <div class="wpf-board">

            <div class="wpf-board-left">

                <?php if( wpfkey( $_GET, 'boardid' ) ) : ?>
                    <?php wp_nonce_field( 'wpforo-board-edit' ); ?>
                    <input type="hidden" name="wpfaction" value="board_edit">
                <?php else : ?>
                    <?php wp_nonce_field( 'wpforo-board-add' ); ?>
                    <input type="hidden" name="wpfaction" value="board_add">
                <?php endif; ?>
                <input type="hidden" name="board[boardid]" value="<?php echo $board['boardid'] ?>">


                <div class="wpf-board-option">
                    <label for="wpf-board-settings-title"><?php _e( 'Board Title', 'wpforo' ); ?></label>
                    <div class="wpf-board-field">
                        <input type="text" name="board[settings][title]" id="wpf-board-settings-title" value="<?php echo $board['settings']['title'] ?>" style="font-size: 22px; background: #fafafa;">
                    </div>
                </div>

                <div class="wpf-board-option">
                    <label for="wpf-board-title"><?php _e('Board Label (used in menu, max 12 characters)', 'wpforo'); ?></label>
                    <div class="wpf-board-field">
                        <input type="text" name="board[title]" id="wpf-board-title" value="<?php echo $board['title'] ?>" maxlength="12" placeholder="<?php _e('Short title of this board, max 12 characters', 'wpforo') ?>">
                    </div>
                </div>

                <div class="wpf-board-option">
                    <label for="wpf-board-slug"><?php _e( 'Board Slug', 'wpforo' ); ?> </label>
                    <div class="wpfb-desc" style="padding: 5px 0 0;"><?php _e( 'The "slug" is the URL-friendly version of the board name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'wpforo' ) ?></div>
                    <div class="wpf-board-field">
                        <input id="wpf-board-slug" type="text" name="board[slug]" value="<?php echo $board['slug'] ?>" required>
                    </div>
                </div>

                <div class="wpf-board-option">
                    <label for="wpf-board-settings-desc"><?php _e( 'Board Description', 'wpforo' ); ?></label>
                    <div class="wpf-board-field">
                        <textarea name="" id="wpf-board-settings-desc" rows="4"><?php echo $board['settings']['desc'] ?></textarea>
                    </div>
                </div>


                <div class="wpf-board-box">
                    <div class="wpf-board-locale">
                        <label for="wpf-board-locale">
                            <?php _e( 'Language', 'wpforo' ); ?>
                            <span class="wpfb-desc"><?php _e('If you have multiple languages in your website and want to have forums with different languages, then make sure the language of this forum board is set correctly, otherwise just leave the default language.', '') ?></span>
                        </label>
                        <?php wp_dropdown_languages( [ 'id' => 'wpf-board-locale', 'name' => 'board[locale]', 'selected' => $board['locale'] ] ); ?>
                    </div>
                    <div class="wpf-board-pageid" style="display: none;">
                        <label for="wpf-board-pageid"><?php _e( 'Page ID', 'wpforo' ); ?> <span class="wpfb-desc"><?php _e('The new forum page will be created automatically with the next autoincrement page ID, you do not need to modify it when you create a new forum board. Only change this ID if you have already created a new page for this forum board.', '') ?></span></label>
                        <input id="wpf-board-pageid" type="number" name="board[pageid]" value="<?php echo $board['pageid'] ?>">
                    </div>
                </div>

                <div class="wpf-board-modules">

                    <div class="wpf-module-type"><?php _e('Modules', 'wpforo') ?></div>

                    <?php foreach( wpforo_get_modules_info(false) as $key => $module ) : ?>
                        <div class="wpf-board-module">
                            <div class="wpf-module-head">
                                <?php if( strpos($module['thumb'], '<svg') === FALSE ): ?>
                                <?php $src = ( $module['thumb'] ) ? $module['thumb'] : WPFORO_URL . "/assets/images/dashboard/addon.png" ?>
                                    <img src="<?php echo esc_url_raw($src) ?>" style="height: 60px;">
                                <?php else: ?>
                                    <?php echo $module['thumb'] ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="wpf-module-title"><?php echo $module['title'] ?></div>
                                <div class="wpf-switch-field">
                                    <input type="radio" value="1" name="board[modules][<?php echo $key ?>]" id="wpf-board-<?php echo $key ?>_1" <?php wpfo_check( (bool) wpfval( $board['modules'], $key ), true ); ?>><label for="wpf-board-<?php echo $key ?>_1"><?php _e( 'Enabled', 'wpforo' ) ?></label> &nbsp;
                                    <input type="radio" value="0" name="board[modules][<?php echo $key ?>]" id="wpf-board-<?php echo $key ?>_0" <?php wpfo_check( (bool) wpfval( $board['modules'], $key ), false ); ?>><label for="wpf-board-<?php echo $key ?>_0"><?php _e( 'Disabled', 'wpforo' ) ?></label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="wpf-module-type" style="margin-top: 15px;"><?php _e('Addons', 'wpforo') ?></div>

                    <?php foreach( wpforo_get_addons_info(false) as $key => $addon ) : ?>
                        <div class="wpf-board-module">
                            <div class="wpf-module-head">
                                <?php if( strpos($addon['thumb'], '<svg') === FALSE ): ?>
                                    <?php $src = $addon['thumb'] ?: WPFORO_URL . "/assets/images/dashboard/addon.png" ?>
                                    <img src="<?php echo esc_url_raw($src) ?>" style="height: 55px;">
                                <?php else: ?>
                                    <?php echo $addon['thumb'] ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="wpf-module-title"><?php echo $addon['title'] ?></div>
                                <?php if( class_exists( $addon['class'] ) ) : ?>
                                    <div class="wpf-switch-field">
                                        <input type="radio" value="1" name="board[modules][<?php echo $key ?>]" id="wpf-board-<?php echo $key ?>_1" <?php wpfo_check( (bool) wpfval( $board['modules'], $key ), true ); ?>><label for="wpf-board-<?php echo $key ?>_1"><?php _e( 'Enabled', 'wpforo' ) ?></label> &nbsp;
                                        <input type="radio" value="0" name="board[modules][<?php echo $key ?>]" id="wpf-board-<?php echo $key ?>_0" <?php wpfo_check( (bool) wpfval( $board['modules'], $key ), false ); ?>><label for="wpf-board-<?php echo $key ?>_0"><?php _e( 'Disabled', 'wpforo' ) ?></label>
                                    </div>
                                <?php else :
                                    printf(
                                    '<a class="button" href="%1$s" title="%2$s" target="_blank">%3$s</a>',
                                        $addon['url'],
                                        __('Go to the addon page', 'wpforo'),
                                        __('Get Addon','wpforo')
                                    );
                                ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="wpf-board-box">
                    <div class="wpf-board-is_standalone">
                        <label>
                            <?php _e( 'Turn WordPress to this forum board', 'wpforo' ) ?> <a href="https://wpforo.com/docs/root/getting-started/forum-page/turn-wordpress-to-wpforo/" title="<?php _e( 'Read the documentation', 'wpforo' ) ?>" target="_blank"><i class="far fa-question-circle"></i></a>
                            <p class="wpf-info"><?php _e( 'This option will disable WordPress on front-end. Only forum pages and excluded post/pages will be available. wpForo will look like as a stand-alone forum.', 'wpforo' ) ?></p>
                        </label>
                        <div class="wpf-switch-field">
                            <input type="radio" value="1" name="board[is_standalone]" id="wpf-board-is_standalone_1" <?php wpfo_check( $board['is_standalone'], true ); ?>><label for="wpf-board-is_standalone_1"><?php _e( 'Enabled', 'wpforo' ) ?></label> &nbsp;
                            <input type="radio" value="0" name="board[is_standalone]" id="wpf-board-is_standalone_0" <?php wpfo_check( $board['is_standalone'], false ); ?>><label for="wpf-board-is_standalone_0"><?php _e( 'Disabled', 'wpforo' ) ?></label>
                        </div>
                    </div>
                    <div class="wpf-board-excld_urls">
                        <label for="wpf-board-excld_urls"><b style="font-weight: bold;">* <?php _e( 'Exclude page URLs', 'wpforo' ) ?></b> <span class="wpf-info">(<?php _e( 'one URL per line', 'wpforo' ) ?>)</span></label>
                        <textarea id="wpf-board-excld_urls" style="font-size: 12px; width: 100%;" rows="5" name="wpforo_excld_urls"  placeholder="<?php echo esc_url( home_url( '/' ) ) ?>sample-page/&#10;<?php echo esc_url( home_url( '/' ) ) ?>hello-world/&#10;<?php echo esc_url( home_url( '/' ) ) ?>category/*&#10; ..."
                        ><?php echo esc_textarea( implode( PHP_EOL, $board['excld_urls'] ) ) ?></textarea>
                    </div>
                </div>












            </div><!-- wpf-board-left END -->

            <div class="wpf-board-right">

                <div class="wpf-board-side-box">
                    <label><?php _e( 'Status', 'wpforo' ); ?></label>
                    <div class="wpf-switch-field">
                        <input type="radio" value="1" name="board[status]" id="wpf-board-status_1" <?php wpfo_check( $board['status'], true ); ?>><label for="wpf-board-status_1"><?php _e( 'Enabled', 'wpforo' ) ?></label> &nbsp;
                        <input type="radio" value="0" name="board[status]" id="wpf-board-status_0" <?php wpfo_check( $board['status'], false ); ?>><label for="wpf-board-status_0"><?php _e( 'Disabled', 'wpforo' ) ?></label>
                    </div>
                </div>

                <div class="wpf-board-submit">
                    <input type="submit" value="<?php _e( 'Save', 'wpforo' ) ?>" class="button button-primary">
                </div>

            </div><!-- wpf-board-right END -->

        </div>

    </form>










	<?php else : ?>
        <!-- Now we can render the completed list table -->
		<?php WPF()->board->list_table->display() ?>
	<?php endif; ?>
</div>
