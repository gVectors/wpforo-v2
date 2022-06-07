<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="email_settings_save">

<?php
WPF()->settings->header( 'email' );
WPF()->settings->form_field( 'email', 'from_name' );
WPF()->settings->form_field( 'email', 'from_email' );
WPF()->settings->form_field( 'email', 'admin_emails' );
WPF()->settings->form_field( 'email', 'new_topic_notify' );
WPF()->settings->form_field( 'email', 'new_reply_notify' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-admin-users"></span> <?php _e( 'Post Reporting Emails', 'wpforo' ) ?>
        <p class="wpf-desc" style="padding: 5px 2px 0;"><?php _e( 'This message comes from post reporting pop-up form.', 'wpforo' ) ?></p>
    </div>
<?php
WPF()->settings->form_field( 'email', 'report_email_subject' );
WPF()->settings->form_field( 'email', 'report_email_message' );
?>


    <div class="wpf-subtitle">
        <span class="dashicons dashicons-admin-users"></span> <?php _e( 'New User Registration Email for Administrators', 'wpforo' ) ?>
        <p class="wpf-desc" style="padding: 5px 2px 0;"><?php _e( 'This message comes when new user registers to site', 'wpforo' ) ?></p>
    </div>
<?php
WPF()->settings->form_field( 'email', 'disable_new_user_admin_notification' );
WPF()->settings->form_field( 'email', 'overwrite_new_user_notification_admin' );
WPF()->settings->form_field( 'email', 'wp_new_user_notification_email_admin_subject' );
WPF()->settings->form_field( 'email', 'wp_new_user_notification_email_admin_message' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-admin-users"></span> <?php _e( 'New User Registration Email for User', 'wpforo' ) ?>
        <p class="wpf-desc" style="padding: 5px 2px 0;"><?php _e( 'This message comes when new user registers to site', 'wpforo' ) ?></p>
    </div>
<?php
WPF()->settings->form_field( 'email', 'overwrite_new_user_notification' );
WPF()->settings->form_field( 'email', 'wp_new_user_notification_email_subject' );
WPF()->settings->form_field( 'email', 'wp_new_user_notification_email_message' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-admin-users"></span> <?php _e( 'Reset Password Emails', 'wpforo' ) ?>
        <p class="wpf-desc" style="padding: 5px 2px 0;"><?php _e( 'This message comes from Reset Password form.', 'wpforo' ) ?></p>
    </div>
<?php
WPF()->settings->form_field( 'email', 'overwrite_reset_password_email' );
WPF()->settings->form_field( 'email', 'reset_password_email_message' );
