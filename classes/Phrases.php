<?php

namespace wpforo\classes;

use wpforo\admin\listtables\Phrases as PhrasesListTable;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Phrases {
	public $phrases;
	public $__phrases;
	public $list_table;
	public $langid;

	public function init() {
		if( WPF()->is_installed() ) {
			$this->langid = $this->get_current_langid();
			if( is_admin() ) add_action( 'wpforo_after_init', [ $this, 'init_list_table' ] );
			if( $phrases = $this->get_phrases() ) {
				foreach( $phrases as $phrase ) {
					$this->phrases[ addslashes( strtolower( $phrase['phrase_key'] ) ) ]   = $phrase['phrase_value'];
					$this->__phrases[ addslashes( strtolower( $phrase['phrase_key'] ) ) ] = wpforo_phrase( $phrase['phrase_key'], false );
				}
				add_action( 'wp_ajax_nopriv_wpforo_get_phrases', [ $this, 'ajax_get_phrases' ] );
				add_action( 'wp_ajax_wpforo_get_phrases', [ $this, 'ajax_get_phrases' ] );
			}
		}
	}

	public function init_list_table() {
		if( wpfval( $_GET, 'page' ) === wpforo_prefix_slug( 'phrases' ) ) {
			$this->list_table = new PhrasesListTable();
			$this->list_table->prepare_items();
		}
	}

	function add( $args = [], $clear_cache = true ) {
		if( empty( $args ) && empty( $_REQUEST['phrase'] ) ) return false;
		if( empty( $args ) && ! empty( $_REQUEST['phrase'] ) ) $args = $_REQUEST['phrase'];

		if( empty( $args['langid'] ) ) $args['langid'] = $this->langid;
		if( empty( $args['package'] ) ) $args['package'] = 'wpforo';
		$sql = WPF()->db->prepare(
			"INSERT IGNORE INTO `" . WPF()->tables->phrases . "` 
												(`langid`, `phrase_key`, `phrase_value`, `package`) 
													VALUES (%d, %s, %s, %s)",
			intval( $args['langid'] ),
			stripslashes( $args['key'] ),
			stripslashes( $args['value'] ),
			stripslashes( $args['package'] )
		);
		if( false !== WPF()->db->query( $sql ) ) {
			$phraseid = WPF()->db->insert_id;
			WPF()->notice->add( 'Phrase successfully added', 'success' );
			if( $clear_cache ) $this->clear_cache();

			return $phraseid;
		}
		WPF()->notice->add( 'Phrase add error', 'error' );

		return false;
	}

	function edit( $args = [] ) {
		if( ! $args && ! empty( $_REQUEST['phrases'] ) ) $args = $_REQUEST['phrases'];
		if( ! empty( $args ) ) {
			foreach( $args as $phraseid => $phrase ) {
				WPF()->db->update( WPF()->tables->phrases, [ 'phrase_value' => sanitize_text_field( stripslashes( $phrase ) ) ], [ 'phraseid' => intval( $phraseid ) ], [ '%s' ], [ '%d' ] );

			}
			$this->clear_cache();
			WPF()->notice->add( 'Phrase successfully updates', 'success' );

			return true;
		}

		WPF()->notice->add( 'Phrase update error', 'error' );

		return false;
	}

	function get_phrase( $phraseid ) {
		$sql = 'SELECT * FROM ' . WPF()->tables->phrases . ' WHERE `phraseid` =' . intval( $phraseid );

		return WPF()->db->get_row( $sql, ARRAY_A );
	}

	function get_phrases( $args = [], &$items_count = 0, $count = false ) {
		$default = [
			'include' => [],        // array( 2, 10, 25 )
			'exclude' => [],        // array( 2, 10, 25 )
			'langid'  => $this->langid,
			'package' => [],

			'orderby'   => 'phraseid',
			'order'     => 'ASC',        // ASC DESC
			'offset'    => '',            // this use when you give row_count
			'row_count' => '',
		];

		$args = wpforo_parse_args( $args, $default );

		$key = substr( md5( serialize( $args ) ), 0, 10 );

		extract( $args, EXTR_OVERWRITE );

		$package = wpforo_parse_args( $package );
		$include = wpforo_parse_args( $include );
		$exclude = wpforo_parse_args( $exclude );

		$wheres = [];

		if( ! empty( $package ) ) $wheres[] = "`package` IN('" . implode( "','", array_map( 'esc_sql', array_map( 'sanitize_text_field', $package ) ) ) . "')";
		if( ! empty( $include ) ) $wheres[] = "`phraseid` IN(" . implode( ', ', array_map( 'intval', $include ) ) . ")";
		if( ! empty( $exclude ) ) $wheres[] = "`phraseid` NOT IN(" . implode( ', ', array_map( 'intval', $exclude ) ) . ")";
		if( ! is_null( $langid ) ) $wheres[] = "`langid` = " . intval( $langid );

		$sql = "SELECT * FROM `" . WPF()->tables->phrases . "`";
		if( ! empty( $wheres ) ) {
			$sql .= " WHERE " . implode( " AND ", $wheres );
		}

		if( $count ) {
			$item_count_sql = preg_replace( '#SELECT.+?FROM#isu', 'SELECT count(*) FROM', $sql );
			if( $item_count_sql ) $items_count = WPF()->db->get_var( $item_count_sql );
		}

		$sql .= esc_sql( " ORDER BY `$orderby` " . $order );

		if( $row_count != '' && $offset == '' ) {  // If you give only row_count this if fixed problam
			$offset    = $row_count;
			$row_count = '';
		}
		$sql .= $offset != '' ? esc_sql( ' LIMIT ' . $offset ) : '';
		$sql .= $row_count != '' ? esc_sql( ', ' . $row_count ) : '';

		if( false === ( $phrases = get_transient( 'wpforo_get_phrases_' . $key ) ) ) {
			$phrases = WPF()->db->get_results( $sql, ARRAY_A );
			set_transient( 'wpforo_get_phrases_' . $key, $phrases, 60 * 60 * 24 );
		}

		return get_transient( 'wpforo_get_phrases_' . $key );
	}

	function search( $needle = '', $fields = [ 'phrase_key', 'phrase_value' ] ) {
		$fields = (array) $fields;
		if( ! $needle || empty( $fields ) ) return [];

		$needle = substr( sanitize_text_field( $needle ), 0, 60 );
		$sql    = "SELECT `phraseid` FROM " . WPF()->tables->phrases;
		$wheres = [];
		foreach( $fields as $field ) {
			$field    = sanitize_text_field( $field );
			$wheres[] = "`" . esc_sql( $field ) . "` LIKE '%" . esc_sql( $needle ) . "%'";
		}
		if( $wheres ) $sql .= ' WHERE ' . implode( ' OR ', $wheres );

		return WPF()->db->get_col( $sql );
	}

	public function xml_import( $xmlfile ) {
		$file = WPFORO_DIR . '/admin/assets/xml/' . $xmlfile;
		if( file_exists( $file ) && function_exists( 'xml_parser_create' ) ) {
			$xr  = xml_parser_create();
			$fp  = fopen( $file, "r" );
			$xml = fread( $fp, filesize( $file ) );

			xml_parser_set_option( $xr, XML_OPTION_CASE_FOLDING, 1 );
			xml_parse_into_struct( $xr, $xml, $vals );
			xml_parser_free( $xr );

			delete_transient( 'wpforo_get_phrases' );

			if( ! empty( $vals ) && isset( $vals[0]['tag'] ) && $vals[0]['tag'] == 'LANGUAGE' && isset( $vals[0]['attributes']['LANGUAGE'] ) && $vals[0]['attributes']['LANGUAGE'] ) {

				$sql    = "SELECT `langid` FROM `" . WPF()->tables->languages . "` WHERE `name` LIKE '" . esc_sql( sanitize_text_field( $vals[0]['attributes']['LANGUAGE'] ) ) . "'";
				$langid = WPF()->db->get_var( $sql );

				if( ! $langid ) {
					$sql = "INSERT INTO `" . WPF()->tables->languages . "` (`name`) VALUES ( '" . esc_sql( sanitize_text_field( $vals[0]['attributes']['LANGUAGE'] ) ) . "' )";
					if( WPF()->db->query( $sql ) ) {
						$langid = WPF()->db->insert_id;
						$this->set_language_status( $langid );
					}
				}

				if( $langid ) {
					foreach( $vals as $val ) {
						if( isset( $val['tag'] ) && $val['tag'] == 'PHRASE' && isset( $val['attributes']['NAME'] ) && trim( $val['attributes']['NAME'] ) && isset( $val['value'] ) && trim( $val['value'] ) ) {
							$sql = "INSERT IGNORE INTO `" . WPF()->tables->phrases . "` 
									(`phraseid`, `langid`, `phrase_key`, `phrase_value`)
									VALUES( NULL, 
									  '" . intval( $langid ) . "', 
									  '" . esc_sql( stripslashes( htmlspecialchars_decode( $val['attributes']['NAME'], ENT_QUOTES ) ) ) . "', 
									  '" . esc_sql( stripslashes( htmlspecialchars_decode( $val['value'], ENT_QUOTES ) ) ) . "')";
							WPF()->db->query( $sql );
						}
					}

					return $langid;
				}
			}
		}

		return false;
	}

	function add_lang() {
		if( is_array( $_FILES['add_lang']['name'] ) && ! empty( $_FILES['add_lang']['name'] ) && isset( $_FILES['add_lang']['name']['xml'] ) ) {
			if( ! is_dir( WPFORO_DIR . '/admin/xml' ) ) wp_mkdir_p( WPFORO_DIR . '/admin/xml' );

			$error = $_FILES['add_lang']['error']['xml'];

			if( $error ) {
				$error = wpforo_file_upload_error( $error );
				WPF()->notice->add( $error, 'error' );

				return false;
			}

			$xmlfile = strtolower( sanitize_file_name( $_FILES['add_lang']['name']['xml'] ) );
			$ext     = strtolower( pathinfo( $xmlfile, PATHINFO_EXTENSION ) );
			if( $ext === 'xml' ) {
				if( move_uploaded_file( sanitize_text_field( $_FILES['add_lang']['tmp_name']['xml'] ), WPFORO_DIR . '/admin/assets/xml/' . $xmlfile ) ) {
					if( $langid = $this->xml_import( $xmlfile ) ) {
						delete_transient( 'wpforo_get_phrases' );
						WPF()->notice->add( 'New language successfully added and changed wpforo language to new language', 'success' );

						return $langid;
					}
				}
			} else {
				WPF()->notice->add( 'Incorrect file type', 'error' );

				return false;
			}
		}

		WPF()->notice->add( 'Can\'t add new language', 'error' );

		return false;
	}

	public function set_language_status( $langid ){
		if( WPF()->db->update(
				WPF()->tables->languages,
				['status' => 1],
				['langid' => $langid],
				['%d'],
				['%d']
		)){
			return false !== WPF()->db->query( WPF()->db->prepare(
				"UPDATE `" . WPF()->tables->languages . "` SET `status` = 0 WHERE `langid` <> %d",
				$langid
			));
		}

		return false;
	}

	public function get_current_langid(){
		$langid = (int) WPF()->db->get_var(
			"SELECT `langid` FROM `" . WPF()->tables->languages . "` WHERE `status` = 1 ORDER BY `langid` DESC"
		);
		if( !$langid ){
			$langid = (int) WPF()->db->get_var(
				"SELECT `langid` FROM `" . WPF()->tables->languages . "` ORDER BY `langid` DESC"
			);
		}

		return $langid;
	}

	public function get_language( $langid ) {
		return WPF()->db->get_row(
			WPF()->db->prepare(
				"SELECT * FROM `" . WPF()->tables->languages . "` WHERE `langid` = %d", $langid
			),
			ARRAY_A
		);
	}

	function get_languages() {
		return WPF()->db->get_results( "SELECT * FROM `" . WPF()->tables->languages . "`", ARRAY_A );
	}

	function show_lang_list( $selected = null ) {
		if( $langs = $this->get_languages() ) {
			if( ! $selected ) $selected = $this->langid;
			$selected = intval( $selected );
			foreach( $langs as $lang ) {
				$lang['langid'] = intval( $lang['langid'] );
				printf(
					'<option value="%1$d" %2$s>%3$s</option>',
					$lang['langid'],
					( $lang['langid'] === $selected ) ? 'selected' : '',
					esc_html( $lang['name'] )
				);
			}
		}
	}

	public function get_distinct_packages() {
		$sql = "SELECT DISTINCT `package` as packages FROM `" . WPF()->tables->phrases . "`";

		return WPF()->db->get_col( $sql );
	}

	function clear_cache() {
		WPF()->db->query( "DELETE FROM " . WPF()->db->options . " WHERE `option_name` LIKE '%_wpforo_get_phrases_%'" );
	}

	public function crawl_phrases( $pattern = null ) {
		if( is_null( $pattern ) ) $pattern = dirname( WPFORO_DIR ) . DIRECTORY_SEPARATOR . 'wpforo*';
		if( $matches = glob( $pattern ) ) {
			$package = 'wpforo';
			if( preg_match( '#[/\\\]wp-content[/\\\]plugins[/\\\]([^/\\\]+)[/\\\]#isu', $pattern, $p ) ) {
				$package = $p[1];
			}
			foreach( $matches as $match ) {
				if( is_dir( $match ) ) {
					$this->crawl_phrases( $match . DIRECTORY_SEPARATOR . '*' );
				} elseif( is_file( $match ) && preg_match( '#\.(php|js)$#isu', $match ) ) {
					if( $file_content = wpforo_get_file_content( $match ) ) {
						if( preg_match_all( '#(?:wpforo_phrase|WPF\(\)->notice->add|wpforo_notice_show|wpforo_load_show)\([\r\n\t\s\0]*[\'\"](?P<phrase_key>.+?)[\'\"][\r\n\t\s\0\,\)]+#isu', $file_content, $phrases, PREG_SET_ORDER ) ) {
							foreach( $phrases as $phrase ) {
								if( $phrase['phrase_key'] ) {
									$args = [
										'key'     => $phrase['phrase_key'],
										'value'   => __( $phrase['phrase_key'], $package ),
										'package' => $package,
									];
									$this->add( $args, false );
								}
							}
						}
					}
				}

			}
		}
	}

	public function rebuild_file_wpf_phrases_php() {
		$file_path = wpforo_fix_dir_sep( WPFORO_DIR . '/includes/phrases.php' );
		if( WPF()->is_installed() && $file_content = wpforo_get_file_content( $file_path ) ) {

			if( $phrases = $this->get_phrases( [ 'package' => 'wpforo' ] ) ) {
				foreach( $phrases as $phrase ) {
					$key = addcslashes( $phrase['phrase_key'], '"' );

					if( ! preg_match( '#[\'\"]' . preg_quote( $key ) . '[\'\"][\r\n\t\s\0]*=>[\r\n\t\s\0]*__\([\r\n\t\s\0]*[\'\"]' . preg_quote( $key ) . '[\'\"][\r\n\t\s\0]*,[\r\n\t\s\0]*[\'\"]wpforo[\'\"][\r\n\t\s\0]*\)#isu', $file_content ) ) {
						$file_content = preg_replace( '#(\$wpforo_phrases[\r\n\t\s\0]*=[\r\n\t\s\0]*array\([\r\n\t\s\0]*)#isu', '$1"' . $key . '" => __("' . $key . '", "wpforo"),' . "\r\n\t", $file_content );
					}
				}

				wpforo_write_file( $file_path, $file_content );
			}

		}
	}

	public function rebuild_file_english_xml() {
		$file_path = wpforo_fix_dir_sep( WPFORO_DIR . '/admin/assets/xml/english.xml' );
		if( WPF()->is_installed() && $file_content = wpforo_get_file_content( $file_path ) ) {

			if( $phrases = $this->get_phrases( [ 'package' => 'wpforo' ] ) ) {
				foreach( $phrases as $phrase ) {
					$key = htmlspecialchars( $phrase['phrase_key'], ENT_QUOTES );

					if( ! preg_match( '#<phrase[^<>]*?name=[\'\"]' . preg_quote( $key ) . '[\'\"][^<>]*?>[^<>]*?<\!\[CDATA\[' . preg_quote( $key ) . '\]\]>[^<>]*?</phrase>#isu', $file_content ) ) {
						$file_content = preg_replace( '#([\r\n\t\s\0]*</language>)#isu', "\r\n\t" . '<phrase name="' . $key . '"><![CDATA[' . $key . ']]></phrase>$1', $file_content );
					}
				}

				wpforo_write_file( $file_path, $file_content );
			}

		}
	}

	public function ajax_get_phrases() {
		echo json_encode( $this->__phrases );
		exit();
	}

	public function get_wpforo_phrases_inline_js() {
		return 'window.wpforo_phrases = ' . json_encode( $this->__phrases ) . ';';
	}

}
