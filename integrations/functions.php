<?php

use wpforo\integrations\BuddyPress as wpForoBuddyPress;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

function wpforo_has_shop_plugin() {
	$profile_url = false;
	if( is_user_logged_in() ) {
		// WooCommerce | Account Page URL
		if( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$profile_url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
		}
	}

	return $profile_url;
}

function wpforo_has_profile_plugin( $userid = 0 ) {
	$profile_url = false;
	if( $userid ) {
		// Ultimate Member | Profile Page URL
		if( function_exists( 'UM' ) ) {
			um_fetch_user( $userid );
			$profile_url = um_user_profile_url();
			um_reset_user();
		} // BuddyPress | Profile Page URL
		elseif( class_exists( 'BuddyPress' ) ) {
			$profile_url = bp_core_get_user_domain( $userid );
		} // Users Ultra | Profile Page URL
		elseif( class_exists( 'XooUserUltra' ) ) {
			global $xoouserultra;
			$profile_url = $xoouserultra->userpanel->get_user_profile_permalink( $userid );
		}
		// User Pro | Profile Page URL
		if( class_exists( 'userpro_api' ) ) {
			global $userpro;
			$profile_url = $userpro->permalink( $userid );
		}
	}

	return $profile_url;
}

add_action( 'wpforo_actions_end', function() {
	if( ! wpforo_setting( 'seo', 'seo_meta' ) ) return;

	if( is_wpforo_page() ) {
		remove_action( 'wp_head', 'jetpack_og_tags' ); // JetPack}
		if( defined( 'WPSEO_VERSION' ) ) { // Yoast SEO
			remove_action( 'wp_head', 'wpseo_head', 20 );
			remove_action( 'wp_head', 'wpseo_opengraph', 20 );
			add_filter( 'wpseo_canonical', '__return_false' );
			add_filter( 'wpseo_title', '__return_false' );
			add_filter( 'wpseo_metadesc', '__return_false' );
			add_filter( 'wpseo_author_link', '__return_false' );
			add_filter( 'wpseo_metakey', '__return_false' );
			add_filter( 'wpseo_locale', '__return_false' );
			add_filter( 'wpseo_opengraph_title', '__return_false' );
			add_filter( 'wpseo_opengraph_url', '__return_false' );
			add_filter( 'wpseo_opengraph_desc', '__return_false' );
			add_filter( 'wpseo_opengraph_author_facebook', '__return_false' );
			add_filter( 'wpseo_opengraph_type', '__return_false' );
			add_filter( 'wpseo_opengraph_image', '__return_false' );
			add_filter( 'wpseo_opengraph_image_size', '__return_false' );
			add_filter( 'wpseo_opengraph_site_name', '__return_false' );
			add_filter( 'wpseo_twitter_card_type', '__return_false' );
			add_filter( 'wpseo_twitter_description', '__return_false' );
			add_filter( 'wpseo_twitter_title', '__return_false' );
			add_filter( 'wpseo_twitter_image', '__return_false' );
			add_filter( 'wpseo_twitter_image_size', '__return_false' );
			add_filter( 'wp_seo_get_bc_ancestors', '__return_false' );
			add_filter( 'wpseo_whitelist_permalink_vars', '__return_false' );
			add_filter( 'wpseo_prev_rel_link', '__return_false' );
			add_filter( 'wpseo_next_rel_link', '__return_false' );
			add_filter( 'wpseo_xml_sitemap_img_src', '__return_false' );
			add_filter( 'wpseo_json_ld_output', '__return_false' );
		}
		if( function_exists( 'aioseo' ) || defined( 'AIOSEOP_VERSION' ) ) { // All-In-One SEO
			if( array_key_exists( 'aiosp', $GLOBALS ) ) {
				global $aiosp;
				remove_action( 'wp_head', [ $aiosp, 'wp_head' ] );
				add_filter( 'aioseop_title', '__return_false' );
				add_filter( 'aioseop_canonical_url', '__return_false' );
			} else {
				add_filter( 'aioseo_disable', '__return_true' );
			}
		}
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'start_post_rel_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		remove_all_filters( 'pre_get_document_title', 15 );
	}
} );

//Stops Rank Math SEO overwriting wpForo SEO
add_action( 'wpforo_after_init', function() {
	if( ! wpforo_setting( 'seo', 'seo_meta' ) ) return;
	if( is_wpforo_page() ) {
		if( defined( 'RANK_MATH_FILE' ) ) {
			add_filter( 'rank_math/frontend/remove_credit_notice', '__return_true' );
			remove_all_actions( 'rank_math/head' );
			remove_all_filters( 'pre_get_document_title', 15 );
			add_action( 'wp_head', '_wp_render_title_tag', 1 );
		}
	}
} );

////////////////////////////////////////////////////////////////////////////////////////
// BuddyPress Integration //////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
add_action( 'bp_init', function() {
	if( ! function_exists( 'buddypress' ) ) {
		function buddypress() { return isset( $GLOBALS['bp'] ) ? $GLOBALS['bp'] : false; }
	}
	if( ! buddypress() || buddypress()->maintenance_mode ) return;
	require( WPFORO_DIR . '/integrations/BuddyPressHooks.php' );
	if( wpforo_setting( 'buddypress', 'forum_tab' ) ) {
		new wpForoBuddyPress();
	}
	if( wpforo_setting( 'profiles', 'profile' ) === 'bp' ) {
		add_filter( 'wpforo_member_profile_url', 'wpforo_bp_profile_url', 10, 3 );
	}
},          0 );

////////////////////////////////////////////////////////////////////////////////////////
// General Data Protection Regulation (GDPR) ///////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
include( WPFORO_DIR . '/integrations/legal.php' );

////////////////////////////////////////////////////////////////////////////////////////
// Ultimate Member Integration /////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////
include( WPFORO_DIR . '/integrations/UltimateMember.php' );
