<?php

namespace wpforo\widgets;

use WP_Widget;

class Forums extends WP_Widget {
	function __construct() {
		parent::__construct( 'wpforo_forums', 'wpForo Forums', [ 'description' => 'Forum tree.' ] );
	}

	public function widget( $args, $instance ) {
		//		wp_enqueue_script('wpforo-widgets-js');

		echo $args['before_widget'];//This is an HTML content//
		echo '<div id="wpf-widget-forums" class="wpforo-widget-wrap">';
		if( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];//This is an HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		WPF()->forum->tree( 'front_list' );
		echo '</div>';
		echo '</div>';
		echo $args['after_widget'];//This is an HTML content//
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Forums';
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
