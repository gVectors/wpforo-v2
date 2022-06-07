<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="recaptcha_settings_save">

<?php
WPF()->settings->header( 'recaptcha' );
WPF()->settings->form_field( 'recaptcha', 'site_key_secret_key' );
WPF()->settings->form_field( 'recaptcha', 'theme' );
WPF()->settings->form_field( 'recaptcha', 'topic_editor' );
WPF()->settings->form_field( 'recaptcha', 'post_editor' );
WPF()->settings->form_field( 'recaptcha', 'wpf_login_form' );
WPF()->settings->form_field( 'recaptcha', 'wpf_reg_form' );
WPF()->settings->form_field( 'recaptcha', 'wpf_lostpass_form' );
WPF()->settings->form_field( 'recaptcha', 'login_form' );
WPF()->settings->form_field( 'recaptcha', 'reg_form' );
WPF()->settings->form_field( 'recaptcha', 'lostpass_form' );
