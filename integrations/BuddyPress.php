<?php

namespace wpforo\integrations;

use BP_Component;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class BuddyPress extends BP_Component {
	public function __construct() {
		parent::start( 'community', __( 'Forums', 'wpforo' ) );
		$this->includes();
		$this->setup_globals();
		$this->setup_actions();
	}

	public function includes( $includes = [] ) {
		$includes[] = 'functions.php';
		$includes[] = 'members.php';
		if( bp_is_active( 'notifications' ) ) $includes[] = 'notifications.php';
		if( bp_is_active( 'activity' ) ) $includes[] = 'activity.php';
		parent::includes( $includes );
	}

	public function setup_globals( $args = [] ) {
		$bp      = buddypress();
		$wpfpath = WPF()->board->route ?: 'community';
		$args    = [
			'path'          => WPFORO_DIR,
			'slug'          => $wpfpath,
			'root_slug'     => isset( $bp->pages->forums->slug ) ? $bp->pages->forums->slug : $wpfpath,
			'has_directory' => false,
			'search_string' => __( 'Search Forums...', 'wpforo' ),
		];
		parent::setup_globals( $args );
	}

	public function setup_nav( $main_nav = [], $sub_nav = [] ) {
		if( ! is_user_logged_in() && ! bp_displayed_user_id() ) return;

		// Add 'Forums' to the main navigation
		$main_nav = [
			'name'                => __( 'Forums', 'wpforo' ),
			'slug'                => $this->slug,
			'position'            => 81,
			'screen_function'     => 'wpforo_bp_forums_screen_topics',
			'default_subnav_slug' => 'topics',
			'item_css_id'         => $this->id,
		];

		// Determine user to use
		if( bp_displayed_user_id() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		// User link
		$forums_link = trailingslashit( $user_domain . $this->slug );

		// Topics started
		$sub_nav[] = [
			'name'            => __( 'Topics Started', 'wpforo' ),
			'slug'            => 'topics',
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'wpforo_bp_forums_screen_topics',
			'position'        => 21,
			'item_css_id'     => 'wpf-topics',
		];

		// Replies to topics
		$sub_nav[] = [
			'name'            => __( 'Replies Created', 'wpforo' ),
			'slug'            => 'replies',
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'wpforo_bp_forums_screen_replies',
			'position'        => 41,
			'item_css_id'     => 'wpf-replies',
		];

		// Liked Posts
		$sub_nav[] = [
			'name'            => __( 'Liked Posts', 'wpforo' ),
			'slug'            => 'likes',
			'parent_url'      => $forums_link,
			'parent_slug'     => $this->slug,
			'screen_function' => 'wpforo_bp_forums_screen_likes',
			'position'        => 61,
			'item_css_id'     => 'wpf-likes',
		];

		// Subscribed topics (my profile only)
		if( bp_is_my_profile() ) {
			$sub_nav[] = [
				'name'            => __( 'Subscriptions', 'wpforo' ),
				'slug'            => 'subscriptions',
				'parent_url'      => $forums_link,
				'parent_slug'     => $this->slug,
				'screen_function' => 'wpforo_bp_forums_screen_subscriptions',
				'position'        => 61,
				'item_css_id'     => 'wpf-subscriptions',
			];
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	public function setup_title() {
		$bp = buddypress();
		if( bp_is_forums_component() ) {
			if( bp_is_my_profile() ) {
				$bp->bp_options_title = __( 'Forums', 'wpforo' );
			} elseif( bp_is_user() ) {
				$bp->bp_options_avatar = bp_core_fetch_avatar( [ 'item_id' => bp_displayed_user_id(), 'type' => 'thumb' ] );
				$bp->bp_options_title  = bp_get_displayed_user_fullname();
			}
		}
		parent::setup_title();
	}
}
