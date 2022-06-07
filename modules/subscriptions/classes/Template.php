<?php

namespace wpforo\modules\subscriptions\classes;

class Template {
	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_action( 'wpforo_template_profile_subscriptions_head_bar', [ $this, 'manager_form' ] );
		add_action( 'wpforo_template_forum_head_bar_action_links',   function(){ echo $this->forum_subscribe_link(); } );
		add_action( 'wpforo_template_topic_head_bar_action_links',   function(){ echo $this->forum_subscribe_link(); } );
		add_action( 'wpforo_template_post_head_bar_action_links',    function(){ echo $this->topic_subscribe_link(); } );
        add_action( 'wpforo_editor_topic_submit_before', function( $forum, $values ){
	        if( !$values ) echo $this->topic_form_checkbox( $forum );
        }, 10, 2 );
		add_action( 'wpforo_editor_post_submit_before', function( $topic, $values, $forum ){
			if( !$values ){
				$layout = $topic['layout'] = WPF()->forum->get_layout( $forum );
				echo $this->post_form_checkbox( $topic, $layout );
			}
		}, 10, 3 );
		add_action( 'wpforo_portable_editor_post_submit_before', function( $topic, $values, $forum ){
			if( !$values ){
				$layout = $topic['layout'] = WPF()->forum->get_layout( $forum );
				echo $this->post_form_checkbox( $topic, $layout );
			}
		}, 10, 3);
	}

	public function manager_form( $userid = 0 ) {
		if(
            (
                ! WPF()->current_object['user_is_same_current_user']
                && ! wpforo_current_user_is( 'admin' )
            ) || (
                wpforo_setting( 'subscriptions', 'subscribe_confirmation' )
                && ! wpforo_current_user_is( 'admin' )
                && ! WPF()->sbscrb->is_email_confirmed()
            )
        ) return;
		$userid = intval( $userid );
		if( ! $userid ) {
			$userid = WPF()->current_object['userid'];
			if( ! $userid ) $userid = WPF()->current_userid;
		}
		$sbs               = [];
		$allposts_checked  = '';
		$alltopics_checked = '';

		if( WPF()->sbscrb->get_subscribes( [ 'type' => 'forums-topics', 'userid' => $userid, 'active' => null ] ) ) {
			$allposts_checked = ' checked';
		}
		if( WPF()->sbscrb->get_subscribes( [ 'type' => 'forums', 'userid' => $userid, 'active' => null ] ) ) {
			$alltopics_checked = ' checked';
		}

		if( ! $allposts_checked && ! $alltopics_checked ) {
			if( $sbs_forum = WPF()->sbscrb->get_subscribes( [ 'type' => 'forum', 'userid' => $userid, 'active' => null ] ) ) {
				foreach( $sbs_forum as $s ) $sbs[ $s['itemid'] ] = $s['type'];
			}
			if( $sbs_forum_topic = WPF()->sbscrb->get_subscribes( [ 'type' => 'forum-topic', 'userid' => $userid, 'active' => null ] ) ) {
				foreach( $sbs_forum_topic as $s ) $sbs[ $s['itemid'] ] = $s['type'];
			}
		}
		?>
		<script type="text/javascript">
            jQuery(document).ready(function ($) {
                if ($('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').length === $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]:checked').length) {
                    $('#wpf_sbs_allposts').prop('checked', true)
                }
                if ($('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').length === $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]:checked').length) {
                    $('#wpf_sbs_alltopics').prop('checked', true)
                }
                if ($('#wpf_sbs_allposts').is(':checked')) {
                    $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').prop('checked', true)
                }
                if ($('#wpf_sbs_alltopics').is(':checked')) {
                    $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').prop('checked', true)
                }
                var wpforo_wrap = $('#wpforo-wrap')
                wpforo_wrap.on('change', '#wpf_sbs_allposts', function () {
                    var stat = $(this).is(':checked')
                    $('#wpf_sbs_alltopics').prop('checked', false)
                    $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').prop('checked', stat)
                    if (stat) $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').prop('checked', !stat)
                })
                wpforo_wrap.on('change', '#wpf_sbs_alltopics', function () {
                    var stat = $(this).is(':checked')
                    $('#wpf_sbs_allposts').prop('checked', false)
                    $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').prop('checked', stat)
                    if (stat) $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').prop('checked', !stat)
                })
                wpforo_wrap.on('change', '#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]', function () {
                    var stat = $(this).is(':checked')
                    $('#wpf_sbs_allposts,#wpf_sbs_alltopics').prop('checked', false)
                    if (stat) {
                        if ($('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').length === $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]:checked').length) {
                            $('#wpf_sbs_allposts').prop('checked', true)
                        }
                        $(this).siblings('input[id^="wpf_sbs_alltopics_"]').prop('checked', false)
                    }
                })
                wpforo_wrap.on('change', '#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]', function () {
                    var stat = $(this).is(':checked')
                    $('#wpf_sbs_allposts,#wpf_sbs_alltopics').prop('checked', false)
                    if (stat) {
                        if ($('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').length === $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]:checked').length) {
                            $('#wpf_sbs_alltopics').prop('checked', true)
                        }
                        $(this).siblings('input[id^="wpf_sbs_allposts_"]').prop('checked', false)
                    }
                })
            })
		</script>
		<div id="wpf_subscription_tools" class="wpf-tools">
			<p class="wpf-sbs-head"><?php wpforo_phrase( 'Subscription Manager' ) ?></p>
			<form id="wpf_sbs_form" method="post" enctype="multipart/form-data" action="">
				<input type="hidden" name="wpfaction" value="subscribe_manager">
				<input type="hidden" name="wpforo[userid]" value="<?php echo $userid ?>">
				<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
				<div class="wpf-sbs-bulk">
					<div class="wpf-sbs-bulk-posts">
						<input id="wpf_sbs_allposts" type="checkbox" name="wpforo[check_all]" value="forums-topics" <?php echo $allposts_checked ?>>
						<label for="wpf_sbs_allposts"><?php wpforo_phrase( 'Subscribe to all new topics and posts' ) ?></label>
					</div>
					<div class="wpf-sbs-bulk-topics">
						<input id="wpf_sbs_alltopics" type="checkbox" name="wpforo[check_all]" value="forums" <?php echo $alltopics_checked ?>>
						<label for="wpf_sbs_alltopics"><?php wpforo_phrase( 'Subscribe to all new topics' ) ?></label>
					</div>
				</div>
				<div class="wpf-sbs-bulk-options">
					<ul>
						<?php WPF()->forum->tree( 'subscribe_manager_form', false, $sbs ); ?>
					</ul>
				</div>
				<div class="wpf-sbs-tool-foot">
					<input type="submit" value="<?php wpforo_phrase( 'Update Subscriptions' ) ?>">
				</div>
			</form>
		</div>
		<?php
	}

	public function forum_subscribe_link() {
		if( WPF()->current_userid || WPF()->current_user_email ) {
			if( WPF()->current_object['forumid'] && WPF()->perm->forum_can( 'sb', WPF()->current_object['forumid'] ) ) {
				$args = [
					"userid"     => WPF()->current_userid,
					"itemid"     => WPF()->current_object['forumid'],
					"type"       => "forum",
					'user_email' => WPF()->current_user_email,
				];
				if( WPF()->sbscrb->get_subscribe( $args ) ) {
					return sprintf(
						'<span class="wpf-unsubscribe-forum wpf-button-outlined" data-type="forum" data-itemid="%1$d">%2$s</span>',
						WPF()->current_object['forumid'],
						wpforo_phrase( 'Unsubscribe', false )
					);
				}

				return sprintf(
					'<span class="wpf-subscribe-forum wpf-button-outlined wpfcl-5" data-type="forum" data-itemid="%1$d"><i class="far fa-envelope wpfcl-5"></i> %2$s</span>',
					WPF()->current_object['forumid'],
					wpforo_phrase( 'Subscribe for new topics', false )
				);
			}
		}

		return '';
	}

	public function topic_subscribe_link() {
		if( WPF()->current_userid || WPF()->current_user_email ) {
			if( WPF()->current_object['forumid'] && WPF()->perm->forum_can( 'sb', WPF()->current_object['forumid'] ) ) {
				$args = [
					"userid"     => WPF()->current_userid,
					"itemid"     => WPF()->current_object['topicid'],
					"type"       => "topic",
					'user_email' => WPF()->current_user_email,
				];
				if( WPF()->sbscrb->get_subscribe( $args ) ) {
					return sprintf(
						'<span class="wpf-unsubscribe-topic wpf-button-outlined" data-type="topic" data-itemid="%1$d">%2$s</span>',
						WPF()->current_object['topicid'],
						wpforo_phrase( 'Unsubscribe', false )
					);
				}

				return sprintf(
					'<span class="wpf-subscribe-topic wpf-button-outlined wpfcl-5" data-type="topic" data-itemid="%1$d"><i class="far fa-envelope"></i> %2$s</span>',
					WPF()->current_object['topicid'],
					wpforo_phrase( 'Subscribe for new replies', false )
				);
			}
		}

		return '';
	}

	public function topic_form_checkbox( $forum ) {
		if( wpforo_setting( 'subscriptions', 'subscribe_checkbox_on_post_editor' ) && WPF()->perm->forum_can( 'sb', $forum['forumid'] ) ) {
			return sprintf(
				'<div class="wpf-topic-sbs">
	                    <input id="wpf-topic-sbs-%1$s" type="checkbox" name="wpforo_topic_subs" value="1" %2$s>&nbsp;
	                    <label for="wpf-topic-sbs-%1$s">%3$s</label>
	                </div>',
				uniqid(),
				wpforo_setting( 'subscriptions', 'subscribe_checkbox_default_status' ) ? 'checked' : '',
				WPF()->forum->get_layout( $forum ) === 3 ? wpforo_phrase( 'Subscribe to this question', false ) : wpforo_phrase( 'Subscribe to this topic', false )
			);
		}

		return '';
	}

	public function post_form_checkbox( $topic, $layout = 1 ) {
		if( wpforo_setting( 'subscriptions', 'subscribe_checkbox_on_post_editor' ) && WPF()->perm->forum_can( 'sb', $topic['forumid'] ) ) {
			$topic     = [ "userid" => WPF()->current_userid, "itemid" => intval( $topic['topicid'] ), "type" => "topic" ];
			$subscribe = WPF()->sbscrb->get_subscribe( $topic );
			if( ! isset( $subscribe['subid'] ) ) {
				return sprintf(
					'<div class="wpf-topic-sbs">
	                    <input id="wpf-topic-sbs-%1$s" type="checkbox" name="wpforo_topic_subs" value="1" %2$s>&nbsp;
	                    <label for="wpf-topic-sbs-%1$s">%3$s</label>
	                </div>',
					uniqid(),
					wpforo_setting( 'subscriptions', 'subscribe_checkbox_default_status' ) ? 'checked' : '',
					$layout === 3 ? wpforo_phrase( 'Subscribe to this question', false ) : wpforo_phrase( 'Subscribe to this topic', false )
				);
			}
		}

		return '';
	}
}
