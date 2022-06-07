<?php

namespace wpforo\modules\subscriptions;

use stdClass;
use wpforo\modules\subscriptions\classes\Template;
use wpforo\modules\subscriptions\classes\Actions;
use wpforo\modules\mentioning\Mentioning;
use wpforo\modules\follows\Follows;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Subscriptions {
	public $default;
	/* @var Template   */ public $Template;
	/* @var Actions    */ public $Actions;
	/* @var Mentioning */ public $Mentioning;
	/* @var Follows    */ public $Follows;

	function __construct() {
		$this->init_defaults();
		$this->init_classes();
		$this->init_hooks();
	}

	private function init_defaults() {
		$this->default                   = new stdClass;
		$this->default->subscribe_format = [
			'subid'      => '%d',
			'type'       => '%s',
			'itemid'     => '%d',
			'userid'     => '%d',
			'confirmkey' => '%s',
			'active'     => '%d',
			'user_name'  => '%s',
			'user_email' => '%s',
		];
	}

	private function init_classes() {
		$this->Template   = new Template();
		$this->Actions    = new Actions();
		$this->Mentioning = new Mentioning();
		$this->Follows    = new Follows();
	}

	private function init_hooks() {
		add_filter( 'wpforo_init_member_templates',     [ $this, 'add_templates' ] );
		add_filter( 'wpforo_after_init_current_object', [ $this, 'init_current_object' ] );
	}

	public function add_templates( $templates ) {
		$templates['subscriptions'] = [
			'type'                  => 'callback',
			'key'                   => 'subscriptions',
			'menu_shortcode'        => 'wpforo-profile-subscriptions',
			'ico'                   => '<i class="fas fa-rss"></i>',
			'title'                 => 'Subscriptions',
			'is_default'            => 1,
			'can'                   => 'vprs',
			'add_in_member_menu'    => 1,
			'add_in_member_buttons' => 1,
			'callback_for_page'     => function(){
				require_once wpftpl( 'profile-subscriptions.php' );
			},
		];

		return $templates;
	}

	public function init_current_object( $current_object ) {
		if( !$current_object['is_404'] && $current_object['template'] === 'subscriptions' ){
			$args                          = [
				'offset'    => ( $current_object['paged'] - 1 ) * $current_object['items_per_page'],
				'row_count' => $current_object['items_per_page'],
				'userid'    => $current_object['userid'],
				'order'     => 'DESC',
			];
			$current_object['items_count'] = 0;
			$current_object['subscribes']  = $this->get_subscribes( $args, $current_object['items_count'] );
		}

		return $current_object;
	}

	function get_confirm_key() {
		return substr( md5( rand() . time() ), 0, 32 );
	}

	function add( $args = [] ) {
		if( empty( $args ) && empty( $_REQUEST['sbscrb'] ) ) return false;
		if( empty( $args ) && ! empty( $_REQUEST['sbscrb'] ) ) $args = $_REQUEST['sbscrb'];
		if( ! isset( $args['active'] ) || ! $args['active'] ) $args['active'] = 0;

		extract( $args );
		if( ! isset( $itemid ) || ! ( ( isset( $userid ) && $userid ) || ( isset( $user_email ) && $user_email ) ) || ! isset( $type ) || ! $type ) return false;

		if( empty( $confirmkey ) ) $confirmkey = $this->get_confirm_key();

		$user_name  = ( isset( $user_name ) && $user_name ? $user_name : '' );
		$user_email = ( isset( $user_email ) && $user_email ? $user_email : '' );

		$sql = "SELECT EXISTS( 
					SELECT * FROM `" . WPF()->tables->subscribes . "` 
						WHERE `itemid` = %d 
						AND `type` = %s 
						AND `userid` = %d 
						AND `user_email` = %s 
		        ) AS is_exists";
		$sql = WPF()->db->prepare( $sql, $itemid, $type, $userid, $user_email );
		if( ! WPF()->db->get_var( $sql ) ) {
			if( WPF()->db->insert(
				WPF()->tables->subscribes,
               [
                   'itemid'     => $itemid,
                   'type'       => $type,
                   'confirmkey' => $confirmkey,
                   'userid'     => $userid,
                   'active'     => $active,
                   'user_name'  => $user_name,
                   'user_email' => $user_email,
               ],
               [ '%d', '%s', '%s', '%d', '%d', '%s', '%s' ]
			) ) {
				if( isset( $active ) && $active == 1 ) {
					if( intval( $userid ) === WPF()->current_userid ) {
						WPF()->notice->add( 'You have been successfully subscribed', 'success' );
					} else {
						WPF()->notice->add( 'Success!', 'success' );
					}
				} else {
					if( intval( $userid ) === WPF()->current_userid ) {
						WPF()->notice->add( 'Success! Thank you. Please check your email and click confirmation link below to complete this step.', 'success' );
					} else {
						WPF()->notice->add( 'Success!', 'success' );
					}
				}

				return $confirmkey;
			}

		}

		WPF()->notice->add( 'Can\'t subscribe to this item', 'error' );

		return false;
	}

	function edit( $confirmkey = '' ) {
		if( ! $confirmkey && isset( $_REQUEST['key'] ) && $_REQUEST['key'] ) $confirmkey = $_REQUEST['key'];
		if( ! $confirmkey ) {
			WPF()->notice->add( 'Invalid request!', 'error' );
			return false;
		}

		if( WPF()->db->update( WPF()->tables->subscribes, [ 'active' => 1 ], [ 'confirmkey' => sanitize_text_field( $confirmkey ) ], [ '%d' ], [ '%s' ] ) ) {
			if( $sbs = $this->get_subscribe( $confirmkey ) ) WPF()->member->set_is_email_confirmed( $sbs['userid'], 1 );

			WPF()->notice->add( 'You have been successfully subscribed', 'success' );
			return true;
		}

		WPF()->notice->add( 'Your subscription for this item could not be confirmed', 'error' );
		return false;
	}

	public function reset( $data = [], $all = '', $user = null ) {
		if( ! $user && ! WPF()->current_userid && ! WPF()->current_user_email ) return false;
		if( ! $user ) $user = ( WPF()->current_userid ? WPF()->current_userid : WPF()->current_user_email );
		$data = array_filter( (array) $data );
		$args = [
			'itemid'     => 0,
			'type'       => '',
			'userid'     => 0,
			'active'     => ( wpforo_setting( 'subscriptions', 'subscribe_confirmation' ) ? 0 : 1 ),
			'user_name'  => '',
			'user_email' => '',
		];
		if( is_numeric( $user ) ) {
			$args['userid'] = $user;
			$where          = WPF()->db->prepare( "`userid` = %d", $user );
		} else {
			$args['user_email'] = $user;
			$args['user_name']  = WPF()->current_user_display_name;
			$where              = WPF()->db->prepare( "`user_email` = %s", $user );
		}

		if( $args['active'] !== 1 && $this->is_email_confirmed( $user ) ) $args['active'] = 1;

		$types = [ 'forum', 'forum-topic' ];
		if( ! $all ) array_push( $types, 'forums', 'forums-topics' );
		$sql = "DELETE FROM `" . WPF()->tables->subscribes . "` WHERE `type` IN('" . implode( "','", $types ) . "') AND " . $where;
		if( ! $all && $data ) {
			$forumids = array_keys( $data );
			$sql      .= " AND `itemid` NOT IN(" . implode( ',', $forumids ) . ")";
		}
		WPF()->db->query( $sql );

		if( ! $all && $data ) {
			foreach( $data as $forumid => $type ) {
				$sql = "SELECT `subid` FROM `" . WPF()->tables->subscribes . "` WHERE `type` IN('forum', 'forum-topic') AND `itemid` = %d AND " . $where;
				$sql = WPF()->db->prepare( $sql, $forumid );
				if( $subid = WPF()->db->get_var( $sql ) ) {
					WPF()->db->update( WPF()->tables->subscribes, [ 'type' => sanitize_text_field( $type ), 'active' => $args['active'] ], [ 'subid' => $subid ], [ '%s', '%d' ], [ '%d' ] );
				} else {
					$args['itemid'] = intval( $forumid );
					$args['type']   = sanitize_text_field( $type );
					$this->add( $args );
				}
			}
		}

		if( $all ) {
			$sql = "SELECT `subid` FROM `" . WPF()->tables->subscribes . "` WHERE `type` IN('forums', 'forums-topics') AND `itemid` = 0 AND " . $where;
			if( $subid = WPF()->db->get_var( $sql ) ) {
				WPF()->db->update( WPF()->tables->subscribes, [ 'type' => sanitize_text_field( $all ), 'active' => $args['active'] ], [ 'subid' => $subid ], [ '%s', '%d' ], [ '%d' ] );
			} else {
				$args['itemid'] = 0;
				$args['type']   = sanitize_text_field( $all );
				$this->add( $args );
			}
		}

		return true;
	}

	function delete( $where = [], $table = '' ) {
		if( ! $where && $confirmkey = wpfval( $_REQUEST, 'confirmkey' ) ) $where = $confirmkey;
		if( ! $where ) {
			WPF()->notice->add( 'Invalid request!', 'error' );
			return false;
		}

		if( ! is_array( $where ) ) $where = [ 'confirmkey' => $where ];
		$where = (array) $where;

		$where = wpforo_array_ordered_intersect_key( $where, $this->default->subscribe_format );
		if( false !== WPF()->db->delete(
				$table ?: WPF()->tables->subscribes,
				$where,
				wpforo_array_ordered_intersect_key( $this->default->subscribe_format, $where )
			) ) {
			WPF()->notice->add( 'You have been successfully unsubscribed', 'success' );
			return true;
		}

		WPF()->notice->add( 'Could not be unsubscribe from this item', 'error' );
		return false;
	}

	public function delete_for_all_active_boards( $where = [] ) {
		foreach( WPF()->get_active_boards_tables( 'subscribes' ) as $table ) $this->delete( $where, $table );
	}

	function get_subscribe( $args = [] ) {
		if( is_string( $args ) ) $args = [ "confirmkey" => sanitize_text_field( $args ) ];
		if( empty( $args ) && ! empty( $_REQUEST['sbscrb'] ) ) $args = $_REQUEST['sbscrb'];
		if( empty( $args ) ) return [];
		extract( $args, EXTR_OVERWRITE );
		if( ( ! isset( $itemid ) || ! $itemid || ! ( ( isset( $userid ) && $userid ) || ( isset( $user_email ) && $user_email ) ) || ! isset( $type ) || ! $type ) && ( ! isset( $confirmkey ) || ! $confirmkey ) ) {
			return [];
		}
		if( isset( $confirmkey ) && $confirmkey ) {
			$where = " `confirmkey` = '" . esc_sql( sanitize_text_field( $confirmkey ) ) . "'";
		} elseif( isset( $itemid ) && $itemid && isset( $userid ) && $userid && isset( $type ) && $type ) {
			$where = " `itemid` = " . wpforo_bigintval( $itemid ) . " AND `userid` = " . wpforo_bigintval( $userid ) . " AND `type` = '" . esc_sql( sanitize_text_field( $type ) ) . "'";
		} elseif( isset( $itemid ) && $itemid && isset( $user_email ) && $user_email && isset( $type ) && $type ) {
			$where = " `itemid` = " . wpforo_bigintval( $itemid ) . " AND `user_email` = '" . esc_sql( $user_email ) . "' AND `type` = '" . esc_sql( sanitize_text_field( $type ) ) . "'";
		} else {
			return [];
		}
		$sql = "SELECT * FROM `" . WPF()->tables->subscribes . "` WHERE " . $where;
		if( WPF()->ram_cache->exists( $sql ) ) {
			$subscribe = WPF()->ram_cache->get( $sql );
		} else {
			$subscribe = (array) WPF()->db->get_row( $sql, ARRAY_A );
			WPF()->ram_cache->set( $sql, $subscribe );
		}

		return $subscribe;
	}

	function get_subscribes( $args = [], &$items_count = 0 ) {
		$default = [
			'itemid'    => null,
			'type'      => [],  // topic | forum
			'userid'    => null, //
			'active'    => 1,
			'orderby'   => 'subid', // order by `field`
			'order'     => 'DESC', // ASC DESC
			'offset'    => null, // OFFSET
			'row_count' => null, // ROW COUNT
		];

		$args = wpforo_parse_args( $args, $default );
		extract( $args );

		$sql    = "SELECT * FROM `" . WPF()->tables->subscribes . "`";
		$wheres = [];

		if( $type ) $wheres[] = " `type` IN( '" . implode( "','", array_map( 'esc_sql', (array) $type ) ) . "')";
		if( ! is_null( $active ) ) $wheres[] = " `active` = " . intval( $active );
		if( ! is_null( $itemid ) ) $wheres[] = " `itemid` = " . wpforo_bigintval( $itemid );
		if( ! is_null( $userid ) ) $wheres[] = " `userid` = " . wpforo_bigintval( $userid );

		if( ! empty( $wheres ) ) $sql .= " WHERE " . implode( " AND ", $wheres );

		$item_count_sql = preg_replace( '#SELECT.+?FROM#isu', 'SELECT count(*) FROM', $sql );
		$item_count_sql = preg_replace( '#ORDER.+$#is', '', $item_count_sql );
		if( $item_count_sql ) $items_count = WPF()->db->get_var( $item_count_sql );

		$sql .= " ORDER BY `$orderby` " . $order;

		if( ! is_null( $row_count ) ) {
			if( ! is_null( $offset ) ) {
				$sql .= esc_sql( " LIMIT $offset,$row_count" );
			} else {
				$sql .= esc_sql( " LIMIT $row_count" );
			}
		}

		return WPF()->db->get_results( $sql, ARRAY_A );

	}

	function get_confirm_link( $args ) {
		if( is_string( $args ) ) return wpforo_home_url( "?wpfaction=sbscrbconfirm&key=" . sanitize_text_field( $args ) );

		if( $args['type'] === 'forum' ) {
			$url = WPF()->forum->get_forum_url( $args['itemid'] ) . '/';
		} elseif( $args['type'] === 'topic' ) {
			$url = WPF()->topic->get_topic_url( $args['itemid'] ) . '/';
		} else {
			$url = wpforo_home_url();
		}

		return wpforo_home_url( $url . "?wpfaction=sbscrbconfirm&key=" . sanitize_text_field( $args['confirmkey'] ) );
	}

	function get_unsubscribe_link( $confirmkey ) {
		return wpforo_home_url( "?wpfaction=unsbscrb&key=" . sanitize_text_field( $confirmkey ) );
	}

	public function is_email_confirmed( $user = null ) {
		if( ! $user && ! WPF()->current_userid && ! WPF()->current_user_email ) return false;
		if( ! $user ) $user = ( WPF()->current_userid ? WPF()->current_userid : WPF()->current_user_email );

		$sql   = ( is_numeric( $user ) ? "`userid` = %d" : "`user_email` = %s" );
		$where = WPF()->db->prepare( $sql, $user );

		if( WPF()->current_userid === $user && wpfval( WPF()->current_user, 'is_email_confirmed' ) ) {
			$has_confirmed = WPF()->current_user['is_email_confirmed'];
		} elseif( is_numeric( $user ) ) {
			$has_confirmed = WPF()->member->get_is_email_confirmed( WPF()->current_userid );
		} else {
			$has_confirmed = WPF()->db->get_var( "SELECT `subid` FROM `" . WPF()->tables->subscribes . "` WHERE `active` = 1 AND " . $where );
		}

		return (bool) $has_confirmed;
	}

	public function get_sbj_msg( $type, $pitem, $item, $owner, $user, $unsubscribe_link ){
		$sbj = '';
		$msg = '';
		switch( $type ){
			case 'new_topic':
				$sbj = wpforo_setting( 'subscriptions', 'new_topic_notification_email_subject' );
				$msg = wpforo_setting( 'subscriptions', 'new_topic_notification_email_message' );
				if( (int) $item['status'] ) {
					$sbj  = __( 'Please Moderate: ', 'wpforo' ) . $sbj;
					$msg .= sprintf( '<br><br><p style="color: #DD0000;">%1$s</p>', __( 'This topic is currently unapproved. You can approve topics in Dashboard &raquo; Forums &raquo; Moderation admin page.', 'wpforo' ) );
				}
			break;
			case 'new_post':
				$sbj = wpforo_setting( 'subscriptions', 'new_post_notification_email_subject' );
				$msg = wpforo_setting( 'subscriptions', 'new_post_notification_email_message' );
				if( (int) $item['status'] ) {
					$sbj  = __( 'Please Moderate: ', 'wpforo' ) . $sbj;
					$msg .= sprintf( '<br><br><p style="color: #DD0000;">%1$s</p>', __( 'This post is currently unapproved. You can approve posts in Dashboard &raquo; Forums &raquo; Moderation admin page.', 'wpforo' ) );
				}
			break;
			case 'user_follow';
				$sbj = wpforo_setting( 'subscriptions', 'user_following_email_subject' );
				$msg = wpforo_setting( 'subscriptions', 'user_following_email_message' );
			break;
			case 'user_mention';
				$sbj = wpforo_setting( 'subscriptions', 'user_mention_email_subject' );
				$msg = wpforo_setting( 'subscriptions', 'user_mention_email_message' );
			break;
		}

		$sbj = wpforo_apply_email_shortcodes( $sbj, $pitem, $item, $owner, $user, $unsubscribe_link );
		$msg = wpforo_apply_email_shortcodes( $msg, $pitem, $item, $owner, $user, $unsubscribe_link );

		return compact( 'sbj', 'msg' );
	}
}
