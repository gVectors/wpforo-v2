<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
?>

<div class="wpfl-3 wpforo-section">
	<div class="wpforo-topic-head">
        <div class="head-stat"><?php wpforo_phrase('Votes & Answers') ?></div>
        <div class="head-title"><?php wpforo_phrase('Question') ?></div>
	    <div class="head-status"><?php wpforo_phrase('Question Status') ?></div>
	</div>

	<?php foreach($topics as $key => $topic) : ?>

		<?php
			$member = wpforo_member($topic);
			if(isset($topic['last_post']) && $topic['last_post']){
				$last_post = wpforo_post($topic['last_post']);
				$last_poster = wpforo_member($last_post);
			}
			$topic_url = wpforo_topic($topic['topicid'], 'url');
		?>
      <div class="topic-wrap <?php wpforo_unread($topic['topicid'], 'topic') ?>">
          <div class="wpforo-topic">
              <div class="wpforo-topic-stat wpfcl-2">
                  <div class="wpf-tbox votes <?php if( $topic['answers'] > 0) echo "wpfcl-4" ?>" wpf-tooltip="<?php wpforo_phrase('Votes') ?>" wpf-tooltip-position="top" wpf-tooltip-size="small">
                      <div class="wpforo-label">
                          <svg class="<?php if( $topic['answers'] > 0) echo "wpfcl-4" ?>" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.873,11.021H5.127a2.126,2.126,0,0,1-1.568-3.56L10.046.872a2.669,2.669,0,0,1,3.939.034l6.431,6.528a2.126,2.126,0,0,1-1.543,3.587ZM5.011,8.837a.115.115,0,0,0,0,.109.111.111,0,0,0,.114.075H18.873a.111.111,0,0,0,.114-.075.109.109,0,0,0-.022-.135L12.528,2.276A.7.7,0,0,0,12,2.021a.664.664,0,0,0-.5.221L5.01,8.838ZM12,24.011a2.667,2.667,0,0,1-1.985-.887L3.584,16.6a2.125,2.125,0,0,1,1.543-3.586H18.873a2.125,2.125,0,0,1,1.568,3.558l-6.487,6.589A2.641,2.641,0,0,1,12,24.011Zm-6.873-9a.125.125,0,0,0-.092.209l6.437,6.534a.7.7,0,0,0,.528.257.665.665,0,0,0,.5-.223l6.493-6.6h0a.112.112,0,0,0,0-.108.111.111,0,0,0-.114-.074Z"/></svg>
                      </div>
                      <div class="count"><?php echo intval($topic['votes']) ?></div>
                  </div>
                  <div class="wpf-tbox answers <?php if( $topic['answers'] > 0) echo "wpfcl-5" ?>" wpf-tooltip="<?php wpforo_phrase('Answers') ?>" wpf-tooltip-position="top" wpf-tooltip-size="small">
                      <div class="wpforo-label">
                          <svg  class="<?php if( $topic['answers'] > 0) echo "wpfcl-5" ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12.0867962,18 L6,21.8042476 L6,18 L4,18 C2.8954305,18 2,17.1045695 2,16 L2,4 C2,2.8954305 2.8954305,2 4,2 L20,2 C21.1045695,2 22,2.8954305 22,4 L22,16 C22,17.1045695 21.1045695,18 20,18 L12.0867962,18 Z M8,18.1957524 L11.5132038,16 L20,16 L20,4 L4,4 L4,16 L8,16 L8,18.1957524 Z M11,10.5857864 L15.2928932,6.29289322 L16.7071068,7.70710678 L11,13.4142136 L7.29289322,9.70710678 L8.70710678,8.29289322 L11,10.5857864 Z" fill-rule="evenodd"/></svg>
                      </div>
                      <div class="count">
                          <?php echo intval($topic['answers']) ?>
                      </div>
                  </div>
                  <div class="wpf-tbox views" wpf-tooltip="<?php wpforo_phrase('Views') ?>" wpf-tooltip-position="top" wpf-tooltip-size="small">
                      <div class="wpforo-label">
                          <svg enable-background="new 0 0 32 32" version="1.1" viewBox="0 0 32 32" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><polyline fill="none" points="   649,137.999 675,137.999 675,155.999 661,155.999  " stroke="#FFFFFF" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2"/><polyline fill="none" points="   653,155.999 649,155.999 649,141.999  " stroke="#FFFFFF" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2"/><polyline fill="none" points="   661,156 653,162 653,156  " stroke="#FFFFFF" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2"/></g><g><g><path d="M16,25c-4.265,0-8.301-1.807-11.367-5.088c-0.377-0.403-0.355-1.036,0.048-1.413c0.404-0.377,1.036-0.355,1.414,0.048    C8.778,21.419,12.295,23,16,23c4.763,0,9.149-2.605,11.84-7c-2.69-4.395-7.077-7-11.84-7c-4.938,0-9.472,2.801-12.13,7.493    c-0.272,0.481-0.884,0.651-1.363,0.377c-0.481-0.272-0.649-0.882-0.377-1.363C5.147,10.18,10.333,7,16,7    c5.668,0,10.853,3.18,13.87,8.507c0.173,0.306,0.173,0.68,0,0.985C26.853,21.819,21.668,25,16,25z"/></g><g><path d="M16,21c-2.757,0-5-2.243-5-5s2.243-5,5-5s5,2.243,5,5S18.757,21,16,21z M16,13c-1.654,0-3,1.346-3,3s1.346,3,3,3    s3-1.346,3-3S17.654,13,16,13z"/></g></g></svg>
                      </div>
                      <div class="count">
                          <?php echo intval($topic['views']) ?>
                      </div>
                  </div>
              </div>
              <div class="wpforo-topic-avatar">
                  <?php if( WPF()->usergroup->can('va') && wpforo_setting( 'profiles', 'avatars' ) ): ?>
                      <?php echo WPF()->member->avatar($member, 'alt="'.esc_attr($member['display_name']).'"', 60) ?>
                  <?php endif; ?>
              </div>
              <div class="wpforo-topic-details">
                  <?php wpforo_topic_title($topic, $topic_url, '{p}{au}{tc}{/a}{n}', true, 'wpforo-topic-title', wpforo_setting( 'forums', 'layout_qa_intro_topics_length' )) ?>
                  <div class="wpforo-topic-author">
                      <span class="wpforo-topic-info wpfcl-2">
                        <?php wpforo_member_link($member, 'by'); ?>, <?php wpforo_date($topic['modified']); ?>
                      </span>
                  </div>
                  <div class="wpforo-topic-bottom">
                        <span class="wpforo-topic-replies wpfcl-2">
                            <i class="fas fa-reply fa-rotate-180"></i> <?php wpforo_phrase('replies', true, 'lower') ?> <?php echo intval($topic['posts']) ?>
                        </span>
                  </div>
              </div>
              <div class="wpforo-topic-status wpfcl-2 <?php if($topic['type']) echo "wpfbr-l-10" ?> <?php if( $topic['solved'] && !$topic['type']) echo "wpfbr-l-8" ?>">
                  <?php echo str_replace( 'fa-check-circle', 'fas fa-square-check', wpforo_topic_icon($topic, 'all', true, false)) ?>
              </div>
          </div><!-- wpforo-topic -->
      </div><!-- topic-wrap -->

	  <?php do_action( 'wpforo_loop_hook', $key ) ?>

    <?php endforeach; ?>
</div><!-- topic-wrap -->
