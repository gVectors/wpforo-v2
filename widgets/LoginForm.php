<?php

namespace wpforo\widgets;

use WP_Widget;

class LoginForm extends WP_Widget {
	function __construct() {
		parent::__construct( 'wpforo_login_form', 'wpForo Login Form', [ 'description' => 'wpForo login form' ] );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget']; //This is an HTML content//
		echo '<div id="wpf-widget-login" class="wpforo-widget-wrap">';
		if( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title']; //This is an HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		?>

		<?php
		echo '</div></div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : 'Account';
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
