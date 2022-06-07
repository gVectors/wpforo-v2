<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="buddypress_settings_save">

<?php
WPF()->settings->header( 'buddypress' );
WPF()->settings->form_field( 'buddypress', 'activity' );
WPF()->settings->form_field( 'buddypress', 'notification' );
WPF()->settings->form_field( 'buddypress', 'forum_tab' );
