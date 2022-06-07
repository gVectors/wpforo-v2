<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="members_settings_save">

<?php
WPF()->settings->header( 'members' );
WPF()->settings->form_field( 'members', 'search_type' );
WPF()->settings->form_field( 'members', 'hide_inactive' );
WPF()->settings->form_field( 'members', 'members_per_page' );
WPF()->settings->form_field( 'members', 'list_order' );
