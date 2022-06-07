<?php

namespace wpforo\classes;

use stdClass;
use WP_User;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Permissions {
	public $default;
	public $accesses;

	function __construct() {
		$this->init_defaults();
	}

	private function init_defaults() {
		$this->default         = new stdClass;
		$this->default->access = [
			'accessid' => 0,
			'access'   => '',
			'title'    => '',
			'cans'     => '',
		];
	}

	public function init() {
		if( WPF()->is_installed() ) {
			if( $accesses = $this->get_accesses() ) {
				foreach( $accesses as $access ) {
					$this->accesses[ intval( $access['accessid'] ) ] = $this->accesses[ $access['access'] ] = $access;
				}
			}
		}
	}

	public function init_current_user_accesses() {
		WPF()->current_user_accesses = $this->get_forum_accesses_by_usergroup();
	}

	public function fix_access( $access ) {
		$access         = wpforo_array_args_cast_and_merge( (array) $access, $this->default->access );
		$cans           = array_map( '__return_zero', WPF()->forum->cans );
		$access['cans'] = maybe_unserialize( $access['cans'] );
		if( is_array( $access['cans'] ) ) {
			$access['cans'] = wpforo_array_args_cast_and_merge( $access['cans'], $cans );
		} else {
			$access['cans'] = $cans;
		}

		return $access;
	}

	/**
	 *
	 * @param string|int $access
	 *
	 * @return array access row by access key
	 */
	function get_access( $access ) {
		if( is_numeric( $access ) ) {
			$access = intval( $access );
		} else {
			$access = sanitize_text_field( $access );
		}
		if( ! empty( $this->accesses[ $access ] ) ) return $this->accesses[ $access ];

		$sql = "SELECT * FROM " . WPF()->tables->accesses;
		if( is_int( $access ) ) {
			$sql .= " WHERE `accessid` = %d";
		} else {
			$sql .= " WHERE `access` = %s";
		}

		return $this->fix_access( WPF()->db->get_row( WPF()->db->prepare( $sql, $access ), ARRAY_A ) );
	}


	/**
	 * get all accesses from accesses table
	 *
	 * @return array|null
	 */
	function get_accesses() {
		$sql = "SELECT * FROM " . WPF()->tables->accesses . " ORDER BY `accessid`";

		return array_map( [$this, 'fix_access'], WPF()->db->get_results( $sql, ARRAY_A ) );
	}

	/**
	 * @param array $access
	 *
	 * @return int|bool inserted id or false
	 */
	function add( $access ) {
		if( ! $access['access'] ) $access['access'] = sanitize_title( $access['title'] );

		$i    = 2;
		$slug = $access['access'];
		while( WPF()->db->get_var( WPF()->db->prepare( "SELECT `access` FROM " . WPF()->tables->accesses . " WHERE `access` = %s", sanitize_text_field( $slug ) ) ) ) {
			$slug = $access['access'] . '-' . $i;
			$i ++;
		}

		if( WPF()->db->insert( WPF()->tables->accesses,
		                       [
			                       'title'  => sanitize_text_field(
				                       $access['title']
			                       ),
			                       'access' => sanitize_text_field(
				                       $slug
			                       ),
			                       'cans'   => serialize(
				                       $access['cans']
			                       ),
		                       ],
		                       [
			                       '%s',
			                       '%s',
			                       '%s',
		                       ] ) ) {
			$access['accessid'] = WPF()->db->insert_id;
			WPF()->notice->add( 'Access successfully added', 'success' );

			return $access['accessid'];
		}

		WPF()->notice->add( 'Access add error', 'error' );

		return false;
	}

	/**
	 * @param array $access
	 *
	 * @return bool|int edited id or false
	 */
	function edit( $access ) {
		if( false !== WPF()->db->update( WPF()->tables->accesses, [
			                                                        'title' => sanitize_text_field( $access['title'] ),
			                                                        'cans'  => serialize( $access['cans'] ),
		                                                        ], [
			                                 'accessid' => $access['accessid'],
		                                 ], [ '%s', '%s' ], [ '%d' ] ) ) {
			WPF()->notice->add( 'Access successfully edited', 'success' );

			return $access['accessid'];
		}

		WPF()->notice->add( 'Access edit error', 'error' );

		return false;
	}

	/**
	 * @param int $accessid
	 *
	 * @return bool|int deleted id or false
	 */
	function delete( $accessid ) {
		$accessid = intval( $accessid );
		if( ! $accessid ) {
			WPF()->notice->add( 'Access delete error', 'error' );

			return false;
		}

		if( false !== WPF()->db->delete( WPF()->tables->accesses, [ 'accessid' => $accessid ], [ '%d' ] ) ) {
			WPF()->notice->add( 'Access successfully deleted', 'success' );

			return $accessid;
		}

		WPF()->notice->add( 'Access delete error', 'error' );

		return false;
	}

	function forum_can( $do, $forumid = null, $groupids = null ) {
		/**
		 * filter for other add-ons to manage can_attach bool value.
		 * e.g. PM add-on attachment function.
		 */
		$filter_forum_can = apply_filters( 'wpforo_permissions_forum_can', null, $do, $forumid, $groupids );
		if( ! is_null( $filter_forum_can ) ) return (int) (bool) $filter_forum_can;

		if( ( is_null( $groupids ) && !WPF()->current_user_groupids ) || !$do ) return 0;

		//User Forum accesses from Current Object of Current user
		if( is_null( $groupids ) && WPF()->current_user_accesses ) {
			$forum_id = (int) (is_null( $forumid ) ? wpfval( WPF()->current_object, 'forum', 'forumid' ) : ( wpfkey( $forumid, 'forumid' ) ? $forumid['forumid'] : $forumid ));
			if( $forum_id && ($forum_accesses = wpfval( WPF()->current_user_accesses, $forum_id )) ) {
				foreach( $forum_accesses as $cans ) {
					if( (int) wpfval( $cans, $do ) ) return 1;
				}
			}

			return 0;
		}

		//Use Custom User Forum Accesses
		$forum = is_null( $forumid ) ? WPF()->current_object['forum'] : ( !wpfkey( $forumid, 'forumid' ) ? WPF()->forum->get_forum( $forumid ) : $forumid );
		if( $forum ) {
			$permissions = maybe_unserialize( $forum['permissions'] );
			if( is_null( $groupids ) ) $groupids = WPF()->current_user_groupids;
			$groupids = array_map( 'intval', (array) $groupids );
			foreach( $groupids as $groupid ) {
				if( $_access = wpfval( $permissions, $groupid ) ) {
					$access = $this->get_access( $_access );
					if( (int) wpfval( $access, 'cans', $do ) ) return 1;
				}
			}
		}

		return 0;
	}

	function user_can_manage_user( $user_id, $managing_user_id ) {
		if( ! $user_id || ! $managing_user_id ) return false;
		if( $user_id == $managing_user_id ) return true;

		$user       = new WP_User( $user_id );
		$user_level = $this->user_wp_level( $user );
		if( ! empty( $user->roles ) && is_array( $user->roles ) ) $user_role = array_shift( $user->roles );

		$managing_user       = new WP_User( $managing_user_id );
		$managing_user_level = $this->user_wp_level( $managing_user );
		if( ! empty( $managing_user->roles ) && is_array( $managing_user->roles ) ) $managing_user_role = array_shift( $managing_user->roles );

		if( (int) $user_level > (int) $managing_user_level ) {
			return true;
		} elseif( $user_id == 1 && $user_role === 'administrator' ) {
			return true;
		} elseif( (int) $user_level === (int) $managing_user_level ) {
			$member                   = WPF()->member->get_member( $user_id );
			$managing_member          = WPF()->member->get_member( $managing_user_id );
			$user_wpforo_can          = WPF()->usergroup->can( 'em', $member['groupids'] );
			$managing_user_wpforo_can = WPF()->usergroup->can( 'em', $managing_member['groupids'] );
			if( $user_wpforo_can && ! $managing_user_wpforo_can ) {
				return true;
			} else {
				return false;
			}
		} elseif( $user_id != 1 && $managing_user_id == 1 && $managing_user_role === 'administrator' ) {
			return false;
		} else {
			return false;
		}
	}

	function user_wp_level( $user_object ) {
		$level  = 0;
		$levels = [];
		if( is_int( $user_object ) ) {
			$user_object = new WP_User( $user_object );
		}
		if( isset( $user_object->allcaps ) && is_array( $user_object->allcaps ) && ! empty( $user_object->allcaps ) ) {
			foreach( $user_object->allcaps as $level_key => $level_value ) {
				if( strpos( $level_key, 'level_' ) !== false && $level_value == 1 ) {
					$levels[] = intval( str_replace( 'level_', '', $level_key ) );
				}
			}
			if( ! empty( $levels ) ) {
				$level = max( $levels );
			}
		}

		return $level;
	}

	function can_edit_user( $userid ) {

		if( ! $userid ) return false;

		if( ! ( $userid == WPF()->current_userid || ( WPF()->usergroup->can( 'em' ) && WPF()->perm->user_can_manage_user( WPF()->current_userid, $userid ) ) ) ) {
			WPF()->notice->clear();
			WPF()->notice->add( 'Permission denied', 'error' );
			wp_safe_redirect( wpforo_get_request_uri() );
			exit();
		}

		return true;
	}

	public function can_link() {
		if( ! WPF()->usergroup->can( 'em' ) ) {
			$posts = WPF()->member->member_approved_posts( WPF()->current_userid );
			$posts = intval( $posts );
			if( ( $min_posts = wpforo_setting( 'antispam', 'min_number_posts_to_link' ) ) && $posts <= $min_posts ) return false;
		}

		return true;
	}

	public function can_attach( $forumid = null ) {
		if( ! $forumid ) $forumid = null;

		/**
		 * filter for other add-ons to manage can_attach bool value.
		 * e.g. PM add-on attachment function.
		 */
		$filter_wpforo_can_attach = apply_filters( 'wpforo_can_attach', null, $forumid );
		if( ! is_null( $filter_wpforo_can_attach ) ) return (bool) $filter_wpforo_can_attach;

		if( ! $this->forum_can( 'a', $forumid ) ) return false;
		if( ! WPF()->usergroup->can( 'em' ) ) {
			$posts = WPF()->member->member_approved_posts( WPF()->current_userid );
			$posts = intval( $posts );
			if( ( $min_posts = wpforo_setting( 'antispam', 'min_number_posts_to_attach' ) ) && $posts <= $min_posts ) return false;
		}

		return true;
	}

	public function can_attach_file_type( $ext = '' ) {
		if( ! WPF()->usergroup->can( 'em' ) && WPF()->member->current_user_is_new() && in_array( $ext, wpforo_setting( 'antispam', 'limited_file_ext' ) ) ) return false;

		return true;
	}

	/**
	 * @return bool
	 */
	public function can_post_now() {
		date_default_timezone_set( 'UTC' );
		ini_set( 'date.timezone', 'UTC' );

		if( wpforo_is_admin() || ( defined( 'IS_GO2WPFORO' ) && IS_GO2WPFORO ) ) {
			return true;
		}

		$email   = ( ( $userid = WPF()->current_userid ) ? '' : WPF()->current_user_email );
		$groupid = WPF()->current_user_groupid;
		if( WPF()->member->current_user_is_new() ) {
			$groupid = 0;
		}
		if( ! $flood_interval = WPF()->usergroup->get_flood_interval( $groupid ) ) {
			return true;
		}
		$hour_ago = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );

		$args        = [
			'userid'    => $userid,
			'email'     => $email,
			'orderby'   => '`created` DESC, `postid` DESC',
			'row_count' => 1,
			'where'     => "`created` >= '$hour_ago'",
		];
		$items_count = 0;
		$lastpost    = WPF()->post->get_posts( $args, $items_count, false );
		if( $lasttime = wpfval( $lastpost, 0, 'created' ) ) {
			$lasttime = strtotime( $lasttime );
			$nowtime  = current_time( 'timestamp', 1 );
			$diff     = $nowtime - $lasttime;
			if( $diff < $flood_interval ) {
				return false;
			}
		}

		return true;
	}

	public function get_forum_accesses_by_usergroup( $groupids = [] ) {
		$forum_accesses = [];
		if( !$groupids ) $groupids = WPF()->current_user_groupids;
		if( ($groupids = array_map( 'intval', (array) $groupids )) && ($forums = WPF()->forum->get_forums()) ) {
			foreach( $forums as $forum ) {
				if( $permissions = maybe_unserialize( $forum['permissions'] ) ) {
					foreach( $groupids as $groupid ) {
						$access = wpfval( $permissions, $groupid );
						if( $cans = $this->accesses[$access]['cans'] ) {
							if( ! wpfkey( $forum_accesses, $forum['forumid'], $access ) ) $forum_accesses[ $forum['forumid'] ][ $access ] = $cans;
						}
					}
				}
			}
		}

		return $forum_accesses;
	}

	public function show_accesses_selectbox( $selected = [], $exclude = [] ) {
		$accesses = $this->get_accesses();
		foreach( $accesses as $accesse ) {
			if( in_array( $accesse['access'], (array) $exclude ) ) continue;
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $accesse['access'] ),
				in_array( $accesse['access'], (array) $selected ) ? 'selected' : '',
				esc_html( $accesse['title'] )
			);
		}
	}
}
