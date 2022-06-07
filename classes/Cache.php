<?php

namespace wpforo\classes;

use FilesystemIterator;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Cache {
	public $object;
	public $dir;
	public $lang;

	function __construct() {
		add_action( 'wpforo_after_init_folders', function( $folders ) {
			$cache_dir = $folders['cache']['dir'];
			if( ! is_dir( $cache_dir ) ) $this->dir( $cache_dir );
			if( ! is_dir( $cache_dir . DIRECTORY_SEPARATOR . 'tag' ) ) $this->mkdir( $cache_dir . DIRECTORY_SEPARATOR . 'tag' );
			if( ! is_dir( $cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'tag' ) ) $this->mkdir( $cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'tag' );
			if( ! is_dir( $cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'option' ) ) $this->mkdir( $cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'option' );

			$this->dir = $cache_dir;
		} );

		add_action( 'wpforo_after_set_locale', function( $locale ) { $this->lang = $locale; } );

		add_action( 'wpforo_after_add_board', function( $board ){
			if( $board['status'] ){
				WPF()->change_board( $board['boardid'] );
				wpforo_clean_cache();
			}
		} );

		add_action( 'wpforo_after_edit_board', function( $boardid ){
			$board = WPF()->board->_get_board( $boardid );
			if( $board['status'] ){
				WPF()->change_board( $boardid );
				wpforo_clean_cache();
			}
		} );
	}

	public function get_key( $type = 'html' ) {
		if( $type === 'html' ) {
			$ug = WPF()->current_user_groupid;

			return md5( preg_replace( '|(.+)\#.+?$|is', '$1', $_SERVER['REQUEST_URI'] ) . $ug );
		}
	}

	private function dir( $cache_dir ) {
		$dirs = [
			$cache_dir,
			$cache_dir . DIRECTORY_SEPARATOR . 'forum',
			$cache_dir . DIRECTORY_SEPARATOR . 'topic',
			$cache_dir . DIRECTORY_SEPARATOR . 'post',
			$cache_dir . DIRECTORY_SEPARATOR . 'tag',
			$cache_dir . DIRECTORY_SEPARATOR . 'item',
			$cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'forum',
			$cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'topic',
			$cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'post',
			$cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'tag',
			$cache_dir . DIRECTORY_SEPARATOR . 'item' . DIRECTORY_SEPARATOR . 'option',
		];
		$this->mkdir( $dirs );
	}

	private function mkdir( $dirs ) {
		foreach( (array) $dirs as $dir ) {
			wp_mkdir_p( $dir );
			wpforo_write_file( $dir . '/index.html', '' );
			wpforo_write_file( $dir . '/.htaccess', 'deny from all' );
		}
	}

	public function on(){
		return wpforo_setting( 'board', 'cache' );
	}

	public function get( $key, $type = 'loop', $template = null ) {
		$template       = ( $template ) ?: WPF()->current_object['template'];
		$loop_templates = [ 'forum', 'topic', 'post', 'tag', 'option' ];
		if( $type === 'loop' && $template ) {
			if( $this->exists( $key, $template ) ) {
				if( in_array( $template, $loop_templates ) ) {
					$cache_file = $this->dir . '/' . $template . '/' . $key;
					$array      = wpforo_get_file_content( $cache_file );

					return @unserialize( $array );
				}
			}
		}
	}

	public function get_item( $id, $type = 'post' ) {
		if( $id ) {
			$key = $id . '_' . $this->lang;
			if( $this->exists( $key, 'item', $type ) ) {
				$cache_file = $this->dir . '/item/' . $type . '/' . $key;
				$array      = wpforo_get_file_content( $cache_file );

				return @unserialize( $array );
			}
		}
	}

	public function get_html() {
		$template = WPF()->current_object['template'];
		if( $template == 'forum' ) {
			$key = $this->get_key();
			if( $this->exists( $key, $template ) ) {
				$cache_file = $this->dir . '/' . $template . '/' . $key;
				$html       = wpforo_get_file_content( $cache_file );

				return $this->filter( $html );
			}
		}

		return false;
	}

	public function html( $content ) {
		/*$template = WPF()->current_object['template'];
		if( $template === 'forum' ){
			$key = $this->get_key();
			$this->create_html( $content, $template, $key );
		}*/
	}

	public function create( $mode = 'loop', $cache = [], $type = 'post' ) {
		if( ! $this->on() ) return false;
		$template = WPF()->current_object['template'];
		if( $template == 'forum' ) {
			$this->check( $this->dir . '/item/post' );
		}

		if( $mode === 'loop' && $template ) {
			if( wpfval( $cache, 'tags' ) ) {
				$this->create_files( $cache['tags'], 'tag' );
			}
			if( $template === 'forum' || $template === 'topic' || $template === 'post' ) {
				$cache = WPF()->forum->get_cache( 'forums' );
				$this->create_files( $cache, $template );
				$cache = WPF()->topic->get_cache( 'topics' );
				$this->create_files( $cache, $template );
				$cache = WPF()->post->get_cache( 'posts' );
				$this->create_files( $cache, $template );
			}
		} elseif( $mode === 'item' && ! empty( $cache ) ) {
			$this->create_files( $cache, 'item', $type );
		}
	}

	public function create_files( $cache = [], $template = '', $type = '' ) {
		if( ! empty( $cache ) ) {
			$type = ( $type ) ? $type . '/' : '';
			foreach( $cache as $key => $object ) {
				if( $template == 'item' ) $key = $key . '_' . $this->lang;
				if( ! $this->exists( $key, $template ) ) {
					$object = serialize( $object );
					wpforo_write_file( $this->dir . '/' . $template . '/' . $type . $key, $object );
				}
			}
		}
	}

	public function create_html( $content, $template = '', $key = '' ) {
		if( $content ) {
			if( ! $this->exists( $key, $template ) ) {
				wpforo_write_file( $this->dir . '/' . $template . '/' . $key, $content );
			}
		}
	}

	public function create_custom( $args = [], $items = [], $template = 'post', $items_count = 0 ) {
		if( empty( $args ) || ! is_array( $args ) ) return;
		if( empty( $items ) || ! is_array( $items ) ) return;
		$cache                               = [];
		$hach                                = serialize( $args );
		$object_key                          = md5( $hach . WPF()->current_user_groupid );
		$cache[ $object_key ]['items']       = $items;
		$cache[ $object_key ]['items_count'] = $items_count;
		$this->create_files( $cache, $template );
	}

	public function filter( $html = '' ) {
		//exit();
		$html = preg_replace( '|<div[\s\t]*id=\"wpf\-msg\-box\"|is', '<div style="display:none;"', $html );

		return $html;
	}

	#################################################################################

	/**
	 * Cleans forum cache
	 *
	 * @param integer        Item ID        (e.g.: $topicid or $postid) | (!) ID is 0 on dome actions (e.g.: delete actions)
	 * @param string        Item Type    (e.g.: 'forum', 'topic', 'post', 'user', 'widget', etc...)
	 * @param array        Item data as array
	 *
	 * @return    NULL
	 * @since 1.2.1
	 *
	 */

	public function clean( $id, $template, $item = [] ) {

		$dirs     = [];
		$userid   = ( isset( $item['userid'] ) && $item['userid'] ) ? $item['userid'] : 0;
		$postid   = ( isset( $item['postid'] ) && $item['postid'] ) ? $item['postid'] : 0;
		$topicid  = ( isset( $item['topicid'] ) && $item['topicid'] ) ? $item['topicid'] : 0;
		$forumid  = ( isset( $item['forumid'] ) && $item['forumid'] ) ? $item['forumid'] : 0;
		$parentid = ( isset( $item['parentid'] ) && $item['parentid'] ) ? $item['parentid'] : 0;
		$root     = ( isset( $item['root'] ) && $item['root'] ) ? $item['root'] : 0;
		$tagid    = ( isset( $item['tagid'] ) && $item['tagid'] ) ? $item['tagid'] : 0;

		WPF()->forum->reset();
		WPF()->topic->reset();
		WPF()->post->reset();

		if( $template === 'forum' || $template === 'forum-soft' ) {
			$id = isset( $id ) ? $id : $forumid;
			if( $template === 'forum' ) {
				$dirs = [ $this->dir . '/forum', $this->dir . '/item/forum' ];
				WPF()->seo->clear_cache();
			} elseif( $template === 'forum-soft' ) {
				$dirs = [ $this->dir . '/forum' ];
			}
			if( $id ) {
				$file = $this->dir . '/item/forum/' . $id . '_' . $this->lang;
				$this->clean_file( $file );
			}
		} elseif( $template === 'topic' || $template === 'topic-soft' ) {
			$id = isset( $id ) ? $id : $topicid;
			if( $template === 'topic' ) {
				WPF()->seo->clear_cache();
				$dirs = [ $this->dir . '/forum', $this->dir . '/item/forum', $this->dir . '/topic', $this->dir . '/post' ];
			}
			if( $template === 'topic-soft' && $forumid ) {
				$file = $this->dir . '/item/forum/' . $forumid . '_' . $this->lang;
				$this->clean_file( $file );
			}
			if( $id ) {
				$file = $this->dir . '/item/topic/' . $id . '_' . $this->lang;
				$this->clean_file( $file );
				$postid = ( isset( $item['first_postid'] ) && $item['first_postid'] ) ? $item['first_postid'] : 0;
				if( $postid ) $file = $this->dir . '/item/post/' . $postid . '_' . $this->lang;
				$this->clean_file( $file );
			}
			WPF()->statistic_cache_clean();
			$this->clear_visitor_tracking();
		} elseif( $template === 'post' || $template === 'post-soft' ) {
			$id = isset( $id ) ? $id : $postid;
			if( $template === 'post' ) {
				$dirs = [ $this->dir . '/forum', $this->dir . '/topic', $this->dir . '/post' ];
			}
			if( $forumid ) {
				$file = $this->dir . '/item/forum/' . $forumid . '_' . $this->lang;
				$this->clean_file( $file );
			}
			if( $topicid ) {
				$file = $this->dir . '/item/topic/' . $topicid . '_' . $this->lang;
				$this->clean_file( $file );
			}
			if( $parentid ) {
				$file = $this->dir . '/item/post/' . $parentid . '_' . $this->lang;
				$this->clean_file( $file );
			}
			if( $root ) {
				$file = $this->dir . '/item/post/' . $root . '_' . $this->lang;
				$this->clean_file( $file );
			}
			if( $id ) {
				$file = $this->dir . '/item/post/' . $id . '_' . $this->lang;
				$this->clean_file( $file );
			}
			WPF()->statistic_cache_clean();
			$this->clear_visitor_tracking();
		} elseif( $template === 'tag' ) {
			if( $id ) {
				$file = $this->dir . '/item/tag/' . md5( $id ) . '_' . $this->lang;
				$this->clean_file( $file );
			} else {
				$dirs = [ $this->dir . '/tag' ];
			}
		} elseif( $template === 'option' ) {
			$dirs = [ $this->dir . '/item/option/' ];
		} elseif( $template === 'user' ) {
			if( wpforo_setting( 'seo', 'seo_profile' ) ) WPF()->seo->clear_cache();
		} elseif( $template === 'loop' ) {
			$dirs = [ $this->dir . '/forum', $this->dir . '/topic', $this->dir . '/post' ];
			WPF()->seo->clear_cache();
		} elseif( $template === 'item' ) {
			$dirs = [ $this->dir . '/item/post', $this->dir . '/item/topic', $this->dir . '/item/forum' ];
			WPF()->seo->clear_cache();
		} else {
			$dirs = [ $this->dir . '/forum', $this->dir . '/topic', $this->dir . '/post', $this->dir . '/tag', $this->dir . '/item/post', $this->dir . '/item/topic', $this->dir . '/item/forum', $this->dir . '/item/tag', $this->dir . '/item/option/' ];
			WPF()->seo->clear_cache();
		}

		if( ! empty( $dirs ) ) {
			foreach( $dirs as $dir ) {
				$this->clean_files( $dir );
			}
		}

	}

	public function clean_files( $directory ) {
		$directory    = wpforo_fix_dir_sep( $directory );
		$directory_ns = trim( $directory, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . '*';
		$directory_ws = DIRECTORY_SEPARATOR . trim( $directory, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . '*';
		$glob         = glob( $directory_ns );
		if( empty( $glob ) ) $glob = glob( $directory_ws );
		foreach( $glob as $item ) {
			if( strpos( $item, 'index.html' ) !== false || strpos( $item, '.htaccess' ) !== false ) continue;
			if( ! is_dir( $item ) && file_exists( $item ) ) {
				@unlink( $item );
			}
		}
	}

	public function clean_file( $file ) {
		if( ! is_dir( $file ) && file_exists( $file ) ) {
			@unlink( $file );
		}
	}

	public function exists( $key, $template, $type = '' ) {
		$type = ( $type ) ? $type . '/' : '';
		if( file_exists( $this->dir . '/' . $template . '/' . $type . $key ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function check( $directory ) {
		$directory = wpforo_fix_dir_sep( $directory );
		$filecount = 0;
		if( class_exists( 'FilesystemIterator' ) && is_dir( $directory ) ) {
			$fi        = new FilesystemIterator( $directory, FilesystemIterator::SKIP_DOTS );
			$filecount = iterator_count( $fi );
		}
		if( ! $filecount ) {
			$directory_ns = trim( $directory, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . '*';
			$directory_ws = DIRECTORY_SEPARATOR . trim( $directory, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . '*';
			$files        = glob( $directory_ns );
			if( empty( $files ) ) $files = glob( $directory_ws );
			$filecount = count( $files );
		}
		if( $filecount > 1000 ) {
			$this->clean_files( $directory );
		}
	}

	public function clear_visitor_tracking() {
		$keep_vistors_data = apply_filters( 'wpforo_keep_visitors_data', 4000 );
		$time              = (int) current_time( 'timestamp', 1 ) - (int) $keep_vistors_data;
		$online            = (int) current_time( 'timestamp', 1 ) - (int) wpforo_setting( 'profiles', 'online_status_timeout' );
		if( $time > 1 ) {
			WPF()->db->query( "DELETE FROM `" . WPF()->tables->visits . "` WHERE `time` < " . intval( $time ) . "  OR (`time` < " . intval( $online ) . " AND `userid` = 0)" );
		}
	}
}
