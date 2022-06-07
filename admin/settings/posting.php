<?php if( ! defined( "ABSPATH" ) ) exit(); ?>

    <input type="hidden" name="wpfaction" value="posting_settings_save">

<?php
WPF()->settings->header( 'posting' );
WPF()->settings->form_field( 'posting', 'qa_display_answer_editor' );
WPF()->settings->form_field( 'posting', 'qa_comments_rich_editor' );
WPF()->settings->form_field( 'posting', 'threaded_reply_rich_editor' );
WPF()->settings->form_field( 'posting', 'topic_title_min_length' );
WPF()->settings->form_field( 'posting', 'topic_title_max_length' );
WPF()->settings->form_field( 'posting', 'topic_body_min_length' );
WPF()->settings->form_field( 'posting', 'topic_body_max_length' );
WPF()->settings->form_field( 'posting', 'post_body_min_length' );
WPF()->settings->form_field( 'posting', 'post_body_max_length' );
WPF()->settings->form_field( 'posting', 'comment_body_min_length' );
WPF()->settings->form_field( 'posting', 'comment_body_max_length' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-edit"></span> <?php _e( 'Edit and Delete', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'posting', 'edit_own_topic_durr' );
WPF()->settings->form_field( 'posting', 'delete_own_topic_durr' );
WPF()->settings->form_field( 'posting', 'edit_own_post_durr' );
WPF()->settings->form_field( 'posting', 'delete_own_post_durr' );
WPF()->settings->form_field( 'posting', 'edit_topic' );
WPF()->settings->form_field( 'posting', 'edit_post' );
WPF()->settings->form_field( 'posting', 'edit_log_display_limit' );
?>

<?php if( wpforo_is_module_enabled( 'revisions' ) ) : ?>
    <div class="wpf-subtitle">
        <span class="dashicons dashicons-schedule"></span> <?php _e( 'Post Preview and Auto Drafting', 'wpforo' ) ?>
    </div>

    <?php
    WPF()->settings->form_field( 'posting', 'is_preview_on' );
    WPF()->settings->form_field( 'posting', 'is_draft_on' );
    WPF()->settings->form_field( 'posting', 'auto_draft_interval' );
    WPF()->settings->form_field( 'posting', 'max_drafts_per_page' );
    ?>
<?php endif ?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-paperclip"></span> <?php _e( 'Attachments', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'posting', 'max_upload_size' );
WPF()->settings->form_field( 'posting', 'attachs_to_medialib' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-editor-kitchensink"></span> <?php _e( 'Rich Editor Toolbar Location', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'posting', 'topic_editor_toolbar_location' );
WPF()->settings->form_field( 'posting', 'reply_editor_toolbar_location' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-shortcode"></span> <?php _e( 'Apply Shortcodes', 'wpforo' ) ?>
    </div>

<?php WPF()->settings->form_field( 'posting', 'content_do_shortcode' ); ?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-text"></span> <?php _e( 'Post Content', 'wpforo' ) ?>
    </div>

<?php WPF()->settings->form_field( 'posting', 'extra_html_tags' );
