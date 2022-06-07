<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="logging_settings_save">

<?php
WPF()->settings->header( 'logging' );
WPF()->settings->form_field( 'logging', 'view_logging' );
WPF()->settings->form_field( 'logging', 'track_logging' );
WPF()->settings->form_field( 'logging', 'goto_unread' );
WPF()->settings->form_field( 'logging', 'goto_unread_button' );
WPF()->settings->form_field( 'logging', 'display_forum_current_viewers' );
WPF()->settings->form_field( 'logging', 'display_topic_current_viewers' );
WPF()->settings->form_field( 'logging', 'display_recent_viewers' );
WPF()->settings->form_field( 'logging', 'display_admin_viewers' );
