<?php if( ! defined( "ABSPATH" ) ) exit() ?>

    <input type="hidden" name="wpfaction" value="rating_settings_save">

<?php
WPF()->settings->header( 'rating' );
WPF()->settings->form_field( 'rating', 'rating' );
WPF()->settings->form_field( 'rating', 'rating_title' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-star-filled"></span> <?php _e( 'Member Reputation and Titles', 'wpforo' ) ?>
    </div>

<?php WPF()->settings->form_field( 'rating', 'levels' ); ?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-calculator"></span> <?php _e( 'Reputation Point Counting', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'rating', 'topic_points' );
WPF()->settings->form_field( 'rating', 'post_points' );
WPF()->settings->form_field( 'rating', 'like_points' );
WPF()->settings->form_field( 'rating', 'dislike_points' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-awards"></span> <?php _e( 'Display Reputation Title and Badges', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'rating', 'rating_title_ug' );
WPF()->settings->form_field( 'rating', 'rating_badge_ug' );
