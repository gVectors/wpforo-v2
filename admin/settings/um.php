<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="um_settings_save">

<?php
WPF()->settings->header( 'um' );
WPF()->settings->form_field( 'um', 'notification' );
WPF()->settings->form_field( 'um', 'forum_tab' );
