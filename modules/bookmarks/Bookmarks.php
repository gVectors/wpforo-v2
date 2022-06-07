<?php

namespace wpforo\modules\bookmarks;

use stdClass;
use wpforo\modules\bookmarks\classes\Template;
use wpforo\modules\bookmarks\classes\Actions;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Bookmarks {
	public $default;
	/* @var Template */ public $Template;
	/* @var Actions  */ public $Actions;

	public function __construct() {
		$this->init_defaults();
		$this->init_classes();
	}

	private function init_classes() {
		$this->Template = new Template();
		$this->Actions  = new Actions();
	}

	private function init_defaults() {
		$this->default                  = new stdClass();
		$this->default->bookmark        = [
			'bookmarkid' => 0,
			'userid'     => 0,
			'boardid'    => 0,
			'postid'     => 0,
			'created'    => current_time( 'timestamp', 1 ),
			'status'     => 1,
		];
		$this->default->bookmark_format = [
			'bookmarkid' => '%d',
			'userid'     => '%d',
			'boardid'    => '%d',
			'postid'     => '%d',
			'created'    => '%d',
			'status'     => '%d',
		];
		$this->default->sql_select_args = [
			'bookmarkid'      => null,
			'userid'          => null,
			'userid_include'  => [],
			'userid_exclude'  => [],
			'boardid'         => null,
			'boardid_include' => [],
			'boardid_exclude' => [],
			'postid'          => null,
			'postid_include'  => [],
			'postid_exclude'  => [],
			'status'          => null,
			'orderby'         => null,
			'offset'          => null,
			'row_count'       => null,
		];
	}

	/**
	 * @param $bookmark
	 *
	 * @return array
	 */
	public function decode( $bookmark ) {
		$bookmark               = array_merge( $this->default->bookmark, (array) $bookmark );
		$bookmark['bookmarkid'] = wpforo_bigintval( $bookmark['bookmarkid'] );
		$bookmark['userid']     = wpforo_bigintval( $bookmark['userid'] );
		$bookmark['boardid']    = intval( $bookmark['boardid'] );
		$bookmark['postid']     = wpforo_bigintval( $bookmark['postid'] );
		$bookmark['created']    = intval( $bookmark['created'] );
		$bookmark['status']     = (bool) intval( $bookmark['status'] );

		return $bookmark;
	}

	/**
	 * @param $bookmark
	 *
	 * @return array
	 */
	private function encode( $bookmark ) {
		$bookmark = $this->decode( $bookmark );
		$bookmark['status'] = intval( $bookmark['status'] );
		return $bookmark;
	}

	/**
	 * @param $bookmark
	 *
	 * @return false|int
	 */
	public function add( $bookmark ) {
		$bookmark = $this->encode( $bookmark );
		unset( $bookmark['bookmarkid'] );
		$bookmark = wpforo_array_ordered_intersect_key( $bookmark, $this->default->bookmark_format );
		if( WPF()->db->insert(
			WPF()->tables->bookmarks,
			$bookmark,
			wpforo_array_ordered_intersect_key( $this->default->bookmark_format, $bookmark )
		) ) {
			$bookmark['bookmarkid'] = WPF()->db->insert_id;
			do_action( 'wpforo_after_add_bookmark', $bookmark );

			return $bookmark['bookmarkid'];
		}

		return false;
	}

	/**
	 * @param array $fields
	 * @param array|int $where
	 *
	 * @return bool
	 */
	public function edit( $fields, $where ) {
		if( is_numeric( $where ) ) $where = [ 'bookmarkid' => wpforo_bigintval( $where ) ];
		$fields     = wpforo_array_ordered_intersect_key( $fields, $this->default->bookmark_format );
		if( false !== WPF()->db->update(
			WPF()->tables->bookmarks,
			$fields = wpforo_array_ordered_intersect_key( $this->encode( $fields ), $fields ),
			$where  = wpforo_array_ordered_intersect_key( $where, $this->default->bookmark_format ),
			wpforo_array_ordered_intersect_key( $this->default->bookmark_format, $fields ),
			wpforo_array_ordered_intersect_key( $this->default->bookmark_format, $where )
		) ) {
			do_action( 'wpforo_after_edit_bookmark', $fields, $where );

			return true;
		}

		return false;
	}

	/**
	 * @param array|int $args
	 * @param string    $operator
	 *
	 * @return bool
	 */
	public function delete( $args, $operator = 'AND' ) {
		if( is_numeric( $args ) ) $args = [ 'bookmarkid' => wpforo_bigintval( $args ) ];
		$operator = trim( strtoupper( $operator ) );
		if( ! in_array( $operator, [ 'AND', 'OR' ], true ) ) $operator = 'AND';

		do_action( 'wpforo_before_delete_bookmark', $args, $operator );

		$sql = "DELETE FROM " . WPF()->tables->bookmarks;
		if( $wheres = $this->build_sql_wheres( $args ) ) $sql .= " WHERE " . implode( " $operator ", $wheres );

		$args = $this->parse_args( $args );
		if( $args['orderby'] )   $sql .= " ORDER BY " . $args['orderby'];
		if( $args['row_count'] ) $sql .= " LIMIT " . intval( $args['row_count'] );

		$r = WPF()->db->query( $sql );

		do_action( 'wpforo_after_delete_bookmark', $args, $operator );

		return false !== $r;
	}

	private function parse_args( $args ) {
		$args                    = wpforo_parse_args( $args, $this->default->sql_select_args );
		$args                    = wpforo_array_ordered_intersect_key( $args, $this->default->sql_select_args );
		$args['userid_include']  = wpforo_parse_args( $args['userid_include'] );
		$args['userid_exclude']  = wpforo_parse_args( $args['userid_exclude'] );
		$args['boardid_include'] = wpforo_parse_args( $args['boardid_include'] );
		$args['boardid_exclude'] = wpforo_parse_args( $args['boardid_exclude'] );
		$args['postid_include']  = wpforo_parse_args( $args['postid_include'] );
		$args['postid_exclude']  = wpforo_parse_args( $args['postid_exclude'] );
		$args['orderby']         = sanitize_sql_orderby( $args['orderby'] );

		return $args;
	}

	private function build_sql_wheres( $args ) {
		$args   = $this->parse_args( $args );
		$wheres = [];

		if( ! is_null( $args['bookmarkid'] ) ) $wheres[] = "`bookmarkid` = '" . wpforo_bigintval( $args['bookmarkid'] ) . "'";
		if( ! is_null( $args['userid'] ) )     $wheres[] = "`userid` = '"     . wpforo_bigintval( $args['userid'] ) . "'";
		if( ! is_null( $args['boardid'] ) )    $wheres[] = "`boardid` = '"    . intval( $args['boardid'] ) . "'";
		if( ! is_null( $args['postid'] ) )     $wheres[] = "`postid` = '"     . wpforo_bigintval( $args['postid'] ) . "'";
		if( ! is_null( $args['status'] ) )     $wheres[] = "`status` = '"     . intval( $args['status'] ) . "'";

		if( ! empty( $args['userid_include'] ) ) $wheres[] = "`userid`     IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['userid_include'] ) ) . ")";
		if( ! empty( $args['userid_exclude'] ) ) $wheres[] = "`userid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['userid_exclude'] ) ) . ")";

		if( ! empty( $args['boardid_include'] ) ) $wheres[] = "`boardid`     IN(" . implode( ',', array_map( 'intval', $args['boardid_include'] ) ) . ")";
		if( ! empty( $args['boardid_exclude'] ) ) $wheres[] = "`boardid` NOT IN(" . implode( ',', array_map( 'intval', $args['boardid_exclude'] ) ) . ")";

		if( ! empty( $args['postid_include'] ) ) $wheres[] = "`postid`     IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postid_include'] ) ) . ")";
		if( ! empty( $args['postid_exclude'] ) ) $wheres[] = "`postid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postid_exclude'] ) ) . ")";

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

		$sql = "SELECT $select FROM " . WPF()->tables->bookmarks;
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
	public function _get_bookmark( $args, $operator = 'AND' ) {
		if( is_numeric( $args ) ) $args = [ 'bookmarkid' => wpforo_bigintval( $args ) ];
		if( ! wpfkey( $args, 'orderby' ) ) $args['orderby'] = '`bookmarkid` DESC';
		$bookmark = (array) WPF()->db->get_row( $this->build_sql_select( $args, '*', $operator ), ARRAY_A );
		if( $bookmark ) $bookmark = $this->decode( $bookmark );

		return $bookmark;
	}

	public function get_bookmark( $args, $operator = 'AND' ) {
		return wpforo_ram_get( [ $this, '_get_bookmark' ], $args, $operator );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function _get_bookmarks( $args = [], $operator = 'AND' ) {
		return array_map( [ $this, 'decode' ], (array) WPF()->db->get_results( $this->build_sql_select( $args, '*', $operator ), ARRAY_A ) );
	}

	public function get_bookmarks( $args = [], $operator = 'AND' ) {
		return wpforo_ram_get( [ $this, '_get_bookmarks' ], $args, $operator );
	}

	public function _get_bookmarks_col( $col, $args = [], $operator = 'AND' ){
		$r = WPF()->db->get_col( $this->build_sql_select( $args, "`$col`", $operator ) );
		if( $this->default->bookmark_format[$col] === '%d' ) $r = array_map( 'wpforo_bigintval', $r );
		return $r;
	}

	public function get_bookmarks_col( $col, $args = [], $operator = 'AND' ){
		return wpforo_ram_get( [ $this, '_get_bookmarks_col' ], $col, $args, $operator );
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

	public function get_user_bookmarks_count( $userid ){
		return $this->get_count( [ 'userid' => $userid, 'status' => true ] );
	}

	public function get_board_post_bookmarks_count( $boardid, $postid ) {
		return $this->get_count( [ 'boardid' => $boardid, 'postid' => $postid, 'status' => true ] );
	}

	/**
	 * @param int $postid
	 * @param int $userid
	 * @param int $boardid
	 *
	 * @return array|null
	 */
	public function get_user_bookmark( $postid, $userid = null, $boardid = null ){
		if( ! ( $userid = wpforo_bigintval( $userid ) ) ) {
			$userid = WPF()->current_userid;
		}
		if( is_null( $boardid ) ) $boardid = WPF()->board->get_current( 'boardid' );
		$boardid = intval( $boardid );

		if( $bookmark = $this->get_bookmark( [ 'userid' => $userid, 'boardid' => $boardid, 'postid' => $postid ] ) ) return $bookmark;
		return null;
	}

	public function is_user_bookmarked_this( $postid, $userid = null, $boardid = null ) {
		$bookmark = $this->get_user_bookmark( $postid, $userid, $boardid );
		return (bool) wpfval( $bookmark, 'status' );
	}
}
