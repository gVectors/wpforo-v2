<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="forums_settings_save">

<?php WPF()->settings->header( 'forums' ); ?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-screenoptions"></span> <?php _e( 'Extended Forum Layout', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'forums', 'layout_extended_intro_topics_toggle' );
WPF()->settings->form_field( 'forums', 'layout_extended_intro_topics_count' );
WPF()->settings->form_field( 'forums', 'layout_extended_intro_topics_length' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-screenoptions"></span> <?php _e( 'Simplified Forum Layout', 'wpforo' ) ?>
    </div>
<?php
WPF()->settings->form_field( 'forums', 'layout_simplified_add_topic_button' ); //@TODO / this is a new option. need to add in core
?>
    <div class="wpf-subtitle">
        <span class="dashicons dashicons-screenoptions"></span> <?php _e( 'Q&A Forum Layout', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'forums', 'layout_qa_intro_topics_toggle' );
WPF()->settings->form_field( 'forums', 'layout_qa_intro_topics_count' );
WPF()->settings->form_field( 'forums', 'layout_qa_intro_topics_length' );
WPF()->settings->form_field( 'forums', 'layout_qa_add_topic_button' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-screenoptions"></span> <?php _e( 'Threaded Forum Layout', 'wpforo' ) ?>
    </div>
<?php
WPF()->settings->form_field( 'forums', 'layout_threaded_intro_topics_toggle' );
WPF()->settings->form_field( 'forums', 'layout_threaded_display_subforums' );
//WPF()->settings->form_field( 'forums', 'layout_threaded_filter_buttons' );
WPF()->settings->form_field( 'forums', 'layout_threaded_intro_topics_count' );
WPF()->settings->form_field( 'forums', 'layout_threaded_intro_topics_length' );
WPF()->settings->form_field( 'forums', 'layout_threaded_add_topic_button' );
