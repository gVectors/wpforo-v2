<?php if( ! defined( "ABSPATH" ) ) exit(); ?>

    <input type="hidden" name="wpfaction" value="topics_settings_save">

<?php WPF()->settings->header( 'topics' ); ?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-screenoptions"></span> <?php _e( 'Extended Forum Layout', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'topics', 'layout_extended_intro_posts_toggle' );
WPF()->settings->form_field( 'topics', 'layout_extended_intro_posts_count' );
WPF()->settings->form_field( 'topics', 'layout_extended_intro_posts_length' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-screenoptions"></span> <?php _e( 'Q&A Forum Layout', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'topics', 'layout_qa_posts_per_page' );
WPF()->settings->form_field( 'topics', 'layout_qa_comments_limit_count' );
WPF()->settings->form_field( 'topics', 'layout_qa_first_post_reply' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-screenoptions"></span> <?php _e( 'Threaded Forum Layout', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'topics', 'layout_threaded_posts_per_page' );
WPF()->settings->form_field( 'topics', 'layout_threaded_nesting_level' );
WPF()->settings->form_field( 'topics', 'layout_threaded_first_post_reply' );
?>

    <div class="wpf-subtitle">
        <span class="dashicons dashicons-admin-generic"></span> <?php _e( 'General', 'wpforo' ) ?>
    </div>

<?php
WPF()->settings->form_field( 'topics', 'topics_per_page' );
WPF()->settings->form_field( 'topics', 'posts_per_page' );
WPF()->settings->form_field( 'topics', 'search_max_results' );
?>

<?php $union_first_post = WPF()->settings->info->core['topics']['options']['union_first_post'] ?>
    <div class="wpf-opt-row" data-wpf-opt="union_first_post">
        <div class="wpf-opt-name">
            <label><?php echo esc_html( $union_first_post["label"] ) ?></label>
            <p class="wpf-desc"><?php echo esc_html( $union_first_post["description"] ) ?></p>
        </div>
        <div class="wpf-opt-input">
            <table style="margin-left: -1px;">
                <tbody>
				<?php if( $layouts = WPF()->tpl->find_layouts() ) : ?>
                    <tr>
                        <td>
                            <table style="margin-left: -1px;">
								<?php
								foreach( $layouts as $layout ) :
									$name = sprintf( 'topics[union_first_post][%1$d]', $layout['id'] );
									$value = WPF()->post->get_option_union_first_post( $layout['id'] );
									?>
                                    <tr style="background-color: transparent;">
                                        <td style="border-bottom: 1px dashed #aaaaaa; padding: 8px 0;">
                                            <div class="wpf-switch-field">
                                                <input type="radio" value="1" name="<?php echo $name ?>" id="<?php echo sanitize_title( $name ) ?>_1" <?php checked( $value ) ?>><label for="<?php echo sanitize_title( $name ) ?>_1"><?php _e( 'Yes', 'wpforo' ); ?></label> &nbsp;
                                                <input type="radio" value="0" name="<?php echo $name ?>" id="<?php echo sanitize_title( $name ) ?>_0" <?php checked( $value, false ) ?>><label for="<?php echo sanitize_title( $name ) ?>_0"><?php _e( 'No', 'wpforo' ); ?></label>
                                            </div>
                                        </td>
                                        <th style="border-bottom: 1px dashed #aaaaaa; width: auto;"><label style="font-weight: normal;"><?php echo $layout['name'] ?></label></th>
                                    </tr>
								<?php endforeach; ?>
                            </table>
                        </td>
                    </tr>
				<?php endif; ?>
                </tbody>
            </table>
        </div>
		<?php echo WPF()->settings->get_doc_link( $union_first_post ) ?>
    </div>
    <!-- Option end -->

<?php
WPF()->settings->form_field( 'topics', 'recent_posts_type' );
WPF()->settings->form_field( 'topics', 'topic_head' );
WPF()->settings->form_field( 'topics', 'topic_head_expanded' );
