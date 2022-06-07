<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="rss_settings_save">

<?php
WPF()->settings->header( 'rss' );
WPF()->settings->form_field( 'rss', 'feed' );
// @TODO
WPF()->settings->form_field( 'rss', 'feed_general' );
WPF()->settings->form_field( 'rss', 'feed_forum' );
WPF()->settings->form_field( 'rss', 'feed_topic' );
