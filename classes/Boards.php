<?php

namespace wpforo\classes;

use stdClass;
use wpforo\admin\listtables\Boards as BoardsListTable;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Boards {
	public  $default;
	private $board;
	public  $list_table;
	public  $route       = 'community';
	public  $full_route  = 'community';
	public  $base_routes = [];
	public  $routes      = [];
	public  $is_inited   = false;

	public function __construct() {
		$this->init_defaults();
		$this->reset_current();
		$this->init_routes();
		$this->init_hooks();
		if( is_admin() ) add_action( 'wpforo_after_init', [ $this, 'init_list_table' ] );
	}

	public function init_hooks() {
		add_action('wpforo_after_add_board', function( $board ) {
			$this->after_save_board( $board['boardid'] );
		});
		add_action('wpforo_after_edit_board', function( $boardid ) {
			$this->after_save_board( $boardid );
		});
		add_filter('wpforo_settings_init_core_info', function( $settings_info ){
			return array_filter($settings_info, function( $v, $k ){
				return $v['base'] || $this->is_module_enabled( $k );
			}, ARRAY_FILTER_USE_BOTH );
		});
		add_filter('wpforo_settings_init_addons_info', function( $settings_info ){
			return array_filter($settings_info, function( $v, $k ){
				return $v['base'] || $this->is_module_enabled( $k );
			}, ARRAY_FILTER_USE_BOTH );
		});
	}

	public function init_list_table() {
		if( wpfval( $_GET, 'page' ) === 'wpforo-boards' ) {
			$this->list_table = new BoardsListTable();
			$this->list_table->prepare_items();
		}
	}

	private function init_defaults() {
		$this->default                  = new stdClass();
		$this->default->board           = [
			'boardid'       => 0,
			'title'         => 'Forums',
			'slug'          => 'community',
			'pageid'        => 0,
			'modules'       => [],
			'locale'        => '',
			'is_standalone' => false,
			'excld_urls'    => [],
			'status'        => true,
			'settings'      => [
				'title' => 'Forum',
				'desc'  => 'Discussion Board',
			],
		];
		$this->default->board_format    = [
			'boardid'       => '%d',
			'title'         => '%s',
			'slug'          => '%s',
			'pageid'        => '%d',
			'modules'       => '%s',
			'locale'        => '%s',
			'is_standalone' => '%d',
			'excld_urls'    => '%s',
			'status'        => '%d',
			'settings'      => '%s',
		];
		$this->default->sql_select_args = [
			'title_like'      => null,
			'title_notlike'   => null,
			'locale_like'     => null,
			'locale_notlike'  => null,
			'locale_empty'    => null,
			'is_standalone'   => null,
			'status'          => null,
			'slug_include'    => [],
			'slug_exclude'    => [],
			'boardid_include' => [],
			'boardid_exclude' => [],
			'pageid_include'  => [],
			'pageid_exclude'  => [],
			'orderby'         => null,
			'offset'          => null,
			'row_count'       => null,
		];
	}

	/**
	 * @param $board
	 *
	 * @return array
	 */
	public function decode( $board ) {
		$board                  = array_merge( $this->default->board, (array) $board );
		$board['boardid']       = intval( $board['boardid'] );
		$board['pageid']        = wpforo_bigintval( $board['pageid'] );
		$board['title']         = trim( strip_tags( $board['title'] ) );
		$board['is_standalone'] = (bool) intval( $board['is_standalone'] );
		$board['status']        = (bool) intval( $board['status'] );
		$board['slug']          = sanitize_title( $board['slug'], 'community' );
		$board['locale']        = trim( strip_tags( $board['locale'] ) );
		$board['excld_urls']    = array_values(
			array_unique(
				array_filter(
					(array) ( wpforo_is_json( $board['excld_urls'] ) ? json_decode( $board['excld_urls'], true ) : ( is_scalar( $board['excld_urls'] ) ? array_map( 'trim', wpforo_string2array( sanitize_textarea_field( $board['excld_urls'] ) ) ) : $board['excld_urls'] ) )
				)
			)
		);

		// -- START -- decode board settings
		$board['settings']          = (array) ( wpforo_is_json( $board['settings'] ) ? json_decode( $board['settings'], true ) : ( is_scalar( $board['settings'] ) ? array_map( 'trim', wpforo_string2array( sanitize_textarea_field( $board['settings'] ) ) ) : $board['settings'] ) );
		$board['settings']          = wpforo_array_args_cast_and_merge( $board['settings'], $this->default->board['settings'] );
		$board['settings']['title'] = trim( strip_tags( $board['settings']['title'] ) );
		$board['settings']['desc']  = trim( strip_tags( $board['settings']['desc'] ) );
		// -- END -- decode board settings

		if( ! $board['locale'] ) $board['locale'] = WPF()->locale;
		if( ! $board['pageid'] ) $board['pageid'] = wpforo_get_option( 'wpforo_pageid', 0 );

		$all_modules      = array_map( '__return_true', wpforo_get_modules_info() );
		$all_addons       = array_map( '__return_true', wpforo_get_addons_info() );
		$modules          = (array) ( ( wpforo_is_json( $board['modules'] ) ) ? json_decode( $board['modules'], true ) : $board['modules'] );
		$modules          = array_merge( $all_modules, $all_addons, $modules );
		$board['modules'] = array_map( function( $a ) { return (bool) intval( $a ); }, $modules );

		return $board;
	}

	/**
	 * @param $board
	 *
	 * @return array
	 */
	private function encode( $board ) {
		$board                  = $this->decode( $board );
		$board['modules']       = json_encode( $board['modules'] );
		$board['excld_urls']    = json_encode( $board['excld_urls'] );
		$board['settings']      = json_encode( $board['settings'] );
		$board['is_standalone'] = intval( $board['is_standalone'] );
		$board['status']        = intval( $board['status'] );

		return $board;
	}

	/**
	 * @param $board
	 *
	 * @return false|int
	 */
	public function add( $board ) {
		$board = $this->encode( $board );
		unset( $board['boardid'] );
		$board = wpforo_array_ordered_intersect_key( $board, $this->default->board_format );
		if( WPF()->db->insert(
			WPF()->tables->boards,
			$board,
			wpforo_array_ordered_intersect_key( $this->default->board_format, $board )
		) ) {
			$board['boardid'] = WPF()->db->insert_id;
			do_action( 'wpforo_after_add_board', $board );

			return $board['boardid'];
		}

		return false;
	}

	/**
	 * @param $fields
	 * @param $boardid
	 *
	 * @return bool
	 */
	public function edit( $fields, $boardid ) {
		$boardid = intval( $boardid );
		$fields  = wpforo_array_ordered_intersect_key( $fields, $this->default->board_format );
		if( false !== WPF()->db->update( WPF()->tables->boards, $data = wpforo_array_ordered_intersect_key( $this->encode( $fields ), $fields ), [ 'boardid' => $boardid ], wpforo_array_ordered_intersect_key( $this->default->board_format, $fields ), [ '%d' ] ) ) {
			do_action( 'wpforo_after_edit_board', $boardid, $data );

			return true;
		}

		return false;
	}

	/**
	 * @param $boardid
	 *
	 * @return bool
	 */
	public function delete( $boardid ) {
		if( ! $boardid = intval( $boardid ) ) return false;

		do_action( 'wpforo_before_delete_board', $boardid );

		if( false !== WPF()->db->delete( WPF()->tables->boards, [ 'boardid' => $boardid ], [ '%d' ] ) ) {
			do_action( 'wpforo_after_delete_board', $boardid );

			return true;
		}

		return false;
	}

	private function after_save_board( $boardid ) {
		if( $board = $this->_get_board( (int) $boardid ) ) {
			if( $board['status'] && $board['is_standalone'] ) {
				$sql = "UPDATE `" . WPF()->tables->boards . "` SET `is_standalone` = 0 WHERE `is_standalone` = 1 AND `boardid` <> %d";
				WPF()->db->query( WPF()->db->prepare( $sql, $board['boardid'] ) );
			}
		}
	}

	private function parse_args( $args ) {
		$args                    = wpforo_parse_args( $args, $this->default->sql_select_args );
		$args                    = wpforo_array_ordered_intersect_key( $args, $this->default->sql_select_args );
		$args['slug_include']    = wpforo_parse_args( $args['slug_include'] );
		$args['slug_exclude']    = wpforo_parse_args( $args['slug_exclude'] );
		$args['boardid_include'] = wpforo_parse_args( $args['boardid_include'] );
		$args['boardid_exclude'] = wpforo_parse_args( $args['boardid_exclude'] );
		$args['pageid_include']  = wpforo_parse_args( $args['pageid_include'] );
		$args['pageid_exclude']  = wpforo_parse_args( $args['pageid_exclude'] );

		return $args;
	}

	private function build_sql_select( $args, $select = '' ) {
		$args = $this->parse_args( $args );
		if( ! $select ) $select = '*';

		$wheres = [];

		if( ! is_null( $args['title_like'] ) ) $wheres[] = "`title` LIKE '%" . WPF()->db->esc_like( $args['title_like'] ) . "%'";
		if( ! is_null( $args['title_notlike'] ) ) $wheres[] = "`title` NOT LIKE '%" . WPF()->db->esc_like( $args['title_notlike'] ) . "%'";

		if( ! is_null( $args['locale_like'] ) ) {
			$locale_like = "`locale` LIKE '%" . WPF()->db->esc_like( $args['locale_like'] ) . "%'";
			if( $args['locale_empty'] ) {
				$locale_like = "( `locale` = '' OR `locale` IS NULL OR " . $locale_like . " )";
			}
			$wheres[] = $locale_like;
		}
		if( ! is_null( $args['locale_notlike'] ) ) {
			$locale_notlike = "`locale` NOT LIKE '%" . WPF()->db->esc_like( $args['locale_notlike'] ) . "%'";
			if( ! is_null( $args['locale_empty'] ) ) {
				if( $args['locale_empty'] ) {
					$locale_notlike = "( `locale` = '' OR `locale` IS NULL OR " . $locale_notlike . " )";
				} else {
					$locale_notlike = "( `locale` <> '' AND `locale` IS NOT NULL AND " . $locale_notlike . " )";
				}
			}
			$wheres[] = $locale_notlike;
		}
		if( ! is_null( $args['locale_empty'] ) && is_null( $args['locale_like'] ) && is_null( $args['locale_notlike'] ) ) {
			if( $args['locale_empty'] ) {
				$wheres[] = "( `locale` = '' OR `locale` IS NULL )";
			} else {
				$wheres[] = "( `locale` <> '' AND `locale` IS NOT NULL )";
			}
		}

		if( ! is_null( $args['status'] ) ) $wheres[] = "`status` = '" . intval( $args['status'] ) . "'";
		if( ! is_null( $args['is_standalone'] ) ) $wheres[] = "`is_standalone` = '" . intval( $args['is_standalone'] ) . "'";

		if( ! empty( $args['slug_include'] ) ) $wheres[] = "`slug` IN('" . implode( "','", array_map( 'trim', $args['slug_include'] ) ) . "')";
		if( ! empty( $args['slug_exclude'] ) ) $wheres[] = "`slug` NOT IN(" . implode( "','", array_map( 'trim', $args['slug_exclude'] ) ) . "')";

		if( ! empty( $args['boardid_include'] ) ) $wheres[] = "`boardid` IN(" . implode( ',', array_map( 'intval', $args['boardid_include'] ) ) . ")";
		if( ! empty( $args['boardid_exclude'] ) ) $wheres[] = "`boardid` NOT IN(" . implode( ',', array_map( 'intval', $args['boardid_exclude'] ) ) . ")";

		if( ! empty( $args['pageid_include'] ) ) $wheres[] = "`pageid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['pageid_include'] ) ) . ")";
		if( ! empty( $args['pageid_exclude'] ) ) $wheres[] = "`pageid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['pageid_exclude'] ) ) . ")";

		$wheres = array_filter( $wheres );

		$sql = "SELECT $select FROM " . WPF()->tables->boards;
		if( $wheres ) $sql .= " WHERE " . implode( " AND ", $wheres );
		if( $args['orderby'] ) $sql .= " ORDER BY " . $args['orderby'];
		if( $args['row_count'] ) $sql .= " LIMIT " . intval( $args['offset'] ) . "," . intval( $args['row_count'] );

		return $sql;
	}

	/**
	 * @param array|numeric $args
	 *
	 * @return array
	 */
	public function _get_board( $args ) {
		if( is_numeric( $args ) ) $args = [ 'boardid_include' => (int) $args ];
		if( ! wpfkey( $args, 'orderby' ) ) $args['orderby'] = '`boardid` DESC';
		if( ! wpfkey( $args, 'row_count' ) ) {
			$args['offset']    = 0;
			$args['row_count'] = 1;
		}
		$board = (array) WPF()->db->get_row( $this->build_sql_select( $args ), ARRAY_A );
		if( $board ) $board = $this->decode( $board );

		return $board;
	}

	/**
	 * @param array|numeric $args
	 *
	 * @return array
	 */
	public function get_board( $args ) {
		return wpforo_ram_get( [$this, '_get_board'], $args );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function _get_boards( $args = [] ) {
		return array_map( [ $this, 'decode' ], (array) WPF()->db->get_results( $this->build_sql_select( $args ), ARRAY_A ) );
	}

	public function get_boards( $args = [] ) {
		return wpforo_ram_get( [ $this, '_get_boards' ], $args );
	}

	/**
	 * @param array $args
	 *
	 * @return int
	 */
	public function get_count( $args = [] ) {
		return (int) WPF()->db->get_var( $this->build_sql_select( $args, 'COUNT(*)' ) );
	}

	public function reset_current() {
		$this->board = $this->default->board;
	}

	private function init_routes() {
		$this->base_routes = array_unique( array_merge( $this->base_routes, array_keys( WPF()->tpl->base_templates ) ) );
		$this->base_routes = array_map( 'wpforo_settings_get_slug', $this->base_routes );
		$slugs  = [];
		if( WPF()->is_installed() ){
			$boards = $this->get_boards( [ 'status' => true ] );
			foreach( $boards as $board ) $slugs[] = $board['slug'];
		}
		$this->routes = array_unique( array_merge( (array) $this->route, $this->base_routes, $slugs ) );
		$this->routes = preg_replace( '#^/?index\.php/?#isu', '', $this->routes );
		$this->routes = array_map( function( $route ) { return trim( $route, '/' ); }, $this->routes );
	}

	/**
	 * @param array $args
	 */
	public function init_current( $args = [] ) {
		if( !WPF()->is_installed() ) return;
		if( is_numeric( $args ) ) $args = ['boardid' => intval( $args )];
		$board = [];
		if( is_null( $boardid = wpfval( $_GET, 'wpforo_boardid' ) ) && is_null( $boardid = wpfval( $_GET, 'boardid' ) ) && is_null( $boardid = wpfval( $args, 'boardid' ) ) ) {
			if( wpforo_is_admin() ) {
				if( preg_match( '#^wpforo-(?:(\d*)-)?#iu', wpfval( $_GET, 'page' ), $match ) ) {
					if( ! $boardid = (int) wpfval( $_GET, 'boardid' ) ) {
						$boardid = (int) wpfval( $match, 1 );
					}
				}
			} else {
				if( $slug = (string) wpfval( $args, 'slug' ) ) {
					$args  = [
						'slug_include' => $slug,
						'locale_like'  => ( $locale = (string) wpfval( $args, 'locale' ) ) ? $locale : WPF()->locale,
						'locale_empty' => true,
						'status'       => true,
						'orderby'      => "( `locale` = '' OR `locale` IS NULL ) ASC, `boardid` DESC",
					];
					$board = $this->get_board( $args );
				}
				if( ! $board && $pageid = get_the_ID() ) {
					$args = [
						'pageid_include' => $pageid,
						'status'         => true,
						'orderby'        => "( `locale` = '' OR `locale` IS NULL ) ASC, `boardid` DESC",
					];
					if( $slug = wpforo_get_url_route() ) $args['slug_include'] = $slug;
					$board = $this->get_board( $args );
				}
				if( ! $board && $slug = wpforo_get_url_route() ) {
					$args  = [
						'slug_include' => $slug,
						'locale_like'  => ( $locale = (string) wpfval( $args, 'locale' ) ) ? $locale : WPF()->locale,
						'locale_empty' => true,
						'status'       => true,
						'orderby'      => "( `locale` = '' OR `locale` IS NULL ) ASC, `boardid` DESC",
					];
					$board = $this->get_board( $args );
				}
				if( ! $board ) {
					$args  = [
						'is_standalone' => true,
						'status'        => true,
						'orderby'       => "( `locale` = '' OR `locale` IS NULL ) ASC, `boardid` DESC",
					];
					$board = $this->get_board( $args );
				}
			}
		}

		if( $board || ( $board = $this->get_board( intval( $boardid ) ) ) ) {
			$this->board = $board;
		} else {
			$this->board = $this->default->board;
		}

		$this->board = apply_filters('wpforo_board_init_current', $this->board);

		if( ! $this->board['pageid'] ) $this->board['pageid'] = wpforo_get_option( 'wpforo_pageid', 0 );
		if( ! wpforo_is_admin() && $this->board['locale'] && WPF()->locale !== $this->board['locale'] ) {
			switch_to_locale( $this->board['locale'] );
		}

		$this->route      = ( ! $this->board['is_standalone'] ? $this->board['slug'] : '' );
		$this->full_route = rtrim(
			( strpos( get_option( 'permalink_structure', '' ), 'index.php' ) !== false ? '/index.php/' : '/' ) . $this->route,
			'/\\'
		);
		## @TODO need to test with or without lang plugins
//		if( $lang = wpforo_get_query_var_lang() ) $this->full_route = "/$lang" . $this->full_route;

		load_plugin_textdomain( 'wpforo', false, basename( WPFORO_DIR ) . '/languages' );

		$this->is_inited = true;
	}

	/**
	 * @param string $field
	 *
	 * @return mixed
	 */
	public function get_current( $field = null ) {
		if( is_null( $field ) ) return $this->board;

		return wpfval( $this->board, $field );
	}

	/**
	 * @param string $module_key
	 * @param array $board
	 *
	 * @return bool
	 */
	public function is_module_enabled( $module_key, $board = [] ) {
		if( ! ( $board = (array) $board ) ) $board = $this->get_current();

		return !wpfkey( $board, 'modules', $module_key ) || wpfval( $board, 'modules', $module_key );
	}

	public function is_multi() {
		return 1 < $this->get_count( [ 'status' => true ] );
	}

	public function get_active_boardids() {
		$boardids = [];
		$boards = $this->get_boards( [ 'status' => true ] );
		foreach( $boards as $board ) $boardids[] = $board['boardid'];
		return $boardids;
	}
}
