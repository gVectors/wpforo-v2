<?php

namespace wpforo\modules\follows;

use stdClass;
use wpforo\modules\follows\classes\Template;
use wpforo\modules\follows\classes\Actions;

class Follows {
	/** @var stdClass */ public $default;
	/** @var Template */ public $Template;
	/** @var Actions  */ public $Actions;


	public function __construct() {
		$this->init_defaults();
		$this->init_classes();
		$this->init_hooks();
	}

	private function init_defaults() {
		$this->default = new stdClass();
		$this->default->follow = [
			'followid'      => 0,
			'itemid'        => 0,
			'itemtype'      => '',
			'confirmkey'    => '',
			'userid'        => 0,
			'active'        => 0,
			'name'          => '',
			'email'         => '',
			'confirm_link'  => '',
			'unfollow_link' => '',
		];
		$this->default->follow_format = [
			'followid'   => '%d',
			'itemid'     => '%d',
			'itemtype'   => '%s',
			'confirmkey' => '%s',
			'userid'     => '%d',
			'active'     => '%d',
			'name'       => '%s',
			'email'      => '%s',
		];
		$this->default->sql_select_args = [
			'followid'         => null,
			'itemid'           => null,
			'itemtype'         => null,
			'itemtype_include' => [],
			'itemtype_exclude' => [],
			'confirmkey'       => null,
			'userid'           => null,
			'active'           => null,
			'name'             => null,
			'email'            => null,
			'orderby'          => null,
			'offset'           => null,
			'row_count'        => null,
		];
	}

	private function init_classes() {
		$this->Template = new Template();
		$this->Actions  = new Actions();
	}

	private function init_hooks() {
		add_filter( 'wpforo_init_member_templates',     [ $this, 'add_templates' ] );
		add_filter( 'wpforo_after_init_current_object', [ $this, 'init_current_object' ] );
	}

	public function add_templates( $templates ) {
		$templates['followers'] = [
			'type'                  => 'callback',
			'key'                   => 'followers',
			'menu_shortcode'        => 'wpforo-profile-followers',
			'ico'                   => '<i class="fas fa-rss"></i>',
			'title'                 => 'Followers',
			'is_default'            => 1,
			'can'                   => 'vprs',
			'add_in_member_menu'    => 0,
			'add_in_member_buttons' => 0,
			'callback_for_page'     => function(){
				require_once wpftpl( 'profile-followers.php' );
			},
		];
		$templates['following'] = [
			'type'                  => 'callback',
			'key'                   => 'following',
			'menu_shortcode'        => 'wpforo-profile-following',
			'ico'                   => '<i class="fas fa-rss"></i>',
			'title'                 => 'Following',
			'is_default'            => 1,
			'can'                   => 'vprs',
			'add_in_member_menu'    => 0,
			'add_in_member_buttons' => 0,
			'callback_for_page'     => function(){
				require_once wpftpl( 'profile-following.php' );
			},
		];

		return $templates;
	}

	public function init_current_object( $current_object ) {
		if( !$current_object['is_404'] ){
			if( $current_object['template'] === 'followers' ){
				$args                          = [
					'offset'    => ( $current_object['paged'] - 1 ) * $current_object['items_per_page'],
					'row_count' => $current_object['items_per_page'],
					'itemid'    => $current_object['userid'],
					'itemtype'  => 'user',
					'order'     => 'DESC',
				];
				$current_object['items_count'] = 0;
				$current_object['follows']  = $this->get_follows( $args, $current_object['items_count'] );
			}elseif( $current_object['template'] === 'following' ){
				$args                          = [
					'offset'    => ( $current_object['paged'] - 1 ) * $current_object['items_per_page'],
					'row_count' => $current_object['items_per_page'],
					'userid'    => $current_object['userid'],
					'itemtype'  => 'user',
					'order'     => 'DESC',
				];
				$current_object['items_count'] = 0;
				$current_object['follows']  = $this->get_follows( $args, $current_object['items_count'] );
			}
		}

		return $current_object;
	}

	private function get_confirm_key() {
		return substr( md5( rand() . time() ), 0, 32 );
	}

	/**
	 * @param $follow
	 *
	 * @return array
	 */
	public function decode( $follow ) {
		$follow             = array_merge( $this->default->follow, (array) $follow );
		$follow['followid'] = wpforo_bigintval( $follow['followid'] );
		$follow['itemid']   = wpforo_bigintval( $follow['itemid'] );
		$follow['userid']   = wpforo_bigintval( $follow['userid'] );
		$follow['active']   = (bool) intval( $follow['active'] );
		$follow['name']     = trim( strip_tags( $follow['name'] ) );
		$follow['email']    = sanitize_email( $follow['email'] );
		if( ! ( $follow['itemtype'] = trim( strip_tags( $follow['itemtype'] ) ) ) ) $follow['itemtype'] = 'user';
		if( ! ( $follow['confirmkey'] = trim( $follow['confirmkey'] ) ) ) $follow['confirmkey'] = $this->get_confirm_key();
		$follow['confirm_link']  = $this->get_confirm_link( $follow['confirmkey'] );
		$follow['unfollow_link'] = $this->get_unfollow_link( $follow['confirmkey'] );

		return $follow;
	}

	/**
	 * @param $follow
	 *
	 * @return array
	 */
	private function encode( $follow ) {
		$follow           = $this->decode( $follow );
		$follow['active'] = intval( $follow['active'] );

		return $follow;
	}

	/**
	 * @param $follow
	 *
	 * @return false|int
	 */
	public function add( $follow ) {
		$follow = $this->encode( $follow );
		unset( $follow['followid'] );
		$follow = wpforo_array_ordered_intersect_key( $follow, $this->default->follow_format );
		if( WPF()->db->insert(
			WPF()->tables->follows,
			$follow,
			wpforo_array_ordered_intersect_key( $this->default->follow_format, $follow )
		) ) {
			$follow['followid'] = WPF()->db->insert_id;
			do_action( 'wpforo_after_add_follow', $follow );

			return $follow['followid'];
		}

		return false;
	}

	/**
	 * @param array $fields
	 * @param array|int|string $where
	 *
	 * @return bool
	 */
	public function edit( $fields, $where ) {
		if( is_numeric( $where ) ){
			$where = [ 'followid' => wpforo_bigintval( $where ) ];
		}elseif( is_string( $where ) ){
			$where = [ 'confirmkey' => $where ];
		}
		$fields     = wpforo_array_ordered_intersect_key( $fields, $this->default->follow_format );
		if( false !== WPF()->db->update(
				WPF()->tables->follows,
				$fields = wpforo_array_ordered_intersect_key( $this->encode( $fields ), $fields ),
				$where  = wpforo_array_ordered_intersect_key( $where, $this->default->follow_format ),
				wpforo_array_ordered_intersect_key( $this->default->follow_format, $fields ),
				wpforo_array_ordered_intersect_key( $this->default->follow_format, $where )
			) ) {
			do_action( 'wpforo_after_edit_follow', $fields, $where );

			return true;
		}

		return false;
	}

	/**
	 * @param array|int|string $args
	 * @param string    $operator
	 *
	 * @return bool
	 */
	public function delete( $args, $operator = 'AND' ) {
		if( is_numeric( $args ) ) {
			$args = [ 'followid' => wpforo_bigintval( $args ) ];
		}elseif( is_string( $args ) ){
			$args = [ 'confirmkey' => $args ];
		}
		$operator = trim( strtoupper( $operator ) );
		if( ! in_array( $operator, [ 'AND', 'OR' ], true ) ) $operator = 'AND';

		do_action( 'wpforo_before_delete_follow', $args, $operator );

		$sql = "DELETE FROM " . WPF()->tables->follows;
		if( $wheres = $this->build_sql_wheres( $args ) ) $sql .= " WHERE " . implode( " $operator ", $wheres );

		$args = $this->parse_args( $args );
		if( $args['orderby'] ) $sql .= " ORDER BY " . $args['orderby'];
		if( $args['row_count'] ) $sql .= " LIMIT " . intval( $args['row_count'] );

		$r = WPF()->db->query( $sql );

		do_action( 'wpforo_after_delete_follow', $args, $operator );

		return false !== $r;
	}

	private function parse_args( $args ) {
		$args                     = wpforo_parse_args( $args, $this->default->sql_select_args );
		$args                     = wpforo_array_ordered_intersect_key( $args, $this->default->sql_select_args );
		$args['itemtype_include'] = wpforo_parse_args( $args['itemtype_include'] );
		$args['itemtype_exclude'] = wpforo_parse_args( $args['itemtype_exclude'] );
		$args['orderby']          = sanitize_sql_orderby( $args['orderby'] );

		return $args;
	}

	private function build_sql_wheres( $args ) {
		$args   = $this->parse_args( $args );
		$wheres = [];

		if( ! is_null( $args['followid'] ) )       $wheres[] = "`followid` = '" . wpforo_bigintval( $args['followid'] ) . "'";
		if( ! is_null( $args['itemid'] ) )         $wheres[] = "`itemid` = '" . wpforo_bigintval( $args['itemid'] ) . "'";
		if( ! is_null( $args['itemtype'] ) )       $wheres[] = "`itemtype` = '" . esc_sql( $args['itemtype'] ) . "'";
		if( ! is_null( $args['confirmkey'] ) )     $wheres[] = "`confirmkey` = '" . esc_sql( $args['confirmkey'] ) . "'";
		if( ! is_null( $args['userid'] ) )         $wheres[] = "`userid` = '" . wpforo_bigintval( $args['userid'] ) . "'";
		if( ! is_null( $args['active'] ) )         $wheres[] = "`active` = '" . intval( $args['active'] ) . "'";
		if( ! is_null( $args['name'] ) )           $wheres[] = "`name` = '" . esc_sql( $args['name'] ) . "'";
		if( ! is_null( $args['email'] ) )          $wheres[] = "`email` = '" . esc_sql( $args['email'] ) . "'";
		if( ! empty( $args['itemtype_include'] ) ) $wheres[] = "`itemtype` IN('" . implode( "','", array_map( 'trim', $args['itemtype_include'] ) ) . "')";
		if( ! empty( $args['itemtype_exclude'] ) ) $wheres[] = "`itemtype` NOT IN(" . implode( "','", array_map( 'trim', $args['itemtype_exclude'] ) ) . "')";

		return $wheres;
	}

	/**
	 * @param $args
	 * @param $select
	 * @param $operator
	 *
	 * @return string
	 */
	private function build_sql_select( $args, $select = '', $operator = 'AND' ) {
		if( ! $select ) $select = '*';
		$operator = trim( strtoupper( $operator ) );
		if( ! in_array( $operator, [ 'AND', 'OR' ], true ) ) $operator = 'AND';

		$sql = "SELECT $select FROM " . WPF()->tables->follows;
		if( $wheres = $this->build_sql_wheres( $args ) ) $sql .= " WHERE " . implode( " $operator ", $wheres );

		$args = $this->parse_args( $args );
		if( $args['orderby'] )   $sql .= " ORDER BY " . $args['orderby'];
		if( $args['row_count'] ) $sql .= " LIMIT " . intval( $args['offset'] ) . "," . intval( $args['row_count'] );

		return $sql;
	}

	/**
	 * @param array|numeric $args
	 *
	 * @return array
	 */
	public function _get_follow( $args, $operator = 'AND' ) {
		if( is_numeric( $args ) ) $args = [ 'followid' => wpforo_bigintval( $args ) ];
		if( ! wpfkey( $args, 'orderby' ) ) $args['orderby'] = '`followid` DESC';
		$follow = (array) WPF()->db->get_row( $this->build_sql_select( $args, '', $operator ), ARRAY_A );
		if( $follow ) $follow = $this->decode( $follow );

		return $follow;
	}

	public function get_follow( $args, $operator = 'AND' ) {
		return wpforo_ram_get( [ $this, '_get_follow' ], $args, $operator );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function _get_follows( $args = [], $operator = 'AND' ) {
		return array_map( [ $this, 'decode' ], (array) WPF()->db->get_results( $this->build_sql_select( $args, '', $operator ), ARRAY_A ) );
	}

	public function get_follows( $args = [], $operator = 'AND' ) {
		return wpforo_ram_get( [ $this, '_get_follows' ], $args, $operator );
	}

	public function _get_follows_col( $col, $args = [], $operator = 'AND' ){
		$r = WPF()->db->get_col( $this->build_sql_select( $args, "`$col`", $operator ) );
		if( $this->default->follow_format[$col] === '%d' ) $r = array_map( 'wpforo_bigintval', $r );
		return $r;
	}

	public function get_follows_col( $col, $args = [], $operator = 'AND' ){
		return wpforo_ram_get( [ $this, '_get_follows_col' ], $col, $args, $operator );
	}

	/**
	 * @param array $args
	 *
	 * @return int
	 */
	public function _get_count( $args = [], $operator = 'AND' ) {
		return (int) WPF()->db->get_var( $this->build_sql_select( $args, 'COUNT(*)', $operator ) );
	}

	public function get_count( $args = [], $operator = 'AND' ) {
		return wpforo_ram_get( [$this, '_get_count'], $args, $operator );
	}

	public function user_is_following_this( $userid, $itemid, $itemtype = 'user' ) {
		return (bool) $this->get_follow( [ 'userid' => $userid, 'itemid' => $itemid, 'itemtype' => $itemtype, 'active' => true ] );
	}

	private function get_confirm_link( $confirmkey ) {
		return wpforo_url( "?wpfaction=followconfirm&key=$confirmkey", 'member' );
	}

	private function get_unfollow_link( $confirmkey ) {
		return wpforo_url( "?wpfaction=unfollow&key=$confirmkey", 'member' );
	}

	/**
	 * @param int    $userid
	 * @param int    $itemid
	 * @param string $itemtype
	 *
	 * @return bool|int
	 */
	public function follow( $userid, $itemid, $itemtype = 'user' ) {
		if( ! $this->user_is_following_this( $userid, $itemid, $itemtype ) ){
			return $this->add( [
               'itemid'   => $itemid,
               'itemtype' => $itemtype,
               'userid'   => $userid,
               'active'   => true,
            ] );
		}

		return true;
	}

	/**
	 * @param int    $userid
	 * @param int    $itemid
	 * @param string $itemtype
	 *
	 * @return bool
	 */
	public function unfollow( $userid, $itemid, $itemtype = 'user' ) {
		return $this->delete( ['userid' => $userid, 'itemid' => $itemid, 'itemtype' => $itemtype] );
	}

	/**
	 * @param string $confirmkey
	 *
	 * @return bool
	 */
	public function unfollow_by_key( $confirmkey ) {
		return $this->delete( $confirmkey );
	}

	public function confirm( $confirmkey ) {
		return $this->edit( [ 'active' => true ], $confirmkey );
	}
}
