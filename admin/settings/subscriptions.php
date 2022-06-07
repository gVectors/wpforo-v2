<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="subscriptions_settings_save">

<?php
WPF()->settings->header( 'subscriptions' );
WPF()->settings->form_field( 'subscriptions', 'subscribe_confirmation' );
WPF()->settings->form_field( 'subscriptions', 'subscribe_checkbox_on_post_editor' );
WPF()->settings->form_field( 'subscriptions', 'subscribe_checkbox_default_status' );
WPF()->settings->form_field( 'subscriptions', 'user_mention_notify' );
WPF()->settings->form_field( 'subscriptions', 'user_following_notify' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-email-alt" style="font-size: 22px;"></span>&nbsp; <?php _e( 'Email Template - Subscription Confirmation', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'subscriptions', 'confirmation_email_subject' );
WPF()->settings->form_field( 'subscriptions', 'confirmation_email_message' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-email-alt" style="font-size: 22px;"></span>&nbsp; <?php _e( 'Email Template - New Topic Notification', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'subscriptions', 'new_topic_notification_email_subject' );
WPF()->settings->form_field( 'subscriptions', 'new_topic_notification_email_message' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-email-alt" style="font-size: 22px;"></span>&nbsp; <?php _e( 'Email Template - New Reply Notification', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'subscriptions', 'new_post_notification_email_subject' );
WPF()->settings->form_field( 'subscriptions', 'new_post_notification_email_message' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-email-alt" style="font-size: 22px;"></span>&nbsp; <?php _e( 'Email Template - User Mentioning', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'subscriptions', 'user_mention_email_subject' );
WPF()->settings->form_field( 'subscriptions', 'user_mention_email_message' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-email-alt" style="font-size: 22px;"></span>&nbsp; <?php _e( 'Email Template - User Following', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'subscriptions', 'user_following_email_subject' );
WPF()->settings->form_field( 'subscriptions', 'user_following_email_message' );
