<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="components_settings_save">

<?php
WPF()->settings->header( 'components' );
WPF()->settings->form_field( 'components', 'admin_cp' );
WPF()->settings->form_field( 'components', 'page_title' );
WPF()->settings->form_field( 'components', 'top_bar' );
WPF()->settings->form_field( 'components', 'top_bar_search' );
WPF()->settings->form_field( 'components', 'breadcrumb' );
WPF()->settings->form_field( 'components', 'footer' );
WPF()->settings->form_field( 'components', 'footer_stat' );
WPF()->settings->form_field( 'components', 'copyright' );




