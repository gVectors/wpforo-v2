<?php

namespace wpforo\widgets;

use WP_Widget;

class Tags extends WP_Widget {
	function __construct() {
		parent::__construct( 'wpforo_tags', 'wpForo Topic Tags', [ 'description' => 'List of most popular tags' ] );
	}

	public function widget( $args, $instance ) {
		//	    wp_enqueue_script('wpforo-widgets-js');

		echo $args['before_widget'];
		echo '<div id="wpf-widget-tags" class="wpforo-widget-wrap">';
		if( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		$tag_args = [ 'row_count' => (int) wpfval( $instance, 'count' ) ];
		$tags     = WPF()->topic->get_tags( $tag_args, $items_count );
		echo '<div class="wpforo-widget-content">';
		if( ! empty( $tags ) ) {
			echo '<ul class="wpf-widget-tags">';
			foreach( $tags as $tag ) {
				$topic_count = ( wpfval( $instance, 'topics' ) ) ? '<span>' . $tag['count'] . '</span>' : '';
				echo '<li><a href="' . esc_url( wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag['tag'] ) . '" title="' . esc_attr( $tag['tag'] ) . '">' . wpforo_text( $tag['tag'], 25, false ) . '</a>' . $topic_count . '</li>';
			}
			echo '</ul>';
			if( $instance['count'] < $items_count ) {
				echo '<div class="wpf-all-tags"><a href="' . esc_url( wpforo_home_url( wpforo_settings_get_slug( 'tags' ) ) ) . '">' . sprintf( wpforo_phrase( 'View all tags (%d)', false ), $items_count ) . '</a></div>';
			}
		} else {
			echo '<p style="text-align:center">' . wpforo_phrase( 'No tags found', false ) . '</p>';
		}
		echo '</div>';
		echo '</div>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title  = ! empty( $instance['title'] ) ? $instance['title'] : 'Topic Tags';
		$topics = ! empty( $instance['topics'] ) ? $instance['topics'] : 1;
		$count  = ! empty( $instance['count'] ) ? $instance['count'] : '20';
		?>
        <p>
            <label><?php _e( 'Title', 'wpforo' ); ?>:</label>
            <label>
                <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
                       value="<?php echo esc_attr( $title ); ?>">
            </label>
        </p>
        <p>
            <label><?php _e( 'Topic Counts', 'wpforo' ); ?>:</label>&nbsp;&nbsp;&nbsp;&nbsp;
            <label><?php _e( 'Yes', 'wpforo' ); ?> <input type="radio"
                                                          name="<?php echo esc_attr( $this->get_field_name( 'topics' ) ); ?>"
                                                          value="1" <?php if( $topics ) echo 'checked="checked"' ?>></label>&nbsp;&nbsp;
            <label><?php _e( 'No', 'wpforo' ); ?> <input type="radio"
                                                         name="<?php echo esc_attr( $this->get_field_name( 'topics' ) ); ?>"
                                                         value="0" <?php if( ! $topics ) echo 'checked="checked"' ?>></label>
        </p>
        <p>
            <label><?php _e( 'Number of Items', 'wpforo' ); ?>:</label>&nbsp;
            <label>
                <input type="number" min="1" style="width: 53px;"
                       name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
                       value="<?php echo esc_attr( $count ); ?>">
            </label>
        </p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance           = [];
		$instance['title']  = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['topics'] = ( ! empty( $new_instance['topics'] ) ) ? intval( $new_instance['topics'] ) : 0;
		$instance['count']  = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : 0;

		return $instance;
	}
}
