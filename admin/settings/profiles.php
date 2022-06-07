<?php if( ! defined( "ABSPATH" ) ) exit(); ?>

    <input type="hidden" name="wpfaction" value="profiles_settings_save">

<?php
WPF()->settings->header( 'profiles' );
WPF()->settings->form_field( 'profiles', 'profile' );
WPF()->settings->form_field( 'profiles', 'profile_header' );
WPF()->settings->form_field( 'profiles', 'profile_footer' );
WPF()->settings->form_field( 'profiles', 'url_structure' );
WPF()->settings->form_field( 'profiles', 'online_status_timeout' );
WPF()->settings->form_field( 'profiles', 'custom_title_is_on' );
WPF()->settings->form_field( 'profiles', 'default_title' );
WPF()->settings->form_field( 'profiles', 'title_groupids' );
WPF()->settings->form_field( 'profiles', 'title_secondary_groupids' );
WPF()->settings->form_field( 'profiles', 'mention_nicknames' );
WPF()->settings->form_field( 'profiles', 'avatars' );
WPF()->settings->form_field( 'profiles', 'custom_avatars' );
WPF()->settings->form_field( 'profiles', 'replace_avatar' );
WPF()->settings->form_field( 'profiles', 'signature' );
