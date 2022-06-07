<?php

namespace wpforo\widgets;

use WP_Widget;

class Search extends WP_Widget {
	function __construct() {
		parent::__construct( 'wpforo_search', 'wpForo Search', [ 'description' => 'wpForo search form' ] );
	}

	public function widget( $args, $instance ) {
		//wp_enqueue_script('wpforo-widgets-js');

		echo $args['before_widget']; //This is an HTML content//
		echo '<div id="wpf-widget-search" class="wpforo-widget-wrap">';
		if( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title']; //This is an HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		?>
        <form action="<?php echo wpforo_home_url() ?>" method="get" id="wpforo-search-form">
			<?php wpforo_make_hidden_fields_from_url( wpforo_home_url() ) ?>
            <label class="wpf-search-widget-label">
                <input type="text" placeholder="<?php wpforo_phrase( 'Search...' ) ?>" name="wpfs" class="wpfw-100" value="<?php echo isset( $_GET['wpfs'] ) ? esc_attr( sanitize_text_field( $_GET['wpfs'] ) ) : '' ?>">
                <svg onclick="document.getElementById('wpforo-search-form').submit();" version="1.1" viewBox="0 0 16 16" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Guide"/><g id="Layer_2"><path d="M13.85,13.15l-2.69-2.69c0.74-0.9,1.2-2.03,1.2-3.28C12.37,4.33,10.04,2,7.18,2S2,4.33,2,7.18s2.33,5.18,5.18,5.18   c1.25,0,2.38-0.46,3.28-1.2l2.69,2.69c0.1,0.1,0.23,0.15,0.35,0.15s0.26-0.05,0.35-0.15C14.05,13.66,14.05,13.34,13.85,13.15z    M3,7.18C3,4.88,4.88,3,7.18,3s4.18,1.88,4.18,4.18s-1.88,4.18-4.18,4.18S3,9.49,3,7.18z"/></g></svg>
            </label>
        </form>
		<?php
		echo '</div></div>';
		echo $args['after_widget']; //This is an HTML content//
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : 'Forum Search';
		?>
        <p>
            <label><?php _e( 'Title', 'wpforo' ); ?>:</label>
            <label>
                <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                       value="<?php echo esc_attr( $title ); ?>">
            </label>
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = [];
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}
