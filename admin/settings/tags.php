<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="tags_settings_save">

<?php
WPF()->settings->header( 'tags' );
WPF()->settings->form_field( 'tags', 'max_per_topic' );
WPF()->settings->form_field( 'tags', 'per_page' );
WPF()->settings->form_field( 'tags', 'length' );
WPF()->settings->form_field( 'tags', 'suggest_limit' );
WPF()->settings->form_field( 'tags', 'lowercase' );
