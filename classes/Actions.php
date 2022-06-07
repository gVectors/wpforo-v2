<?php

namespace wpforo\classes;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit();

class Actions {
	/**
	 * wpForoAction constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * method for initializing all necessary hooks
	 */
	public function init_hooks() {
		add_action( 'wpforo_after_init_classes', function() { if( ! WPF()->is_installed() ) wpforo_activation(); }, 0 );

		add_action( 'wpforo_after_init',                              [ $this, 'do_actions' ], 999 );
		add_action( 'wpforo_action_user_delete',                      [ $this, 'user_delete' ] );
		add_action( 'deleted_user',                                   [ $this, 'deleted_user' ], 10, 2 );
		add_action( 'wp_ajax_wpforo_deactivate',                      [ $this, 'deactivate' ] );
		if( ! wpforo_is_admin() ) {
			add_action( 'wpforo_actions',                             [ $this, 'init_default_attach_hooks' ] );
			add_action( 'wpforo_actions',                             [ $this, 'init_wp_emoji_hooks' ] );

			add_action( 'wpforo_actions',                             [ $this, 'feed_rss2' ] );
			add_action( 'wpforo_actions',                             [ $this, 'ucf_file_delete' ] );
			add_action( 'wpforo_actions',                             [ $this, 'mark_all_read' ] );
			add_action( 'wpforo_actions',                             [ $this, 'mark_notification_read' ] );

			add_action( 'wpforo_action_registration',                 [ $this, 'registration' ] );
			add_action( 'wpforo_action_login',                        [ $this, 'login' ] );
			add_action( 'wpforo_action_lostpassword',                 [ $this, 'lostpassword' ] );
			add_action( 'wpforo_action_resetpassword_form',           [ $this, 'resetpassword_form' ] );
			add_action( 'wpforo_action_resetpassword',                [ $this, 'resetpassword' ] );
			add_action( 'wpforo_action_profile_update',               [ $this, 'profile_update' ] );
			add_action( 'wpforo_action_cantlogin_contact',            [ $this, 'cantlogin_contact' ] );

			add_action( 'wpforo_action_topic_add',                    [ $this, 'topic_add' ] );
			add_action( 'wpforo_action_topic_edit',                   [ $this, 'topic_edit' ] );
			add_action( 'wpforo_action_topic_move',                   [ $this, 'topic_move' ] );
			add_action( 'wpforo_action_topic_merge',                  [ $this, 'topic_merge' ] );
			add_action( 'wpforo_action_topic_split',                  [ $this, 'topic_split' ] );

			add_action( 'wpforo_action_post_add',                     [ $this, 'post_add' ] );
			add_action( 'wpforo_action_post_edit',                    [ $this, 'post_edit' ] );

			## ajax actions ##
			add_action( 'wp_ajax_wpforo_dissmiss_recaptcha_note',         [ $this, 'dissmiss_recaptcha_note' ] );
			add_action( 'wp_ajax_wpforo_acp_toggle',                      [ $this, 'acp_toggle' ] );
			add_action( 'wp_ajax_wpforo_clear_all_notifications',         [ $this, 'clear_all_notifications' ] );
			add_action( 'wp_ajax_wpforo_profile_cover_upload',            [ $this, 'profile_cover_upload' ] );
			add_action( 'wp_ajax_wpforo_get_topic_head_more_info',        [ $this, 'get_topic_head_more_info' ] );
			add_action( 'wp_ajax_nopriv_wpforo_get_topic_head_more_info', [ $this, 'get_topic_head_more_info' ] );
			add_action( 'wp_ajax_wpforo_get_topic_overview_chunk',        [ $this, 'get_topic_overview_chunk' ] );
			add_action( 'wp_ajax_nopriv_wpforo_get_topic_overview_chunk', [ $this, 'get_topic_overview_chunk' ] );
			add_action( 'wp_ajax_wpforo_get_overview',                    [ $this, 'get_overview' ] );
			add_action( 'wp_ajax_nopriv_wpforo_get_overview',             [ $this, 'get_overview' ] );
			add_action( 'wp_ajax_wpforo_user_ban',                        [ $this, 'user_ban_ajax' ] );
		} else {
			add_action( 'wpforo_actions',                             [ $this, 'check_dashboard_permissions' ], 1 );
			add_action( 'wpforo_actions',                             [ $this, 'repair_lost_main_shortcode_page' ] );

			add_action( 'wpforo_action_synch_user_profiles',          [ $this, 'synch_user_profiles' ] );
			add_action( 'wpforo_action_reset_user_cache',             [ $this, 'reset_user_cache' ] );
			add_action( 'wpforo_action_reset_forums_stats',           [ $this, 'reset_forums_stats' ] );
			add_action( 'wpforo_action_reset_topics_stats',           [ $this, 'reset_topics_stats' ] );
			add_action( 'wpforo_action_reset_users_stats',            [ $this, 'reset_users_stats' ] );
			add_action( 'wpforo_action_rebuild_threads',              [ $this, 'rebuild_threads' ] );
			add_action( 'wpforo_action_reset_phrase_cache',           [ $this, 'reset_phrase_cache' ] );
			add_action( 'wpforo_action_recrawl_phrases',              [ $this, 'recrawl_phrases' ] );
			add_action( 'wpforo_action_clean_up',                     [ $this, 'clean_up' ] );

			add_action( 'wpforo_action_base_slugs_settings_save',     [ $this, 'base_slugs_settings_save' ] );
			add_action( 'wpforo_action_general_settings_save',        [ $this, 'general_settings_save' ] );

			add_action( 'wpforo_action_slugs_settings_save',          [ $this, 'slugs_settings_save' ] );
			add_action( 'wpforo_action_board_settings_save',          [ $this, 'board_settings_save' ] );

			add_action( 'wpforo_action_akismet_settings_save',        [ $this, 'akismet_settings_save' ] );
			add_action( 'wpforo_action_antispam_settings_save',       [ $this, 'antispam_settings_save' ] );
			add_action( 'wpforo_action_authorization_settings_save',  [ $this, 'authorization_settings_save' ] );
			add_action( 'wpforo_action_buddypress_settings_save',     [ $this, 'buddypress_settings_save' ] );
			add_action( 'wpforo_action_components_settings_save',     [ $this, 'components_settings_save' ] );
			add_action( 'wpforo_action_email_settings_save',          [ $this, 'email_settings_save' ] );
			add_action( 'wpforo_action_forums_settings_save',         [ $this, 'forums_settings_save' ] );
			add_action( 'wpforo_action_logging_settings_save',        [ $this, 'logging_settings_save' ] );
			add_action( 'wpforo_action_members_settings_save',        [ $this, 'members_settings_save' ] );
			add_action( 'wpforo_action_notifications_settings_save',  [ $this, 'notifications_settings_save' ] );
			add_action( 'wpforo_action_posting_settings_save',        [ $this, 'posting_settings_save' ] );
			add_action( 'wpforo_action_profiles_settings_save',       [ $this, 'profiles_settings_save' ] );
			add_action( 'wpforo_action_rating_settings_save',         [ $this, 'rating_settings_save' ] );
			add_action( 'wpforo_action_recaptcha_settings_save',      [ $this, 'recaptcha_settings_save' ] );
			add_action( 'wpforo_action_rss_settings_save',            [ $this, 'rss_settings_save' ] );
			add_action( 'wpforo_action_seo_settings_save',            [ $this, 'seo_settings_save' ] );
			add_action( 'wpforo_action_social_settings_save',         [ $this, 'social_settings_save' ] );
			add_action( 'wpforo_action_styles_settings_save',         [ $this, 'styles_settings_save' ] );
			add_action( 'wpforo_action_tags_settings_save',           [ $this, 'tags_settings_save' ] );
			add_action( 'wpforo_action_topics_settings_save',         [ $this, 'topics_settings_save' ] );
			add_action( 'wpforo_action_um_settings_save',             [ $this, 'um_settings_save' ] );

			add_action( 'wpforo_action_board_add',                    [ $this, 'board_add' ] );
			add_action( 'wpforo_action_board_edit',                   [ $this, 'board_edit' ] );
			add_action( 'wpforo_action_board_delete',                 [ $this, 'board_delete' ] );

			add_action( 'wpforo_action_add_new_xml_translation',      [ $this, 'add_new_xml_translation' ] );
			add_action( 'wpforo_action_phrases_change_lang',          [ $this, 'phrases_change_lang' ] );
			add_action( 'wpforo_action_dashboard_options_save',       [ $this, 'dashboard_options_save' ] );
			add_action( 'wpforo_action_colors_css_download',          [ $this, 'colors_css_download' ] );
//			add_action( 'wpforo_action_cleanup_options_save',         [ $this, 'cleanup_options_save' ] );
			add_action( 'wpforo_action_misc_options_save',            [ $this, 'misc_options_save' ] );
			add_action( 'wpforo_action_legal_options_save',           [ $this, 'legal_options_save' ] );
			add_action( 'wpforo_action_delete_spam_file',             [ $this, 'delete_spam_file' ] );
			add_action( 'wpforo_action_delete_all_spam_files',        [ $this, 'delete_all_spam_files' ] );
			add_action( 'wpforo_action_database_update',              [ $this, 'database_update' ] );

			add_action( 'wpforo_action_forum_add',                    [ $this, 'forum_add' ] );
			add_action( 'wpforo_action_forum_edit',                   [ $this, 'forum_edit' ] );
			add_action( 'wpforo_action_forum_delete',                 [ $this, 'forum_delete' ] );
			add_action( 'wpforo_action_forum_hierarchy_save',         [ $this, 'forum_hierarchy_save' ] );

			add_action( 'wpforo_action_dashboard_post_unapprove',     [ $this, 'dashboard_post_unapprove' ] );
			add_action( 'wpforo_action_dashboard_post_approve',       [ $this, 'dashboard_post_approve' ] );
			add_action( 'wpforo_action_dashboard_post_delete',        [ $this, 'dashboard_post_delete' ] );
			add_action( 'wpforo_action_bulk_moderation',              [ $this, 'bulk_moderation' ] );

			add_action( 'wpforo_action_phrase_add',                   [ $this, 'phrase_add' ] );
			add_action( 'wpforo_action_phrase_edit_form',             [ $this, 'phrase_edit_form' ] );
			add_action( 'wpforo_action_phrase_edit',                  [ $this, 'phrase_edit' ] );

			add_action( 'wpforo_action_user_ban',                     [ $this, 'user_ban' ] );
			add_action( 'wpforo_action_user_unban',                   [ $this, 'user_unban' ] );
			add_action( 'wpforo_action_user_activate',                [ $this, 'user_activate' ] );
			add_action( 'wpforo_action_user_deactivate',              [ $this, 'user_deactivate' ] );
			add_action( 'wpforo_action_bulk_members',                 [ $this, 'bulk_members' ] );

			add_action( 'wpforo_action_usergroup_add',                [ $this, 'usergroup_add' ] );
			add_action( 'wpforo_action_usergroup_edit',               [ $this, 'usergroup_edit' ] );
			add_action( 'wpforo_action_usergroup_delete',             [ $this, 'usergroup_delete' ] );
			add_action( 'wpforo_action_default_groupid_change',       [ $this, 'default_groupid_change' ] );
			add_action( 'wpforo_action_usergroup_delete_form',        [ $this, 'usergroup_delete_form' ] );

			add_action( 'wpforo_action_access_add',                   [ $this, 'access_add' ] );
			add_action( 'wpforo_action_access_edit',                  [ $this, 'access_edit' ] );
			add_action( 'wpforo_action_access_delete',                [ $this, 'access_delete' ] );

			add_action( 'wpforo_action_theme_activate',               [ $this, 'theme_activate' ] );
			add_action( 'wpforo_action_theme_delete',                 [ $this, 'theme_delete' ] );

			add_action( 'wpforo_action_update_addons_css',            [ $this, 'update_addons_css' ] );
			add_action( 'wpforo_action_dissmiss_poll_version_is_old', [ $this, 'dissmiss_poll_version_is_old' ] );
		}
		add_action( 'wpforo_action_reset_all_caches',                 [ $this, 'reset_all_caches' ] );
	}

	/**
	 * wpforo main actions doing place
	 */
	public function do_actions() {
		do_action( 'wpforo_actions' );
		$wpforo_actions = array_unique( array_merge( (array) wpfval( $_POST, 'wpfaction' ), (array) wpfval( WPF()->GET, 'wpfaction' ) ) );
		if( ! empty( $wpforo_actions ) ) {
			foreach( $wpforo_actions as $wpforo_action ) {
				$wpforo_action = sanitize_title( $wpforo_action );
				do_action( "wpforo_action_{$wpforo_action}" );
			}
		}
		do_action( 'wpforo_actions_end' );
	}

	/**
	 * init wpforo default attachments system when wpforo advanced attachments addon has not exists
	 */
	public function init_default_attach_hooks() {
		add_action( 'delete_attachment', 'wpforo_delete_attachment', 10 );
		if( has_action( 'wpforo_topic_form_extra_fields_after', [ WPF()->tpl, 'add_default_attach_input' ] ) ) {
			add_filter( 'wpforo_add_topic_data_filter', 'wpforo_add_default_attachment' );
			add_filter( 'wpforo_edit_topic_data_filter', 'wpforo_add_default_attachment' );
			add_filter( 'wpforo_add_post_data_filter', 'wpforo_add_default_attachment' );
			add_filter( 'wpforo_edit_post_data_filter', 'wpforo_add_default_attachment' );
			add_filter( 'wpforo_body_text_filter', 'wpforo_default_attachments_filter' );
		}
	}

	/**
	 * init wp emojis when wpforo emoticons addon has not exists
	 */
	public function init_wp_emoji_hooks() {
		if( ! class_exists( 'wpForoSmiles' ) ) {
			add_filter( 'wpforo_body_text_filter', 'wp_encode_emoji', 9 );
			add_filter( 'wpforo_body_text_filter', 'convert_smilies' );
		}
	}

	/**
	 * get request_uri redirect to url with concatenation of &can_do=do
	 * @return bool true if you can do action now | false if you can not do action now
	 */
	private function can_do() {
		if( wpfval( $_GET, 'can_do' ) === 'do' ) return true;

		$refresh_url = preg_replace( '#&can_do=?[^=?&\r\n]*#isu', '', wpforo_get_request_uri() );
		$refresh_url .= '&can_do=do';
		header( "refresh:0.1;url=" . $refresh_url );

		add_filter( 'wpforo_admin_loading', '__return_true' );

		return false;
	}

	/**
	 * @return string $u_action return union bulk action
	 */
	private function get_current_bulk_action() {
		$u_action = '';
		if( ! empty( $_GET['action'] ) && $_GET['action'] !== '-1' ) {
			$u_action = sanitize_textarea_field( $_GET['action'] );
		} elseif( ! empty( $_GET['action2'] ) && $_GET['action2'] !== '-1' ) {
			$u_action = sanitize_textarea_field( $_GET['action2'] );
		}

		return $u_action;
	}

	/**
	 * catch if rss url show rss feed for given arguments
	 */
	public function feed_rss2() {
		if( wpfval( WPF()->GET, 'type' ) === 'rss2' ) {
			$forum_rss_items = apply_filters( 'wpforo_forum_feed_limit', 10 );
			$topic_rss_items = apply_filters( 'wpforo_topic_feed_limit', 10 );

			$forumid = intval( wpfval( WPF()->GET, 'forum' ) );
			if( ! $forumid ) {
				$forum             = [];
				$forum['forumurl'] = wpforo_home_url();
				$forum['title']    = '';
			} elseif( $forum = wpforo_forum( $forumid ) ) {
				$forum['forumurl'] = $forum['url'];
			}

			if( wpfval( WPF()->GET, 'topic' ) ) {
				$topicid = intval( WPF()->GET['topic'] );
				if( ! $topicid ) {
					$posts             = WPF()->post->get_posts( [
						                                             'row_count'     => $topic_rss_items,
						                                             'orderby'       => '`created` DESC, `postid` DESC',
						                                             'check_private' => true,
					                                             ] );
					$topic['title']    = '';
					$topic['topicurl'] = wpforo_home_url();
				} else {
					$topic             = wpforo_topic( $topicid );
					$topic['topicurl'] = ( wpfval( $topic, 'url' ) ) ? $topic['url'] : WPF()->topic->get_topic_url( $topicid );
					$posts             = WPF()->post->get_posts( [
						                                             'topicid'       => $topicid,
						                                             'row_count'     => $topic_rss_items,
						                                             'orderby'       => '`created` DESC, `postid` DESC',
						                                             'check_private' => true,
					                                             ] );
				}
				foreach( $posts as $key => $post ) {
					$member                       = wpforo_member( $post );
					$posts[ $key ]['description'] = wpforo_text( trim( strip_tags( $post['body'] ) ), 190, false );
					$posts[ $key ]['content']     = trim( $post['body'] );
					$posts[ $key ]['posturl']     = WPF()->post->get_post_url( $post['postid'] );
					$posts[ $key ]['author']      = $member['display_name'];
				}
				WPF()->feed->rss2_topic( $forum, $topic, $posts );
			} else {
				if( ! $forumid ) {
					$topics = WPF()->topic->get_topics( [
						                                    'row_count' => $forum_rss_items,
						                                    'orderby'   => 'created',
						                                    'order'     => 'DESC',
					                                    ] );
				} else {
					$topics = WPF()->topic->get_topics( [
						                                    'forumid'   => $forumid,
						                                    'row_count' => $forum_rss_items,
						                                    'orderby'   => 'created',
						                                    'order'     => 'DESC',
					                                    ] );
				}
				foreach( $topics as $key => $topic ) {
					$post                          = wpforo_post( $topic['first_postid'] );
					$member                        = wpforo_member( $topic );
					$topics[ $key ]['description'] = wpforo_text( trim( strip_tags( $post['body'] ) ), 190, false );
					$topics[ $key ]['content']     = trim( $post['body'] );
					$topics[ $key ]['topicurl']    = WPF()->topic->get_topic_url( $topic['topicid'] );
					$topics[ $key ]['author']      = $member['display_name'];
				}
				WPF()->feed->rss2_forum( $forum, $topics );
			}
			exit();
		}
	}

	/**
	 * ucf_file_delete delete /wp-content/uploads/UCFFILENAME
	 */
	public function ucf_file_delete() {
		if( wpfval( WPF()->GET, 'foro_f' ) && wpfval( WPF()->GET, 'foro_u' ) && wpfval( WPF()->GET, 'foro_n' ) ) {
			if( wp_verify_nonce( WPF()->GET['foro_n'], 'wpforo_delete_profile_field' ) ) {
				$userid = intval( WPF()->GET['foro_u'] );
				$field  = sanitize_title( WPF()->GET['foro_f'] );
				if( $file = WPF()->member->get_custom_field( $userid, $field ) ) {
					$file   = WPF()->folders['wp_upload']['dir'] . DIRECTORY_SEPARATOR . ltrim( wpforo_fix_dir_sep( $file ), DIRECTORY_SEPARATOR );
					$result = WPF()->member->update_custom_field( $userid, $field, '' );
					if( $result ) {
						@unlink( $file );
						WPF()->phrase->clear_cache();
						WPF()->notice->add( 'Deleted Successfully!', 'success' );
					} else {
						WPF()->notice->clear();
						WPF()->notice->add( 'Sorry, this file cannot be deleted', 'error' );
					}
				}
			}
		}
	}

	/**
	 * mark all bold forum topics as read
	 */
	public function mark_all_read() {
		if( wpfval( WPF()->GET, 'foro' ) === 'allread' ) {
			if( wpfval( WPF()->GET, 'foro_n' ) && wp_verify_nonce( WPF()->GET['foro_n'], 'wpforo_mark_all_read' ) ) {
				WPF()->log->mark_all_read();
				$current_url = wpforo_get_request_uri();
				$current_url = strtok( $current_url, '?' );
				wp_safe_redirect( $current_url );
				exit();
			}
		}
	}

	/**
	 * Open/Close Frontend Admin CPanel
	 */
	public function acp_toggle() {
		$toggle_status = wpfval( $_POST, 'toggle_status' );
		if( in_array( $toggle_status, [ 'open', 'close' ] ) ) {
			update_user_meta( WPF()->current_userid, 'wpf-acp-toggle', $toggle_status );
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * set a notification read
	 */
	public function mark_notification_read() {
		if( wpfval( WPF()->GET, '_nread' ) && is_user_logged_in() ) {
			if( wpfval( WPF()->GET, 'foro_n' ) && wp_verify_nonce( WPF()->GET['foro_n'], 'wpforo_mark_notification_read' ) ) {
				$id = intval( WPF()->GET['_nread'] );
				WPF()->activity->read_notification( $id );
				$current_url = wpforo_get_request_uri();
				$current_url = strtok( $current_url, '?' );
				wp_safe_redirect( $current_url );
				exit();
			}
		}
	}

	/**
	 * clear all notifications
	 */
	public function clear_all_notifications() {
		if( wpfval( $_POST, 'foro_n' ) && wp_verify_nonce( $_POST['foro_n'], 'wpforo_clear_notifications' ) ) {
			WPF()->activity->clear_notifications();
			echo WPF()->activity->get_no_notifications_html();
		}
		exit();
	}

	public function profile_cover_upload() {
		$r = false;
		if( ($image_blob = wpfval( $_POST, 'image_blob' )) && WPF()->current_object['user'] ){
			if( $file_content = file_get_contents($image_blob) ){
				$file_basename = WPF()->current_object['user']['user_login'] . '_' . WPF()->current_object['user']['userid'] . '.jpg';
				$file_dir = WPF()->folders['covers']['dir'] . DIRECTORY_SEPARATOR . $file_basename;
				$file_url = WPF()->folders['covers']['url//'] . '/' . $file_basename;
				if( file_put_contents($file_dir, $file_content) ){
					$r = WPF()->member->update_profile_field( WPF()->current_object['user']['userid'], 'cover', $file_url );
				}
			}
		}
		if( $r !== false ){
			wp_send_json_success();
		}else{
			wp_send_json_error();
		}
	}

	public function get_topic_head_more_info() {
		if( $topicid = wpforo_bigintval( wpfval( $_POST, 'topicid' ) ) ){
			wp_send_json_success( [ 'html' => wpforo_topic_active_participants( $topicid ) . wpforo_topic_overview( $topicid ) ] );
		}

		wp_send_json_error();
	}

	public function get_topic_overview_chunk() {
		if( $topicid = wpforo_bigintval( wpfval( $_POST, 'topicid' ) ) ){
			if( !( $chunksize = (int) wpfval( $_POST, 'chunksize' ) ) ) $chunksize = 5;
			$offset = (int) wpfval( $_POST, 'offset' );

			wp_send_json_success( wpforo_get_topic_overview_chunk( $topicid, $chunksize, $offset ) );
		}

		wp_send_json_error();
	}

	public function get_overview() {
		if( $postid = wpforo_bigintval( wpfval( $_POST, 'postid' ) ) ){
			if( $post = wpforo_post( $postid ) ){
				wp_send_json_success(
					[
                      'title'   => '<i class="fas fa-user"></i> &nbsp;' . wpforo_phrase( 'Posted by ', false )
                                   . ' ' . wpforo_member_link(wpforo_member($post), '', 20, '', false)
                                   . '&nbsp;&bullet;&nbsp;'
                                   . ' ' . wpforo_date($post['created'], 'ago', false),
                      'content' => trim( $post['body'] ),
					]
				);
			}
		}

		wp_send_json_error();
	}

	/**
	 * registration form submit action
	 */
	public function registration() {
		if( ! empty( $_POST['wpfreg'] ) ) {
			wpforo_verify_form( 'wpforo_user_register' );
			if( $userid = WPF()->member->create( $_POST ) ) {
				if( wpforo_setting( 'authorization', 'redirect_url_after_register' ) ) {
					$redirect_url = wpforo_setting( 'authorization', 'redirect_url_after_register' );
				} elseif( ( $redirect_to = wpfval( $_GET, 'redirect_to' ) ) && wpforo_is_url_internal( urldecode( $redirect_to ) ) ) {
					$redirect_url = urldecode( $redirect_to );
				} elseif( is_wpforo_url() ) {
					$redirect_url = preg_replace( '#\?.*$#is', '', wpforo_get_request_uri() );
				} else {
					$redirect_url = ( wpforo_setting( 'authorization', 'user_register_email_confirm' ) ? wpforo_home_url() : WPF()->member->get_profile_url( $userid, 'account' ) );
				}

				wp_safe_redirect( $redirect_url );
				exit();
			}
		}
	}

	/**
	 * login form submit action
	 */
	public function login() {
		wpforo_verify_form( 'login' );
		if( isset( $_POST['wpforologin'] ) && isset( $_POST['log'] ) && isset( $_POST['pwd'] ) ) {
			if( ! is_wp_error( $user = wp_signon() ) ) {
				$wpf_login_times = intval( get_user_meta( $user->ID, '_wpf_login_times', true ) );
				if( isset( $user->ID ) && $wpf_login_times >= 1 ) {
					$name = ( isset( $user->data->display_name ) ) ? $user->data->display_name : '';
					WPF()->notice->add( 'Welcome back %s!', 'success', $name );
				} else {
					WPF()->notice->add( 'Welcome to our Community!', 'success' );
				}
				$wpf_login_times ++;
				update_user_meta( $user->ID, '_wpf_login_times', $wpf_login_times );
				if( wpforo_setting( 'authorization', 'redirect_url_after_login' ) ) {
					$redirect_url = wpforo_setting( 'authorization', 'redirect_url_after_login' );
				} elseif( ( $redirect_to = wpfval( $_GET, 'redirect_to' ) ) && wpforo_is_url_internal( urldecode( $redirect_to ) ) ) {
					$redirect_url = urldecode( $redirect_to );
				} elseif( is_wpforo_url() ) {
					$redirect_url = preg_replace( '#\?.*$#is', '', wpforo_get_request_uri() );
				} else {
					$redirect_url = wpforo_home_url();
				}
				wp_safe_redirect( $redirect_url );
			} else {
				$args = [];
				foreach( $user->errors as $u_err ) $args[] = $u_err[0];
				WPF()->notice->add( $args, 'error' );
				wp_safe_redirect( wpforo_get_request_uri() );
			}
			exit();
		}
	}

	public function lostpassword() {
		wpforo_verify_form( 'lostpassword' );
		$redirect_url = wp_get_raw_referer();
		if( wpfval( $_POST, 'user_login' ) ) {
			$errors = retrieve_password();
			if( is_wp_error( $errors ) ) {
				$redirect_url = wpforo_lostpassword_url();
				WPF()->notice->add( implode( ',', $errors->get_error_codes() ), 'error' );
			} else {
				$redirect_url = wpforo_login_url();
				WPF()->notice->add( 'Email has been sent', 'success' );
			}
		}

		wp_safe_redirect( $redirect_url );
		exit();
	}

	public function resetpassword_form() {
		$rp_key   = sanitize_text_field( wp_unslash( $_REQUEST['rp_key'] ) );
		$rp_login = sanitize_user( wp_unslash( $_REQUEST['rp_login'] ) );
		$user     = check_password_reset_key( $rp_key, $rp_login );
		if( ! $user || is_wp_error( $user ) ) {
			if( $user && $user->get_error_code() === 'expired_key' ) {
				WPF()->notice->add( 'The key is expired', 'error' );
			} else {
				WPF()->notice->add( 'The key is invalid', 'error' );
			}
			wp_safe_redirect( wpforo_login_url() );
			exit();
		}
	}

	public function resetpassword() {
		$this->resetpassword_form();

		$pass1 = wpfval( $_POST, 'pass1' );
		$pass2 = wpfval( $_POST, 'pass2' );

		if( ! $pass1 ) {
			WPF()->notice->add( 'The password reset empty', 'error' );
			wp_safe_redirect( wp_get_raw_referer() );
			exit();
		}

		if( strlen( $pass1 ) < WPF()->member->pass_min_length || strlen( $pass1 ) > WPF()->member->pass_max_length ) {
			WPF()->notice->add( 'Password length must be between %d characters and %d characters.', 'error', [
				WPF()->member->pass_min_length,
				WPF()->member->pass_max_length,
			] );
			wp_safe_redirect( wp_get_raw_referer() );
			exit();
		}

		if( $pass1 !== $pass2 ) {
			WPF()->notice->add( 'The password reset mismatch', 'error' );
			wp_safe_redirect( wp_get_raw_referer() );
			exit();
		}

		$rp_login = sanitize_user( wp_unslash( $_REQUEST['rp_login'] ) );
		reset_password( get_user_by( 'login', $rp_login ), $pass1 );
		wp_signon( [ 'user_login' => $rp_login, 'user_password' => $pass1 ] );

		WPF()->notice->add( 'The password has been changed', 'success' );
		wp_safe_redirect( wpforo_home_url() );
		exit();
	}

	public function logout() {
		wp_logout();
		$redirect_url = wpforo_home_url();
		if( ( $redirect_to = wpfval( $_GET, 'redirect_to' ) ) && wpforo_is_url_internal( urldecode( $redirect_to ) ) ) {
			$redirect_url = urldecode( $redirect_to );
			if( strpos( $redirect_url, 'lostpassword' ) !== false ) $redirect_url = wpforo_login_url();
		}
		wp_safe_redirect( $redirect_url );
		exit();
	}

	/**
	 * profile_update form submit action
	 */
	public function profile_update() {
		if( wpfval( $_POST, 'member', 'userid' ) ) {
			wpforo_verify_form();
			$uid = intval( $_POST['member']['userid'] );
			if( ! ( $uid === WPF()->current_userid || ( WPF()->usergroup->can( 'em' ) && WPF()->perm->user_can_manage_user( WPF()->current_userid, $uid ) ) ) ) {
				WPF()->notice->clear();
				WPF()->notice->add( 'Permission denied', 'error' );
				wp_safe_redirect( wpforo_get_request_uri() );
				exit();
			}
			if( WPF()->member->update( $_POST ) ) {
				if( $profile_url = WPF()->member->get_profile_url( $uid, 'account' ) ) {
					wp_safe_redirect( $profile_url );
					exit();
				}
			}
		}

		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	public function cantlogin_contact() {
		if( wpforo_setting( 'authorization', 'manually_approval_contact_form' ) && ($msg = wpfval( $_POST, 'msg' )) ){
			$admin_emails = wpforo_setting( 'email', 'admin_emails' );
			$admin_email  = wpfval($admin_emails, 0);
			$sbj = wpforo_phrase( 'Request for account approval', false ) . ' ( '. wpfval( $_POST, 'user_login' ) .' )';

			add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
			if( @wpforo_send_email( $admin_email, $sbj, $msg, wpforo_admin_mail_headers() ) ) {
				WPF()->notice->add( 'Message has been sent', 'success' );
			} else {
				WPF()->notice->add( 'Can\'t send report email', 'error' );
			}
			remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
		}

		wp_safe_redirect( wpforo_home_url() );
		exit();
	}

	/**
	 * topic_add form submit action
	 */
	public function topic_add() {
		wpforo_verify_form();
		$args              = $_REQUEST['thread'];
		$args['postmetas'] = (array) wpfval( $_REQUEST, 'data' );
		if( $topicid = WPF()->topic->add( $args ) ) {
			wp_safe_redirect( WPF()->topic->get_topic_url( $topicid ) );
			exit();
		}
		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * topic_edit form submit action
	 */
	public function topic_edit() {
		wpforo_verify_form();
		$args              = $_REQUEST['thread'];
		$args['postmetas'] = (array) wpfval( $_REQUEST, 'data' );
		if( $topicid = WPF()->topic->edit( $args ) ) {
			wp_safe_redirect( WPF()->topic->get_topic_url( $topicid ) );
			exit();
		}
		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * post_add form submit action
	 */
	public function post_add() {
		wpforo_verify_form();
		$args              = $_REQUEST['post'];
		$args['postmetas'] = (array) wpfval( $_REQUEST, 'data' );
		if( $postid = WPF()->post->add( $args ) ) {
			wp_safe_redirect( WPF()->post->get_post_url( $postid ) );
			exit();
		}
		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * post_edit form submit action
	 */
	public function post_edit() {
		wpforo_verify_form();
		$args              = $_REQUEST['post'];
		$args['postmetas'] = (array) wpfval( $_REQUEST, 'data' );
		if( $postid = WPF()->post->edit( $args ) ) {
			wp_safe_redirect( WPF()->post->get_post_url( $postid ) );
			exit();
		}
		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * topic_move form submit action
	 */
	public function topic_move() {
		if( ! empty( $_POST['topic_move'] ) ) {
			wpforo_verify_form();
			$topicid = intval( wpfval( $_POST['topic_move'], 'topicid' ) );
			$forumid = intval( wpfval( $_POST['topic_move'], 'forumid' ) );
			if( $topicid && $forumid ) {
				WPF()->topic->move( $topicid, $forumid );
				wp_safe_redirect( WPF()->topic->get_topic_url( $topicid ) );
				exit();
			}
		}

		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * topic_merge form submit action
	 */
	public function topic_merge() {
		wpforo_verify_form();
		$redirect_to = wpforo_get_request_uri();
		if( WPF()->current_object['topic'] && ! empty( $_POST['wpforo'] ) && ! empty( $_POST['wpforo']['target_topic_url'] ) ) {
			$target_slug = wpforo_get_topic_slug_from_url( esc_url( $_POST['wpforo']['target_topic_url'] ) );
			if( ! is_null( $target_slug ) && $target = WPF()->topic->get_topic( $target_slug ) ) {
				$append          = ( empty( $_POST['wpforo']['update_date_and_append'] ) ? 0 : 1 );
				$to_target_title = ( empty( $_POST['wpforo']['to_target_title'] ) ? 0 : 1 );

				if( WPF()->topic->merge( $target, WPF()->current_object['topic'], [], $to_target_title, $append ) ) {
					$redirect_to = WPF()->topic->get_topic_url( $target );
				}
			} else {
				WPF()->notice->add( 'Target Topic not found', 'error' );
			}
		}
		wp_safe_redirect( $redirect_to );
		exit();
	}

	/**
	 * topic_split form submit action
	 */
	public function topic_split() {
		wpforo_verify_form();
		$redirect_to = wpforo_get_request_uri();
		if( WPF()->current_object['topic'] && ! empty( $_POST['wpforo'] ) ) {
			if( ! empty( $_POST['wpforo']['create_new'] ) ) {
				$args            = [
					'title'   => sanitize_text_field( $_POST['wpforo']['new_topic_title'] ),
					'forumid' => intval( $_POST['wpforo']['new_topic_forumid'] ),
					'postids' => array_map( 'intval', $_POST['wpforo']['posts'] ),
				];
				$to_target_title = ( empty( $_POST['wpforo']['to_target_title'] ) ? 0 : 1 );
				if( $topicid = WPF()->topic->split( $args, $to_target_title ) ) {
					$redirect_to = WPF()->topic->get_topic_url( $topicid );
				}
			} else {
				if( ! empty( $_POST['wpforo']['target_topic_url'] ) && ! empty( $_POST['wpforo']['posts'] ) ) {
					$target_slug = wpforo_get_topic_slug_from_url( esc_url( $_POST['wpforo']['target_topic_url'] ) );
					if( ! is_null( $target_slug ) && $target = WPF()->topic->get_topic( $target_slug ) ) {
						$append          = ( empty( $_POST['wpforo']['update_date_and_append'] ) ? 0 : 1 );
						$to_target_title = ( empty( $_POST['wpforo']['to_target_title'] ) ? 0 : 1 );
						$postids         = array_map( 'intval', $_POST['wpforo']['posts'] );
						if( WPF()->topic->merge( $target, WPF()->current_object['topic'], $postids, $to_target_title, $append ) ) {
							$redirect_to = WPF()->topic->get_topic_url( $target );
						}
					} else {
						WPF()->notice->add( 'Target Topic not found', 'error' );
					}
				}
			}
		}
		wp_safe_redirect( $redirect_to );
		exit();
	}

	/**
	 * board add action
	 */
	public function board_add() {
		check_admin_referer( 'wpforo-board-add' );
		if( $board = (array) wpfval( $_POST, 'board' ) ) {
			if( $boardid = WPF()->board->add( $board ) ) {
				// Handle translation installation.
				if( (int) $board['status'] && $board['locale'] && current_user_can( 'install_languages' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/translation-install.php';
					if( wp_can_install_language_pack() ) wp_download_language_pack( $board['locale'] );
				}

				if( ( $board = WPF()->board->_get_board( $boardid ) ) && $board['status'] ) {
					wp_safe_redirect( admin_url( 'admin.php?page=wpforo-' . $boardid . '-settings' ) );
					exit();
				}
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpforo-boards' ) );
		exit();
	}

	/**
	 * board edit action
	 */
	public function board_edit() {
		check_admin_referer( 'wpforo-board-edit' );
		if( $board = (array) wpfval( $_POST, 'board' ) ) {
			$boardid = (int) wpfval( $board, 'boardid' );
			if( WPF()->board->edit( $board, $boardid ) ) {
				// Handle translation installation.
				if( (int) $board['status'] && $board['locale'] && current_user_can( 'install_languages' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/translation-install.php';
					if( wp_can_install_language_pack() ) wp_download_language_pack( $board['locale'] );
				}
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpforo-boards' ) );
		exit();
	}

	/**
	 * board delete action
	 */
	public function board_delete() {
		if( ( $boardid = (int) wpfval( $_GET, 'boardid' ) ) && $boardid === WPF()->board->get_current( 'boardid' ) ) {
			check_admin_referer( 'wpforo-board-delete-' . $boardid );
			if( WPF()->board->delete( $boardid ) ) wpforo_uninstall();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wpforo-boards' ) );
		exit();
	}

	/**
	 * action to synchronize wp_users to wp_wpforo_profiles
	 */
	public function synch_user_profiles() {
		check_admin_referer( 'wpforo_synch_user_profiles' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( $this->can_do() ) {
			wpforo_set_max_execution_time();
			wp_raise_memory_limit();

			if( WPF()->member->synchronize_users( apply_filters( 'wpforo_rebuild_per_request', 200 ) ) ) {
				WPF()->member->clear_db_cache();
				wpforo_clean_cache();
				WPF()->notice->add( 'Synched Successfully!', 'success' );
				wp_safe_redirect( admin_url( 'admin.php?page=wpforo-overview' ) );
			} else {
				wp_safe_redirect( htmlspecialchars_decode( wp_nonce_url( admin_url( 'admin.php?page=wpforo-overview&wpfaction=synch_user_profiles' ), 'wpforo_synch_user_profiles' ) ) );
			}
			exit();
		}
	}

	/**
	 * reset user caches
	 */
	public function reset_user_cache() {
		check_admin_referer( 'wpforo_reset_user_cache' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		WPF()->member->clear_db_cache();
		WPF()->notice->add( 'Deleted Successfully!', 'success' );

		wp_safe_redirect( admin_url( 'admin.php?page=wpforo-overview' ) );
		exit();
	}

	/**
	 * rebuild forums statistics first|last posts etc.
	 */
	public function reset_forums_stats() {
		check_admin_referer( 'wpforo_reset_forums_stat' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		$forumids = WPF()->db->get_col( "SELECT `forumid` FROM " . WPF()->tables->forums . " WHERE `is_cat` = 0 ORDER BY `forumid` ASC" );
		if( ! empty( $forumids ) ) {
			foreach( $forumids as $forumid ) {
				WPF()->forum->rebuild_stats( $forumid );
			}
			WPF()->statistic_cache_clean();
			WPF()->forum->delete_tree_cache();
			WPF()->notice->add( 'Updated Successfully!', 'success' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) ) );
		exit();
	}

	/**
	 * rebuild topics statistics first|last posts etc.
	 */
	public function reset_topics_stats() {
		check_admin_referer( 'wpforo_reset_topics_stat' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( $this->can_do() ) {
			wpforo_set_max_execution_time();
			wp_raise_memory_limit();

			$lastid   = (int) wpfval( $_GET, 'topic_lastid' );
			$sql      = "SELECT `topicid` FROM " . WPF()->tables->topics . " WHERE `topicid` > %d ORDER BY `topicid` ASC LIMIT %d";
			$topicids = WPF()->db->get_col( WPF()->db->prepare( $sql, $lastid, apply_filters( 'wpforo_rebuild_per_request', 200 ) ) );
			if( $topicids ) {
				foreach( $topicids as $topicid ) {
					$topic = WPF()->topic->get_topic( $topicid );
					WPF()->topic->rebuild_first_last( $topic );
					WPF()->topic->rebuild_stats( $topic );
				}
				wp_safe_redirect( htmlspecialchars_decode( wp_nonce_url( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) . '&wpfaction=reset_topics_stats&topic_lastid=' . end( $topicids ) ), 'wpforo_reset_topics_stat' ) ) );
			} else {
				@WPF()->db->query(
					"UPDATE `" . WPF()->tables->topics . "` t
								INNER JOIN `" . WPF()->tables->posts . "` p ON p.`topicid` = t.`topicid` AND p.`is_answer` = 1
								SET t.`solved` = 1
								WHERE t.`solved` = 0"
				);
				WPF()->statistic_cache_clean();
				WPF()->notice->add( 'Updated Successfully!', 'success' );
				wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) ) );
			}
			exit();
		}
	}

	/**
	 * rebuild users statistics etc.
	 */
	public function reset_users_stats() {
		check_admin_referer( 'wpforo_reset_users_stat' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( $this->can_do() ) {
			wpforo_set_max_execution_time();
			wp_raise_memory_limit();

			$lastid  = (int) wpfval( $_GET, 'user_lastid' );
			$sql     = "SELECT `userid` FROM " . WPF()->tables->profiles . " WHERE `userid` > %d ORDER BY `userid` ASC LIMIT %d";
			$userids = WPF()->db->get_col( WPF()->db->prepare( $sql, $lastid, apply_filters( 'wpforo_rebuild_per_request', 200 ) ) );
			if( $userids ) {
				foreach( $userids as $userid ) {
					WPF()->member->rebuild_stats( $userid );
				}

				wp_safe_redirect( htmlspecialchars_decode( wp_nonce_url( admin_url( 'admin.php?page=wpforo-overview&wpfaction=reset_users_stats&user_lastid=' . end( $userids ) ), 'wpforo_reset_users_stat' ) ) );
			} else {
				WPF()->notice->add( 'Updated Successfully!', 'success' );
				wp_safe_redirect( admin_url( 'admin.php?page=wpforo-overview' ) );
			}
			exit();
		}
	}

	/**
	 * rebuild 4 layout forum topics threads root
	 */
	public function rebuild_threads() {
		check_admin_referer( 'wpforo_rebuild_threads' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		wpforo_set_max_execution_time( 3600 );
		wp_raise_memory_limit();

		WPF()->topic->rebuild_forum_threads();
		wpforo_clean_cache();
		WPF()->notice->add( 'Threads rebuilt successfully', 'success' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) ) );
		exit();
	}

	/**
	 * reset phrases cache from db
	 */
	public function reset_phrase_cache() {
		check_admin_referer( 'wpforo_reset_phrase_cache' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		WPF()->phrase->clear_cache();
		WPF()->notice->add( 'Deleted Successfully!', 'success' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) ) );
		exit();
	}

	/**
	 * recrawling phrases from all wpforo, wpforo-addons code files
	 */
	public function recrawl_phrases() {
		check_admin_referer( 'wpforo_recrawl_phrases' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		WPF()->phrase->crawl_phrases();
		WPF()->phrase->clear_cache();
		WPF()->notice->clear();
		WPF()->notice->add( 'Rebuilt Successfully!', 'success' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) ) );
		exit();
	}

	/**
	 * reset wpforo all caches (phrase, user, forum, post, stats) etc.
	 */
	public function reset_all_caches() {
		check_admin_referer( 'wpforo_reset_cache' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		WPF()->member->clear_db_cache();
		wpforo_clean_cache();
		WPF()->notice->add( 'Deleted Successfully!', 'success' );

		$redirect = ( is_admin() ) ? admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) ) : wpforo_home_url();
		wp_safe_redirect( $redirect );
		exit();
	}

	/**
	 * Clean Up damaged content in database
	 */
	public function clean_up() {
		check_admin_referer( 'wpforo_clean_up' );

		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		wpforo_clean_up();
		WPF()->notice->add( 'Cleaned Up!', 'success' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) ) );
		exit();
	}

	/**
	 * dashboard_options_save form submit action
	 */
	public function dashboard_options_save() {
		if( ! current_user_can( 'administrator' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}
		if( $dashboard_count_per_page = (int) wpfval( $_POST, 'wpforo_dashboard_count_per_page' ) ) {
			wpforo_update_option( 'count_per_page', $dashboard_count_per_page );
		}
		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * checking accesses to forum admin menu pages settings etc...
	 */
	public function check_dashboard_permissions() {
		$page = wpfval( WPF()->GET, 'page' );
		if( $page === wpforo_prefix_slug( 'settings' ) ) {
			if( ! WPF()->usergroup->can( 'ms' ) ) {
				WPF()->notice->add( 'Permission denied', 'error' );
				wp_safe_redirect( admin_url() );
				exit();
			}
		}
	}

	/**
	 * check if [wpforo] page has been deleted, restore or create new [wpforo] page
	 */
	public function repair_lost_main_shortcode_page() {
		if( wpfval( WPF()->GET, 'page' ) === wpforo_prefix_slug( 'settings' ) ) wpforo_repair_main_shortcode_page();
	}

	/**
	 * add_new_xml_translation form submit action
	 */
	public function add_new_xml_translation() {
		check_admin_referer( 'wpforo-settings-language' );

		if( ! WPF()->usergroup->can( 'ms' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_FILES['add_lang'] ) ) {
			WPF()->phrase->add_lang();
			wpforo_clean_cache();
		}
		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'settings' ) . '&tab=general' ) );
		exit();
	}

	/**
	 * add_new_xml_translation form submit action
	 */
	public function phrases_change_lang() {
		check_admin_referer( 'wpforo-phrases-change-language' );

		if( $langid = (int) wpfval( $_POST, 'langid' ) ){
			if( WPF()->phrase->set_language_status( $langid ) ){
				WPF()->notice->add( 'Successfully updated', 'success' );
			}else{
				WPF()->notice->add( 'Invalid request!', 'error' );
			}
		}

		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * colors.css download action
	 */
	public function colors_css_download() {
		check_admin_referer( 'dynamic_css_download' );

		if( ! WPF()->usergroup->can( 'ms' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		$dynamic_css = WPF()->tpl->generate_dynamic_css();
		header( 'Content-Type: application/download' );
		header( 'Content-Disposition: attachment; filename="colors.css"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( "Content-Length: " . strlen( $dynamic_css ) );
		echo $dynamic_css;
		exit();
	}


	/**
	 * cleanup_options_save form submit action
	 */
	public function cleanup_options_save() {
		check_admin_referer( 'wpforo-tools-cleanup' );

		if( ! WPF()->usergroup->can( 'mt' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! wpfkey( $_POST, 'reset' ) ) {
			if( $options = wpfval( $_POST, 'wpforo_tools_cleanup' ) ) {
				if( wpforo_update_option( 'tools_cleanup', $options ) ) {
					WPF()->notice->add( 'Settings successfully updated', 'success' );
				}
			}
		} else {
			wpforo_delete_option( 'tools_cleanup' );
			WPF()->notice->add( 'Cleanup options reset successfully', 'success' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=cleanup' ) );
		exit();
	}

	/**
	 * misc_options_save form submit action
	 */
	public function misc_options_save() {
		check_admin_referer( 'wpforo-tools-misc' );

		if( ! WPF()->usergroup->can( 'mt' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! wpfkey( $_POST, 'reset' ) ) {
			if( $options = wpfval( $_POST, 'wpforo_tools_misc' ) ) {
				$options['admin_note']        = wpforo_kses( $options['admin_note'] );
				$options['admin_note_groups'] = ( wpfval( $_POST, 'wpforo_tools_misc', 'admin_note_groups' ) ) ? array_map( 'intval', $options['admin_note_groups'] ) : [];
				$options['admin_note_pages']  = ( wpfval( $_POST, 'wpforo_tools_misc', 'admin_note_pages' ) ) ? array_map( 'sanitize_textarea_field', $options['admin_note_pages'] ) : [];
				if( wpforo_update_option( 'tools_misc', $options ) ) {
					wpforo_clean_cache( 'forum-soft' );
					WPF()->notice->add( 'Settings successfully updated', 'success' );
				}
			}
		} else {
			wpforo_delete_option( 'tools_misc' );
			WPF()->notice->add( 'Misc options reset successfully', 'success' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=misc' ) );
		exit();
	}

	/**
	 * legal_options_save form submit action
	 */
	public function legal_options_save() {
		check_admin_referer( 'wpforo-tools-legal' );

		if( ! WPF()->usergroup->can( 'mt' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! wpfkey( $_POST, 'reset' ) ) {
			if( $options = wpfval( $_POST, 'wpforo_tools_legal' ) ) {
				$options['contact_page_url']        = esc_url( $options['contact_page_url'] );
				$options['checkbox_terms_privacy']  = intval( $options['checkbox_terms_privacy'] );
				$options['checkbox_email_password'] = intval( $options['checkbox_email_password'] );
				$options['page_terms']              = esc_url( $options['page_terms'] );
				$options['page_privacy']            = esc_url( $options['page_privacy'] );
				$options['checkbox_forum_privacy']  = intval( $options['checkbox_forum_privacy'] );
				$options['forum_privacy_text']      = wpforo_kses( $options['forum_privacy_text'], 'post' );
				$options['checkbox_fb_login']       = intval( $options['checkbox_fb_login'] );
				$options['cookies']                 = intval( $options['cookies'] );
				$options['rules_checkbox']          = intval( $options['rules_checkbox'] );
				$options['rules_text']              = wpforo_kses( $options['rules_text'], 'post' );
				if( wpforo_update_option( 'tools_legal', $options ) ) {
					WPF()->notice->add( 'Settings successfully updated', 'success' );
				}
			}
		} else {
			wpforo_delete_option( 'tools_legal' );
			WPF()->notice->add( 'Settings reset successfully', 'success' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=legal' ) );
		exit();
	}

	/**
	 * delete detected spam file
	 */
	public function delete_spam_file() {
		check_admin_referer( 'wpforo_tools_antispam_files' );

		if( ! WPF()->usergroup->can( 'mt' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( $filename = trim( wpfval( $_GET, 'sfname' ) ) ) {
			$filename = str_replace( [ '../', './', '/' ], '', sanitize_file_name( $filename ) );
			$filename = urldecode( $filename );
			if( $filename ) {
				$attachmentid = WPF()->post->get_attachment_id( '/' . $filename );
				if( ! wp_delete_attachment( $attachmentid ) ) {
					@unlink( WPF()->folders['default_attachments']['dir'] . DIRECTORY_SEPARATOR . $filename );
				}
				WPF()->notice->add( 'Deleted', 'success' );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=antispam' ) );
		exit();
	}

	/**
	 * delete_all_spam_files all detected spam file using level attribute
	 */
	public function delete_all_spam_files() {
		check_admin_referer( 'wpforo_tools_antispam_files' );

		if( ! WPF()->usergroup->can( 'mt' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( $delete_level = (int) wpfval( $_GET, 'level' ) ) {
			$default_attachments_dir = WPF()->folders['default_attachments']['dir'];
			if( is_dir( $default_attachments_dir ) ) {
				if( $handle = opendir( $default_attachments_dir ) ) {
					while( false !== ( $filename = readdir( $handle ) ) ) {
						if( $filename === '.' || $filename === '..' ) continue;
						if( ! $level = WPF()->moderation->spam_file( $filename ) ) continue;
						if( $delete_level === $level ) {
							$attachmentid = WPF()->post->get_attachment_id( '/' . $filename );
							if( ! wp_delete_attachment( $attachmentid ) ) {
								@unlink( $default_attachments_dir . DIRECTORY_SEPARATOR . $filename );
							}
						}
					}
					closedir( $handle );
					WPF()->notice->add( 'Deleted', 'success' );
				}
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=antispam' ) );
		exit();
	}

	/**
	 * do database alter fixing using install.sql db-strukture
	 */
	public function database_update() {
		check_admin_referer( 'wpforo_update_database' );

		if( ! WPF()->usergroup->can( 'mt' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		wpforo_set_max_execution_time( 3600 );

		wpforo_update_db();

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) . '&tab=debug&view=tables' ) );
		exit();
	}

	/**
	 * forum_add form submit action
	 */
	public function forum_add() {
		check_admin_referer( 'wpforo-forum-add' );

		if( ! WPF()->usergroup->can_manage_forum() ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_REQUEST['forum'] ) ) {
			WPF()->forum->add();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'forums' ) ) );
		exit();
	}

	/**
	 * forum_edit form submit action
	 */
	public function forum_edit() {
		check_admin_referer( 'wpforo-forum-edit' );

		if( ! WPF()->usergroup->can_manage_forum() ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_REQUEST['forum'] ) ) {
			WPF()->forum->edit();
		}

		wp_safe_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * forum_delete form submit action
	 */
	public function forum_delete() {
		check_admin_referer( 'wpforo-forum-delete' );

		if( ! WPF()->usergroup->can_manage_forum() ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		$delete = (int) wpfval( $_REQUEST, 'forum', 'delete' );
		if( $delete === 1 ) {
			WPF()->forum->delete( 0, false );
		} elseif( $delete === 0 ) {
			WPF()->forum->merge();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'forums' ) ) );
		exit();
	}

	/**
	 * forum_hierarchy_save form submit action
	 */
	public function forum_hierarchy_save() {
		check_admin_referer( 'wpforo-forums-hierarchy' );

		if( ! WPF()->usergroup->can_manage_forum() ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_REQUEST['forum'] ) ) {
			WPF()->forum->update_hierarchy();
			wpforo_clean_cache( 'forum' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'forums' ) ) );
		exit();
	}

	/**
	 * dashboard_post_unapprove action
	 */
	public function dashboard_post_unapprove() {
		$postid = wpfval( $_GET, 'postid' );
		check_admin_referer( "wpforo-unapprove-post-{$postid}" );

		if( ! WPF()->usergroup->can( 'aum' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		WPF()->moderation->post_unapprove( $postid );
		wpforo_clean_cache( 'post', $postid );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * dashboard_post_approve action
	 */
	public function dashboard_post_approve() {
		$postid = wpfval( $_GET, 'postid' );
		check_admin_referer( "wpforo-approve-post-{$postid}" );

		if( ! WPF()->usergroup->can( 'aum' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		WPF()->moderation->post_approve( $postid );
		wpforo_clean_cache( 'post', $postid );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * dashboard_post_delete action
	 */
	public function dashboard_post_delete() {
		$postid = wpfval( $_GET, 'postid' );
		check_admin_referer( "wpforo-delete-post-{$postid}" );

		if( ! WPF()->usergroup->can( 'aum' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		WPF()->post->delete( $postid );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * doing bulk moderation actions ( approve, unapprove, delete )
	 */
	public function bulk_moderation() {
		check_admin_referer( 'bulk-moderations' );

		if( ! WPF()->usergroup->can( 'aum' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		$u_action = $this->get_current_bulk_action();
		$postids  = (array) wpfval( $_GET, 'postids' );
		if( $u_action && ! empty( $postids ) ) {
			if( $u_action === 'delete' ) {
				foreach( $postids as $postid ) WPF()->post->delete( $postid );
			} elseif( $u_action === 'approve' ) {
				foreach( $postids as $postid ) WPF()->moderation->post_approve( $postid );
			} elseif( $u_action === 'unapprove' ) {
				foreach( $postids as $postid ) WPF()->moderation->post_unapprove( $postid );
			}
		}

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * phrase_add form submit action
	 */
	public function phrase_add() {
		check_admin_referer( 'wpforo-phrase-add' );

		if( ! WPF()->usergroup->can( 'mp' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_POST['phrase'] ) ) {
			WPF()->phrase->add();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'phrases' ) ) );
		exit();
	}

	/**
	 * phrase_edit_form action redirect to phrase list page when phraseid(s) not chosen
	 */
	public function phrase_edit_form() {
		$phraseids = array_filter( array_map( 'intval', array_merge( (array) wpfval( $_GET, 'phraseid' ), (array) wpfval( $_GET, 'phraseids' ) ) ) );
		if( ! $phraseids ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'phrases' ) ) );
			exit();
		}
	}

	/**
	 * phrase_edit form submit action
	 */
	public function phrase_edit() {
		check_admin_referer( 'wpforo-phrases-edit' );

		if( ! WPF()->usergroup->can( 'mp' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_POST['phrases'] ) ) {
			WPF()->phrase->edit();
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'phrases' ) ) );
		exit();
	}

	/**
	 * user_ban action
	 */
	public function user_ban() {
		$userid = intval( wpfval( $_GET, 'userid' ) );
		check_admin_referer( 'wpforo-user-ban-' . $userid );

		if( ! WPF()->usergroup->can( 'vm' ) || ! WPF()->usergroup->can( 'bm' ) || $userid === WPF()->current_userid ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		WPF()->member->ban( $userid );
		wpforo_clean_cache( 'user' );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * user_unban action
	 */
	public function user_unban() {
		$userid = intval( wpfval( $_GET, 'userid' ) );
		check_admin_referer( 'wpforo-user-unban-' . $userid );

		if( ! WPF()->usergroup->can( 'vm' ) || ! WPF()->usergroup->can( 'bm' ) || $userid === WPF()->current_userid ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		WPF()->member->unban( $userid );
		wpforo_clean_cache( 'user' );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * user_activate action
	 */
	public function user_activate() {
		$userid = intval( wpfval( $_GET, 'userid' ) );
		check_admin_referer( 'wpforo-user-activate-' . $userid );

		if( ! WPF()->usergroup->can( 'vm' ) || ! WPF()->usergroup->can( 'bm' ) || $userid === WPF()->current_userid ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		WPF()->member->activate( $userid );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * user_deactivate action
	 */
	public function user_deactivate() {
		$userid = intval( wpfval( $_GET, 'userid' ) );
		check_admin_referer( 'wpforo-user-deactivate-' . $userid );

		if( ! WPF()->usergroup->can( 'vm' ) || ! WPF()->usergroup->can( 'bm' ) || $userid === WPF()->current_userid ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		WPF()->member->deactivate( $userid );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	public function user_ban_ajax() {
			$userid = WPF()->current_object['user']['userid'];
			$currentstate = (int) wpfval( $_POST, 'currentstate' );
			if( $currentstate ){
				$r = WPF()->member->unban( $userid );
			}else{
				$r = WPF()->member->ban( $userid );
			}

			if( $r ){
				wp_send_json_success( [
					'currentstate' => (int) !$currentstate,
					'notice'       => WPF()->notice->get_notices(),
                ] );
			}else{
				wp_send_json_error( [ 'notice' => WPF()->notice->get_notices() ] );
			}
	}

	public function user_delete() {
		if(
			WPF()->current_object['user']
		    && ( $action = WPF()->member->get_action( WPF()->current_object['user'], 'delete' ) )
			&& is_callable( $action['callback_for_can'] )
		    && call_user_func( $action['callback_for_can'] )
		){
			if( ! function_exists( 'wp_delete_user' ) ) require_once ABSPATH . "wp-admin/includes/user.php";
			if( wp_delete_user( WPF()->current_object['user']['userid'] ) ){
				WPF()->notice->add( 'User successfully deleted', 'success' );
			}else{
				WPF()->notice->add( 'User delete error', 'error' );
			}
		}else{
			WPF()->notice->add( 'Permission denied for this action', 'error' );
		}

		wp_safe_redirect( wpforo_url( '', 'members' ) );
		exit();
	}

	/**
	 * action after WordPress native deleted_user hook
	 *
	 * @param int $userid already deleted user ID
	 */
	public function deleted_user( $userid, $reassign = null ) {
		if( wpfval( $_REQUEST, 'wpforo_user_delete_option' ) === 'reassign' ){
			WPF()->member->delete( $userid, wpforo_bigintval( wpfval( $_REQUEST, 'wpforo_reassign_userid' ) ) );
		}elseif( wpfval( $_REQUEST, 'wpforo_user_delete_option' ) === 'delete' ){
			WPF()->member->delete( $userid );
		}elseif( $reassign ){
			WPF()->member->delete( $userid, $reassign );
		}else{
			WPF()->member->delete( $userid, ( wpforo_setting( 'authorization', 'user_delete_method' ) === 'soft' ? 0 : null ) );
		}
		WPF()->notice->clear();
	}

	/**
	 * doing bulk member actions ( ban, unban, delete )
	 */
	public function bulk_members() {
		check_admin_referer( 'bulk-members' );

		if( ! WPF()->usergroup->can( 'vm' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		$new_groupid = - 1;
		if( ! empty( $_GET['new_groupid'] ) && $_GET['new_groupid'] !== '-1' ) {
			$new_groupid = intval( $_GET['new_groupid'] );
		} elseif( ! empty( $_GET['new_groupid2'] ) && $_GET['new_groupid2'] !== '-1' ) {
			$new_groupid = intval( $_GET['new_groupid2'] );
		}

		$u_action = $this->get_current_bulk_action();
		if( in_array( $u_action, [ 'ban', 'unban', 'activate', 'deactivate' ] ) && ! WPF()->usergroup->can( 'bm' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		} elseif( $u_action === 'delete' && ! WPF()->usergroup->can( 'dm' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		$userids = (array) wpfval( $_GET, 'userids' );
		$userids = array_filter( array_map( 'wpforo_bigintval', $userids ) );
		$userids = array_diff( $userids, (array) WPF()->current_userid );
		if( $u_action && ! empty( $userids ) ) {
			if( $u_action === 'delete' ) {
				$url = self_admin_url( 'users.php?action=delete&users[]=' . implode( '&users[]=', $userids ) );
				$url = str_replace( '&amp;', '&', wp_nonce_url( $url, 'bulk-users' ) );
				wp_safe_redirect( $url );
				exit();
			} elseif( $u_action === 'ban' ) {
				foreach( $userids as $userid ) {
					WPF()->member->ban( $userid );
				}
			} elseif( $u_action === 'unban' ) {
				foreach( $userids as $userid ) {
					WPF()->member->unban( $userid );
				}
			} elseif( $u_action === 'activate' ) {
				foreach( $userids as $userid ) {
					WPF()->member->activate( $userid );
				}
			} elseif( $u_action === 'deactivate' ) {
				foreach( $userids as $userid ) {
					WPF()->member->deactivate( $userid );
				}
			}
		} elseif( ! $u_action && wpfkey( $_GET, 'change_group' ) ) {
			if( ! empty( $userids ) && $new_groupid !== - 1 ) {
				$status = WPF()->usergroup->set_users_groupid( [ $new_groupid => $userids ] );
				if( $status['success'] ) WPF()->notice->add( 'Usergroup is successfully changed for selected users', 'success' );
			} else {
				WPF()->notice->add( 'Please select users and usergroup', 'error' );
			}
		}
		wpforo_clean_cache( 'user' );

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * usergroup_add form submit action
	 */
	public function usergroup_add() {
		check_admin_referer( 'wpforo-usergroup-add' );

		if( ! WPF()->usergroup->can( 'vmg' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_POST['usergroup'] ) ) {
			$group   = WPF()->usergroup->fix_group( $_POST['usergroup'] );
			$color   = wpfval( $group, 'wpfugc' ) ? '' : sanitize_text_field( $group['color'] );
			$groupid = WPF()->usergroup->add( $group['name'], $group['cans'], $group['description'], $group['role'], $group['access'], $color, $group['visible'], $group['secondary'] );
			if( $groupid ) wpforo_clean_cache( 'loop', $groupid );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'usergroups' ) ) );
		exit();
	}

	/**
	 * usergroup_edit form submit action
	 */
	public function usergroup_edit() {
		check_admin_referer( 'wpforo-usergroup-edit' );

		if( ! WPF()->usergroup->can( 'vmg' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_POST['usergroup'] ) ) {
			$group = WPF()->usergroup->fix_group( $_POST['usergroup'] );
			$color = wpfval( $group, 'wpfugc' ) ? '' : sanitize_text_field( $group['color'] );
			WPF()->usergroup->edit( $group['groupid'], $group['name'], $group['cans'], $group['description'], $group['role'], null, $color, $group['visible'], $group['secondary'] );
			wpforo_clean_cache( 'loop', $group['groupid'] );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'usergroups' ) ) );
		exit();
	}

	/**
	 * usergroup_delete form submit action
	 */
	public function usergroup_delete() {
		check_admin_referer( 'wpforo-usergroup-delete' );

		if( ! WPF()->usergroup->can( 'vmg' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( wpfval( $_POST, 'usergroup', 'delete' ) ) {
			$args = [ 'groupid' => wpfval( $_POST, 'usergroup', 'groupid' ) ];
			if( $userids = WPF()->member->get_userids( $args ) ) {
				$redirect_to = self_admin_url( 'users.php?action=delete&users[]=' . implode( '&users[]=', $userids ) );
				$redirect_to = str_replace( '&amp;', '&', wp_nonce_url( $redirect_to, 'bulk-users' ) );
				wp_safe_redirect( $redirect_to );
				exit();
			}
		}

		if( ! empty( $_POST['usergroup'] ) ) {
			WPF()->usergroup->delete( wpfval( $_POST['usergroup'], 'groupid' ), wpfval( $_POST['usergroup'], 'mergeid' ) );
			wpforo_clean_cache( 'user' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'usergroups' ) ) );
		exit();
	}

	/**
	 * default_groupid_change action
	 */
	public function default_groupid_change() {
		$default_groupid = intval( wpfval( $_GET, 'default_groupid' ) );
		check_admin_referer( 'wpforo-default-groupid-change-' . $default_groupid );

		if( ! WPF()->usergroup->can( 'vmg' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( $default_groupid ) WPF()->usergroup->set_default( $default_groupid );

		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * prevent to show usergroup delete form when !$groupid || $groupid <= 5
	 */
	public function usergroup_delete_form() {
		if( intval( wpfval( $_GET, 'groupid' ) ) <= 5 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'usergroups' ) ) );
			exit();
		}
	}

	/**
	 * access_add form submit action
	 */
	public function access_add() {
		check_admin_referer( 'wpforo-access-add' );

		if( ! WPF()->usergroup->can( 'ms' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_POST['access'] ) ) {
			WPF()->perm->add( WPF()->perm->fix_access( $_POST['access'] ) );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'accesses' ) ) );
		exit();
	}

	/**
	 * access_edit form submit action
	 */
	public function access_edit() {
		check_admin_referer( 'wpforo-access-edit' );

		if( ! WPF()->usergroup->can( 'ms' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		if( ! empty( $_POST['access'] ) ) {
			WPF()->perm->edit( WPF()->perm->fix_access( $_POST['access'] ) );
			wpforo_clean_cache( 'loop' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'accesses' ) ) );
		exit();
	}

	/**
	 * access_delete form submit action
	 */
	public function access_delete() {
		$accessid = intval( wpfval( $_GET, 'accessid' ) );
		check_admin_referer( 'wpforo-access-delete-' . $accessid );

		if( ! WPF()->usergroup->can( 'ms' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		WPF()->perm->delete( $accessid );
		wpforo_clean_cache( 'loop' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'accesses' ) ) );
		exit();
	}

	public function theme_activate() {
		check_admin_referer( 'wpforo-theme-activate' );

		if( ! WPF()->usergroup->can( 'mth' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		$notice      = __( 'Theme file not readable', 'wpforo' );
		$notice_type = 'error';
		if( ( $theme = trim( sanitize_text_field( wpfval( $_GET, 'theme' ) ) ) ) && WPF()->tpl->theme_exists( $theme ) ) {
			$general                  = WPF()->settings->general;
			$general['current_theme'] = $theme;
			wpforo_update_option( 'wpforo_general', $general );
			$notice      = __( 'Theme Successfully Activated', 'wpforo' );
			$notice_type = 'success';
		}

		WPF()->notice->add( $notice, $notice_type );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * theme_delete action
	 */
	public function theme_delete() {
		check_admin_referer( 'wpforo-theme-delete' );

		if( ! WPF()->usergroup->can( 'mth' ) ) {
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( admin_url() );
			exit();
		}

		$notice      = __( 'Theme delete error', 'wpforo' );
		$notice_type = 'error';
		if( $theme = trim( sanitize_text_field( wpfval( $_GET, 'theme' ) ) ) ) {
			if( WPF()->tpl->theme !== $theme ) {
				$remove_dir = WPFORO_THEME_DIR . '/' . $theme;
				if( is_dir( $remove_dir ) ) {
					wpforo_remove_directory( $remove_dir );
					$notice      = __( 'Theme delete success', 'wpforo' );
					$notice_type = 'success';
				}
			}
		}

		WPF()->notice->add( $notice, $notice_type );
		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'themes' ) ) );
		exit();
	}

	/**
	 * update wpForo addons CSS styles to make compatible with the current version of wpForo
	 */
	function update_addons_css() {
		check_admin_referer( 'wpforo-update-addons-css' );
		wpforo_wrap_in_all_addons_css();
		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'settings' ) ) );
		exit();
	}

	/**
	 * dissmiss the poll version is old notification for admins
	 */
	public function dissmiss_poll_version_is_old() {
		check_admin_referer( 'wpforo-dissmiss-poll-version-is-old' );
		WPF()->dissmissed['poll_version_is_old'] = 1;
		wpforo_update_option( 'dissmissed', WPF()->dissmissed );
		wp_safe_redirect( admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'dashboard' ) ) );
		exit();
	}

	/**
	 * dissmiss the recaptcha not configured notification for admins
	 */
	public function dissmiss_recaptcha_note() {
		if( wpfval( $_POST, 'backend' ) ) {
			WPF()->dissmissed['recaptcha_backend_note'] = 1;
		} else {
			WPF()->dissmissed['recaptcha_note'] = 1;
		}
		$response = wpforo_update_option( 'dissmissed', WPF()->dissmissed );
		if( $response ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * wpforo before deactivate action
	 */
	public function deactivate() {
		$response = [ 'code' => 0 ];
		$json     = filter_input( INPUT_POST, 'deactivateData' );
		if( $json ) {
			parse_str( $json, $data );

			$blogTitle   = get_option( 'blogname' );
			$to          = 'feedback@wpforo.com';
			$subject     = '[wpForo Feedback - ' . WPFORO_VERSION . ']';
			$headers     = [];
			$contentType = 'text/html';
			$fromName    = apply_filters( 'wp_mail_from_name', $blogTitle );
			$fromName    = html_entity_decode( $fromName, ENT_QUOTES );
			$siteUrl     = get_site_url();
			$parsedUrl   = parse_url( $siteUrl );
			$domain      = isset( $parsedUrl['host'] ) ? $parsedUrl['host'] : '';
			$fromEmail   = 'no-reply@' . $domain;
			$headers[]   = "Content-Type:  $contentType; charset=UTF-8";
			$headers[]   = "From: " . $fromName . " <" . $fromEmail . "> \r\n";
			$message     = "Dismiss and never show again";

			if( isset( $data['never_show'] ) && ( $v = intval( $data['never_show'] ) ) ) {
				wpforo_update_option( 'deactivation_dialog_never_show', $v );
				$response['code'] = 'dismiss_and_deactivate';
			} elseif( isset( $data['deactivation_reason'] ) && ( $reason = trim( $data['deactivation_reason'] ) ) ) {
				$subject .= ' - ' . $reason;
				$message = "<strong>Deactivation reason:</strong> " . $reason . "\r\n" . "<br/>";
				if( isset( $data['deactivation_reason_desc'] ) && ( $reasonDesc = trim( $data['deactivation_reason_desc'] ) ) ) {
					$message .= "<strong>Deactivation reason description:</strong> " . $reasonDesc . "\r\n" . "<br/>";
				}
				if( isset( $data['deactivation_feedback_email'] ) && ( $feedback_email = trim( $data['deactivation_feedback_email'] ) ) ) {
					$to      = 'support@wpforo.com';
					$message .= "<strong>Feedback Email:</strong> " . $feedback_email . "\r\n" . "<br/>";
				}
				$subject          = html_entity_decode( $subject, ENT_QUOTES );
				$message          = html_entity_decode( $message, ENT_QUOTES );
				$response['code'] = 'send_and_deactivate';
			}

			wp_mail( $to, $subject, $message, $headers );
		}
		wp_die( json_encode( $response ) );
	}

	/**
	 * base_slugs_settings_save from submit action
	 *
	 * @return void
	 */
	public function base_slugs_settings_save() {
		check_admin_referer( 'wpforo_settings_save_general' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_base_slugs' );
			WPF()->notice->add( 'Successfully Done', 'success' );
		} else {
			$slugs = wpforo_array_args_cast_and_merge( array_filter( array_map( 'sanitize_title', wp_unslash( $_POST['slugs'] ) ) ), WPF()->settings->_slugs );
			$slugs = array_intersect_key($slugs, WPF()->settings->_slugs);
			$slugs = array_diff_key( $slugs, WPF()->tpl->templates );
			if( $slugs == array_unique( $slugs ) ){
				wpforo_update_option( 'wpforo_base_slugs', $slugs );
				WPF()->notice->add( 'Successfully Done', 'success' );
			}else{
				WPF()->notice->add( 'Please save "Forum template slugs" uniqueness', 'error' );
			}
		}

		/*wp_safe_redirect( wp_get_raw_referer() );
		exit();*/
	}

	/**
	 * general_settings_save from submit action
	 *
	 * @return void
	 */
	public function general_settings_save() {
		check_admin_referer( 'wpforo_settings_save_general' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_general' );
		} else {
			$general = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['general'] ), WPF()->settings->_general );
			$general['admin_bar'] = array_map( 'intval', (array) wpfval( $_POST['general'], 'admin_bar' ) );
			wpforo_update_option( 'wpforo_general', $general );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * base_slugs_settings_save from submit action
	 *
	 * @return void
	 */
	public function slugs_settings_save() {
		check_admin_referer( 'wpforo_settings_save_board' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'slugs' );
			WPF()->notice->add( 'Successfully Done', 'success' );
		} else {
			$slugs = wpforo_array_args_cast_and_merge( array_filter( array_map( 'sanitize_title', wp_unslash( $_POST['slugs'] ) ) ), WPF()->settings->_slugs );
			$slugs = array_intersect_key($slugs, WPF()->tpl->templates);
			if( $slugs == array_unique( $slugs ) ){
				foreach( $this->generate_option_names( 'slugs' ) as $option_name ) {
					wpforo_update_option( $option_name, $slugs );
				}
				WPF()->notice->add( 'Successfully Done', 'success' );
			}else{
				WPF()->notice->add( 'Please save "Forum template slugs" uniqueness', 'error' );
			}
		}

		/*wp_safe_redirect( wp_get_raw_referer() );
		exit();*/
	}

	/**
	 * general_settings_save from submit action
	 *
	 * @return void
	 */
	public function board_settings_save() {
		check_admin_referer( 'wpforo_settings_save_board' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'board' );
		} else {
			$board = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['board'] ), WPF()->settings->_board );
			foreach( $this->generate_option_names( 'board' ) as $option_name ) {
				wpforo_update_option( $option_name, $board );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * akismet_settings_save from submit action
	 *
	 * @return void
	 */
	public function akismet_settings_save() {
		check_admin_referer( 'wpforo_settings_save_akismet' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'akismet' );
		} else {
			$akismet = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['akismet'] ), WPF()->settings->_akismet );
			foreach( $this->generate_option_names( 'akismet' ) as $option_name ) {
				wpforo_update_option( $option_name, $akismet );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * antispam_settings_save from submit action
	 *
	 * @return void
	 */
	public function antispam_settings_save() {
		check_admin_referer( 'wpforo_settings_save_antispam' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'antispam' );
		} else {
			$antispam                     = wp_unslash( $_POST['antispam'] );
			$antispam['limited_file_ext'] = array_unique( array_filter( preg_split( '#\s*\|\s*|\s*,\s*|\s+#', trim( sanitize_textarea_field( (string) wpfval( $antispam, 'limited_file_ext' ) ) ) ) ) );
			$antispam['exclude_file_ext'] = array_unique( array_filter( preg_split( '#\s*\|\s*|\s*,\s*|\s+#', trim( sanitize_textarea_field( (string) wpfval( $antispam, 'exclude_file_ext' ) ) ) ) ) );
			$antispam                     = wpforo_array_args_cast_and_merge( $antispam, WPF()->settings->_antispam );
			foreach( $this->generate_option_names( 'antispam' ) as $option_name ) {
				wpforo_update_option( $option_name, $antispam );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * authorization_settings_save after submit action
	 *
	 * @return void
	 */
	public function authorization_settings_save() {
		check_admin_referer( 'wpforo_settings_save_authorization' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_authorization' );
		} else {
			$authorization = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['authorization'] ), WPF()->settings->_authorization );
			if( preg_match( '#^https?://[^\r\n\t\s\0]+#isu', $authorization['login_url'] ) ) $authorization['login_url'] = '';
			if( preg_match( '#^https?://[^\r\n\t\s\0]+#isu', $authorization['register_url'] ) ) $authorization['register_url'] = '';
			if( preg_match( '#^https?://[^\r\n\t\s\0]+#isu', $authorization['lost_password_url'] ) ) $authorization['lost_password_url'] = '';

			if( ! preg_match( '#^https?://[^\r\n\t\s\0]+#isu', $authorization['redirect_url_after_login'] ) ) $authorization['redirect_url_after_login'] = '';
			if( ! preg_match( '#^https?://[^\r\n\t\s\0]+#isu', $authorization['redirect_url_after_register'] ) ) $authorization['redirect_url_after_register'] = '';
			if( ! preg_match( '#^https?://[^\r\n\t\s\0]+#isu', $authorization['redirect_url_after_confirm_sbscrb'] ) ) $authorization['redirect_url_after_confirm_sbscrb'] = '';

			$authorization['login_url']                                    = esc_url_raw( $authorization['login_url'] );
			$authorization['register_url']                                 = esc_url_raw( $authorization['register_url'] );
			$authorization['lost_password_url']                            = esc_url_raw( $authorization['lost_password_url'] );
			$authorization['redirect_url_after_login']                     = esc_url_raw( $authorization['redirect_url_after_login'] );
			$authorization['redirect_url_after_register']                  = esc_url_raw( $authorization['redirect_url_after_register'] );
			$authorization['redirect_url_after_confirm_sbscrb']            = esc_url_raw( $authorization['redirect_url_after_confirm_sbscrb'] );
			$authorization['fb_api_id']                                    = sanitize_text_field( $authorization['fb_api_id'] );
			$authorization['fb_api_secret']                                = sanitize_text_field( $authorization['fb_api_secret'] );
			$authorization['fb_redirect_url']                              = esc_url_raw( $authorization['fb_redirect_url'] );

			wpforo_update_option( 'wpforo_authorization', $authorization );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * buddypress_settings_save from submit action
	 *
	 * @return void
	 */
	public function buddypress_settings_save() {
		check_admin_referer( 'wpforo_settings_save_buddypress' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_buddypress' );
		} else {
			$buddypress = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['buddypress'] ), WPF()->settings->_buddypress );
			wpforo_update_option( 'wpforo_buddypress', $buddypress );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * components_settings_save from submit action
	 *
	 * @return void
	 */
	public function components_settings_save() {
		check_admin_referer( 'wpforo_settings_save_components' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'components' );
		} else {
			$components = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['components'] ), WPF()->settings->_components );
			foreach( $this->generate_option_names( 'components' ) as $option_name ) {
				wpforo_update_option( $option_name, $components );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * email_settings_save from submit action
	 *
	 * @return void
	 */
	public function email_settings_save() {
		check_admin_referer( 'wpforo_settings_save_email' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_email' );
		} else {
			$email                         = wp_unslash( $_POST['email'] );
			$email['admin_emails']         = sanitize_text_field( $email['admin_emails'] );
			$email['admin_emails']         = array_map( 'sanitize_email', preg_split('#\s*,\s*#u', trim($email['admin_emails'])) );
			$email['admin_emails']         = array_filter( $email['admin_emails'] );
			if( !$email['admin_emails'] ) $email['admin_emails'] = (array) get_option( 'admin_email' );
			$email                         = wpforo_array_args_cast_and_merge( $email, WPF()->settings->_email );

			$email['from_name']                                    = sanitize_text_field( $email['from_name'] );
			$email['from_email']                                   = sanitize_text_field( $email['from_email'] );
			$email['report_email_subject']                         = sanitize_text_field( $email['report_email_subject'] );
			$email['report_email_message']                         = wpforo_kses( $email['report_email_message'], 'email' );
			$email['wp_new_user_notification_email_admin_subject'] = sanitize_text_field( $email['wp_new_user_notification_email_admin_subject'] );
			$email['wp_new_user_notification_email_admin_message'] = wpforo_kses( $email['wp_new_user_notification_email_admin_message'], 'email' );
			$email['wp_new_user_notification_email_subject']       = sanitize_text_field( $email['wp_new_user_notification_email_subject'] );
			$email['wp_new_user_notification_email_message']       = wpforo_kses( $email['wp_new_user_notification_email_message'], 'email' );
			$email['reset_password_email_message']                 = wpforo_kses( $email['reset_password_email_message'], 'email' );

			wpforo_update_option( 'wpforo_email', $email );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * forums_settings_save from submit action
	 *
	 * @return void
	 */
	public function forums_settings_save() {
		check_admin_referer( 'wpforo_settings_save_forums' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'forums' );
		} else {
			$forums = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['forums'] ), WPF()->settings->_forums );
			foreach( $this->generate_option_names( 'forums' ) as $option_name ) {
				wpforo_update_option( $option_name, $forums );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * logging_settings_save from submit action
	 *
	 * @return void
	 */
	public function logging_settings_save() {
		check_admin_referer( 'wpforo_settings_save_logging' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'logging' );
		} else {
			$logging = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['logging'] ), WPF()->settings->_logging );
			foreach( $this->generate_option_names( 'logging' ) as $option_name ) {
				wpforo_update_option( $option_name, $logging );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * members_settings_save from submit action
	 *
	 * @return void
	 */
	public function members_settings_save() {
		check_admin_referer( 'wpforo_settings_save_members' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_members' );
		} else {
			$members = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['members'] ), WPF()->settings->_members );
			wpforo_update_option( 'wpforo_members', $members );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * notifications_settings_save from submit action
	 *
	 * @return void
	 */
	public function notifications_settings_save() {
		check_admin_referer( 'wpforo_settings_save_notifications' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'notifications' );
		} else {
			$notifications = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['notifications'] ), WPF()->settings->_notifications );
			foreach( $this->generate_option_names( 'notifications' ) as $option_name ) {
				wpforo_update_option( $option_name, $notifications );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * posting_settings_save from submit action
	 *
	 * @return void
	 */
	public function posting_settings_save() {
		check_admin_referer( 'wpforo_settings_save_posting' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'posting' );
		} else {
			$posting                          = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['posting'] ), WPF()->settings->_posting );
			$posting['max_upload_size']       = $posting['max_upload_size'] * 1024 * 1024;
			$posting['edit_own_topic_durr']   = $posting['edit_own_topic_durr'] * 60;
			$posting['delete_own_topic_durr'] = $posting['delete_own_topic_durr'] * 60;
			$posting['edit_own_post_durr']    = $posting['edit_own_post_durr'] * 60;
			$posting['delete_own_post_durr']  = $posting['delete_own_post_durr'] * 60;
			$posting['extra_html_tags']       = sanitize_textarea_field( $posting['extra_html_tags'] );
			foreach( $this->generate_option_names( 'posting' ) as $option_name ) {
				wpforo_update_option( $option_name, $posting );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * profiles_settings_save from submit action
	 *
	 * @return void
	 */
	public function profiles_settings_save() {
		check_admin_referer( 'wpforo_settings_save_profiles' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_profiles' );
		} else {
			$profiles                             = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['profiles'] ), WPF()->settings->_profiles );
			$profiles['default_title']            = sanitize_text_field( $profiles['default_title'] );
			$profiles['online_status_timeout']    = $profiles['online_status_timeout'] * 60;
			$profiles['title_groupids']           = array_map( 'intval', (array) wpfval( $_POST['profiles'], 'title_groupids' ) );
			$profiles['title_secondary_groupids'] = array_map( 'intval', (array) wpfval( $_POST['profiles'], 'title_secondary_groupids' ) );
			wpforo_update_option( 'wpforo_profiles', $profiles );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * rating_settings_save from submit action
	 *
	 * @return void
	 */
	public function rating_settings_save() {
		check_admin_referer( 'wpforo_settings_save_rating' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_rating' );
		} else {
			$rating                    = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['rating'] ), WPF()->settings->_rating );
			$rating['rating_title_ug'] = array_map( 'intval', (array) wpfval( $_POST['rating'], 'rating_title_ug' ) );
			$rating['rating_badge_ug'] = array_map( 'intval', (array) wpfval( $_POST['rating'], 'rating_badge_ug' ) );
			wpforo_update_option( 'wpforo_rating', $rating );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * recaptcha_settings_save from submit action
	 *
	 * @return void
	 */
	public function recaptcha_settings_save() {
		check_admin_referer( 'wpforo_settings_save_recaptcha' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_recaptcha' );
		} else {
			$recaptcha               = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['recaptcha'] ), WPF()->settings->_recaptcha );
			$recaptcha['site_key']   = sanitize_text_field( $recaptcha['site_key'] );
			$recaptcha['secret_key'] = sanitize_text_field( $recaptcha['secret_key'] );
			wpforo_update_option( 'wpforo_recaptcha', $recaptcha );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * rss_settings_save from submit action
	 *
	 * @return void
	 */
	public function rss_settings_save() {
		check_admin_referer( 'wpforo_settings_save_rss' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'rss' );
		} else {
			$rss = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['rss'] ), WPF()->settings->_rss );
			foreach( $this->generate_option_names( 'rss' ) as $option_name ) {
				wpforo_update_option( $option_name, $rss );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * seo_settings_save from submit action
	 *
	 * @return void
	 */
	public function seo_settings_save() {
		check_admin_referer( 'wpforo_settings_save_seo' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'seo' );
		} else {
			$seo             = wp_unslash( $_POST['seo'] );
			$seo['dofollow'] = array_filter( preg_split( '#\s+#', sanitize_textarea_field( (string) wpfval( $seo, 'dofollow' ) ) ) );
			$seo['noindex']  = array_filter( preg_split( '#\s+#', sanitize_textarea_field( (string) wpfval( $seo, 'noindex' ) ) ) );
			$seo['noindex']  = array_map( 'esc_url_raw', $seo['noindex'] );
			$seo             = wpforo_array_args_cast_and_merge( $seo, WPF()->settings->_seo );
			foreach( $this->generate_option_names( 'seo' ) as $option_name ) {
				wpforo_update_option( $option_name, $seo );
			}
			wpforo_clean_cache( 'forum-soft' );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * social_settings_save from submit action
	 *
	 * @return void
	 */
	public function social_settings_save() {
		check_admin_referer( 'wpforo_settings_save_social' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'social' );
		} else {
			$social                    = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['social'] ), WPF()->settings->_social );
			$social['sb']              = wpforo_array_args_cast_and_merge( (array) wpfval( $_POST['social'], 'sb' ), array_map( '__return_false', WPF()->settings->_social['sb'] ) );
			$social['sb_location']     = wpforo_array_args_cast_and_merge( (array) wpfval( $_POST['social'], 'sb_location' ), array_map( '__return_false', WPF()->settings->_social['sb_location'] ) );
			foreach( $this->generate_option_names( 'social' ) as $option_name ) {
				wpforo_update_option( $option_name, $social );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * styles_settings_save from submit action
	 *
	 * @return void
	 */
	public function styles_settings_save() {
		check_admin_referer( 'wpforo_settings_save_styles' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'styles_' . WPF()->tpl->theme );
		} else {
			$styles               = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['styles'] ), WPF()->settings->_styles );
			$styles['custom_css'] = sanitize_textarea_field( $styles['custom_css'] );
			foreach( $this->generate_option_names( 'styles_' . WPF()->tpl->theme ) as $option_name ) {
				wpforo_update_option( $option_name, $styles );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * tags_settings_save from submit action
	 *
	 * @return void
	 */
	public function tags_settings_save() {
		check_admin_referer( 'wpforo_settings_save_tags' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'tags' );
		} else {
			$tags = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['tags'] ), WPF()->settings->_tags );
			foreach( $this->generate_option_names( 'tags' ) as $option_name ) {
				wpforo_update_option( $option_name, $tags );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * topics_settings_save from submit action
	 *
	 * @return void
	 */
	public function topics_settings_save() {
		check_admin_referer( 'wpforo_settings_save_topics' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'topics' );
		} else {
			$topics = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['topics'] ), WPF()->settings->_topics );
			foreach( $this->generate_option_names( 'topics' ) as $option_name ) {
				wpforo_update_option( $option_name, $topics );
			}
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * um_settings_save from submit action
	 *
	 * @return void
	 */
	public function um_settings_save() {
		check_admin_referer( 'wpforo_settings_save_um' );

		if( wpfkey( $_POST, 'reset' ) ) {
			wpforo_delete_option( 'wpforo_um' );
		} else {
			$um = wpforo_array_args_cast_and_merge( wp_unslash( $_POST['um'] ), WPF()->settings->_um );
			wpforo_update_option( 'wpforo_um', $um );
		}

		WPF()->notice->add( 'Successfully Done', 'success' );
		wp_safe_redirect( wp_get_raw_referer() );
		exit();
	}

	/**
	 * @return array with all boardids where need to save options
	 */
	private function get_boardids_to_be_saved() {
		$boardids = (array) WPF()->board->get_current( 'boardid' );
		if( wpfkey( $_POST, 'save_for_all' ) ) {
			$boardids = array_unique( array_merge( $boardids, WPF()->board->get_active_boardids() ) );
		}

		return $boardids;
	}

	/**
	 * @param string $basename
	 *
	 * @return string[]
	 */
	public function generate_option_names( $basename ) {
		return array_map(
			function( $boardid ) use ( $basename ) {
				return 'wpforo_' . ( $boardid ? $boardid . '_' : '' ) . $basename;
			},
			$this->get_boardids_to_be_saved()
		);
	}
}
