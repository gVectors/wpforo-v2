<?php if( ! defined( "ABSPATH" ) ) exit(); ?>

    <input type="hidden" name="wpfaction" value="seo_settings_save">

<?php
WPF()->settings->header( 'seo' );
WPF()->settings->form_field( 'seo', 'seo_title' );
WPF()->settings->form_field( 'seo', 'seo_meta' );
WPF()->settings->form_field( 'seo', 'seo_profile' );
WPF()->settings->form_field( 'seo', 'forums_sitemap' );
WPF()->settings->form_field( 'seo', 'topics_sitemap' );
WPF()->settings->form_field( 'seo', 'members_sitemap' );
WPF()->settings->form_field( 'seo', 'dofollow' );
WPF()->settings->form_field( 'seo', 'noindex' );
