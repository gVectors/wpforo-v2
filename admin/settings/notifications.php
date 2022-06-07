<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="notifications_settings_save">

<?php
WPF()->settings->header( 'notifications' );
WPF()->settings->form_field( 'notifications', 'notifications' );
WPF()->settings->form_field( 'notifications', 'notifications_live' );
WPF()->settings->form_field( 'notifications', 'notifications_bar' );



