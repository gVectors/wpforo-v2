<?php

namespace wpforo\modules\revisions;

use stdClass;
use wpforo\modules\revisions\classes\Template;
use wpforo\modules\revisions\classes\Actions;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Revisions {
    /* @var Template */ public  $Template;
    /* @var Actions  */ public  $Actions;
	public  $revision;
	private $default;

	public function __construct() {
        $this->init_classes();
        $this->init();
	}

	private function init_classes() {
        $this->Template = new Template();
        $this->Actions  = new Actions();
    }

	private function init() {
		$this->init_defaults();
		$this->revision = $this->default->revision;
	}

	private function init_defaults() {
		$this->default                  = new stdClass();
		$this->default->revision        = [
			'revisionid' => 0,
			'userid'     => 0,
			'textareaid' => '',
			'postid'     => 0,
			'body'       => '',
			'created'    => 0,
			'version'    => 0,
			'email'      => '',
			'url'        => '',
		];
		$this->default->revision_format = [
			'revisionid' => '%d',
			'userid'     => '%d',
			'textareaid' => '%s',
			'postid'     => '%d',
			'body'       => '%s',
			'created'    => '%d',
			'version'    => '%d',
			'email'      => '%s',
			'url'        => '%s',
		];
		$this->default->sql_select_args = [
			'include'             => [],
			'exclude'             => [],
			'userids_include'     => [],
			'userids_exclude'     => [],
			'textareaids_include' => [],
			'textareaids_exclude' => [],
			'postids_include'     => [],
			'postids_exclude'     => [],
			'urls_include'        => [],
			'urls_exclude'        => [],
			'emails_include'      => [],
			'emails_exclude'      => [],
			'orderby'             => 'revisionid',
			'order'               => 'DESC',
			'offset'              => null,
			'row_count'           => null,
		];
	}

	public function get_current_url_query_vars_str() {
		$url_query_vars_str = wpforo_get_url_query_vars_str();
		$url_query_vars_str = preg_replace( '#/?\?.*$#isu', '', $url_query_vars_str );

		$wpf_url_parse = array_filter( explode( '/', trim( $url_query_vars_str, '/' ) ) );
		$wpf_url_parse = array_reverse( $wpf_url_parse );
		if( in_array( wpforo_settings_get_slug( 'paged' ), $wpf_url_parse ) ) {
			foreach( $wpf_url_parse as $key => $value ) {
				unset( $wpf_url_parse[ $key ] );
				if( $value === wpforo_settings_get_slug( 'paged' ) ) break;
			}
			$wpf_url_parse      = array_values( $wpf_url_parse );
			$wpf_url_parse      = array_reverse( $wpf_url_parse );
			$url_query_vars_str = implode( '/', $wpf_url_parse );
		}

		if( ! $url_query_vars_str ) $url_query_vars_str = 'wpforo_home_url';

		return $url_query_vars_str;
	}

	public function parse_revision( $revision ) {
		$revision = array_merge( $this->default->revision, (array) $revision );
		if( $revision['body'] ) {
			$revision['body'] = preg_replace( '#</pre>[\r\n\t\s\0]*<pre>#iu', "\r\n", $revision['body'] );
			$revision['body'] = wpforo_kses( trim( $revision['body'] ) );
			$revision['body'] = stripslashes( $revision['body'] );
		}

		return $revision;
	}

	private function parse_args( $args ) {
		$args = wpforo_parse_args( $args, $this->default->sql_select_args );

		$args['include'] = wpforo_parse_args( $args['include'] );
		$args['exclude'] = wpforo_parse_args( $args['exclude'] );

		$args['userids_include'] = wpforo_parse_args( $args['userids_include'] );
		$args['userids_exclude'] = wpforo_parse_args( $args['userids_exclude'] );

		$args['textareaids_include'] = wpforo_parse_args( $args['textareaids_include'] );
		$args['textareaids_exclude'] = wpforo_parse_args( $args['textareaids_exclude'] );

		$args['postids_include'] = wpforo_parse_args( $args['postids_include'] );
		$args['postids_exclude'] = wpforo_parse_args( $args['postids_exclude'] );

		$args['urls_include'] = wpforo_parse_args( $args['urls_include'] );
		$args['urls_exclude'] = wpforo_parse_args( $args['urls_exclude'] );

		$args['emails_include'] = wpforo_parse_args( $args['emails_include'] );
		$args['emails_exclude'] = wpforo_parse_args( $args['emails_exclude'] );

		return $args;
	}

	public function build_sql_where( $args ) {
		$where = '';
		$args  = $this->parse_args( $args );

		$wheres = [];
		if( ! empty( $args['include'] ) ) {
			$wheres[] = "`revisionid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['include'] ) ) . ")";
		}
		if( ! empty( $args['exclude'] ) ) {
			$wheres[] = "`revisionid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['exclude'] ) ) . ")";
		}

		if( ! empty( $args['userids_include'] ) ) {
			$wheres[] = "`userid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['userids_include'] ) ) . ")";
		}
		if( ! empty( $args['userids_exclude'] ) ) {
			$wheres[] = "`userid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['userids_exclude'] ) ) . ")";
		}

		if( ! empty( $args['textareaids_include'] ) ) {
			$wheres[] = "`textareaid` IN('" . implode( "','", array_map( 'trim', $args['textareaids_include'] ) ) . "')";
		}
		if( ! empty( $args['textareaids_exclude'] ) ) {
			$wheres[] = "`textareaid` IN('" . implode( "','", array_map( 'trim', $args['textareaids_exclude'] ) ) . "')";
		}

		if( ! empty( $args['postids_include'] ) ) {
			$wheres[] = "`postid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postids_include'] ) ) . ")";
		}
		if( ! empty( $args['postids_exclude'] ) ) {
			$wheres[] = "`postid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postids_exclude'] ) ) . ")";
		}

		if( ! empty( $args['urls_include'] ) ) {
			$wheres[] = "`url` IN('" . implode( "','", array_map( 'trim', $args['urls_include'] ) ) . "')";
		}
		if( ! empty( $args['urls_exclude'] ) ) {
			$wheres[] = "`url` IN('" . implode( "','", array_map( 'trim', $args['urls_exclude'] ) ) . "')";
		}

		if( ! empty( $args['emails_include'] ) ) {
			$wheres[] = "`email` IN('" . implode( "','", array_map( 'trim', $args['emails_include'] ) ) . "')";
		}
		if( ! empty( $args['emails_exclude'] ) ) {
			$wheres[] = "`email` IN('" . implode( "','", array_map( 'trim', $args['emails_exclude'] ) ) . "')";
		}

		if( $wheres ) {
			$where = " WHERE " . implode( " AND ", $wheres );
		}

		return $where;
	}

	private function build_sql_select( $args ) {
		$args = $this->parse_args( $args );
		$sql  = "SELECT * FROM " . WPF()->tables->post_revisions;
		$sql  .= $this->build_sql_where( $args );
		$sql  .= " ORDER BY " . $args['orderby'] . " " . $args['order'];
		if( $args['row_count'] ) $sql .= " LIMIT " . wpforo_bigintval( $args['offset'] ) . "," . wpforo_bigintval( $args['row_count'] );

		return $sql;
	}

	public function add( $data ) {
		if( empty( $data ) ) return false;
		$revision = $this->parse_revision( $data );
		unset( $revision['revisionid'] );

		if( ! $revision['created'] ) $revision['created'] = current_time( 'timestamp', 1 );
		if( ! $revision['url'] ) $revision['url'] = $this->get_current_url_query_vars_str();
		if( ! $revision['userid'] ) $revision['userid'] = WPF()->current_userid;
		if( ! $revision['email'] ) $revision['email'] = WPF()->current_user_email;
		if( ! $revision['textareaid'] || ! $revision['url'] || ! $revision['body'] || ! ( $revision['userid'] || $revision['email'] ) ) return false;

		$revision = wpforo_array_ordered_intersect_key( $revision, $this->default->revision_format );
		if( WPF()->db->insert(
			WPF()->tables->post_revisions,
			$revision,
			wpforo_array_ordered_intersect_key( $this->default->revision_format, $revision )
		) ) {
			return WPF()->db->insert_id;
		}

		return false;
	}

	public function edit( $data, $where ) {
		if( empty( $data ) || empty( $where ) ) return false;
		if( is_numeric( $where ) ) $where = [ 'revisionid' => $where ];
		$data  = (array) $data;
		$where = (array) $where;

		$data  = wpforo_array_ordered_intersect_key( $data, $this->default->revision_format );
		$where = wpforo_array_ordered_intersect_key( $where, $this->default->revision_format );
		if( false !== WPF()->db->update(
				WPF()->tables->post_revisions,
				$data,
				$where,
				wpforo_array_ordered_intersect_key( $this->default->revision_format, $data ),
				wpforo_array_ordered_intersect_key( $this->default->revision_format, $where )
			) ) {
			return true;
		}

		return false;
	}

	public function delete( $where ) {
		if( empty( $where ) ) return false;
		if( is_numeric( $where ) ) $where = [ 'revisionid' => $where ];
		$where = (array) $where;

		$where = wpforo_array_ordered_intersect_key( $where, $this->default->revision_format );
		if( false !== WPF()->db->delete(
				WPF()->tables->post_revisions,
				$where,
				wpforo_array_ordered_intersect_key( $this->default->revision_format, $where )
			) ) {
			return true;
		}

		return false;
	}

	public function get_revision( $args ) {
		if( empty( $args ) ) return false;

		return $this->parse_revision( WPF()->db->get_row( $this->build_sql_select( $args ), ARRAY_A ) );
	}

	public function get_revisions( $args ) {
		if( empty( $args ) ) return false;

		return array_map( [ $this, 'parse_revision' ], WPF()->db->get_results( $this->build_sql_select( $args ), ARRAY_A ) );
	}

	/**
	 * @param array $args
	 *
	 * @return int
	 */
	public function get_count( $args ) {
		$sql = "SELECT SQL_NO_CACHE COUNT(*) FROM " . WPF()->tables->post_revisions;
		$sql .= $this->build_sql_where( $args );

		return intval( WPF()->db->get_var( $sql ) );
	}
}
