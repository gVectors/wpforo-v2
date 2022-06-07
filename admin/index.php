<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function() {
	if( wpforo_is_multiboard() ){
		wpforo_admin_menu();
	}else{
		wpforo_admin_menu_mono();
	}
}, 39 );

function wpforo_admin_menu_mono(){
	if( wpforo_current_user_is( 'admin' ) || WPF()->usergroup->can( 'mf' ) || WPF()->usergroup->can( 'ms' ) || WPF()->usergroup->can( 'vm' ) || WPF()->usergroup->can( 'mp' ) || WPF()->usergroup->can( 'aum' ) || WPF()->usergroup->can( 'vmg' ) || WPF()->usergroup->can( 'mth' ) ) {
		$boards = WPF()->board->get_boards( [ 'status' => true ] );
		$menu_position = apply_filters( 'wpforo_admin_menu_position', 23 );
		$parent_slug = 'wpforo-overview';
		$attention_count = WPF()->member->get_count( [ 'p.status' => ['banned','inactive'] ] );
		$after_members_menu_title = $attention_count ? ' <span class="awaiting-mod count-1" title="'. __( 'Inactive and banned', 'wpforo' ) .'"><span class="pending-count" style="color:#ffffff;">' . $attention_count . '</span></span> ' : '';
		add_menu_page(
			'wpForo',
			'wpForo' . ( $after_members_menu_title ? ' <span class="awaiting-mod count-1"><span class="pending-count">' . $attention_count . '</span></span> ' : '' ),
			'read',
			$parent_slug,
			function() {
				require( WPFORO_DIR . '/admin/pages/overview.php' );
				require WPFORO_DIR . '/admin/pages/dashboard.php';
			},
			'dashicons-format-chat',
			$menu_position
		);
		add_submenu_page(
			$parent_slug,
			__( 'Overview', 'wpforo' ),
			__( 'Overview', 'wpforo' ),
			'read',
			$parent_slug
		);

		foreach( $boards as $board ) {
			WPF()->change_board( $board['boardid'] );

			if( wpforo_setting( 'antispam', 'spam_file_scanner' ) ) WPF()->moderation->spam_attachment();

			$all_count = 0;
			$mod_count = WPF()->post->unapproved_count();
			$all_count += $mod_count;
			$mod_count = $mod_count ? ' <span class="awaiting-mod count-1"><span class="pending-count">' . $mod_count . '</span></span> ' : '';
			$all_count = $all_count ? ' <span class="awaiting-mod count-1"><span class="pending-count">' . $all_count . '</span></span> ' : '';

			if( wpforo_current_user_is( 'admin' ) || WPF()->usergroup->can( 'mf' ) || WPF()->usergroup->can( 'ms' ) || WPF()->usergroup->can( 'mt' ) || WPF()->usergroup->can( 'mp' ) || WPF()->usergroup->can( 'mth' ) || WPF()->usergroup->can( 'aum' ) ) {
				if( WPF()->usergroup->can( 'mf' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Forums', 'wpforo' ), __( 'Forums', 'wpforo' ), 'read', wpforo_prefix_slug( 'forums' ), function() {
						require( WPFORO_DIR . '/admin/pages/forum.php' );
					} );
				}
				if( WPF()->usergroup->can( 'ms' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Settings', 'wpforo' ), __( 'Settings', 'wpforo' ), 'read', wpforo_prefix_slug( 'settings' ), function() {
						require( WPFORO_DIR . '/admin/pages/settings.php' );
					} );
				}
				if( WPF()->usergroup->can( 'ms' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Accesses', 'wpforo' ), __( 'Accesses', 'wpforo' ), 'read', wpforo_prefix_slug( 'accesses' ), function() {
						require( WPFORO_DIR . '/admin/pages/accesses.php' );
					} );
				}
				if( WPF()->usergroup->can( 'aum' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Moderation', 'wpforo' ), __( 'Moderation', 'wpforo' ) . $mod_count, 'read', wpforo_prefix_slug( 'moderations' ), function() {
						require( WPFORO_DIR . '/admin/pages/moderation.php' );
					} );
				}
				if( WPF()->usergroup->can( 'mp' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Phrases', 'wpforo' ), __( 'Phrases', 'wpforo' ), 'read', wpforo_prefix_slug( 'phrases' ), function() {
						require( WPFORO_DIR . '/admin/pages/phrase.php' );
					} );
				}
				if( WPF()->usergroup->can( 'mt' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Tools', 'wpforo' ), __( 'Tools', 'wpforo' ), 'read', wpforo_prefix_slug( 'tools' ), function() {
						require( WPFORO_DIR . '/admin/pages/tools.php' );
					} );
				}

				do_action( 'wpforo_admin_menu', $parent_slug, $board );
			}
		}

		add_submenu_page( $parent_slug, __( 'Boards', 'wpforo' ), __( 'Boards', 'wpforo' ), 'read', 'wpforo-boards', function() {
			require( WPFORO_DIR . '/admin/pages/board.php' );
		} );
		add_submenu_page( $parent_slug, __( 'Usergroups', 'wpforo' ), __( 'Usergroups', 'wpforo' ), 'read', 'wpforo-usergroups', function() {
			require( WPFORO_DIR . '/admin/pages/usergroup.php' );
		} );
		add_submenu_page( $parent_slug, __( 'Members', 'wpforo' ), __( 'Members', 'wpforo' ) . $after_members_menu_title, 'read', 'wpforo-members', function() {
			require( WPFORO_DIR . '/admin/pages/member.php' );
		} );
		if( WPF()->usergroup->can( 'mth' ) || wpforo_current_user_is( 'admin' ) ) {
			add_submenu_page( $parent_slug, __( 'Themes', 'wpforo' ), __( 'Themes', 'wpforo' ), 'read', wpforo_prefix_slug( 'themes' ), function() {
				require( WPFORO_DIR . '/admin/pages/themes.php' );
			} );
		}
		if( wpforo_current_user_is( 'admin' ) ) {
			add_submenu_page( $parent_slug, __( 'Addons', 'wpforo' ), __( 'Addons', 'wpforo' ), 'read', wpforo_prefix_slug( 'addons' ), function() {
				require( WPFORO_DIR . '/admin/pages/addons.php' );
			} );
		}

		do_action( 'wpforo_admin_base_menu', $parent_slug );
	}
}

function wpforo_admin_menu(){
	if( wpforo_current_user_is( 'admin' ) || WPF()->usergroup->can( 'mf' ) || WPF()->usergroup->can( 'ms' ) || WPF()->usergroup->can( 'vm' ) || WPF()->usergroup->can( 'mp' ) || WPF()->usergroup->can( 'aum' ) || WPF()->usergroup->can( 'vmg' ) || WPF()->usergroup->can( 'mth' ) ) {
		$menu_position = apply_filters( 'wpforo_admin_menu_position', 23 );
		$parent_slug = 'wpforo-overview';
		$attention_count = WPF()->member->get_count( [ 'p.status' => ['banned', 'inactive'] ] );
		$after_members_menu_title = $attention_count ? ' <span class="awaiting-mod count-1" title="'. __( 'Inactive and Banned', 'wpforo' ) .'"><span class="pending-count" style="color:#ffffff;">' . $attention_count . '</span></span> ' : '';
		add_menu_page(
			'wpForo',
			'wpForo' . ( $after_members_menu_title ? ' <span class="awaiting-mod count-1"><span class="pending-count">' . $attention_count . '</span></span> ' : '' ),
			'read',
			$parent_slug,
			function() {
				require( WPFORO_DIR . '/admin/pages/overview.php' );
			},
			'dashicons-format-chat',
			$menu_position
		);
		add_submenu_page(
			$parent_slug,
			__( 'Overview', 'wpforo' ),
			__( 'Overview', 'wpforo' ),
			'read',
			$parent_slug
		);
		add_submenu_page( $parent_slug, __( 'Boards', 'wpforo' ), __( 'Boards', 'wpforo' ), 'read', 'wpforo-boards', function() {
			require( WPFORO_DIR . '/admin/pages/board.php' );
		} );
		add_submenu_page( $parent_slug, __( 'Usergroups', 'wpforo' ), __( 'Usergroups', 'wpforo' ), 'read', 'wpforo-usergroups', function() {
			require( WPFORO_DIR . '/admin/pages/usergroup.php' );
		} );
		add_submenu_page( $parent_slug, __( 'Members', 'wpforo' ), __( 'Members', 'wpforo' ) . $after_members_menu_title, 'read', 'wpforo-members', function() {
			require( WPFORO_DIR . '/admin/pages/member.php' );
		} );
		if( WPF()->usergroup->can( 'ms' ) || wpforo_current_user_is( 'admin' ) ) {
			add_submenu_page( $parent_slug, __( 'Settings', 'wpforo' ), __( 'Settings', 'wpforo' ), 'read', 'wpforo-base-settings', function() {
				require( WPFORO_DIR . '/admin/pages/settings.php' );
			} );
		}
		if( WPF()->usergroup->can( 'mth' ) || wpforo_current_user_is( 'admin' ) ) {
			add_submenu_page( $parent_slug, __( 'Themes', 'wpforo' ), __( 'Themes', 'wpforo' ), 'read', wpforo_prefix_slug( 'themes' ), function() {
				require( WPFORO_DIR . '/admin/pages/themes.php' );
			} );
		}
		if( wpforo_current_user_is( 'admin' ) ) {
			add_submenu_page( $parent_slug, __( 'Addons', 'wpforo' ), __( 'Addons', 'wpforo' ), 'read', wpforo_prefix_slug( 'addons' ), function() {
				require( WPFORO_DIR . '/admin/pages/addons.php' );
			} );
		}

		do_action( 'wpforo_admin_base_menu', $parent_slug );

		$boards = WPF()->board->get_boards( [ 'status' => true ] );
		foreach( $boards as $board ) {
			WPF()->change_board( [ 'boardid' => $board['boardid'] ] );

			if( wpforo_setting( 'antispam', 'spam_file_scanner' ) ) WPF()->moderation->spam_attachment();

			$all_count = 0;
			$mod_count = WPF()->post->unapproved_count();
			$all_count += $mod_count;
			$mod_count = $mod_count ? ' <span class="awaiting-mod count-1"><span class="pending-count">' . $mod_count . '</span></span> ' : '';
			$all_count = $all_count ? ' <span class="awaiting-mod count-1"><span class="pending-count">' . $all_count . '</span></span> ' : '';

			if( wpforo_current_user_is( 'admin' ) || WPF()->usergroup->can( 'mf' ) || WPF()->usergroup->can( 'ms' ) || WPF()->usergroup->can( 'mt' ) || WPF()->usergroup->can( 'mp' ) || WPF()->usergroup->can( 'mth' ) || WPF()->usergroup->can( 'aum' ) ) {
				$parent_slug = wpforo_prefix_slug( 'dashboard' );
				add_menu_page(
					__( 'Dashboard', 'wpforo' ),
					$board['title'] . $all_count,
					'read',
					$parent_slug,
					function() {
						require( WPFORO_DIR . '/admin/pages/dashboard.php' );
					},
					'dashicons-format-chat',
					$menu_position
				);
				add_submenu_page(
					$parent_slug,
					__( 'Dashboard', 'wpforo' ),
					__( 'Dashboard', 'wpforo' ),
					'read',
					$parent_slug
				);
				if( WPF()->usergroup->can( 'mf' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Forums', 'wpforo' ), __( 'Forums', 'wpforo' ), 'read', wpforo_prefix_slug( 'forums' ), function() {
						require( WPFORO_DIR . '/admin/pages/forum.php' );
					} );
				}
				if( WPF()->usergroup->can( 'ms' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Settings', 'wpforo' ), __( 'Settings', 'wpforo' ), 'read', wpforo_prefix_slug( 'settings' ), function() {
						require( WPFORO_DIR . '/admin/pages/settings.php' );
					} );
				}
				if( WPF()->usergroup->can( 'ms' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Accesses', 'wpforo' ), __( 'Accesses', 'wpforo' ), 'read', wpforo_prefix_slug( 'accesses' ), function() {
						require( WPFORO_DIR . '/admin/pages/accesses.php' );
					} );
				}
				if( WPF()->usergroup->can( 'aum' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Moderation', 'wpforo' ), __( 'Moderation', 'wpforo' ) . $mod_count, 'read', wpforo_prefix_slug( 'moderations' ), function() {
						require( WPFORO_DIR . '/admin/pages/moderation.php' );
					} );
				}
				if( WPF()->usergroup->can( 'mp' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Phrases', 'wpforo' ), __( 'Phrases', 'wpforo' ), 'read', wpforo_prefix_slug( 'phrases' ), function() {
						require( WPFORO_DIR . '/admin/pages/phrase.php' );
					} );
				}
				if( WPF()->usergroup->can( 'mt' ) || wpforo_current_user_is( 'admin' ) ) {
					add_submenu_page( $parent_slug, __( 'Tools', 'wpforo' ), __( 'Tools', 'wpforo' ), 'read', wpforo_prefix_slug( 'tools' ), function() {
						require( WPFORO_DIR . '/admin/pages/tools.php' );
					} );
				}

				do_action( 'wpforo_admin_menu', $parent_slug, $board );
			}
		}
	}
}

add_action( 'admin_footer', function() {
	if( ! wpforo_get_option( 'deactivation_dialog_never_show', false, false ) && ( strpos( wpforo_get_request_uri(), '/plugins.php' ) !== false ) ) {
		require( WPFORO_DIR . '/admin/includes/deactivation-dialog.php' );
	}
} );

add_action( 'admin_footer', function() {
	$display = ( apply_filters( 'wpforo_admin_loading', false ) ) ? 'block' : 'none';
	echo '<div id="wpf-admin-loading-extrawrap" style="display: ' . $display . '">
			<div class="wpf-admin-loading-wrap">
				<div class="wpf-admin-loading-ico dashicons dashicons-update"></div>
				<div class="wpf-admin-loading-txt">' . __( 'don\'t stop or close browser', 'wpforo' ) . '</div>
			</div>
	</div>';
} );
