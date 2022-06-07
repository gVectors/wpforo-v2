<?php
/*
* Plugin Name: wpForo
* Plugin URI: https://wpforo.com
* Description: WordPress Forum plugin. wpForo is a full-fledged forum solution for your community. Comes with multiple modern forum layouts.
* Author: gVectors Team
* Author URI: https://gvectors.com/
* Version: 2.0.0.1
* Text Domain: wpforo
* Domain Path: /languages
*/

namespace wpforo;

define( 'WPFORO_VERSION', '2.0.0.1' );

//Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

define( 'WPFORO_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'WPFORO_URL', rtrim( plugins_url( '', __FILE__ ), '/' ) );
define( 'WPFORO_BASENAME', plugin_basename( __FILE__ ) ); //wpforo/wpforo.php

require_once WPFORO_DIR . "/autoload.php";

use stdClass;
use wpdb;
use wpforo\classes\Actions;
use wpforo\classes\Activity;
use wpforo\classes\API;
use wpforo\classes\Boards;
use wpforo\classes\Cache;
use wpforo\classes\Feed;
use wpforo\classes\Forms;
use wpforo\classes\Forums;
use wpforo\classes\Logs;
use wpforo\classes\Members;
use wpforo\classes\Moderation;
use wpforo\classes\Notices;
use wpforo\classes\Permissions;
use wpforo\classes\Phrases;
use wpforo\classes\PostMeta;
use wpforo\classes\Posts;
use wpforo\classes\RamCache;
use wpforo\classes\SEO;
use wpforo\classes\Settings;
use wpforo\classes\Template;
use wpforo\classes\Topics;
use wpforo\classes\UserGroups;
use wpforo\modules\bookmarks\Bookmarks;
use wpforo\modules\revisions\Revisions;
use wpforo\modules\reactions\Reactions;
use wpforo\modules\subscriptions\Subscriptions;

final class wpforo {
	private static $_instance = null;

	/** @var wpdb * */
	public $db;
	public $default;

	public  $blog_prefix  = "wp_";
	public  $base_prefix  = "wpforo_";
	public  $prefix       = "wpforo_";
	public  $_base_tables = [
		'boards',
		'usergroups',
		'profiles',
		'logs',
		'follows',
		'bookmarks',
	];
	public  $_tables      = [
		'accesses',
		'activity',
		'forums',
		'languages',
		'phrases',
		'postmeta',
		'posts',
		'post_revisions',
		'subscribes',
		'topics',
		'tags',
		'visits',
		'reactions',
	];
	public  $tables;
	private $_folders     = [
		'assets',
		'attachments',
		'avatars',
		'covers',
		'cache',
		'default_attachments',
		'emoticons',
	];
	public  $folders      = [];

	public $current_url;
	public $GET;
	public $current_object;

	public $wp_current_user                 = [];
	public $current_user                    = [];
	public $current_usermeta                = [];
	public $current_user_groupid            = 4;
	public $current_user_groupids           = [ 4 ];
	public $current_user_secondary_groupids = [];
	public $current_userid     = 0;
	public $current_user_login = '';
	public $current_user_email = '';
	public $current_user_display_name       = '';
	public $current_user_status             = '';
	public $current_user_accesses           = [];
	public $session_token                   = '';

	public $tools_cleanup;
	public $tools_misc;
	public $tools_legal;
	public $locale;

	public $dissmissed;

	/** @var Settings */
	public $settings;
	/** @var Actions */
	public $action;
	/** @var Activity */
	public $activity;
	/** @var API */
	public $api;
	/** @var Boards */
	public $board;
	/** @var Cache */
	public $cache;
	/** @var Feed */
	public $feed;
	/** @var Forms */
	public $form;
	/** @var Forums */
	public $forum;
	/** @var Logs */
	public $log;
	/** @var Members */
	public $member;
	/** @var Moderation */
	public $moderation;
	/** @var Notices */
	public $notice;
	/** @var Permissions */
	public $perm;
	/** @var Phrases */
	public $phrase;
	/** @var PostMeta */
	public $postmeta;
	/** @var Posts */
	public $post;
	/** @var RamCache */
	public $ram_cache;
	/** @var Revisions */
	public $revision;
	/** @var Reactions */
	public $reaction;
	/** @var Bookmarks */
	public $bookmark;
	/** @var SEO */
	public $seo;
	/** @var Subscriptions */
	public $sbscrb;
	/** @var Template */
	public $tpl;
	/** @var Topics */
	public $topic;
	/** @var UserGroups */
	public $usergroup;

	public static function instance() {
		if( is_null( self::$_instance ) ) self::$_instance = new self();

		return self::$_instance;
	}

	private function init_db() {
		global $wpdb;
		$this->db = $wpdb;
	}

	public function is_db_mysql8() {
		if( preg_match( '#([\d.]+)-MariaDB#iu', $this->db->get_var( "SELECT VERSION()" ), $match ) ) {
			$use_mysql8 = version_compare( $match[1], '10.2.6', '>=' );
		} else {
			$use_mysql8 = version_compare( $this->db->db_version(), '8.0.0', '>=' );
		}

		return apply_filters( 'wpforo_use_mysql8', $use_mysql8 );
	}

	private function __construct() {
		$this->init_db();
		$this->init_hooks();
	}

	public function generate_prefix( $boardid ){
		$boardid = intval( $boardid );
		return $this->base_prefix . ( $boardid ? $boardid . '_' : '' );
	}

	private function init_prefix() {
		$boardid      = $this->board->get_current( 'boardid' );
		$this->prefix = $this->generate_prefix( $boardid );
	}

	public function change_board( $args = [] ) {
		do_action( 'wpforo_before_change_board', $args );

		$boardid   = $this->board->get_current( 'boardid' );
		$is_inited = $this->board->is_inited;
		$this->board->init_current( $args );

		if( !$is_inited || $boardid !== $this->board->get_current( 'boardid' ) ){
			$this->ram_cache->reset();

			$this->init_prefix();
			$this->init_tables();
			$this->init_folders();
			$this->init_options();

			do_action( 'wpforo_after_change_board', $args );
		}
	}

	private function init_base_classes() {
		$this->requires();
		$this->init_defaults();
		$this->reset_current_object();
		$this->init_base_tables();
		$this->init_folders();
		$this->init_base_options();

		do_action( 'wpforo_before_init_base_classes' );

		$this->settings  = new Settings();
		$this->tpl       = new Template();
		$this->ram_cache = new RamCache();
		$this->cache     = new Cache();
		$this->board     = new Boards();
		$this->usergroup = new UserGroups();
		$this->member    = new Members();
		$this->notice    = new Notices();

		do_action( 'wpforo_after_init_base_classes' );
	}

	private function init_classes() {
		do_action( 'wpforo_before_init_classes' );

		$this->action     = new Actions();
		$this->activity   = new Activity();
		$this->api        = new API();
		$this->feed       = new Feed();
		$this->form       = new Forms();
		$this->forum      = new Forums();
		$this->log        = new Logs();
		$this->moderation = new Moderation();
		$this->perm       = new Permissions();
		$this->phrase     = new Phrases();
		$this->postmeta   = new PostMeta();
		$this->post       = new Posts();
		$this->seo        = new SEO();
		$this->topic      = new Topics();
		$this->reaction   = new Reactions();
		if( wpforo_is_module_enabled( 'bookmarks' ) )     $this->bookmark = new Bookmarks();
		if( wpforo_is_module_enabled( 'revisions' ) )     $this->revision = new Revisions();
		if( wpforo_is_module_enabled( 'subscriptions' ) ) $this->sbscrb   = new Subscriptions();

		do_action( 'wpforo_after_init_classes' );
	}

	public function set_locale( $locale ) {
		$this->locale = $locale;
		do_action( 'wpforo_after_set_locale', $locale );
	}

	private function init_hooks() {
		add_action(
			'after_setup_theme',
			function() {
				$this->init_base_classes();
				if( ! $this->is_installed() ) $this->init();
			},
			0
		);
		add_action( 'change_locale', [ $this, 'set_locale' ] );
		add_action( 'init', function() { $this->set_locale( get_locale() ); } );
		add_action( 'widgets_init', function() {
			$boards = $this->board->get_boards( [ 'status' => true ] );
			foreach( $boards as $board ) {
				register_sidebar( [
					                  'id'            => sprintf(
						                  'wpforo_%1$ssidebar',
						                  $board['boardid'] ? $board['boardid'] . '_' : ''
					                  ),
					                  'name'          => __( 'wpForo Sidebar', 'wpforo' ) . ' - ( ' . $board['title'] . ' )',
					                  'description'   => __( "NOTE: If you're going to add widgets in this sidebar, please use 'Full Width' template for wpForo index page to avoid sidebar duplication.", 'wpforo' ),
					                  'before_widget' => '<aside id="%1$s" class="footer-widget-col %2$s clearfix">',
					                  'after_widget'  => '</aside>',
					                  'before_title'  => '<h3 class="widget-title">',
					                  'after_title'   => '</h3>',
				                  ] );
			}

			//			register_widget( 'wpforo\widgets\LoginForm' );
			//			register_widget( 'wpforo\widgets\Forums' );
			register_widget( 'wpforo\widgets\Profile' );
			register_widget( 'wpforo\widgets\Search' );
			register_widget( 'wpforo\widgets\OnlineMembers' );
			register_widget( 'wpforo\widgets\RecentTopics' );
			register_widget( 'wpforo\widgets\RecentPosts' );
			register_widget( 'wpforo\widgets\Tags' );
		} );
		add_filter( 'plugin_locale', function( $locale ) {
			if( defined( 'DOING_AJAX' ) && DOING_AJAX ) $locale = $this->locale;

			return $locale;
		} );
		add_action( 'wpforo_after_init_classes', function() {
			if( wpforo_setting( 'email', 'disable_new_user_admin_notification' ) ) {
				remove_action( 'after_password_reset', 'wp_password_change_notification' );

				remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
				add_action( 'register_new_user', 'wpforo_send_new_user_notifications' );

				remove_action( 'edit_user_created_user', 'wp_send_new_user_notifications' );
				add_action( 'edit_user_created_user', 'wpforo_send_new_user_notifications', 10, 2 );

				remove_action( 'network_site_new_created_user', 'wp_send_new_user_notifications' );
				add_action( 'network_site_new_created_user', 'wpforo_send_new_user_notifications' );

				remove_action( 'network_site_users_created_user', 'wp_send_new_user_notifications' );
				add_action( 'network_site_new_created_user', 'wpforo_send_new_user_notifications' );

				remove_action( 'network_user_new_created_user', 'wp_send_new_user_notifications' );
				add_action( 'network_user_new_created_user', 'wpforo_send_new_user_notifications' );
			}
		} );

		if( is_admin() || strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) === 0 || preg_match( '#^/?(?:([^\s/?&=<>:\'\"*\\\|]*/)(?1)*)?[^\s/?&=<>:\'\"*\\\|]+\.(?:php|js|css|jpe?g|png|gif)/?(?:\?[^/]*)?$#iu', $_SERVER['REQUEST_URI'] ) ) {
			add_action( 'init', [ $this, 'init' ], 99 );
			add_action( 'admin_init', [ $this, 'admin_init' ] );
		} else {
			add_action( 'wp', [ $this, 'init' ], 99 );
		}
		add_action( 'switch_blog', [ $this, 'after_switch_blog' ], 10, 2 );
		add_action( 'wpforo_before_init', function() {
			$this->change_board();
			$this->init_classes();
			do_action( 'wpforo_wp_init' );
		} );
	}

	public function after_switch_blog( $new_blog_id, $prev_blog_id ) {
		if( intval( $new_blog_id ) !== intval( $prev_blog_id ) ) {
			$this->init_base_tables();
			$this->init_tables();
			$this->init_folders();
		}
	}

	private function init_base_tables() {
		$this->db->query( "SET SESSION group_concat_max_len = 1000000" );
		$blog_id      = apply_filters( 'wpforo_current_blog_id', 0 );
		$this->tables = new stdClass;
		if( ! $blog_id ) $blog_id = $this->db->blogid;
		$this->blog_prefix = $this->db->get_blog_prefix( $blog_id );
		$this->_base_tables = apply_filters( 'wpforo_init_base_tables', $this->_base_tables );
		foreach( $this->_base_tables as $table ) $this->tables->$table = $this->fix_table_name( $table );
	}

	private function init_tables() {
		$this->_tables = apply_filters( 'wpforo_init_tables', $this->_tables );
		foreach( $this->_tables as $table ) $this->tables->$table = $this->fix_table_name( $table );
	}

	public function get_active_boards_tables( $basename ){
		$tables = [];
		if( $boardids = $this->board->get_active_boardids() ){
			foreach( $boardids as $boardid ) $tables[] = $this->fix_table_name( $basename, $this->generate_prefix( $boardid ) );
		}

		return array_unique( $tables );
	}

	/**
	 * @param string $basename
	 *
	 * @return string
	 */
	public function fix_table_name( $basename, $prefix = null ) {
		return $this->blog_prefix . ( in_array( $basename, $this->_base_tables, true ) ? $this->base_prefix : ( is_null($prefix) ? $this->prefix : $prefix) ) . $basename;
	}

	public function fix_dir_sep( $dir ) {
		$dir = str_replace( [ '/', '\\', '\\\\' ], DIRECTORY_SEPARATOR, $dir );

		return rtrim( trim( $dir ), DIRECTORY_SEPARATOR );
	}

	public function fix_url_sep( $url ) {
		return trim( str_replace( [ '/', '\\', '\\\\' ], '/', $url ) );
	}

	private function init_folders() {
		$this->_folders                      = apply_filters( 'wpforo_init_folders', $this->_folders );
		$boardid                             = ( is_callable( [ $this->board, 'get_current' ] ) ? $this->board->get_current( 'boardid' ) : 0 );
		$upload_dir                          = wp_get_upload_dir();
		$this->folders['wp_upload']          = [
			'dir' => $this->fix_dir_sep( $upload_dir['basedir'] ),
			'url' => $this->fix_url_sep( $upload_dir['baseurl'] ),
		];
		$this->folders['wp_upload']['url//'] = preg_replace( '#^https?:#i', '', $this->folders['wp_upload']['url'] );
		$this->folders['upload']             = [
			'dir'   => $this->folders['wp_upload']['dir'] . DIRECTORY_SEPARATOR . "wpforo" . ( $boardid ? '_' . $boardid : '' ),
			'url'   => $this->folders['wp_upload']['url'] . "/wpforo" . ( $boardid ? '_' . $boardid : '' ),
			'url//' => $this->folders['wp_upload']['url//'] . "/wpforo" . ( $boardid ? '_' . $boardid : '' ),
		];
		foreach( $this->_folders as $folder ) {
			$this->folders[ $folder ] = [
				'dir'   => $this->folders['upload']['dir'] . DIRECTORY_SEPARATOR . trim( $this->fix_dir_sep( $folder ), DIRECTORY_SEPARATOR ),
				'url'   => $this->folders['upload']['url'] . "/" . trim( $this->fix_url_sep( $folder ), '/' ),
				'url//' => $this->folders['upload']['url//'] . "/" . trim( $this->fix_url_sep( $folder ), '/' ),
			];
		}
		do_action( 'wpforo_after_init_folders', $this->folders );
	}

	private function requires() {
		require_once  WPFORO_DIR . '/includes/functions.php';
		require_once  WPFORO_DIR . '/includes/functions-template.php';
		require_once  WPFORO_DIR . '/includes/hooks.php';
		require_once  WPFORO_DIR . '/includes/installation.php';
		require_once  WPFORO_DIR . '/integrations/functions.php';
		if( wpforo_is_admin() ) require_once WPFORO_DIR . '/admin/index.php';
	}

	public function admin_init() {
		if( wpforo_is_admin() ) {
			$this->change_board();

			$this->check_database();

			if( strpos( wpforo_get_request_uri(), 'user-new.php' ) === false ) {
				if( ! $this->member->get_groupid( $this->current_userid ) ) {
					$this->member->synchronize_user( $this->current_userid );
				}
			}

			if( ! $this->usergroup->can_manage_forum() && wpforo_current_user_is( 'admin' ) ) {
				$this->member->set_groupid( $this->current_userid, 1 );
			}
		}
	}

	public function check_database() {
		//Make sure all users profiles are created
		if( $this->is_installed() ) {
			$users    = (int) $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->users . "`" );
			$profiles = (int) $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->tables->profiles . "`" );
			$delta    = $users - $profiles;
			if( $users > 100 && $delta > 2 ) add_action( 'admin_notices', 'wpforo_profile_notice', 10 );
		}
		//Make sure tables structures are correct for current version
		$wpforo_version_db = wpforo_get_option( 'version_db', null, false );
		if( ! $wpforo_version_db || version_compare( $wpforo_version_db, WPFORO_VERSION, '<' ) ) {
			if( 'tables' !== wpfval( $_GET, 'view' ) ) {
				$db_note  = false;
				$problems = wpforo_database_check();
				if( ! empty( $problems ) ) {
					foreach( $problems as $problem ) {
						if( wpfval( $problem, 'fields' ) ) $db_note = true;
						if( wpfval( $problem, 'exists' ) ) $db_note = true;
					}
					if( $db_note ) {
						add_action( 'admin_notices', 'wpforo_database_notice', 10 );
					}
				} else {
					wpforo_update_option( 'version_db', WPFORO_VERSION );
				}
			}
		}
	}

	public function init() {
		do_action( 'wpforo_before_init' );

		$this->phrase->init();

		$this->perm->init();
		$this->perm->init_current_user_accesses();

		$this->init_current_url();
		$this->init_current_object();

		$this->moderation->init();

		do_action( 'wpforo_after_init' );
	}

	public function shortcode_atts_to_url( $atts ) {
		$url = wpforo_home_url();

		$args = shortcode_atts( [
			                        'item' => 'forum',
			                        'id'   => 0,
			                        'slug' => '',
		                        ], (array) $atts );

		if( $args['item'] === 'profile' && ! $args['id'] ) $args['id'] = $this->current_userid;

		if( $args['item'] === 'add-topic' ) {
			$forum   = $this->forum->get_forum( ( $args['slug'] ?: $args['id'] ) );
			$forumid = (int) wpfval( $forum, 'is_cat' ) ? 0 : (int) wpfval( $forum, 'forumid' );
			$url     = wpforo_home_url( wpforo_settings_get_slug( 'add-topic' ) . '/' . $forumid );
		} elseif( $args['item'] === 'recent' ) {
			$url = wpforo_settings_get_slug( 'recent' ) . '/';
			if( trim( $args['slug'] ) ) $url .= '?view=' . wpforo_settings_get_slug( $args['slug'] );
			$url = wpforo_home_url( $url );
		} elseif( $args['id'] || $args['slug'] ) {
			$getid = ( $args['slug'] ?: $args['id'] );
			if( $args['item'] === 'topic' ) {
				$url = $this->topic->get_topic_url( $getid );
			} elseif( $args['item'] === 'profile' ) {
				$url = $this->member->get_profile_url( $getid );
			} else {
				$url = $this->forum->get_forum_url( $getid );
			}
		} elseif( $args['item'] === 'login' ) {
			$url = wpforo_login_url();
		} elseif( $args['item'] === 'register' ) {
			$url = wpforo_register_url();
		} elseif( $args['item'] === 'lostpassword' ) {
			$url = wpforo_lostpassword_url();
		}

		return $url;
	}

	public function init_current_url( $atts = [] ) {
		$url = wpforo_get_request_uri();
		if( $atts || is_wpforo_shortcode_page( $url ) ) {
			if( $atts || ( $atts = get_wpforo_shortcode_atts( '', $url ) ) ) {
				$url = $this->shortcode_atts_to_url( $atts );
			} else {
				$url = wpforo_home_url();
			}
		} elseif( is_wpforo_url( $url ) && preg_match( '#/' . preg_quote( wpforo_settings_get_slug( 'postid' ) ) . '/(\d+)/?$#isu', strtok( $url, '?' ), $matches ) ) {
			$post_url = $this->post->get_post_url( $matches[1] );
			if( $post_url !== wpforo_home_url() ) $url = $post_url;
		}

		$url = wpforo_fix_url( $url );
		$url = preg_replace( '#\#[^/?&]*$#iu', '', $url );
		parse_str( parse_url( $url, PHP_URL_QUERY ), $get );
		$get = array_merge( (array) $get, $_GET );
		$get = wp_unslash( $get );

		$this->current_url = apply_filters( 'wpforo_init_current_url', $url );
		$this->GET         = apply_filters( 'wpforo_init_current_url_GET', $get );
	}

	private function init_defaults() {
		date_default_timezone_set( 'UTC' );
		ini_set( 'date.timezone', 'UTC' );

		$this->default = new stdClass;

		$this->default->current_object = [
			'route'                     => '',
			'template'                  => '',
			'qvars'                     => [],
			'args'                      => [],
			'layout'                    => 1,
			'og_text'                   => '',
			'paged'                     => 1,
			'items_count'               => 0,
			'items_per_page'            => 15,
			'is_404'                    => false,
			'user_is_same_current_user' => false,
			'orderby'                   => null,
			'members'                   => [],
			'categories'                => [],
			'topics'                    => [],
			'posts'                     => [],
			'user'                      => [],
			'userid'                    => 0,
			'user_nicename'             => '',
			'forum'                     => [],
			'forumid'                   => 0,
			'forum_slug'                => '',
			'forum_desc'                => '',
			'forum_meta_key'            => '',
			'forum_meta_desc'           => '',
			'topic'                     => [],
			'topicid'                   => 0,
			'topic_slug'                => '',
			'tags'                      => [],
			'load_tinymce'              => false,
		];

		$this->default->tools_cleanup = [
			'user_reg_days_ago'  => 7,
			'auto_cleanup_users' => 0,
			'usergroup'          => [ 1 => '0', 5 => '0', 2 => '1', 3 => '0' ],
		];

		$this->default->tools_misc = [
			'admin_note'        => '',
			'admin_note_groups' => [ 1, 2, 3, 4, 5 ],
			'admin_note_pages'  => [ 'forum' ],
		];

		$this->default->tools_legal = [
			'rules_checkbox'          => 0,
			'rules_text'              => null,
			'page_terms'              => '',
			'page_privacy'            => '',
			'forum_privacy_text'      => null,
			'checkbox_terms_privacy'  => 0,
			'checkbox_email_password' => 1,
			'checkbox_forum_privacy'  => 0,
			'checkbox_fb_login'       => 1,
			'contact_page_url'        => null,
			'cookies'                 => 1,
		];

		$this->default->stats = [
			'forums'                    => 0,
			'topics'                    => 0,
			'posts'                     => 0,
			'members'                   => 0,
			'online_members_count'      => 0,
			'last_post_title'           => '',
			'last_post_url'             => '',
			'newest_member'             => [],
			'newest_member_dname'       => '',
			'newest_member_profile_url' => '',
		];

		$this->default->dissmissed = [
			'recaptcha_backend_note' => 0,
			'recaptcha_note'         => 0,
			'addons_css_update'      => 0,
		];
	}

	private function reset_current_object() {
		$this->current_object = $this->default->current_object;
	}

	private function init_base_options() {
		// @TODO need to choose this setting is base or not
		$this->tools_legal = wpforo_get_option( 'wpforo_tools_legal', $this->default->tools_legal );
	}

	private function init_options() {
		// @TODO need to choose this settings are base or not
		$this->tools_cleanup   = wpforo_get_option( 'tools_cleanup', $this->default->tools_cleanup );
		$this->tools_misc      = wpforo_get_option( 'tools_misc', $this->default->tools_misc );
		$this->dissmissed      = wpforo_get_option( 'dissmissed', $this->default->dissmissed );
	}

	public function can_use_trailing_slashes() {
		return '/' === substr( get_option( 'permalink_structure', '' ), - 1, 1 );
	}

	public function user_trailingslashit( $url ) {
		$rtrimed_url     = '';
		$url_append_vars = '';
		if( preg_match( '#^(.+?)(/?[?&].*)?$#isu', $url, $match ) ) {
			if( wpfval( $match, 1 ) ) $rtrimed_url = rtrim( $match[1], '/\\' );
			if( wpfval( $match, 2 ) ) $url_append_vars = '?' . trim( $match[2], '?&/\\' );
			if( $rtrimed_url ) {
				$home_url = rtrim( preg_replace( '#/?\?.*$#isu', '', home_url() ), '/\\' );
				if( $rtrimed_url == $home_url ) {
					$url = $rtrimed_url . '/';
				} else {
					$url = $rtrimed_url . ( wpforo_ram_get( [ $this, 'can_use_trailing_slashes' ] ) ? '/' : '' );
				}
			}
		}

		return $url . $url_append_vars;
	}

	public function strip_url_paged_var( $url ) {
		$patterns = [
			'#/' . preg_quote( wpforo_settings_get_slug( 'paged' ) ) . '/?\d*/?#isu',
			'#[&?]wpfpaged=\d*#iu',
		];
		$url      = preg_replace( $patterns, '', $url );

		return $this->user_trailingslashit( $url );
	}

	public function statistic_cache_clean() {
		$this->db->query( "DELETE FROM `" . $this->db->options . "` WHERE `option_name` REGEXP '^" . wpforo_prefix( 'stat_[0-9]+' ) . "'" );
	}

	public function statistic( $mode = 'get', $template = 'all' ) {
		$key = 'stat_' . $this->current_user_groupid;
		if( $mode === 'get' ) {
			if( $cached_stat = wpforo_get_option( $key, [] ) ) {
				$cached_stat['online_members_count'] = $this->member->online_members_count();
				if( wpfval( $cached_stat, 'forums' ) && wpfval( $cached_stat, 'topics' ) && wpfval( $cached_stat, 'posts' ) ) {
					return wpforo_array_args_cast_and_merge( $cached_stat, $this->default->stats );
				}
			}
		}

		if( $mode === 'get' || $template === 'all' ) {
			$stats['forums']               = $this->forum->get_count( [ 'is_cat' => 0 ] );
			$stats['topics']               = $this->topic->get_count();
			$stats['posts']                = $this->post->get_count();
			$member_status                 = [ 'p.`status`' => $this->member->get_inlist_enabled_statuses() ];
			$stats['members']              = $this->member->get_count( $member_status );
			$stats['online_members_count'] = $this->member->online_members_count();
			$row_count                     = apply_filters( 'wpforo_get_statistic_row_count', 20 );

			$posts = $this->topic->get_topics( [ 'orderby' => 'modified', 'order' => 'DESC', 'row_count' => $row_count, 'private' => 0, 'status' => 0, 'permgroup' => 4 ] );
			$first = key( $posts );
			if( isset( $posts[ $first ] ) && ! empty( $posts[ $first ] ) && $this->perm->forum_can( 'vf', $posts[ $first ]['forumid'] ) ) {
				$stats['last_post_title'] = $posts[ $first ]['title'];
				$stats['last_post_url']   = $this->post->get_post_url( $posts[ $first ]['last_post'] );
			}

			$members = $this->member->get_members( [ 'orderby' => 'userid', 'status' => [ 'active' ], 'order' => 'DESC', 'row_count' => 1, 'groupids' => $this->usergroup->get_visible_usergroup_ids() ] );
			if( isset( $members[0] ) && ! empty( $members[0] ) ) {
				$members[0]['profile_url']          = $this->member->get_profile_url( $members[0] );
				$stats['newest_member']             = $members[0];
				$stats['newest_member_dname']       = wpforo_user_dname( $members[0] );
				$stats['newest_member_profile_url'] = $members[0]['profile_url'];
			}
		} else {
			$stats = wpforo_get_option( $key, $this->default->stats );
			switch( $template ) {
				case 'forum':
					$stats['forums'] = $this->forum->get_count( [ 'is_cat' => 0 ] );
				break;
				case 'topic':
					$stats['topics'] = $this->topic->get_count();
					$posts           = $this->topic->get_topics( [ 'orderby' => 'modified', 'order' => 'DESC', 'row_count' => 1 ] );
					if( isset( $posts[0] ) && ! empty( $posts[0] ) && $this->perm->forum_can( 'vf', $posts[0]['forumid'] ) ) {
						$stats['last_post_title'] = $posts[0]['title'];
						$stats['last_post_url']   = $this->post->get_post_url( $posts[0]['last_post'] );
					}
				break;
				case 'post':
					$stats['posts'] = $this->post->get_count();
					$posts          = $this->topic->get_topics( [ 'orderby' => 'modified', 'order' => 'DESC', 'row_count' => 1 ] );
					if( isset( $posts[0] ) && ! empty( $posts[0] ) && $this->perm->forum_can( 'vf', $posts[0]['forumid'] ) ) {
						$stats['last_post_title'] = $posts[0]['title'];
						$stats['last_post_url']   = $this->post->get_post_url( $posts[0]['last_post'] );
					}
				break;
				case 'user':
					$member_status                 = [ 'p.`status`' => $this->member->get_inlist_enabled_statuses() ];
					$stats['members']              = $this->member->get_count( $member_status );
					$stats['online_members_count'] = $this->member->online_members_count();

					$members = $this->member->get_members( [ 'orderby' => 'userid', 'order' => 'DESC', 'row_count' => 1, 'groupids' => $this->usergroup->get_visible_usergroup_ids() ] );
					if( isset( $members[0] ) && ! empty( $members[0] ) ) {
						$members[0]['profile_url']          = $this->member->get_profile_url( $members[0] );
						$stats['newest_member']             = $members[0];
						$stats['newest_member_dname']       = wpforo_user_dname( $members[0] );
						$stats['newest_member_profile_url'] = $members[0]['profile_url'];
					}
				break;
			}
		}

		$stats = apply_filters( 'wpforo_get_statistic_array_filter', $stats );
		$stats = wpforo_array_args_cast_and_merge( $stats, $this->default->stats );
		wpforo_update_option( $key, $stats );

		return $stats;
	}

	public function init_current_template() {}

	public function init_current_object() {
		$this->reset_current_object();
		$this->current_object['items_per_page'] = $this->post->get_option_items_per_page();
		$url                                    = $this->current_url;
		$get                                    = $this->GET;

		if( ! is_wpforo_page( $url ) ) return;

		$current_url = wpforo_get_url_query_vars_str( $url );

		$current_object = [];
		if( wpfkey( $get, 'wpfs' ) || wpfval( $get, 'foro' ) === 'search' ) $this->current_object['template'] = 'search';
		if( wpfval( $get, 'wpforo' ) || wpfval( $get, 'foro' ) ) {
			$request = ( wpfval( $get, 'wpforo' ) ) ? wpfval( $get, 'wpforo' ) : wpfval( $get, 'foro' );
			if( $request === 'page' ) $this->current_object['template'] = 'page';
		}

		$template_key  = '';
		$wpf_url       = preg_replace( '#/?\?.*$#isu', '', $current_url );
		$wpf_url_parse = array_values( array_filter( explode( '/', trim( $wpf_url, '/' ) ) ) );
		if( array_key_exists( 0, $wpf_url_parse ) && in_array( $wpf_url_parse[0], $this->board->routes ) ) {
			$this->current_object['route'] = $wpf_url_parse[0];
			unset( $wpf_url_parse[0] );

			if( in_array( $this->current_object['route'], $this->board->base_routes, true ) ){
				$this->current_object['template'] = ($template_key = wpforo_settings_get_slug_key( $this->current_object['route'] )) !== 'member' ? $template_key : '';

				if( $this->current_object['template'] === 'register' ) {
					$this->form->current['template']            = 'register';
					$this->form->current['value']['user_login'] = sanitize_user( (string) wpfval( $_POST, 'wpfreg', 'user_login' ) );
					$this->form->current['value']['user_email'] = sanitize_email( (string) wpfval( $_POST, 'wpfreg', 'user_email' ) );
					$this->form->current['varname']             = 'wpfreg';
				}elseif( $this->current_object['template'] === 'logout' && $this->current_userid ) {
					$this->action->logout();
				}
			}

			if( $this->current_userid && in_array( $this->current_object['template'], ['register', 'login', 'lostpassword'], true ) ) wpforo_redirect_to();
		}
		$wpf_url_parse = array_reverse( $wpf_url_parse );

		if( in_array( wpforo_settings_get_slug( 'paged' ), $wpf_url_parse ) ) {
			foreach( $wpf_url_parse as $key => $value ) {
				if( $value === wpforo_settings_get_slug( 'paged' ) ) {
					unset( $wpf_url_parse[ $key ] );
					break;
				}
				if( is_numeric( $value ) ) $paged = intval( $value );

				unset( $wpf_url_parse[ $key ] );
			}
		}
		if( $_paged = intval( wpfval( $get, 'wpfpaged' ) ) ) $paged = $_paged;
		$current_object['paged']   = ( isset( $paged ) && $paged > 0 ) ? $paged : 1;
		$current_object['orderby'] = wpfval( $get, 'orderby' );

		$wpf_url_parse = array_values( $wpf_url_parse );

		if( ! $this->current_object['template'] ) {
			$current_object = apply_filters( 'wpforo_before_init_current_object', $current_object, $wpf_url_parse );
			if( wpfkey( $current_object, 'template' ) ) $this->current_object['template'] = (string) wpfval( $current_object, 'template' );
		}

		if( ! $this->current_object['template'] ) {
			$templates = $template_key === 'member' ? $this->tpl->get_member_templates_list() : $this->tpl->get_templates_list();
			if( $templates ) {
				if( $template_key === 'member' ){
					if( count( $wpf_url_parse ) > 1 ){
						$nickname      = end( $wpf_url_parse );
						$profile_route = prev( $wpf_url_parse );
						array_pop(  $wpf_url_parse );
						array_pop(  $wpf_url_parse );
						array_push( $wpf_url_parse, $nickname, $profile_route );
					}else{
						$wpf_url_parse[] = wpforo_settings_get_slug( 'profile' );
					}
				}
				$__slug = end( $wpf_url_parse );
				foreach( $templates as $template ) {
					if( $__slug === wpforo_settings_get_slug( $template ) ) {
						$this->current_object['template'] = $template;
						$current_object['qvars']          = $wpf_url_parse;
						array_pop( $current_object['qvars'] );
						$current_object['qvars'] = array_reverse( $current_object['qvars'] );
						break;
					}
				}

				if( $template_key === 'member' ){
					if( ! $this->current_object['template'] && ! $wpf_url_parse ){
						if( $this->current_userid ){
							$this->current_object['template'] = 'profile';
						}else{
							wp_safe_redirect( wpforo_login_url() );
							exit();
						}
					}elseif( !wpforo_is_member_template( $this->current_object['template'] ) ){
						$this->current_object['template'] = '';
						$current_object['qvars'] = [];
					}
				}
			}
		}

		if( ! $this->current_object['template'] ) {
			$this->current_object['template'] = 'forum';
			$this->form->current['varname']   = 'thread';
			if( isset( $wpf_url_parse[0] ) ) {
				if( isset( $wpf_url_parse[1] ) ) {
					$current_object['topic_slug']     = $wpf_url_parse[0];
					$current_object['forum_slug']     = $wpf_url_parse[1];
					$this->current_object['template'] = 'post';
					$this->form->current['varname']   = 'post';
				} else {
					$current_object['forum_slug']     = $wpf_url_parse[0];
					$this->current_object['template'] = 'topic';
					$this->form->current['varname']   = 'thread';
				}
			}
		}

		$current_object = apply_filters( 'wpforo_after_init_current_template', $current_object, $wpf_url_parse, $get );
		if( wpfkey( $current_object, 'template' ) ) $this->current_object['template'] = (string) wpfval( $current_object, 'template' );

		if( $this->current_object['template'] ) {
			if( wpforo_is_member_template( $this->current_object['template'] ) ) {
				if( ! wpfval( $current_object, 'userid' ) && ! wpfval( $current_object, 'user_nicename' ) ){
					if( $qvar0 = wpfval( $current_object['qvars'], 0 ) ) {
						if( wpforo_setting( 'profiles', 'url_structure' ) === 'id' ) {
							$current_object['userid'] = $qvar0;
						} else {
							$current_object['user_nicename'] = $qvar0;
						}
					} else {
						if( $this->current_userid ) {
							$current_object['userid'] = $this->current_userid;
						} else {
							wp_safe_redirect( wpforo_login_url() );
							exit();
						}
					}
				}

				// redirect old type of member urls to new one
				if( $template_key !== 'member' ){
					if( count($current_object['qvars']) === 1 ){
						$redirect = wpforo_url(
						array_shift( $current_object['qvars'] )
							. ($this->current_object['template'] !== 'profile' ? '/' . wpforo_settings_get_slug( $this->current_object['template'] ) : ''),
						'member'
						);
					}else{
						$redirect = wpforo_url(
							array_shift( $current_object['qvars'] ) . '/'
								. wpforo_settings_get_slug( $this->current_object['template'] ) . '/' . implode('/', $current_object['qvars']),
							'member'
						);
					}
					wp_safe_redirect( $redirect );
					exit();
				}
				// redirectiong

			} elseif( $this->current_object['template'] === 'search' ) {
				$args = [
					'needle'      => sanitize_text_field( wpfval( $get, 'wpfs' ) ),
					'type'        => sanitize_text_field( wpfval( $get, 'wpfin' ) ),
					'date_period' => intval( wpfval( $get, 'wpfd' ) ),
					'forumids'    => (array) wpfval( $get, 'wpff' ),
					'offset'      => ( $current_object['paged'] - 1 ) * $this->current_object['items_per_page'],
					'row_count'   => $this->current_object['items_per_page'],
					'orderby'     => 'relevancy',
					'order'       => 'desc',
					'postids'     => [],
				];
				if( ! empty( $get['wpfob'] ) ) {
					$args['orderby'] = sanitize_text_field( $get['wpfob'] );
				} elseif( in_array( wpfval( $args, 'type' ), [ 'tag', 'user-posts', 'user-topics' ], true ) ) {
					$args['orderby'] = 'date';
				}
				$wpfo = strtolower( wpfval( $get, 'wpfo' ) );
				if( in_array( $wpfo, [ 'asc', 'desc' ], true ) ) $args['order'] = $wpfo;
				$sdata                  = array_filter( (array) wpfval( $get, 'data' ) );
				$args['postids']        = $this->postmeta->search( $sdata );
				$current_object['args'] = $args;
				if( $sdata && ! $args['postids'] ) {
					$current_object['items_count'] = 0;
					$current_object['posts']       = [];
				} else {
					$current_object['posts'] = $this->post->search( $args, $current_object['items_count'] );
				}
			} elseif( $this->current_object['template'] === 'recent' ) {
				$current_object['items_per_page'] = wpforo_setting( 'topics', 'topics_per_page' );
			} elseif( $this->current_object['template'] === 'tags' ) {
				$current_object['items_per_page'] = wpforo_setting( 'tags', 'per_page' );
				$args                             = [
					'offset'    => ( $current_object['paged'] - 1 ) * $current_object['items_per_page'],
					'row_count' => $current_object['items_per_page'],
				];
				$current_object['tags']           = $this->topic->get_tags( $args, $current_object['items_count'] );
			} elseif( $this->current_object['template'] === 'members' ) {
				$current_object['items_per_page'] = wpforo_setting( 'members', 'members_per_page' );

				$this->form->current['template'] = 'members';
				$this->form->current['value']    = $get;
				$this->form->current['varname']  = '';

				if( ! empty( $get['_wpfms'] ) ) {
					$users_include       = [];
					$search_fields_names = $this->member->get_search_fields_names();

					$wpfms = ( isset( $get['wpfms'] ) ) ? sanitize_text_field( $get['wpfms'] ) : '';
					if( $wpfms ) {
						$users_include = $this->member->search( $wpfms, $search_fields_names );
					} else {
						if( $filters = array_filter( $get, function( $v ) { return ! ( is_null( $v ) || $v === false || $v === '' ); } ) ) {
							$filters = array_merge( array_filter( (array) wpfval( $get, 'data' ), function( $v ) { return ! ( is_null( $v ) || $v === false || $v === '' ); } ), $filters );
							unset( $filters['data'] );
							$args = [];
							foreach( $filters as $filter_key => $filter ) {
								if( in_array( $filter_key, $search_fields_names ) ) {
									$args[ $filter_key ] = $filter;
								}
							}
							$users_include = $this->member->filter( $args );
						}
					}

					$users_include = apply_filters( 'wpforo_member_search_users_include', $users_include );
				}
				$member_status = $this->member->get_inlist_enabled_statuses();
				$args          = [
					'offset'    => ( $current_object['paged'] - 1 ) * $current_object['items_per_page'],
					'row_count' => $current_object['items_per_page'],
					'status'    => $member_status,
					'groupids'  => $this->usergroup->get_visible_usergroup_ids(),
				];
				switch( wpforo_setting( 'members', 'list_order' ) ){
					case 'online_time':
						$args['orderby'] = 'online_time';
						$args['order']   = 'desc';
					break;
					case 'posts__asc':
						$args['orderby'] = 'posts';
						$args['order']   = 'ASC';
					break;
					case 'user_registered__asc':
						$args['orderby'] = 'user_registered';
						$args['order']   = 'ASC';
					break;
					case 'user_registered__desc':
						$args['orderby'] = 'user_registered';
						$args['order']   = 'DESC';
					break;
					case 'display_name__asc':
						$args['orderby'] = 'display_name';
						$args['order']   = 'ASC';
					break;
					case 'display_name__desc':
						$args['orderby'] = 'display_name';
						$args['order']   = 'DESC';
					break;
					default:
						$args['orderby'] = 'posts';
						$args['order']   = 'DESC';
					break;
				}
				if( ! empty( $users_include ) ) $args['include'] = $users_include;
				$current_object['members'] = $this->member->get_members( $args, $current_object['items_count'] );
				if( isset( $users_include ) && empty( $users_include ) ) {
					$current_object['members']     = [];
					$current_object['items_count'] = 0;
				}
			} elseif( $this->current_object['template'] === 'add-topic' ) {
				if( $qvar0 = (int) wpfval( $current_object['qvars'], 0 ) ) {
					$forum = (array) $this->forum->get_forum( $qvar0 );
					if( ! $forum || (int) wpfval( $forum, 'is_cat' ) ) {
						wp_safe_redirect( wpforo_home_url( wpforo_settings_get_slug( 'add-topic' ) ), 301 );
						exit();
					}
				}
			}
		}

		if( wpfval( $current_object, 'userid' ) || wpfval( $current_object, 'user_nicename' ) ) {
			$args = [];
			if( isset( $current_object['userid'] ) ) $args['userid'] = $current_object['userid'];
			if( isset( $current_object['user_nicename'] ) ) $args['user_nicename'] = $current_object['user_nicename'];
			$selected_user = $this->member->get_member( $args );
			if( isset( $current_object['userid'] ) && empty( $selected_user ) ) $selected_user = $this->member->get_member( [ 'user_nicename' => $current_object['userid'] ] );
			if( $selected_user ) {
				$current_object['user']                      = $selected_user;
				$current_object['userid']                    = $selected_user['userid'];
				$current_object['user_nicename']             = $selected_user['user_nicename'];
				$current_object['user_is_same_current_user'] = ! empty( $this->current_userid ) && $selected_user['userid'] === $this->current_userid;

				if( $this->tpl->can_view_template( $this->current_object['template'], $current_object['user'] ) ) {
					switch( $this->current_object['template'] ) {
						case 'activity':
							$args                          = [
								'offset'        => ( $current_object['paged'] - 1 ) * $this->current_object['items_per_page'],
								'row_count'     => $this->current_object['items_per_page'],
								'userid'        => $current_object['userid'],
								'orderby'       => '`created` DESC, `postid` DESC',
								'check_private' => true,
								'is_first_post' => wpfval( $this->GET, 'filter' ) ? $this->GET['filter'] === 'topics' : null
							];
							$current_object['items_count'] = 0;
							$current_object['activities']  = $this->post->get_posts( $args, $current_object['items_count'] );
						break;
						case 'favored':
							$current_object['filter'] = strtolower( wpfval( WPF()->GET, 'filter' ) );
							if( ! in_array( $current_object['filter'], [ 'likes', 'dislikes' ], true ) ) $current_object['filter'] = 'bookmarks';
							if( $current_object['filter'] === 'likes' ){
								$postids = $this->reaction->get_reactions_col(
									'postid',
									[
										'userid'       => $current_object['userid'],
										'type_include' => 'up',
									]
								);
							}elseif( $current_object['filter'] === 'dislikes' ){
								$postids = $this->reaction->get_reactions_col(
									'postid',
									[
										'userid'       => $current_object['userid'],
										'type_include' => 'down',
									]
								);
							}else{
								$postids = $this->bookmark->get_bookmarks_col(
									'postid',
									[
										'userid'  => $current_object['userid'],
										'boardid' => $this->board->get_current( 'boardid' ),
										'status'  => true,
									]
								);
							}

							if( $postids ){
								$args                          = [
									'offset'        => ( $current_object['paged'] - 1 ) * $this->current_object['items_per_page'],
									'row_count'     => $this->current_object['items_per_page'],
									'orderby'       => '`created` DESC, `postid` DESC',
									'check_private' => true,
									'include'       => $postids,
								];
								$current_object['items_count']   = 0;
								$current_object['favored_posts'] = $this->post->get_posts( $args, $current_object['items_count'] );
							}else{
								$current_object['items_count']   = 0;
								$current_object['favored_posts'] = [];
							}
						break;
						case 'account':
							$this->form->current['template'] = 'account';
							$this->form->current['varname']  = 'member';
							$this->form->current['value']    = array_merge( $current_object['user'], (array) wpfval( $_POST, 'member' ) );
						break;
						default:
							$this->form->current['template'] = 'profile';
							$this->form->current['value']    = $current_object['user'];
						break;
					}
				} else {
					if( ! $this->current_userid ) {
						wp_safe_redirect( wpforo_login_url() );
						exit();
					}
					$current_object['is_404'] = true;
				}

			} else {
				$current_object['is_404'] = true;
			}
		}

		if( wpfval( $current_object, 'topic_slug' ) ) {
			$topic = $this->topic->get_topic( [ 'slug' => $current_object['topic_slug'] ], false );
			if( ! empty( $topic ) ) {
				$topic_forumid = intval( wpfval( $topic, 'forumid' ) );
				$is_owner      = wpforo_is_owner( $topic['userid'], $topic['email'] );

				if( $topic_forumid && ( ! $this->perm->forum_can( 'vf', $topic_forumid ) || ( ! $is_owner && ! $this->perm->forum_can( 'vt', $topic_forumid ) ) || ( wpfval( $topic, 'private' ) && ! $is_owner && ! $this->perm->forum_can( 'vp', $topic_forumid ) ) || ( wpfval( $topic, 'status' ) && ! $is_owner && ! $this->perm->forum_can( 'au', $topic_forumid ) ) ) ) {
					if( ! $this->current_userid ) {
						wp_safe_redirect( wpforo_login_url() );
						exit();
					}
					$current_object['is_404'] = true;
				} else {
					$current_object['topic']   = $topic;
					$current_object['topicid'] = $topic['topicid'];
					$current_object['og_text'] = (string) wpfval( $topic, 'title' );
				}
			} else {
				$current_object['is_404'] = true;
			}
		}

		if( wpfval( $current_object, 'forum_slug' ) ) {
			$args = ( empty( $topic ) ? [ 'slug' => $current_object['forum_slug'] ] : $topic['forumid'] );
			if( $forum = $this->forum->get_forum( $args ) ) {
				if( ! empty( $topic ) && strtolower( $current_object['forum_slug'] ) !== strtolower( $forum['slug'] ) ) {
					wp_safe_redirect( $this->topic->get_topic_url( $topic, $forum ), 301 );
					exit();
				}
				if( $forum['is_cat'] ) $this->current_object['template'] = 'forum';
				$current_object['forum']           = $forum;
				$current_object['forumid']         = $forum['forumid'];
				$current_object['forum_desc']      = $forum['description'];
				$current_object['forum_meta_key']  = $forum['meta_key'];
				$current_object['forum_meta_desc'] = $forum['meta_desc'];
				$current_object['og_text']         = $forum['title'];
				$current_object['layout']          = $this->forum->get_layout( $forum );

				if( $this->current_object['template'] === 'topic' ) {
					$current_object['items_per_page'] = wpforo_setting( 'topics', 'topics_per_page' );
					$args                             = [
						'offset'    => ( $current_object['paged'] - 1 ) * $current_object['items_per_page'],
						'row_count' => $current_object['items_per_page'],
						'forumid'   => $current_object['forumid'],
						'orderby'   => 'type, modified',
						'order'     => 'DESC',
					];
					$args                             = apply_filters( 'wpforo_topic_list_args', $args );
					$current_object['topics']         = $this->topic->get_topics( $args, $current_object['items_count'] );
				}
			} else {
				$current_object['is_404'] = true;
			}
		}

		if( in_array( $this->current_object['template'], [ 'forum', 'topic' ] ) ) {
			if( ! empty( $forum ) ) {
				$current_object['categories'] = [ $forum ];
			} else {
				$current_object['categories'] = $this->forum->get_forums( [ "type" => 'category' ] );
			}
		}

		if( $this->current_object['template'] === 'post' && ! empty( $forum ) && ! empty( $topic ) ) {
			$current_object['items_per_page'] = $this->post->get_option_items_per_page( $current_object['layout'] );

			$args = [
				'forumid'   => $forum['forumid'],
				'topicid'   => $topic['topicid'],
				'offset'    => ( $current_object['paged'] - 1 ) * $current_object['items_per_page'],
				'row_count' => $current_object['items_per_page'],
			];
			if( $current_object['layout'] == 4 ) {
				$args['parentid'] = 0;
			} elseif( $current_object['layout'] == 3 ) {
				$args['parentid'] = 0;
				switch( $current_object['orderby'] ) {
					case 'oldest':
						$args['orderby'] = '`is_first_post` DESC, `is_answer` DESC, `created` ASC, `postid` ASC';
					break;
					case 'newest':
						$args['orderby'] = '`is_first_post` DESC, `is_answer` DESC, `modified` DESC, `postid` DESC';
					break;
					default:
						$args['orderby'] = '`is_first_post` DESC, `is_answer` DESC, `votes` DESC, `created` ASC, `postid` ASC';
					break;
				}
			}
			if( $this->post->get_option_union_first_post( $current_object['layout'] ) ) $args['union_first_post'] = true;
			$args                    = apply_filters( 'wpforo_post_list_args', $args );
			$current_object['posts'] = $this->post->get_posts( $args, $current_object['items_count'] );
		}

		$this->current_object = wpforo_parse_args( $current_object, $this->current_object );

		$this->current_object = apply_filters( 'wpforo_after_init_current_object', $this->current_object, $wpf_url_parse );

		if( $this->current_object['template'] ) {
			/**
			 * redirect not logged-in users to login page when that user no access to this page
			 */
			if( ! $this->current_userid && $this->current_object['forumid'] && ( ( in_array( $this->current_object['template'], [ 'forum', 'topic' ] ) && ! $this->perm->forum_can( 'vf', $this->current_object['forumid'] ) ) || ( $this->current_object['template'] === 'post' && ! $this->perm->forum_can( 'vt', $this->current_object['forumid'] ) ) ) ) {
				wp_safe_redirect( wpforo_login_url() );
				exit();
			}

			if( $this->current_object['template'] === 'cantlogin' ) {
				if( $this->current_userid ){
					WPF()->ram_cache->set( 'USER_LOGIN_REFERER', WPF()->current_user_login );
					wp_logout();
				}else{
					wp_safe_redirect( wpforo_login_url() );
					exit();
				}
			}

			/**
			 * redirect to the first page when paged var is greater items_count
			 */
			if( $this->current_object['items_count'] && $this->current_object['paged'] > 1 && ( ( $this->current_object['paged'] - 1 ) * $this->current_object['items_per_page'] ) >= $this->current_object['items_count'] ) {
				wp_safe_redirect( $this->strip_url_paged_var( $this->current_url ), 301 );
				exit();
			}
		} else {
			$this->current_object['is_404'] = true;
		}
	}

	public function is_installed() {
		return WPFORO_VERSION === wpforo_get_option( 'version', null, false );
	}

	public function can_use_this_slug( $slug ) {
		$return = ! in_array( $slug, $this->settings->slugs, true ) && ! in_array( $slug, array_keys( $this->settings->slugs ), true );

		return apply_filters( 'wpforo_can_use_this_slug', $return, $slug );
	}
}
