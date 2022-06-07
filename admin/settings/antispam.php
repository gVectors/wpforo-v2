<?php if( ! defined( "ABSPATH" ) ) exit(); ?>

<input type="hidden" name="wpfaction" value="antispam_settings_save">

<?php WPF()->settings->header( 'antispam' ); ?>

<div class="wpf-subtitle">
    <span class="dashicons dashicons-shield"></span> <?php _e( 'wpForo Spam Control', 'wpforo' ) ?>
    <p class="wpf-desc"></p>
</div>

<?php WPF()->settings->form_field( 'antispam', 'spam_filter' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'spam_user_ban' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'spam_filter_level_topic' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'spam_filter_level_post' ); ?>

<div class="wpf-subtitle">
    <span class="dashicons dashicons-admin-users"></span> <?php _e( 'New Registered User', 'wpforo' ) ?>
    <p class="wpf-desc"><?php _e( 'Some useful options to limit just registered users and minimize spam. These options don\'t affect users whose Usergroup has "Can edit member" and "Can pass moderation" permissions.', 'wpforo' ) ?></p>
</div>
<?php WPF()->settings->form_field( 'antispam', 'new_user_max_posts' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'unapprove_post_if_user_is_new' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'min_number_posts_to_edit_account' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'min_number_posts_to_attach' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'min_number_posts_to_link' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'limited_file_ext' ); ?>

<div class="wpf-subtitle">
    <span class="dashicons dashicons-paperclip"></span> <?php _e( 'Possible Spam Attachments', 'wpforo' ) ?>
    <p class="wpf-desc"><?php _e( 'This tool is designed to find attachment which have been uploaded by spammers. The tool checks most common spammer filenames and suggest to delete but you should check one by one and make sure those are spam files before deleting.', 'wpforo' ) ?></p>
</div>
<?php WPF()->settings->form_field( 'antispam', 'spam_file_scanner' ); ?>
<?php WPF()->settings->form_field( 'antispam', 'exclude_file_ext' ); ?>
<div class="wpf-spam-attach">
    <div class="wpf-spam-attach-dir"><?php _e( 'Directory', 'wpforo' ); ?>: <?php echo WPF()->folders['default_attachments']['dir'] ?>&nbsp;</div>
    <div style="margin-top:10px; clear:both;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tbody>
			<?php
			$default_attachments_dir = WPF()->folders['default_attachments']['dir'];
			if( is_dir( $default_attachments_dir ) && wpforo_setting( 'antispam', 'spam_file_scanner' ) ):
				if( $handle = opendir( $default_attachments_dir ) ):
					while( false !== ( $filename = readdir( $handle ) ) ):
						if( $filename == '.' || $filename == '..' ) continue;

						$level     = 0;
						$color     = '';
						$file      = $default_attachments_dir . DIRECTORY_SEPARATOR . $filename;
						$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
						if( ! $level = WPF()->moderation->spam_file( $filename ) ) continue;
						if( $level == 2 ) $color = 'style="color:#EE9900;"';
						if( $level == 3 ) $color = 'style="color:#FF0000;"';
						if( $level == 4 ) $color = 'style="color:#BB0000;"';
						?>
                        <tr>
                            <td class="wpf-spam-item" <?php echo $color; ?> title="<?php echo WPF()->folders['default_attachments']['url'] . '/' . $filename ?>">
								<?php if( WPF()->moderation->spam_file( $filename, 'file-open' ) ): ?>
                                    <a href="<?php echo WPF()->folders['default_attachments']['url'] . '/' . $filename ?>" target="_blank" <?php echo $color ?>><?php echo wpforo_text( $filename, 50, false ); ?></a>
								<?php else: ?>
									<?php echo $filename; ?>
								<?php endif; ?>
								<?php echo ' (' . strtoupper( $extension ) . ' | ' . wpforo_human_filesize( filesize( $file ), 1 ) . ')'; ?>
                            </td>
                            <td class="wpf-actions"><a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=antispam&wpfaction=delete_spam_file&sfname=' . urlencode( $filename ) ), 'wpforo_tools_antispam_files' ); ?>" title="<?php _e( 'Delete this file', 'wpforo' ); ?>" onclick="return confirm('<?php _e( 'Are you sure you want to permanently delete this file?', 'wpforo' ); ?>');"><?php _e( 'Delete', 'wpforo' ); ?></a></td>
                        </tr>
					<?php
					endwhile;
					closedir( $handle );
				endif;
			endif;
			?>
            <tr style="background:#fff;">
                <td colspan="2" class="wpf-actions" style="padding-top:20px; text-align:right;">
                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=antispam&wpfaction=delete_all_spam_files&level=1' ), 'wpforo_tools_antispam_files' ); ?>"
                       title="<?php _e( 'Click to delete Blue marked files', 'wpforo' ); ?>"
                       onclick="return confirm('<?php _e( 'Are you sure you want to delete all BLUE marked files listed here. Please download Wordpress /wp-content/uploads/wpforo/ folder to your local computer before deleting files, this is not undoable.', 'wpforo' ); ?>');">
						<?php _e( 'Delete All', 'wpforo' ); ?>
                    </a> |
                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=antispam&wpfaction=delete_all_spam_files&level=2' ), 'wpforo_tools_antispam_files' ); ?>"
                       title="<?php _e( 'Click to delete Orange marked files', 'wpforo' ); ?>"
                       style="color:#EE9900;"
                       onclick="return confirm('<?php _e( 'Are you sure you want to delete all ORANGE marked files listed here. Please download Wordpress /wp-content/uploads/wpforo/ folder to your local computer before deleting files, this is not undoable.', 'wpforo' ); ?>');">
						<?php _e( 'Delete All', 'wpforo' ); ?>
                    </a> |
                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=antispam&wpfaction=delete_all_spam_files&level=3' ), 'wpforo_tools_antispam_files' ); ?>"
                       title="<?php _e( 'Click to delete Red marked files', 'wpforo' ); ?>"
                       style="color:#FF0000;"
                       onclick="return confirm('<?php _e( 'Are you sure you want to delete all RED marked files listed here. Please download Wordpress /wp-content/uploads/wpforo/ folder to your local computer before deleting files, this is not undoable.', 'wpforo' ); ?>');">
						<?php _e( 'Delete All', 'wpforo' ); ?>
                    </a> |
                    <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=antispam&wpfaction=delete_all_spam_files&level=4' ), 'wpforo_tools_antispam_files' ); ?>"
                       title="<?php _e( 'Click to delete Dark Red marked files', 'wpforo' ); ?>"
                       style="color:#BB0000;"
                       onclick="return confirm('<?php _e( 'Are you sure you want to delete all DARK RED marked files listed here. Please download Wordpress /wp-content/uploads/wpforo/ folder to your local computer before deleting files, this is not undoable.', 'wpforo' ); ?>');">
						<?php _e( 'Delete All', 'wpforo' ); ?>
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
