<?php

function _wpforo_emails_old_shortcodes_to_new( $txt ){
	return str_replace(
		['[reply_title]', '[reply_desc]', '[topic_desc]', '[author-user-name]',   '[post_author_name]',   '[member_name]',       '[mentioned-user-name]', '[topic-title]', '[post-url]', '[post-desc]', '[post_desc]', '[forum]',      '[topic]',      '[post]',      '[reply]'],
		['[post_title]',  '[post_body]',  '[topic_body]', '[owner_display_name]', '[owner_display_name]', '[user_display_name]', '[user_display_name]',   '[topic_title]', '[post_url]', '[post_body]', '[post_body]', '[forum_link]', '[topic_link]', '[post_link]', '[post_link]'],
		$txt
	);
}

function _wpforo_migrate_old_options_to_new() {
	$blogname            = get_option( 'blogname', '' );
	$adminemail          = get_option( 'admin_email', '' );
	$upload_max_filesize = @ini_get( 'upload_max_filesize' );
	$upload_max_filesize = wpforo_human_size_to_bytes( $upload_max_filesize );
	if( ! $upload_max_filesize || $upload_max_filesize > 10485760 ) $upload_max_filesize = 10485760;

	$_general        = wpforo_get_option( 'wpforo_general_options', [
		'title'       => $blogname . ' ' . __( 'Forum', 'wpforo' ),
		'description' => $blogname . ' ' . __( 'Discussion Board', 'wpforo' ),
		'lang'        => 1
	], false );
	$_features       = wpforo_get_option( 'wpforo_features', [
		'user-admin-bar'                      => 0,
		'page-title'                          => 1,
		'top-bar'                             => 1,
		'top-bar-search'                      => 1,
		'breadcrumb'                          => 1,
		'footer-stat'                         => 1,
		'notifications'                       => 1,
		'notifications-live'                  => 0,
		'notifications-bar'                   => 1,
		'mention-nicknames'                   => 1,
		'content-do_shortcode'                => 0,
		'view-logging'                        => 1,
		'track-logging'                       => 1,
		'goto-unread'                         => 1,
		'goto-unread-button'                  => 0,
		'profile'                             => 1,
		'user-register'                       => 1,
		'user-register-email-confirm'         => 1,
		'disable_new_user_admin_notification' => 1,
		'register-url'                        => 0,
		'login-url'                           => 0,
		'resetpass-url'                       => 1,
		'replace-avatar'                      => 1,
		'avatars'                             => 1,
		'custom-avatars'                      => 1,
		'signature'                           => 1,
		'rating'                              => 1,
		'rating_title'                        => 1,
		'member_cashe'                        => 1,
		'object_cashe'                        => 1,
		'option_cache'                        => 1,
		'html_cashe'                          => 0,
		'memory_cashe'                        => 1,
		'seo-title'                           => 1,
		'seo-meta'                            => 1,
		'seo-profile'                         => 1,
		'rss-feed'                            => 1,
		'font-awesome'                        => 1,
		'bp_activity'                         => 1,
		'bp_notification'                     => 1,
		'bp_forum_tab'                        => 1,
		'um_forum_tab'                        => 1,
		'um_notification'                     => 1,
		'user-synch'                          => 0,
		'role-synch'                          => 1,
		'output-buffer'                       => 1,
		'wp-date-format'                      => 0,
		'subscribe_conf'                      => 1,
		'subscribe_checkbox_on_post_editor'   => 1,
		'subscribe_checkbox_default_status'   => 0,
		'attach-media-lib'                    => 1,
		'admin-cp'                            => 1,
		'debug-mode'                          => 0,
		'copyright'                           => 1,
	],                                    false );
	$_members        = wpforo_get_option( 'wpforo_member_options', [
		'custom_title_is_on'                => 1,
		'default_title'                     => 'Member',
		'members_per_page'                  => 15,
		'online_status_timeout'             => 240,
		'url_structure'                     => 'nicename',
		'search_type'                       => 'search', // can to be 'search' or 'filter'
		'login_url'                         => '',
		'register_url'                      => '',
		'lost_password_url'                 => '',
		'redirect_url_after_login'          => '',
		'redirect_url_after_register'       => '',
		'redirect_url_after_confirm_sbscrb' => '',
		'rating_title_ug'                   => [ 3, 4, 5 ],
		'rating_badge_ug'                   => [ 1, 2, 3, 4, 5 ],
		'title_usergroup'                   => [ 1, 2, 4, 5 ],
		'title_second_usergroup'            => [ 3 ],
	],                                    false );
	$_subscribes     = wpforo_get_option( 'wpforo_subscribe_options', [
		'from_name'                                    => $blogname . ' - ' . __( 'Forum', 'wpforo' ),
		'from_email'                                   => $adminemail,
		'admin_emails'                                 => $adminemail,
		'new_topic_notify'                             => 1,
		'new_reply_notify'                             => 0,
		'confirmation_email_subject'                   => __( "Please confirm subscription to [entry_title]", 'wpforo' ),
		'confirmation_email_message'                   => __( "Hello [member_name]!<br>\n Thank you for subscribing.<br>\n This is an automated response.<br>\n We are glad to inform you that after confirmation you will get updates from - [entry_title].<br>\n Please click on link below to complete this step.<br>\n [confirm_link]", 'wpforo' ),
		'new_topic_notification_email_subject'         => __( "New Topic", 'wpforo' ),
		'new_topic_notification_email_message'         => __( "Hello [member_name]!<br>\n New topic has been created on your subscribed forum - [forum].\n <br><br>\n <strong>[topic_title]</strong>\n <blockquote>\n [topic_desc]\n </blockquote>\n <br><hr>\n If you want to unsubscribe from this forum please use the link below.<br>\n [unsubscribe_link]", 'wpforo' ),
		'new_post_notification_email_subject'          => __( "New Reply", 'wpforo' ),
		'new_post_notification_email_message'          => __( "Hello [member_name]!<br>\n New reply has been posted on your subscribed topic - [topic].\n <br><br>\n <strong>[reply_title]</strong>\n <blockquote >\n [reply_desc]\n </blockquote>\n <br><hr>\n If you want to unsubscribe from this topic please use the link below.<br>\n [unsubscribe_link]", 'wpforo' ),
		'user_post_notification_email_subject'         => __( "New Post from [post_author_name]", 'wpforo' ),
		'user_post_notification_email_message'         => __( "Hello [member_name]!<br>\n New topic or post has been created by [post_author_name] you are following.\n <br><br>\n <strong>[post_title]</strong>\n <blockquote >\n [post_desc]\n </blockquote>\n <br><hr>\n If you want to unfollow this user please use the link below.<br>\n [unsubscribe_link]", 'wpforo' ),
		'report_email_subject'                         => __( "Forum Post Report", 'wpforo' ),
		'report_email_message'                         => __( "<strong>Report details:</strong>\n Reporter: [reporter], <br>\n Message: [message],<br>\n <br>\n [post_url]", 'wpforo' ),
		'overwrite_new_user_notification_admin'        => 1,
		'wp_new_user_notification_email_admin_subject' => __( "[blogname] New User Registration", 'wpforo' ),
		'wp_new_user_notification_email_admin_message' => __( "New user registration on your site [blogname]:\n\nUsername: [user_login]\n\nEmail: [user_email]\n", 'wpforo' ),
		'overwrite_new_user_notification'              => 1,
		'wp_new_user_notification_email_subject'       => __( "[blogname] Your username and password info", 'wpforo' ),
		'wp_new_user_notification_email_message'       => __( "Username: [user_login]\n\nTo set your password, visit the following address:\n\n[set_password_url]\n\n", 'wpforo' ),
		'overwrite_reset_password_email_message'       => 1,
		'reset_password_email_message'                 => __( "Hello! \n\n You asked us to reset your password for your account using the email address [user_login]. \n\n If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen. \n\n To reset your password, visit the following address: \n\n [reset_password_url] \n\n Thanks!", 'wpforo' ),
		'user_mention_notify'                          => 1,
		'user_mention_email_subject'                   => __( "You have been mentioned in forum post", 'wpforo' ),
		'user_mention_email_message'                   => __( "Hi [mentioned-user-name]! <br>\n\n You have been mentioned in a post on \"[topic-title]\" by [author-user-name].<br/><br/>\n\n Post URL: [post-url]", 'wpforo' ),
	],                                    false );
	$_tools_antispam = wpforo_get_option( 'wpforo_tools_antispam', [
		'spam_filter'                   => 1,
		'spam_filter_level_topic'       => mt_rand( 30, 60 ),
		'spam_filter_level_post'        => mt_rand( 30, 60 ),
		'spam_user_ban'                 => 0,
		'new_user_max_posts'            => 3,
		'unapprove_post_if_user_is_new' => 0,
		'spam_user_ban_notification'    => 1,
		'min_number_post_to_attach'     => 0,
		'min_number_post_to_link'       => 0,
		'spam_file_scanner'             => 0,
		'limited_file_ext'              => 'pdf|doc|docx|txt|htm|html|rtf|xml|xls|xlsx|zip|rar|tar|gz|bzip|7z',
		'exclude_file_ext'              => 'pdf|doc|docx|txt',
		'rc_site_key'                   => '',
		'rc_secret_key'                 => '',
		'rc_theme'                      => 'light',
		'rc_login_form'                 => 0,
		'rc_reg_form'                   => 0,
		'rc_lostpass_form'              => 0,
		'rc_wpf_login_form'             => 1,
		'rc_wpf_reg_form'               => 1,
		'rc_wpf_lostpass_form'          => 1,
		'rc_topic_editor'               => 1,
		'rc_post_editor'                => 1,
		'html'                          => 'embed(src width height name pluginspage type wmode allowFullScreen allowScriptAccess flashVars),',
	],                                    false );
	$_forums         = wpforo_get_option( 'wpforo_forum_options', [
		'layout_extended_intro_topics_toggle' => 1,
		'layout_extended_intro_topics_count'  => 5,
		'layout_extended_intro_topics_length' => 45,
		'layout_qa_intro_topics_toggle'       => 1,
		'layout_qa_intro_topics_count'        => 3,
		'layout_qa_intro_topics_length'       => 90,
		'layout_threaded_intro_topics_toggle' => 0,
		'layout_threaded_display_subforums'   => 1,
		'layout_threaded_intro_topics_count'  => 10,
		'layout_threaded_intro_topics_length' => 0,
		'layout_threaded_filter_buttons'      => 1,
		'layout_threaded_add_topic_button'    => 1,
		'display_current_viewers'             => 1,
	],                                    false );
	$_posts          = wpforo_get_option( 'wpforo_post_options', [
		'layout_extended_intro_posts_toggle' => 1,
		'layout_extended_intro_posts_count'  => 4,
		'layout_extended_intro_posts_length' => 50,
		'recent_posts_type'                  => 'topics',
		'tags'                               => 1,
		'max_tags'                           => 5,
		'tags_per_page'                      => 100,
		'topics_per_page'                    => 10,
		'edit_topic'                         => 1,
		'edit_post'                          => 1,
		'eot_durr'                           => 300,
		'dot_durr'                           => 300,
		'posts_per_page'                     => 15,
		'layout_threaded_posts_per_page'     => 5,
		'layout_qa_posts_per_page'           => 15,
		'layout_qa_comments_limit_count'     => 3,
		'layout_qa_first_post_reply'         => 1,
		'layout_threaded_nesting_level'      => 5,
		'layout_threaded_first_post_reply'   => 0,
		'eor_durr'                           => 300,
		'dor_durr'                           => 300,
		'max_upload_size'                    => $upload_max_filesize,
		'display_current_viewers'            => 1,
		'display_recent_viewers'             => 1,
		'display_admin_viewers'              => 1,
		'union_first_post'                   => [
			1 => 0,
			2 => 0,
			3 => 1,
			4 => 0,
		],
		'search_max_results'                 => 100,
		'topic_title_min_length'             => 1,
		'topic_title_max_length'             => 0,
		'topic_body_min_length'              => 2,
		'topic_body_max_length'              => 0,
		'post_body_min_length'               => 2,
		'post_body_max_length'               => 0,
		'comment_body_min_length'            => 2,
		'comment_body_max_length'            => 0,
		'toolbar_location_topic'             => 'top',
		'toolbar_location_reply'             => 'top',
	],                                    false );
	$_forms          = wpforo_get_option( 'wpforo_form_options', [
		'qa_comments_rich_editor'    => 0,
		'threaded_reply_rich_editor' => 1,
		'qa_display_answer_editor'   => 1,
	],                                    false );
	$_activity       = wpforo_get_option( 'wpforo_activity_options', [
		'edit_topic'             => 1,
		'edit_post'              => 1,
		'edit_log_display_limit' => 0,
	],                                    false );
	$_revisions      = wpforo_get_option( 'wpforo_revision_options', [
		'auto_draft_interval' => 60000,
		'max_drafts_per_page' => 3,
		'is_preview_on'       => 1,
		'is_draft_on'         => 1,
	],                                    false );
	$_styles         = wpforo_get_option( 'wpforo_style_options', [
		'font_size_forum'        => 17,
		'font_size_topic'        => 16,
		'font_size_post_content' => 14,
		'custom_css'             => "#wpforo-wrap {\r\n   font-size: 13px; width: 100%; padding:10px 0; margin:0px;\r\n}\r\n",
	],                                    false );
	$_theme          = wpforo_get_option( 'wpforo_theme_options', [ 'style' => 'default' ], false );
	$_seo            = wpforo_get_option( 'wpforo_seo_options', [
		'members_sitemap'        => 1,
		'forums_sitemap'         => 1,
		'topics_sitemap'         => 1,
		'sitemap_items_per_page' => 1000,
		'allow_ping'             => 1,
		'ping_immediately'       => 0,
	],                                    false );
	$_tools_misc     = wpforo_get_option( 'wpforo_tools_misc', [
		'dofollow'          => '',
		'noindex'           => '',
		'admin_note'        => '',
		'admin_note_groups' => [ 1, 2, 3, 4, 5 ],
		'admin_note_pages'  => [ 'forum' ],
	],                                    false );
	$_api            = wpforo_get_option( 'wpforo_api_options', [
		'fb_api_id'          => '',
		'fb_api_secret'      => '',
		'fb_login'           => 0,
		'fb_load_sdk'        => 1,
		'fb_sdk_version'     => 'v2.10',
		'fb_lb_on_lp'        => 1,
		'fb_lb_on_rp'        => 1,
		'fb_redirect'        => 'profile',
		'fb_redirect_url'    => '',
		'tw_load_wjs'        => 1,
		'gg_load_js'         => 0,
		'vk_load_js'         => 1,
		'ok_load_js'         => 1,
		'sb_on'              => 1,
		'sb_toggle_on'       => 0,
		'sb'                 => [ 'fb' => 1, 'tw' => 1, 'wapp' => 1, 'lin' => 0, 'vk' => 0, 'ok' => 0, 'gg' => 0 ],
		'sb_icon'            => 'mixed',
		'sb_type'            => 'icon',
		'sb_style'           => 'grey',
		'sb_toggle'          => 4,
		'sb_location_toggle' => 'top',
		'sb_toggle_type'     => 'collapsed',
		'sb_location'        => [ 'top' => 0, 'bottom' => 1 ],
	],                                    false );


	/**
	 * #### -- new settings array -- ####
	 */
	$general = [
		'admin_bar'      => $_features['user-admin-bar'] ? array_map( 'intval', (array) WPF()->usergroup->get_usergroups( 'groupid' ) ) : [],
		'wp_date_format' => $_features['wp-date-format'],
		'debug_mode'     => $_features['debug-mode'],
		'fontawesome'    => ( $_features['font-awesome'] == 1 ? 'forum' : ( $_features['font-awesome'] == 2 ? 'sitewide' : 'off' ) ),
	];

	$members = [
		'list_order'       => 'posts',
		'hide_inactive'    => true,
		'members_per_page' => $_members['members_per_page'],
		'search_type'      => $_members['search_type'],
	];

	$profiles = [
		'profile'                  => ( $_features['profile'] == 2 ? 'wpforo' : ( $_features['profile'] == 3 ? 'bp' : ( $_features['profile'] == 4 ? 'um' : 'default' ) ) ),
		'url_structure'            => $_members['url_structure'],
		'online_status_timeout'    => $_members['online_status_timeout'],
		'custom_title_is_on'       => $_members['custom_title_is_on'],
		'default_title'            => $_members['default_title'],
		'title_groupids'           => $_members['title_usergroup'],
		'title_secondary_groupids' => $_members['title_second_usergroup'],
		'mention_nicknames'        => $_features['mention-nicknames'],
		'avatars'                  => $_features['avatars'],
		'custom_avatars'           => $_features['custom-avatars'],
		'replace_avatar'           => $_features['replace-avatar'],
		'signature'                => $_features['signature'],
	];

	$rating = [
		'rating'          => $_features['rating'],
		'rating_title'    => $_features['rating_title'],
		'topic_points'    => 2,
		'post_points'     => 1,
		'like_points'     => 0.5,
		'dislike_points'  => - 0.5,
		'rating_title_ug' => $_members['rating_title_ug'],
		'rating_badge_ug' => $_members['rating_badge_ug'],
	];

	$authorization = [
		'user_register'                                => $_features['user-register'],
		'user_register_email_confirm'                  => $_features['user-register-email-confirm'],
		'role_synch'                                   => $_features['role-synch'],
		'use_our_register_url'                         => $_features['register-url'],
		'use_our_login_url'                            => $_features['login-url'],
		'use_our_lostpassword_url'                     => $_features['resetpass-url'],
		'login_url'                                    => $_members['login_url'],
		'register_url'                                 => $_members['register_url'],
		'lost_password_url'                            => $_members['lost_password_url'],
		'redirect_url_after_login'                     => $_members['redirect_url_after_login'],
		'redirect_url_after_register'                  => $_members['redirect_url_after_register'],
		'redirect_url_after_confirm_sbscrb'            => $_members['redirect_url_after_confirm_sbscrb'],
		'fb_api_id'                                    => $_api['fb_api_id'],
		'fb_api_secret'                                => $_api['fb_api_secret'],
		'fb_login'                                     => $_api['fb_login'],
		'fb_lb_on_lp'                                  => $_api['fb_lb_on_lp'],
		'fb_lb_on_rp'                                  => $_api['fb_lb_on_rp'],
		'fb_redirect'                                  => $_api['fb_redirect'],
		'fb_redirect_url'                              => $_api['fb_redirect_url'],
	];

	$recaptcha = [
		'site_key'          => $_tools_antispam['rc_site_key'],
		'secret_key'        => $_tools_antispam['rc_secret_key'],
		'theme'             => $_tools_antispam['rc_theme'],
		'topic_editor'      => $_tools_antispam['rc_topic_editor'],
		'post_editor'       => $_tools_antispam['rc_post_editor'],
		'wpf_login_form'    => $_tools_antispam['rc_wpf_login_form'],
		'wpf_reg_form'      => $_tools_antispam['rc_wpf_reg_form'],
		'wpf_lostpass_form' => $_tools_antispam['rc_wpf_lostpass_form'],
		'login_form'        => $_tools_antispam['rc_login_form'],
		'reg_form'          => $_tools_antispam['rc_reg_form'],
		'lostpass_form'     => $_tools_antispam['rc_lostpass_form'],
	];

	$buddypress = [
		'activity'     => $_features['bp_activity'],
		'notification' => $_features['bp_notification'],
		'forum_tab'    => $_features['bp_forum_tab'],
	];

	$um = [
		'notification' => $_features['um_notification'],
		'forum_tab'    => $_features['um_forum_tab'],
	];

	$forums = [
		'layout_extended_intro_topics_toggle' => $_forums['layout_extended_intro_topics_toggle'],
		'layout_extended_intro_topics_count'  => $_forums['layout_extended_intro_topics_count'],
		'layout_extended_intro_topics_length' => $_forums['layout_extended_intro_topics_length'],
		'layout_qa_intro_topics_toggle'       => $_forums['layout_qa_intro_topics_toggle'],
		'layout_qa_intro_topics_count'        => $_forums['layout_qa_intro_topics_count'],
		'layout_qa_intro_topics_length'       => $_forums['layout_qa_intro_topics_length'],
		'layout_threaded_intro_topics_toggle' => $_forums['layout_threaded_intro_topics_toggle'],
		'layout_threaded_display_subforums'   => $_forums['layout_threaded_display_subforums'],
		'layout_threaded_filter_buttons'      => $_forums['layout_threaded_filter_buttons'],
		'layout_threaded_add_topic_button'    => $_forums['layout_threaded_add_topic_button'],
		'layout_threaded_intro_topics_count'  => $_forums['layout_threaded_intro_topics_count'],
		'layout_threaded_intro_topics_length' => $_forums['layout_threaded_intro_topics_length'],
	];

	$topics = [
		'layout_extended_intro_posts_toggle' => $_posts['layout_extended_intro_posts_toggle'],
		'layout_extended_intro_posts_count'  => $_posts['layout_extended_intro_posts_count'],
		'layout_extended_intro_posts_length' => $_posts['layout_extended_intro_posts_length'],
		'layout_qa_posts_per_page'           => $_posts['layout_qa_posts_per_page'],
		'layout_qa_comments_limit_count'     => $_posts['layout_qa_comments_limit_count'],
		'layout_qa_first_post_reply'         => $_posts['layout_qa_first_post_reply'],
		'layout_threaded_posts_per_page'     => $_posts['layout_threaded_posts_per_page'],
		'layout_threaded_nesting_level'      => $_posts['layout_threaded_nesting_level'],
		'layout_threaded_first_post_reply'   => $_posts['layout_threaded_first_post_reply'],
		'topics_per_page'                    => $_posts['topics_per_page'],
		'posts_per_page'                     => $_posts['posts_per_page'],
		'search_max_results'                 => $_posts['search_max_results'],
		'union_first_post'                   => $_posts['union_first_post'],
		'recent_posts_type'                  => $_posts['recent_posts_type'],
	];

	$posting = [
		'qa_display_answer_editor'      => $_forms['qa_display_answer_editor'],
		'qa_comments_rich_editor'       => $_forms['qa_comments_rich_editor'],
		'threaded_reply_rich_editor'    => $_forms['threaded_reply_rich_editor'],
		'topic_title_min_length'        => $_posts['topic_title_min_length'],
		'topic_title_max_length'        => $_posts['topic_title_max_length'],
		'topic_body_min_length'         => $_posts['topic_body_min_length'],
		'topic_body_max_length'         => $_posts['topic_body_max_length'],
		'post_body_min_length'          => $_posts['post_body_min_length'],
		'post_body_max_length'          => $_posts['post_body_max_length'],
		'comment_body_min_length'       => $_posts['comment_body_min_length'],
		'comment_body_max_length'       => $_posts['comment_body_max_length'],
		'edit_own_topic_durr'           => $_posts['eot_durr'],
		'delete_own_topic_durr'         => $_posts['dot_durr'],
		'edit_own_post_durr'            => $_posts['eor_durr'],
		'delete_own_post_durr'          => $_posts['dor_durr'],
		'edit_topic'                    => $_posts['edit_topic'],
		'edit_post'                     => $_posts['edit_post'],
		'edit_log_display_limit'        => $_activity['edit_log_display_limit'],
		'is_preview_on'                 => $_revisions['is_preview_on'],
		'is_draft_on'                   => $_revisions['is_draft_on'],
		'auto_draft_interval'           => $_revisions['auto_draft_interval'],
		'max_drafts_per_page'           => $_revisions['max_drafts_per_page'],
		'max_upload_size'               => $_posts['max_upload_size'],
		'attachs_to_medialib'           => $_features['attach-media-lib'],
		'topic_editor_toolbar_location' => $_posts['toolbar_location_topic'],
		'reply_editor_toolbar_location' => $_posts['toolbar_location_reply'],
		'content_do_shortcode'          => $_features['content-do_shortcode'],
		'extra_html_tags'               => $_tools_antispam['html'],
	];

	$components = [
		'admin_cp'       => $_features['admin-cp'],
		'page_title'     => $_features['page-title'],
		'top_bar'        => $_features['top-bar'],
		'top_bar_search' => $_features['top-bar-search'],
		'breadcrumb'     => $_features['breadcrumb'],
		'footer'         => true,
		'footer_stat'    => $_features['footer-stat'],
		'copyright'      => $_features['copyright'],
	];

	$styles = [
		'font_size_forum'        => $_styles['font_size_forum'],
		'font_size_topic'        => $_styles['font_size_topic'],
		'font_size_post_content' => $_styles['font_size_post_content'],
		'custom_css'             => $_styles['custom_css'],
		'style'                  => $_theme['style'],
	];

	$tags = [
		'max_per_topic' => $_posts['max_tags'],
		'per_page'      => $_posts['tags_per_page'],
		'length'        => 25,
		'suggest_limit' => 5,
		'lowercase'     => false,
	];

	$email = [
		'from_name'                                    => $_subscribes['from_name'],
		'from_email'                                   => $_subscribes['from_email'],
		'admin_emails'                                 => $_subscribes['admin_emails'],
		'new_topic_notify'                             => $_subscribes['new_topic_notify'],
		'new_reply_notify'                             => $_subscribes['new_reply_notify'],
		'disable_new_user_admin_notification'          => $_features['disable_new_user_admin_notification'],
		'report_email_subject'                         => _wpforo_emails_old_shortcodes_to_new( $_subscribes['report_email_subject'] ),
		'report_email_message'                         => _wpforo_emails_old_shortcodes_to_new( $_subscribes['report_email_message'] ),
		'overwrite_new_user_notification_admin'        => $_subscribes['overwrite_new_user_notification_admin'],
		'wp_new_user_notification_email_admin_subject' => _wpforo_emails_old_shortcodes_to_new( $_subscribes['wp_new_user_notification_email_admin_subject'] ),
		'wp_new_user_notification_email_admin_message' => _wpforo_emails_old_shortcodes_to_new( $_subscribes['wp_new_user_notification_email_admin_message'] ),
		'overwrite_new_user_notification'              => $_subscribes['overwrite_new_user_notification'],
		'wp_new_user_notification_email_subject'       => _wpforo_emails_old_shortcodes_to_new($_subscribes['wp_new_user_notification_email_subject']),
		'wp_new_user_notification_email_message'       => _wpforo_emails_old_shortcodes_to_new($_subscribes['wp_new_user_notification_email_message']),
		'overwrite_reset_password_email'               => $_subscribes['overwrite_reset_password_email_message'],
		'reset_password_email_message'                 => _wpforo_emails_old_shortcodes_to_new($_subscribes['reset_password_email_message']),
	];
	$email['admin_emails']         = sanitize_text_field( $email['admin_emails'] );
	$email['admin_emails']         = array_map( 'sanitize_email', preg_split('#\s*,\s*#u', trim($email['admin_emails'])) );
	$email['admin_emails']         = array_filter( $email['admin_emails'] );
	if( !$email['admin_emails'] ) $email['admin_emails'] = (array) get_option( 'admin_email' );

	$subscriptions = [
		'subscribe_confirmation'               => $_features['subscribe_conf'],
		'subscribe_checkbox_on_post_editor'    => $_features['subscribe_checkbox_on_post_editor'],
		'subscribe_checkbox_default_status'    => $_features['subscribe_checkbox_default_status'],
		'user_mention_notify'                  => $_subscribes['user_mention_notify'],
		'user_following_notify'                => true,
		'confirmation_email_subject'           => _wpforo_emails_old_shortcodes_to_new($_subscribes['confirmation_email_subject']),
		'confirmation_email_message'           => _wpforo_emails_old_shortcodes_to_new($_subscribes['confirmation_email_message']),
		'new_topic_notification_email_subject' => _wpforo_emails_old_shortcodes_to_new($_subscribes['new_topic_notification_email_subject']),
		'new_topic_notification_email_message' => _wpforo_emails_old_shortcodes_to_new($_subscribes['new_topic_notification_email_message']),
		'new_post_notification_email_subject'  => _wpforo_emails_old_shortcodes_to_new($_subscribes['new_post_notification_email_subject']),
		'new_post_notification_email_message'  => _wpforo_emails_old_shortcodes_to_new($_subscribes['new_post_notification_email_message']),
		'user_mention_email_subject'           => _wpforo_emails_old_shortcodes_to_new($_subscribes['user_mention_email_subject']),
		'user_mention_email_message'           => _wpforo_emails_old_shortcodes_to_new($_subscribes['user_mention_email_message']),
		'user_following_email_subject'         => _wpforo_emails_old_shortcodes_to_new($_subscribes['user_post_notification_email_subject']),
		'user_following_email_message'         => _wpforo_emails_old_shortcodes_to_new($_subscribes['user_post_notification_email_message']),
	];

	$notifications = [
		'notifications'      => $_features['notifications'],
		'notifications_live' => $_features['notifications-live'],
		'notifications_bar'  => $_features['notifications-bar'],
	];

	$logging = [
		'view_logging'                  => $_features['view-logging'],
		'track_logging'                 => $_features['track-logging'],
		'goto_unread'                   => $_features['goto-unread'],
		'goto_unread_button'            => $_features['goto-unread-button'],
		'display_forum_current_viewers' => $_forums['display_current_viewers'],
		'display_topic_current_viewers' => $_posts['display_current_viewers'],
		'display_recent_viewers'        => $_posts['display_recent_viewers'],
		'display_admin_viewers'         => $_posts['display_admin_viewers'],
	];

	$seo = [
		'seo_title'       => $_features['seo-title'],
		'seo_meta'        => $_features['seo-meta'],
		'seo_profile'     => $_features['seo-profile'],
		'forums_sitemap'  => $_seo['forums_sitemap'],
		'topics_sitemap'  => $_seo['topics_sitemap'],
		'members_sitemap' => $_seo['members_sitemap'],
		'dofollow'        => array_filter( preg_split( '#\s+#', $_tools_misc['dofollow'] ) ),
		'noindex'         => array_filter( preg_split( '#\s+#', $_tools_misc['noindex'] ) ),
	];

	$antispam = [
		'spam_filter'                   => $_tools_antispam['spam_filter'],
		'spam_user_ban'                 => $_tools_antispam['spam_user_ban'],
		'spam_filter_level_topic'       => $_tools_antispam['spam_filter_level_topic'],
		'spam_filter_level_post'        => $_tools_antispam['spam_filter_level_post'],
		'new_user_max_posts'            => $_tools_antispam['new_user_max_posts'],
		'unapprove_post_if_user_is_new' => $_tools_antispam['unapprove_post_if_user_is_new'],
		'min_number_posts_to_attach'    => $_tools_antispam['min_number_post_to_attach'],
		'min_number_posts_to_link'      => $_tools_antispam['min_number_post_to_link'],
		'limited_file_ext'              => array_unique( array_filter( preg_split( '#\s*\|\s*|\s*,\s*|\s+#', trim( $_tools_antispam['limited_file_ext'] ) ) ) ),
		'spam_file_scanner'             => $_tools_antispam['spam_file_scanner'],
		'exclude_file_ext'              => array_unique( array_filter( preg_split( '#\s*\|\s*|\s*,\s*|\s+#', trim( $_tools_antispam['exclude_file_ext'] ) ) ) ),
	];

	$rss = [
		'feed'         => $_features['rss-feed'],
		'feed_general' => true,
		'feed_forum'   => true,
		'feed_topic'   => true,
	];

	$social = [
		'sb'                 => $_api['sb'],
		'sb_on'              => $_api['sb_on'],
		'sb_toggle_on'       => $_api['sb_toggle_on'],
		'sb_style'           => $_api['sb_style'],
		'sb_type'            => $_api['sb_type'],
		'sb_toggle'          => $_api['sb_toggle'],
		'sb_toggle_type'     => $_api['sb_toggle_type'],
		'sb_icon'            => $_api['sb_icon'],
		'sb_location'        => $_api['sb_location'],
		'sb_location_toggle' => $_api['sb_location_toggle'],
	];

	wpforo_update_option( 'wpforo_general', $general );
	wpforo_update_option( 'wpforo_members', $members );
	wpforo_update_option( 'wpforo_profiles', $profiles );
	wpforo_update_option( 'wpforo_rating', $rating );
	wpforo_update_option( 'wpforo_authorization', $authorization );
	wpforo_update_option( 'wpforo_email', $email );
	wpforo_update_option( 'wpforo_recaptcha', $recaptcha );
	wpforo_update_option( 'wpforo_buddypress', $buddypress );
	wpforo_update_option( 'wpforo_um', $um );
	wpforo_update_option( 'forums', $forums );
	wpforo_update_option( 'topics', $topics );
	wpforo_update_option( 'posting', $posting );
	wpforo_update_option( 'components', $components );
	wpforo_update_option( 'styles', $styles );
	wpforo_update_option( 'tags', $tags );
	wpforo_update_option( 'subscriptions', $subscriptions );
	wpforo_update_option( 'notifications', $notifications );
	wpforo_update_option( 'logging', $logging );
	wpforo_update_option( 'seo', $seo );
	wpforo_update_option( 'antispam', $antispam );
	wpforo_update_option( 'rss', $rss );
	wpforo_update_option( 'social', $social );

	WPF()->phrase->set_language_status( $_general['lang'] );
	WPF()->usergroup->set_default( wpforo_get_option( 'wpforo_default_groupid', 3 ) );
}
