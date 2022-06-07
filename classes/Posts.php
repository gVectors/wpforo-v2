<?php

namespace wpforo\classes;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Posts {
	private $fields = [];

	public static $cache = [ 'posts' => [], 'post' => [], 'item' => [], 'topic_slug' => [], 'forum_slug' => [], 'post_url' => [] ];

	function __construct() {
		$this->init_hooks();
	}

	public function get_cache( $var ) {
		if( isset( self::$cache[ $var ] ) ) return self::$cache[ $var ];
	}

	public function reset() {
		self::$cache = [ 'posts' => [], 'post' => [], 'item' => [], 'topic_slug' => [], 'forum_slug' => [], 'post_url' => [] ];
	}

	private function init_hooks() {
		add_filter( 'wpforo_content_after', [ $this, 'print_custom_fields' ], 99, 2 );
		add_action( 'wpforo_after_delete_user', [ $this, 'after_delete_user' ], 10, 2 );
	}

	/**
	 * @param int $layout
	 *
	 * @return int items_per_page
	 */
	public function get_option_items_per_page( $layout = null ) {
		switch( $layout ) {
			case 4:
				$items_per_page = wpforo_setting( 'topics', 'layout_threaded_posts_per_page' );
			break;
			case 3:
				$items_per_page = wpforo_setting( 'topics', 'layout_qa_posts_per_page' );
			break;
			default:
				$items_per_page = wpforo_setting( 'topics', 'posts_per_page' );
			break;
		}

		return (int) apply_filters( 'wpforo_post_get_option_items_per_page', $items_per_page, $layout );
	}

	/**
	 * @param int $layout
	 *
	 * @return bool
	 */
	public function get_option_union_first_post( $layout ) {
		$layout           = intval( $layout );
		$union_first_post = (bool) wpforo_setting( 'topics', 'union_first_post', $layout );

		return (bool) apply_filters( 'wpforo_post_options_get_union_first_post', $union_first_post, $layout );
	}

	public function add( $args = [] ) {

		$guestposting = false;
		$root_exists  = wpforo_root_exist();

		if( empty( $args ) && empty( $_REQUEST['post'] ) ) {
			WPF()->notice->add( 'Reply request error', 'error' );

			return false;
		}
		if( empty( $args ) && ! empty( $_REQUEST['post'] ) ) $args = $_REQUEST['post'];
		if( ! isset( $args['body'] ) || ! $args['body'] ) {
			WPF()->notice->add( 'Post is empty', 'error' );

			return false;
		}
		if( ! wpfval( $args, 'title' ) && wpfval( $args, 'topicid' ) ) {
			$args['title'] = wpforo_phrase( 'RE', false ) . ': ' . wpforo_topic( $args['topicid'], 'title' );
		}
		$args['name']  = ( isset( $args['name'] ) ? strip_tags( $args['name'] ) : '' );
		$args['email'] = ( isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '' );
		if( isset( $args['userid'] ) && $args['userid'] == 0 && $args['name'] && $args['email'] ) $guestposting = true;

		extract( $args );

		if( ! isset( $topicid ) || ! $topicid ) {
			WPF()->notice->add( 'Error: No topic selected', 'error' );

			return false;
		}
		if( ! $topic = WPF()->topic->get_topic( intval( $topicid ) ) ) {
			WPF()->notice->add( 'Error: Topic is not found', 'error' );

			return false;
		}
		if( ! $forum = WPF()->forum->get_forum( intval( $topic['forumid'] ) ) ) {
			WPF()->notice->add( 'Error: Forum is not found', 'error' );

			return false;
		}

		if( $topic['closed'] ) {
			WPF()->notice->add( 'Can\'t write a post: This topic is closed', 'error' );

			return false;
		}

		if( ! $guestposting && ! ( WPF()->perm->forum_can( 'cr', $topic['forumid'] ) || ( wpforo_is_owner( $topic['userid'], $topic['email'] ) && WPF()->perm->forum_can( 'ocr', $topic['forumid'] ) ) ) ) {
			WPF()->notice->add( 'You don\'t have permission to create post in this forum', 'error' );

			return false;
		}

		if( ! WPF()->perm->can_post_now() ) {
			WPF()->notice->add( 'You are posting too quickly. Slow down.', 'error' );

			return false;
		}

		if( ! is_user_logged_in() ) {
			if( ! $args['name'] || ! $args['email'] ) {
				WPF()->notice->add( 'Please insert required fields!', 'error' );

				return false;
			} else {
				WPF()->member->set_guest_cookies( $args );
			}
		}

		do_action( 'wpforo_start_add_post', $args );

		$post             = $args;
		$post['forumid']  = $forumid = ( isset( $topic['forumid'] ) ? intval( $topic['forumid'] ) : 0 );
		$post['parentid'] = $parentid = ( isset( $parentid ) ? intval( $parentid ) : 0 );
		$post['title']    = $title = ( isset( $title ) ? wpforo_text( trim( $title ), 250, false ) : '' );
		$post['body']     = $body = ( isset( $body ) ? preg_replace( '#</pre>[\r\n\t\s\0]*<pre>#isu', "\r\n", $body ) : '' );
		$post['created']  = $created = ( isset( $created ) ? $created : current_time( 'mysql', 1 ) );
		$post['userid']   = $userid = ( isset( $userid ) ? intval( $userid ) : WPF()->current_userid );
		if( $root_exists ) {
			$post['root'] = ( $parentid ) ? ( isset( $root ) ? intval( $root ) : $this->get_root( $parentid ) ) : - 1;
		} else {
			$root = null;
		}

		$post = apply_filters( 'wpforo_add_post_data_filter', $post );

		if( empty( $post ) ) return false;

		extract( $post, EXTR_OVERWRITE );

		if( isset( $forumid ) ) $forumid = intval( $forumid );
		if( isset( $topicid ) ) $topicid = intval( $topicid );
		if( isset( $parentid ) ) $parentid = intval( $parentid );
		if( isset( $title ) ) $title = sanitize_text_field( trim( $title ) );
		if( isset( $created ) ) $created = sanitize_text_field( $created );
		if( isset( $userid ) ) $userid = intval( $userid );
		if( isset( $body ) ) $body = wpforo_kses( trim( $body ), 'post' );
		$status  = ( isset( $status ) && $status ? 1 : 0 );
		$private = ( isset( $topic['private'] ) && $topic['private'] ? 1 : 0 );
		if( isset( $name ) ) $name = strip_tags( trim( $name ) );
		if( isset( $email ) ) $email = strip_tags( trim( $email ) );

		do_action( 'wpforo_before_add_post', $post );

		$fields = [
			'forumid'  => $forumid,
			'topicid'  => $topicid,
			'parentid' => $parentid,
			'userid'   => $userid,
			'title'    => stripslashes( $title ),
			'body'     => stripslashes( $body ),
			'created'  => $created,
			'modified' => $created,
			'status'   => $status,
			'private'  => $private,
			'name'     => $name,
			'email'    => $email,
			'root'     => $root,
		];

		$values = [ '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d' ];

		if( ! $root_exists ) {
			unset( $fields['root'] );
			unset( $fields[12] );
		}

		if( WPF()->db->insert(
			WPF()->tables->posts,
			$fields,
			$values
		) ) {
			$postid = WPF()->db->insert_id;

			$post['postid']  = $postid;
			$post['status']  = $status;
			$post['private'] = $private;
			$post['posturl'] = $this->get_post_url( $postid );

			if( $root_exists ) WPF()->topic->rebuild_threads( $topic, $root );

			do_action( 'wpforo_after_add_post', $post, $topic, $forum );

			wpforo_clean_cache( 'post', $postid, $post );
			WPF()->notice->add( 'You successfully replied', 'success' );

			return $postid;
		}

		WPF()->notice->add( 'Reply request error', 'error' );

		return false;
	}

	public function edit( $args = [] ) {

		//This variable will be based on according CAN of guest usergroup once Guest Posing is ready
		$guestposting = false;

		if( empty( $args ) && ( ! isset( $_REQUEST['post'] ) || empty( $_REQUEST['post'] ) ) ) return false;
		if( empty( $args ) && ! empty( $_REQUEST['post'] ) ) $args = $_REQUEST['post'];
		if( isset( $args['name'] ) ) {
			$args['name'] = strip_tags( $args['name'] );
		}
		if( isset( $args['email'] ) ) {
			$args['email'] = sanitize_email( $args['email'] );
		}

		do_action( 'wpforo_start_edit_post', $args );

		if( ! isset( $args['postid'] ) || ! $args['postid'] || ! is_numeric( $args['postid'] ) ) {
			WPF()->notice->add( 'Cannot update post data', 'error' );

			return false;
		}
		$args['postid'] = intval( $args['postid'] );
		if( ! $post = $this->get_post( $args['postid'] ) ) {
			WPF()->notice->add( 'No Posts found for update', 'error' );

			return false;
		}
		if( ! $topic = WPF()->topic->get_topic( $post['topicid'] ) ) {
			WPF()->notice->add( 'No Topic found for update', 'error' );

			return false;
		}
		if( ! $forum = WPF()->forum->get_forum( $topic['forumid'] ) ) {
			WPF()->notice->add( 'No Forum found for update', 'error' );

			return false;
		}

		if( ! is_user_logged_in() ) {
			if( ! isset( $post['email'] ) || ! $post['email'] ) {
				WPF()->notice->add( 'Permission denied', 'error' );

				return false;
			} elseif( ! wpforo_current_guest( $post['email'] ) ) {
				WPF()->notice->add( 'You are not allowed to edit this post', 'error' );

				return false;
			}
			if( ! $args['name'] || ! $args['email'] ) {
				WPF()->notice->add( 'Please insert required fields!', 'error' );

				return false;
			} else {
				WPF()->member->set_guest_cookies( $args );
			}
		}

		$args['userid'] = $post['userid'];
		$args['status'] = $post['status'];

		if( isset( $args['userid'] ) && $args['userid'] == 0 && isset( $args['name'] ) && isset( $args['email'] ) ) $guestposting = true;

		$args = apply_filters( 'wpforo_edit_post_data_filter', $args );
		if( empty( $args ) ) return false;

		extract( $args, EXTR_OVERWRITE );

		if( ! $guestposting ) {
			$diff = current_time( 'timestamp', 1 ) - strtotime( $post['created'] );
			if( ! ( WPF()->perm->forum_can( 'er', $post['forumid'] ) || ( WPF()->current_userid == $post['userid'] && WPF()->perm->forum_can( 'eor', $post['forumid'] ) ) ) ) {
				WPF()->notice->add( 'You don\'t have permission to edit post from this forum', 'error' );

				return false;
			}

			if( ! WPF()->perm->forum_can( 'er', $post['forumid'] ) && wpforo_setting( 'posting', 'edit_own_post_durr' ) !== 0 && $diff > wpforo_setting( 'posting', 'edit_own_post_durr' ) ) {
				WPF()->notice->add( 'The time to edit this post is expired.', 'error' );

				return false;
			}
		}

		$title = ( isset( $title ) ? wpforo_text( trim( $title ), 250, false ) : '' );
		$body  = ( isset( $body ) ? preg_replace( '#</pre>[\r\n\t\s\0]*<pre>#isu', "\r\n", $body ) : '' );
		$body  = wpforo_kses( trim( $body ), 'post' );

		$topicid = wpforo_bigintval( ( isset( $topicid ) ? $topicid : $post['topicid'] ) );
		$title   = trim( $title ) ? stripslashes( sanitize_text_field( trim( $title ) ) ) : stripslashes( $post['title'] );
		$body    = $body ? stripslashes( $body ) : stripslashes( $post['body'] );
		$status  = isset( $status ) ? intval( $status ) : intval( $post['status'] );
		$private = isset( $private ) ? intval( $private ) : intval( $post['private'] );
		$name    = isset( $name ) ? stripslashes( strip_tags( trim( $name ) ) ) : stripslashes( $post['name'] );
		$email   = isset( $email ) ? stripslashes( strip_tags( trim( $email ) ) ) : stripslashes( $post['email'] );

		if( false !== WPF()->db->update( WPF()->tables->posts,
		                                 [
			                                 'title'    => $title,
			                                 'body'     => $body,
			                                 'modified' => current_time(
				                                 'mysql',
				                                 1
			                                 ),
			                                 'status'   => $status,
			                                 'name'     => $name,
			                                 'email'    => $email,
		                                 ],
		                                 [ 'postid' => $postid ],
		                                 [ '%s', '%s', '%s', '%d', '%s', '%s' ],
		                                 [ '%d' ] ) ) {
			$post['topicid'] = $topicid;
			$post['title']   = $title;
			$post['body']    = $body;
			$post['status']  = $status;
			$post['private'] = $private;
			$post['name']    = $name;
			$post['email']   = $email;
			do_action( 'wpforo_after_edit_post', $post, $topic, $forum, $args );

			wpforo_clean_cache( 'post', $postid, $post );
			WPF()->notice->add( 'This post successfully edited', 'success' );

			return $postid;
		}

		WPF()->notice->add( 'Reply request error', 'error' );

		return false;
	}

	#################################################################################

	/**
	 * Delete post from DB
	 * Returns true if successfully deleted or false.
	 *
	 * @param int $postid
	 * @param bool $delete_cache
	 * @param bool $rebuild_data
	 * @param array &$exclude
	 * @param bool $check_permissions
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	function delete( $postid, $delete_cache = true, $rebuild_data = true, &$exclude = [], $check_permissions = true ) {
		$postid  = intval( $postid );
		$exclude = (array) $exclude;

		if( ! $post = $this->get_post( $postid ) ) return true;

		do_action( 'wpforo_before_delete_post', $post );

		$diff = current_time( 'timestamp', 1 ) - strtotime( $post['created'] );
		if( $check_permissions && ( ! ( WPF()->perm->forum_can( 'dr', $post['forumid'] ) || ( WPF()->current_userid == $post['userid'] && WPF()->perm->forum_can( 'dor', $post['forumid'] ) ) ) ) ) {
			WPF()->notice->add( 'You don\'t have permission to delete post from this forum', 'error' );

			return false;
		}

		if( $check_permissions && ( ! WPF()->perm->forum_can( 'dr', $post['forumid'] ) && wpforo_setting( 'posting', 'delete_own_post_durr' ) !== 0 && $diff > wpforo_setting( 'posting', 'delete_own_post_durr' ) ) ) {
			WPF()->notice->add( 'The time to delete this post is expired.', 'error' );

			return false;
		}
		//Find and delete default attachments before deleting post
		$this->delete_attachments( $postid );

		//Delete post
		if( WPF()->db->delete( WPF()->tables->posts, [ 'postid' => $postid ], [ '%d' ] ) ) {
			$layout = WPF()->forum->get_layout( $post['forumid'] );

			if( isset( $post['parentid'] ) ) {
				if( ! $post['is_first_post'] && $layout === 4 ) {
					if( $post['parentid'] == 0 ) {
						$replies = WPF()->db->get_results( "SELECT `postid` FROM `" . WPF()->tables->posts . "` WHERE `root` = " . wpforo_bigintval( $postid ), ARRAY_A );
					} else {
						$children = [];
						$replies  = $this->get_children( $postid, $children, true );
					}
					if( ! empty( $replies ) ) {
						foreach( $replies as $reply ) {
							if( ! in_array( $reply['postid'], $exclude ) ) {
								$exclude[] = $reply['postid'];
								$this->delete( $reply['postid'], false, false, $exclude, false );
							}
						}
					}
				} elseif( $post['parentid'] != 0 ) {
					WPF()->db->query( "UPDATE `" . WPF()->tables->posts . "` SET `parentid` = " . wpforo_bigintval( $post['parentid'] ) . " WHERE `parentid` = " . wpforo_bigintval( $postid ) );
				}
			}

			if( $rebuild_data && ! $post['is_first_post'] && $layout === 4 ) WPF()->topic->rebuild_threads( $post['topicid'] );

			do_action( 'wpforo_after_delete_post', $post );

			WPF()->notice->add( 'This post successfully deleted', 'success' );

			if( $post['is_first_post'] ) return WPF()->topic->delete( $post['topicid'], true, $check_permissions );
			if( $delete_cache ) wpforo_clean_cache( 'post', $postid, $post );

			return true;
		}

		WPF()->notice->add( 'Post delete error', 'error' );
		return false;
	}

	/**
	 * @param int $postid
	 * @param bool $protect
	 *
	 * @return array
	 * @since 1.0.0
	 *
	 */
	public function _get_post( $postid, $protect = true ) {
		$sql  = "SELECT * FROM `" . WPF()->tables->posts . "` WHERE `postid` = %d";
		$post = (array) WPF()->db->get_row( WPF()->db->prepare( $sql, $postid ), ARRAY_A );

		if( ! empty( $post ) ) $post['userid'] = wpforo_bigintval( $post['userid'] );

		if( $protect ) {
			if( isset( $post['forumid'] ) && $post['forumid'] && ! WPF()->perm->forum_can( 'vf', $post['forumid'] ) ) {
				return [];
			}
			if( isset( $post['status'] ) && $post['status'] && ! wpforo_is_owner( $post['userid'], $post['email'] ) ) {
				if( isset( $post['forumid'] ) && $post['forumid'] && ! WPF()->perm->forum_can( 'au', $post['forumid'] ) ) {
					return [];
				}
			}
		}

		$post['url'] = $this->get_post_url( $post );

		return apply_filters( 'wpforo_get_post', $post );
	}

	public function get_post( $postid, $protect = true ) {
		return wpforo_ram_get( [ $this, '_get_post' ], $postid, $protect );
	}


	/**
	 * get all posts based on provided arguments
	 *
	 * @param array $args
	 * @param int $items_count
	 * @param bool $count
	 *
	 * @return    array
	 * @since 1.0.0
	 *
	 */
	function get_posts( $args = [], &$items_count = 0, $count = true ) {

		$cache = WPF()->cache->on();

		$default = [
			'include'          => [],        // array( 2, 10, 25 )
			'exclude'          => [],        // array( 2, 10, 25 )
			'forumids'         => [],
			'topicid'          => null,        // topic id in DB
			'forumid'          => null,        // forum id in DB
			'parentid'         => null,        // parent post id
			'root'             => null,        // root postid
			'userid'           => null,        // user id in DB
			'orderby'          => '`is_first_post` DESC, `created` ASC, `postid` ASC',    // forumid, order, parentid
			'order'            => '',            // ASC DESC
			'offset'           => null,        // this use when you give row_count
			'row_count'        => null,        // 4 or 1 ...
			'status'           => null,        // 0 or 1 ...
			'private'          => null,        // 0 or 1 ...
			'email'            => null,        // example@example.com ...
			'check_private'    => true,
			'where'            => null,
			'owner'            => null,
			'cache_type'       => 'sql',       // sql or args
			'limit_per_topic'  => null,
			'union_first_post' => false,
			'is_first_post'    => null,
			'is_answer'        => null,
			'threaded'         => false,
		];

		$request = $args;
		if( empty( $args['orderby'] ) ) $args['order'] = '';

		$args = wpforo_parse_args( $args, $default );

		if( $args['row_count'] === 0 ) return [];
		if( $args['forumid'] && $args['check_private'] && ! WPF()->perm->forum_can( 'vf', $args['forumid'] ) ) return [];
		if( strtoupper( $args['order'] ) != 'DESC' && strtoupper( $args['order'] ) != 'ASC' ) $args['order'] = '';
		if( ! wpforo_root_exist() && ! is_null( $args['root'] ) ) {
			$args['parentid'] = $args['root'];
			$args['root']     = null;
		}

		$wheres = $this->get_posts_conditions( $args );

		$ordering = ( $args['orderby'] ? " ORDER BY " . esc_sql( $args['orderby'] . ' ' . $args['order'] ) : '' );
		$limiting = ( $args['row_count'] ? " LIMIT " . intval( $args['offset'] ) . "," . intval( $args['row_count'] ) : '' );

		if( $limit_per_topic = intval( $args['limit_per_topic'] ) ) {
			$sql = "SELECT SUBSTRING_INDEX( GROUP_CONCAT(`postid` ORDER BY `created` DESC), ',', " . $limit_per_topic . " ) postids
					FROM `" . WPF()->tables->posts . "` " . ( $wheres ? " WHERE " . implode( " AND ", $wheres ) : '' ) . " GROUP BY `topicid` ORDER BY MAX(`postid`) DESC " . $limiting;

			if( $cache ) {
				if( $args['cache_type'] === 'sql' ) {
					$object_key   = md5( $sql . WPF()->current_user_groupid );
					$object_cache = WPF()->cache->get( $object_key );
					if( ! empty( $object_cache ) ) {
						return $object_cache['items'];
					}
				}
			}

			// Returns an array of post IDs ////////////////////////////////
			$posts = WPF()->db->get_col( $sql );
			////////////////////////////////////////////////////////////////

		} else {

			$sql = "SELECT * FROM `" . WPF()->tables->posts . "`";
			if( ! empty( $wheres ) ) {
				$sql .= " WHERE " . implode( " AND ", $wheres );
			}
			if( $count ) {
				$item_count_sql = preg_replace( '#SELECT.+?FROM#isu', 'SELECT count(*) FROM', $sql, 1 );
				if( $item_count_sql ) $items_count = WPF()->db->get_var( $item_count_sql );
			}

			$sql .= $ordering . $limiting;

			if( $args['union_first_post'] && $args['topicid'] && ! $args['parentid'] && $items_count > intval( $args['offset'] ) ) {
				$sql = "( SELECT * FROM `" . WPF()->tables->posts . "` 
				WHERE `topicid` = " . wpforo_bigintval( $args['topicid'] ) . " 
				AND `is_first_post` = 1 ) 
				UNION 
				( " . $sql . " )";
			}

			if( $cache ) {
				if( $args['cache_type'] == 'sql' ) {
					$object_key   = md5( $sql . WPF()->current_user_groupid );
					$object_cache = WPF()->cache->get( $object_key );
					if( ! empty( $object_cache ) ) {
						return $object_cache['items'];
					}
				}
				if( $args['cache_type'] == 'args' ) {
					$hach           = serialize( $request );
					$cache_args_key = md5( $hach . WPF()->current_user_groupid );
					$object_cache   = WPF()->cache->get( $cache_args_key, 'loop', 'post' );
					if( ! empty( $object_cache ) ) {
						return $object_cache['items'];
					}
				}
			}

			// Returns an array of posts /////////////////////////////////
			$posts = WPF()->db->get_results( $sql, ARRAY_A );
			//////////////////////////////////////////////////////////////

			$posts = apply_filters( 'wpforo_get_posts', $posts );

			if( $args['check_private'] && ! $args['forumid'] ) {
				$posts = $this->access_filter( $posts );
			}
		}

		if( $cache && isset( $object_key ) && ! empty( $posts ) ) {
			self::$cache['posts'][ $object_key ]['items']       = $posts;
			self::$cache['posts'][ $object_key ]['items_count'] = $items_count;
			if( isset( $cache_args_key ) && $args['cache_type'] == 'args' ) {
				WPF()->cache->create_custom( $request, $posts, 'post', $items_count );
			}
		}

		return $posts;
	}

	function get_posts_conditions( $args = [] ) {

		$wheres          = [];
		$table_as_prefix = '`' . WPF()->tables->posts . '`.';

		$args['include']  = wpforo_parse_args( $args['include'] );
		$args['exclude']  = wpforo_parse_args( $args['exclude'] );
		$args['forumids'] = wpforo_parse_args( $args['forumids'] );

		if( ! empty( $args['include'] ) ) $wheres[] = $table_as_prefix . "`postid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['include'] ) ) . ")";
		if( ! empty( $args['exclude'] ) ) $wheres[] = $table_as_prefix . "`postid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['exclude'] ) ) . ")";
		if( ! empty( $args['forumids'] ) ) $wheres[] = $table_as_prefix . "`forumid` IN(" . implode( ',', array_map( 'intval', $args['forumids'] ) ) . ")";

		if( ! is_null( $args['topicid'] ) ) $wheres[] = $table_as_prefix . "`topicid` = " . wpforo_bigintval( $args['topicid'] );
		if( ! is_null( $args['parentid'] ) ) $wheres[] = $table_as_prefix . "`parentid` = " . wpforo_bigintval( $args['parentid'] );
		if( ! is_null( $args['root'] ) ) $wheres[] = $table_as_prefix . "`root` = " . wpforo_bigintval( $args['root'] );
		if( ! is_null( $args['userid'] ) ) $wheres[] = $table_as_prefix . "`userid` = " . wpforo_bigintval( $args['userid'] );
		if( ! is_null( $args['status'] ) ) $wheres[] = $table_as_prefix . "`status` = " . intval( (bool) $args['status'] );
		if( ! is_null( $args['private'] ) ) $wheres[] = $table_as_prefix . "`private` = " . intval( (bool) $args['private'] );
		if( ! is_null( $args['is_first_post'] ) ) $wheres[] = $table_as_prefix . "`is_first_post` = " . intval( (bool) $args['is_first_post'] );
		if( ! is_null( $args['is_answer'] ) ) $wheres[] = $table_as_prefix . "`is_answer` = " . intval( (bool) $args['is_answer'] );
		if( ! is_null( $args['email'] ) ) $wheres[] = $table_as_prefix . "`email` = '" . esc_sql( $args['email'] ) . "' ";
		if( ! is_null( $args['where'] ) ) $wheres[] = $table_as_prefix . $args['where'];

		if( wpfval( $args, 'forumid' ) && $args['check_private'] ) {

			/////Check "View Reply" Access//////////////////////////////
			if( ! WPF()->perm->forum_can( 'vr', $args['forumid'] ) ) {
				$wheres[] = $table_as_prefix . " `is_first_post` = 1";
			}

			/////Check Unapproved Post Access////////////////////////////
			if( WPF()->perm->forum_can( 'au', $args['forumid'] ) ) {
				//Check "Can Approve/Unapprove Posts" Access (View Unapproved Posts)
				if( ! is_null( $args['status'] ) ) $wheres[] = $table_as_prefix . " `status` = " . intval( $args['status'] );
			} elseif( WPF()->current_userid ) {
				//Allow Users see own unapproved posts
				$wheres[] = " ( " . $table_as_prefix . "`status` = 0 OR (" . $table_as_prefix . "`status` = 1 AND " . $table_as_prefix . "`userid` = " . intval( WPF()->current_userid ) . ") )";
			} elseif( WPF()->current_user_email ) {
				//Allow Guests see own unapproved posts
				$wheres[] = " ( " . $table_as_prefix . "`status` = 0 OR (" . $table_as_prefix . "`status` = 1 AND " . $table_as_prefix . "`email` = '" . sanitize_email( WPF()->current_user_email ) . "') )";
			} else {
				//If doesn't have "Can Approve/Unapprove Posts" access and not Owner, only return approved posts
				$wheres[] = " " . $table_as_prefix . "`status` = 0";
			}
		}

		return $wheres;
	}

	function access_filter( $posts, $user = null ) {
		if( ! empty( $posts ) ) {
			foreach( $posts as $key => $post ) {
				if( ! $this->view_access( $post, $user ) ) unset( $posts[ $key ] );
			}
		}

		return $posts;
	}

	function view_access( $post, $user = null ) {
		$groupids = wpfval( $user, 'groupids' );
		if(
			! WPF()->perm->forum_can( 'vf', $post['forumid'], $groupids )
			|| ! WPF()->perm->forum_can( 'vt', $post['forumid'], $groupids )
			|| ( ! (int) wpfval( $post, 'is_first_post' ) && ! WPF()->perm->forum_can( 'vr', $post['forumid'], $groupids ) )
		) return false;

		if( (int) wpfval( $post, 'status' ) && ! WPF()->perm->forum_can( 'au', $post['forumid'], $groupids ) ){
			$post_owner  = wpforo_member( $post );
			if( ! wpforo_is_users_same( $post_owner, $user ) ) return false;
		}

		$topic = wpforo_topic( $post['topicid'] );
		if(
			(int) wpfval( $topic, 'private' )
			&& ! WPF()->perm->forum_can( 'vp', $post['forumid'], $groupids )
		){
			$topic_owner = wpforo_member( $topic );
			if( ! wpforo_is_users_same( $topic_owner, $user ) ) return false;
		}

		return true;
	}

	function replies( array $posts, $topic = [], $forum = [], $level = 0 ) {
		$level ++;
		if( function_exists( 'wpforo_thread_reply' ) ) {
			if( wpfval( $posts, 'posts' ) ) {
				$key       = key( $posts['posts'] );
				$parentid  = ( wpfval( $posts, 'posts', $key, 'parentid' ) ) ? $posts['posts'][ $key ]['parentid'] : 0;
				$max_level = wpforo_setting( 'topics', 'layout_threaded_nesting_level' );
				if( ! $max_level ) {
					$class = ( $level > 1 ) ? '' : ' level-1';
				} elseif( $level > ( $max_level + 1 ) ) {
					$class = '';
				} else {
					$class = ' level-' . $level;
				}
				echo '<div id="wpf-post-replies-' . intval( $parentid ) . '" class="wpf-post-replies ' . $class . '">';
				foreach( $posts['posts'] as $post ) {
					$parents = ( wpfval( $posts, 'parents' ) ) ? $posts['parents'] : [];
					wpforo_thread_reply( $post, $topic, $forum, $level, $parents );
					if( ! empty( $post['children'] ) ) {
						$posts['posts'] = $post['children'];
						$this->replies( $posts, $topic, $forum, $level );
					}
				}
				echo '</div>';
			}
		} else {
			wpforo_phrase( 'Function wpforo_thread_reply() not found.' );
		}
	}

	function get_thread_tree( $post, $parents = true ) {
		if( ! wpfval( $post, 'postid' ) || ( wpfkey( $post, 'root' ) && $post['root'] == - 1 ) ) {
			return [ 'posts' => [], 'parents' => [], 'count' => 0, 'children' => '' ];
		}

		$items    = [];
		$thread   = [];
		$parentid = (int) $post['postid'];
		$type     = apply_filters( 'wpforo_thread_builder_type', 'topic-query' ); //'topic-query', 'inside-mysql', 'multi-query'
		if( $type === 'topic-query' ) {
			if( wpfval( $post, 'topicid' ) ) {
				$args  = [ 'root' => $post['postid'], 'orderby' => '`created` ASC' ];
				$posts = $this->get_posts( $args, $items_count, false );
				if( empty( $posts ) ) {
					$args  = [ 'topicid' => $post['topicid'], 'orderby' => '`created` ASC' ];
					$posts = $this->get_posts( $args, $items_count, false );
				}

				if( ! empty( $posts ) ) {
					foreach( $posts as $post ) {
						$items[ $post['postid'] ] = $post;
					}
					$thread = $this->build_thread_data( $parentid, $items );
				}
			}
		} elseif( $type === 'inside-mysql' ) {
			$mod = wpforo_current_user_is( 'admin' ) || wpforo_current_user_is( 'moderator' );
			if( wpforo_is_db_mysql8() ) {
				$sql   = "WITH RECURSIVE `post_path` AS (
					SELECT `postid`, `parentid`, `userid`, `status`, `email`
						FROM `" . WPF()->tables->posts . "`
						WHERE `parentid` = %d
					UNION
					SELECT p.`postid`, p.`parentid`, p.`userid`, p.`status`, p.`email`
						FROM `" . WPF()->tables->posts . "` p
						INNER JOIN `post_path` pp ON pp.`parentid` <> pp.`postid` AND pp.`postid` = p.`parentid`
				) SELECT CONCAT( `postid`, '-', `parentid`, '-', `userid`, '-', `status`, '-', `email` ) AS tree FROM `post_path`";
				$posts = WPF()->db->get_col( WPF()->db->prepare( $sql, $parentid ) );
			} else {
				$sql   = "SELECT GROUP_CONCAT( @id :=  ( 
	                                SELECT  GROUP_CONCAT(postid,'-', parentid, '-', userid, '-', status, '-', email)  
	                                    FROM  `" . WPF()->tables->posts . "` 
	                                    WHERE parentid = @id 
	                                ) 
	                    ) AS tree
                      FROM ( SELECT  @id := %d ) vars 
                      STRAIGHT_JOIN `" . WPF()->tables->posts . "` 
                      WHERE @id IS NOT NULL";
				$posts = explode( ',', (string) WPF()->db->get_var( WPF()->db->prepare( $sql, $parentid ) ) );
			}
			if( ! empty( $posts ) ) {
				foreach( $posts as $post ) {
					$post = explode( '-', $post );
					if( ! $mod && isset( $post[3] ) && $post[3] ) {
						if( isset( $post[2] ) && isset( $post[4] ) && ( isset( WPF()->current_user['userid'] ) || isset( WPF()->current_user['user_email'] ) ) ) {
							if( WPF()->current_user['userid'] != $post[2] && WPF()->current_user['user_email'] != $post[4] ) continue;
						}
					}
					if( isset( $post[0] ) && isset( $post[1] ) ) {
						$items[ $post[0] ] = [ 'postid' => $post[0], 'parentid' => $post[1] ];
					}
				}
				$thread = $this->build_thread_data( $parentid, $items );
			}
		} elseif( $type === 'multi-query' ) {
			$mod    = wpforo_current_user_is( 'admin' ) || wpforo_current_user_is( 'moderator' );
			$items  = $this->get_children( $parentid, $children, $mod );
			$thread = $this->build_thread_data( $parentid, $items );
		}

		return $thread;
	}

	function build_thread_data( $parentid, $items = [], $count = 0 ) {
		$parents = [];
		$thread  = [ 'posts' => [], 'parents' => [], 'count' => 0, 'children' => '' ];

		if( ! empty( $items ) ) {
			foreach( $items as $item ) {
				$parents[ $item['postid'] ] = $this->parents( $item['postid'], $items );
			}
			if( ! empty( $parents ) ) $thread['parents'] = $parents;
			$thread['posts']    = $this->build_thread_tree( $items, $parentid );
			$children           = $this->children( $parentid, $thread['posts'] );
			$thread['count']    = count( $children );
			$thread['children'] = array_keys( $children );
		}

		return $thread;
	}

	function build_thread_tree( array $posts, $parentid = 0 ) {
		$tree = [];
		foreach( $posts as $post ) {
			if( $post['parentid'] == $parentid ) {
				$children = $this->build_thread_tree( $posts, $post['postid'] );
				if( $children ) {
					$post['children'] = $children;
				}
				$tree[] = $post;
			}
		}

		return $tree;
	}

	function root( $postid, $parentid = null ) {
		if( ! $postid ) return 0;
		$parents = $this->get_parents( $postid, $parentid );
		$root    = array_pop( $parents );

		return intval( $root );
	}

	function get_root( $postid ) {
		if( ! $postid || ! wpforo_root_exist() ) return $postid;
		$root = WPF()->db->get_var( "SELECT `root` FROM `" . WPF()->tables->posts . "` WHERE  `postid` = " . intval( $postid ) );
		if( ! is_null( $root ) && ( $root <= 0 || $root == $postid ) ) {
			$root = $postid;
		} else {
			$root = $this->root( $postid );
		}

		return $root;
	}

	function parents( $postid, $posts, $parents = [] ) {
		if( ! empty( $posts ) ) {
			if( isset( $posts[ $postid ] ) ) {
				$parentid = wpfval( $posts[ $postid ], 'parentid' ) ? $posts[ $postid ]['parentid'] : 0;
				if( $parentid > 0 ) {
					array_unshift( $parents, $parentid );

					return $this->parents( $parentid, $posts, $parents );
				}
			}
		}

		return $parents;
	}

	function get_parents( $postid, $parentid = null, &$parents = [], $mod = false ) {
		if( $postid ) {
			$status = ( ! $mod ) ? ' AND `status` = 0 ' : '';
			if( is_null( $parentid ) ) {
				$where = "`postid` = " . intval( $postid );
			} else {
				$where = "`postid` = " . intval( $parentid );
			}
			if( $parentid === 0 ) {
				return $parents;
			} else {
				$post = WPF()->db->get_row( "SELECT `postid`, `parentid` FROM `" . WPF()->tables->posts . "` WHERE  " . $where . $status, ARRAY_A );
				if( wpfval( $post, 'parentid' ) ) {
					$parents[ $post['postid'] ] = $post['parentid'];
					$this->get_parents( $post['postid'], $post['parentid'], $parents, $mod );
				}
			}
		}

		return $parents;
	}

	function children( $parentid, $posts, &$children = [] ) {
		if( $parentid ) {
			if( ! empty( $posts ) ) {
				foreach( $posts as $post ) {
					$children[ $post['postid'] ] = [ 'postid' => $post['postid'], 'parentid' => $post['parentid'] ];
					if( isset( $post['children'] ) ) $this->children( $post['postid'], $post['children'], $children );
				}
			}
		}

		return $children;
	}

	function get_children( $parentid, &$children = [], $mod = false ) {
		if( $parentid ) {
			$status = ( ! $mod ) ? ' AND `status` = 0 ' : '';
			$posts  = WPF()->db->get_results( "SELECT `postid`, `parentid` FROM `" . WPF()->tables->posts . "` FORCE INDEX (PRIMARY) WHERE `parentid` = " . intval( $parentid ) . " " . $status, ARRAY_A );
			if( ! empty( $posts ) ) {
				foreach( $posts as $post ) {
					$children[ $post['postid'] ] = [ 'postid' => $post['postid'], 'parentid' => $post['parentid'] ];
					$this->get_children( $post['postid'], $children, $mod );
				}
			}
		}

		return $children;
	}

	function get_root_replies_count( $postid ) {
		$postid = intval( $postid );
		if( $postid && wpforo_root_exist() ) {
			return (int) WPF()->db->get_var( "SELECT COUNT(*) FROM `" . WPF()->tables->posts . "` WHERE `root` = " . $postid );
		} else {
			return (int) WPF()->db->get_var( "SELECT COUNT(*) FROM `" . WPF()->tables->posts . "` WHERE `parentid` = " . $postid );
		}
	}

	function get_posts_filtered( $args = [] ) {
		$posts = $this->get_posts( $args, $items_count, false );
		if( ! empty( $posts ) ) {
			foreach( $posts as $key => $post ) {
				if( isset( $post['forumid'] ) && ! WPF()->perm->forum_can( 'vf', $post['forumid'] ) ) {
					unset( $posts[ $key ] );
				}
				if( isset( $posts[ $key ] ) && isset( $post['forumid'] ) && isset( $post['private'] ) && $post['private'] && ! wpforo_is_owner( $post['userid'], $post['email'] ) ) {
					if( ! WPF()->perm->forum_can( 'vp', $post['forumid'] ) ) {
						unset( $posts[ $key ] );
					}
				}
				if( isset( $posts[ $key ] ) && isset( $post['forumid'] ) && isset( $post['status'] ) && $post['status'] && ! wpforo_is_owner( $post['userid'], $post['email'] ) ) {
					if( ! WPF()->perm->forum_can( 'au', $post['forumid'] ) ) {
						unset( $posts[ $key ] );
					}
				}
			}
		}

		return $posts;
	}

	function search( $args = [], &$items_count = 0 ) {
		if( ! is_array( $args ) ) $args = [ 'needle' => $args ];
		if( ! wpfval( $args, 'needle' ) && ! wpfval( $args, 'postids' ) ) return [];

		$args = array_filter( $args );

		$default = [
			'needle'      => '',                 // search needle
			'forumids'    => [],         // array( 2, 10, 25 )
			'postids'     => [],         // array( 2, 10, 25 )
			'date_period' => 0,                 // topic id in DB
			'type'        => 'entire-posts',     // search type ( entire-posts | titles-only | user-posts | user-topics | tag )
			'orderby'     => 'relevancy',      // Sort Search Results by ( relevancy | date | user | forum )
			'order'       => 'DESC',             // Sort Search Results ( ASC | DESC )
			'offset'      => null,             // this use when you give row_count
			'row_count'   => null             // 4 or 1 ...
		];

		$args            = wpforo_parse_args( $args, $default );
		$args['postids'] = wpforo_parse_args( $args['postids'] );
		$args['postids'] = array_filter( array_map( 'wpforo_bigintval', $args['postids'] ) );

		$args['order'] = strtoupper( $args['order'] );
		if( ! in_array( $args['order'], [ 'ASC', 'DESC' ] ) ) $args['order'] = 'DESC';

		$date_period = intval( $args['date_period'] );

		$fa         = "p";
		$from       = "`" . WPF()->tables->posts . "` " . $fa;
		$selects    = [ $fa . '.`postid`', $fa . '.`topicid`', $fa . '.`private`', $fa . '.`status`', $fa . '.`forumid`', $fa . '.`userid`', $fa . '.`title`', $fa . '.`created`', $fa . '.`body`', $fa . '.`is_first_post`' ];
		$innerjoins = [];
		$wheres     = [];
		$orders     = [];

		if( $args['forumids'] ) $wheres[] = $fa . ".`forumid` IN(" . implode( ', ', array_map( 'intval', $args['forumids'] ) ) . ")";
		if( $date_period != 0 ) {
			$date = date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ) - ( $date_period * 24 * 60 * 60 ) );
			if( $date ) $wheres[] = $fa . ".`created` > '" . esc_sql( $date ) . "'";
		}

		if( $args['needle'] ) {
			if( in_array( $args['type'], [ 'entire-posts', 'titles-only' ] ) ) {
				$words  = preg_split( '#[^\p{L}\p{N}\'`]+#u', $args['needle'] );
				$words  = array_slice( array_filter( $words ), 0, 10 );
				$words  = array_map( function( $w ) {
					return '+' . esc_sql( str_replace( [ '(', ')', '*', '+', '-', '~', '<', '>', '@', '"' ], '', $w ) ) . '*';
				}, $words );
				$needle = implode( ' ', $words );
			} else {
				$needle = esc_sql( $args['needle'] );
			}

			if( $args['type'] === 'entire-posts' ) {
				$selects[] = "MATCH(" . $fa . ".`title`) AGAINST('$needle' IN BOOLEAN MODE) + MATCH(" . $fa . ".`body`) AGAINST('$needle' IN BOOLEAN MODE) AS matches";
				$wheres[]  = "( MATCH(" . $fa . ".`title`, " . $fa . ".`body`) AGAINST('$needle' IN BOOLEAN MODE) OR " . $fa . ".`title` LIKE '%" . esc_sql( $args['needle'] ) . "%' OR " . $fa . ".`body` LIKE '%" . esc_sql( $args['needle'] ) . "%' )";
				$orders[]  = "matches";
				$orders[]  = "`created`";
			} elseif( $args['type'] === 'titles-only' ) {
				$selects[] = "MATCH(" . $fa . ".`title`) AGAINST('$needle' IN BOOLEAN MODE) AS matches";
				$wheres[]  = "( MATCH(" . $fa . ".`title`) AGAINST('$needle' IN BOOLEAN MODE) OR " . $fa . ".`title` LIKE '%" . esc_sql( $args['needle'] ) . "%' )";
				$orders[]  = "matches";
				$orders[]  = "`created`";
			} elseif( $args['type'] === 'user-posts' || $args['type'] === 'user-topics' ) {
				$innerjoins[] = "INNER JOIN `" . WPF()->db->users . "` u ON u.`ID` = " . $fa . ".`userid`";
				$wheres[]     = "( u.`user_nicename` LIKE '%{$needle}%' OR u.`display_name` LIKE '%{$needle}%' )";
				if( $args['type'] === 'user-topics' ) $wheres[] = "" . $fa . ".`is_first_post` = 1";
			} elseif( $args['type'] === 'tag' ) {
				$fa         = "t";
				$from       = "`" . WPF()->tables->topics . "` " . $fa;
				$selects    = [ $fa . '.`first_postid` AS postid', $fa . '.`topicid`', $fa . '.`private`', $fa . '.`status`', $fa . '.`forumid`', $fa . '.`userid`', $fa . '.`title`', $fa . '.`created`', '1 AS `is_first_post`' ];
				$innerjoins = [];
				$wheres     = [ "( " . $fa . ".`tags` LIKE '%{$needle}%' )" ];
				//              $wheres = array( "( FIND_IN_SET('{$needle}', ".$fa.".`tags`) )" ); //exact version
			}
		}

		if( $args['postids'] ) $wheres[] = "(" . $fa . ".`postid` IN(" . implode( ',', $args['postids'] ) . "))";

		if( $args['orderby'] === 'date' ) {
			$orders = [ $fa . '.`created`' ];
		} elseif( $args['orderby'] === 'user' ) {
			$orders = [ $fa . '.`userid`' ];
		} elseif( $args['orderby'] === 'forum' ) {
			$orders = [ $fa . '.`forumid`' ];
		}

		$sql = "SELECT COUNT(*) FROM " . $from . " " . implode( ' ', $innerjoins );
		if( $wheres ) $sql .= " WHERE " . implode( " AND ", $wheres );
		$items_count = (int) WPF()->db->get_var( $sql );
		if( wpforo_setting( 'topics', 'search_max_results' ) && $items_count > wpforo_setting( 'topics', 'search_max_results' ) ) $items_count = wpforo_setting( 'topics', 'search_max_results' );

		$sql = "SELECT " . implode( ', ', $selects ) . " FROM " . $from . " " . implode( ' ', $innerjoins );
		if( $wheres ) $sql .= " WHERE " . implode( " AND ", $wheres );
		if( $orders ) $sql .= " ORDER BY " . implode( ' ' . $args['order'] . ', ', $orders ) . " " . $args['order'];

		if( wpforo_setting( 'topics', 'search_max_results' ) ) $sql = "SELECT * FROM (" . $sql . " LIMIT " . wpforo_setting( 'topics', 'search_max_results' ) . ") AS p";

		if( $args['row_count'] ) $sql .= " LIMIT " . intval( $args['offset'] ) . "," . intval( $args['row_count'] );

		$posts = WPF()->db->get_results( $sql, ARRAY_A );

		do_action( 'wpforo_search_result_after', $args, $items_count, $posts, $sql );

		foreach( $posts as $key => $post ) {
			if( ! WPF()->perm->forum_can( 'vf', $post['forumid'] ) ) unset( $posts[ $key ] );
			if( ! WPF()->perm->forum_can( 'vt', $post['forumid'] ) ) unset( $posts[ $key ] );
			if( ! $post['is_first_post'] && ! WPF()->perm->forum_can( 'vr', $post['forumid'] ) ) unset( $posts[ $key ] );
			if( $post['private'] && ! WPF()->perm->forum_can( 'vp', $post['forumid'] ) ) unset( $posts[ $key ] );
			if( $post['status'] && ! WPF()->perm->forum_can( 'au', $post['forumid'] ) ) unset( $posts[ $key ] );
		}

		return $posts;
	}

	/**
	 * @param int $postid
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	public function _get_forumslug_byid( $postid ) {
		return (string) WPF()->db->get_var(
			WPF()->db->prepare(
				"SELECT `slug` FROM " . WPF()->tables->forums . " WHERE `forumid` = (
					SELECT forumid FROM `" . WPF()->tables->topics . "` WHERE `topicid` = (
						SELECT `topicid` FROM `" . WPF()->tables->posts . "` WHERE postid = %d
					)
				)",
				$postid
			)
		);
	}

	public function get_forumslug_byid( $postid ) {
		return wpforo_ram_get( [ $this, '_get_forumslug_byid' ], $postid );
	}

	/**
	 * @param int
	 *
	 * @return    string
	 * @since 1.0.0
	 *
	 */
	public function _get_topicslug_byid( $postid ) {
		return (string) WPF()->db->get_var(
			WPF()->db->prepare(
				"SELECT `slug` FROM " . WPF()->tables->topics . " WHERE `topicid` = (
					SELECT `topicid` FROM `" . WPF()->tables->posts . "` WHERE postid = %d
				)",
				$postid
			)
		);
	}

	public function get_topicslug_byid( $postid ) {
		return wpforo_ram_get( [ $this, '_get_topicslug_byid' ], $postid );
	}

	/**
	 * @param array $post the array of wpforo post
	 *
	 * @return int
	 */
	private function get_position_in_topic( $post ) {
		$layout = WPF()->forum->get_layout( $post['forumid'] );
		$pid    = $post['postid'];
		if( $post['parentid'] ) {
			switch( $layout ) {
				case 3:
					$pid = $post['parentid'];
				break;
				case 4:
					$pid = $post['root'];
				break;
			}
		}

		$where   = "";
		$orderby = "`is_first_post` DESC, `created` ASC, `postid` ASC";
		if( $layout === 3 ) {
			$where   .= " AND NOT p.`parentid` ";
			$orderby = "`is_first_post` DESC, `is_answer` DESC, `votes` DESC, `created` ASC, `postid` ASC";
		} elseif( $layout === 4 ) {
			$where .= " AND NOT p.`parentid` ";
		}

		if( ! wpforo_current_user_is( 'admin' ) && ! wpforo_current_user_is( 'moderator' ) && ! WPF()->perm->forum_can( 'au', $post['forumid'] ) ) {
			if( WPF()->current_userid ) {
				$where .= " AND ( p.`status` = 0 OR (p.`status` = 1 AND p.`userid` = %d) ) ";
				$where = WPF()->db->prepare( $where, WPF()->current_userid );
			} elseif( WPF()->current_user_email ) {
				$where .= " AND ( p.`status` = 0 OR (p.`status` = 1 AND p.`email` = %s) ) ";
				$where = WPF()->db->prepare( $where, sanitize_email( WPF()->current_user_email ) );
			} else {
				$where .= " AND NOT p.`status` ";
			}
		}

		if( wpforo_is_db_mysql8() ) {
			$sql = "SELECT tmp_view.`rownum` FROM
							(SELECT ROW_NUMBER() OVER() AS rownum, p.`postid`
								FROM `" . WPF()->tables->posts . "` p
								WHERE p.`topicid` = %d
								" . $where . "
								ORDER BY " . $orderby . ") AS tmp_view
						WHERE tmp_view.`postid` = %d";
		} else {
			$sql = "SELECT tmp_view.`rownum` FROM
							(SELECT @rownum := @rownum + 1 AS rownum, p.`postid`
								FROM `" . WPF()->tables->posts . "` p
								CROSS JOIN ( SELECT @rownum := 0 ) AS init_var
								WHERE p.`topicid` = %d
								" . $where . "
								ORDER BY " . $orderby . ") AS tmp_view
						WHERE tmp_view.`postid` = %d";
		}
		$sql = WPF()->db->prepare( $sql, $post['topicid'], $pid );

		return (int) WPF()->db->get_var( $sql );
	}

	/**
	 * return post full url by id
	 *
	 * @param int|array $arg
	 *
	 * @return string $url
	 * @since 1.0.0
	 *
	 */
	function get_post_url( $arg ) {
		if( isset( $arg ) && ! is_array( $arg ) ) {
			$postid = wpforo_bigintval( $arg );
			$post   = $this->get_post( $postid, false );
		} elseif( ! empty( $arg ) && isset( $arg['postid'] ) ) {
			$post   = $arg;
			$postid = $post['postid'];
		}

		if( ! empty( $post ) && is_array( $post ) && ! empty( $postid ) ) {
			$forum_slug = ( wpfval( $post, 'forumid' ) ) ? wpforo_forum( $post['forumid'], 'slug' ) : $this->get_forumslug_byid( $postid );
			$topic_slug = ( wpfval( $post, 'topicid' ) ) ? wpforo_topic( $post['topicid'], 'slug' ) : $this->get_topicslug_byid( $postid );

			$url = $forum_slug . '/' . $topic_slug;

			$position       = $this->get_position_in_topic( $post );
			$items_per_page = $this->get_option_items_per_page( WPF()->forum->get_layout( $post['forumid'] ) );

			if( $position <= $items_per_page ) {
				return wpforo_home_url( $url ) . "#post-" . wpforo_bigintval( $postid );
			}
			if( $position && $items_per_page ) {
				$paged = (int) ceil( $position / $items_per_page );
			} else {
				$paged = 1;
			}

			return wpforo_home_url( $url . "/" . wpforo_settings_get_slug( 'paged' ) . "/" . $paged ) . "#post-" . wpforo_bigintval( $postid );
		}

		return wpforo_home_url();
	}


	/**
	 *
	 * @param int $postid
	 *
	 * @return int
	 * @since 1.0.0
	 *
	 */
	function is_answered( $postid ) {
		$is_answered = WPF()->db->get_var(
			WPF()->db->prepare(
				"SELECT is_answer FROM `" . WPF()->tables->posts . "` WHERE postid = %d",
				$postid
			)
		);

		return $is_answered;
	}

	function get_best_answer( $topicid, $extended = true ) {
		$best_answer  = [];
		$args         = [
			'topicid'   => $topicid,
			'is_answer' => 1,
			'status'    => 0,
		];
		$best_answers = $this->get_posts( $args );
		if( ! empty( $best_answers ) && isset( $best_answers[0] ) ) {
			$best_answer = $best_answers[0];
		} else if( $extended ) {
			$best_answer = WPF()->db->get_row(
				WPF()->db->prepare(
					"SELECT * FROM `" . WPF()->tables->posts . "` WHERE `topicid` = %d AND `is_first_post` = 0 AND `parentid` = 0 AND `votes` > 0 ORDER BY `votes` DESC LIMIT 1",
					$topicid
				),
				ARRAY_A
			);
		}

		return $best_answer;
	}

	function get_suggested_answers( $topicid, $count = 3 ) {
		$args = [
			'topicid'       => $topicid,
			'is_first_post' => 0,
			'parentid'      => 0,
			'is_answer'     => 0,
			'orderby'       => '`votes` DESC, `created` ASC',
			'row_count'     => $count,
			'status'        => 0,
		];

		return $this->get_posts( $args );
	}

	function is_approved( $postid ) {
		$post = WPF()->db->get_var( "SELECT `status` FROM " . WPF()->tables->posts . " WHERE `postid` = " . intval( $postid ) );
		if( $post ) return false;

		return true;
	}

	function get_count( $args = [] ) {
		$sql = "SELECT SQL_NO_CACHE COUNT(*) FROM `" . WPF()->tables->posts . "`";
		if( $args && is_array( $args ) ) {
			$wheres = [];
			foreach( $args as $key => $value ) $wheres[] = "`$key` = '" . esc_sql( $value ) . "'";
			if( $wheres ) $sql .= " WHERE " . implode( ' AND ', $wheres );
		}

		return WPF()->db->get_var( $sql );
	}

	function unapproved_count() {
		return (int) WPF()->db->get_var( "SELECT COUNT(*) FROM `" . WPF()->tables->posts . "` WHERE `status` = 1" );
	}

	function get_attachment_id( $filename ) {
		$attach_id = WPF()->db->get_var( "SELECT `post_id` FROM `" . WPF()->db->postmeta . "` WHERE `meta_key` = '_wp_attached_file' AND `meta_value` LIKE '%" . esc_sql( $filename ) . "' LIMIT 1" );

		return $attach_id;
	}

	function delete_attachments( $postid ) {
		$post = $this->get_post( $postid );
		if( isset( $post['body'] ) && $post['body'] ) {
			if( preg_match_all( '|\/wpforo\/default_attachments\/([^\s\"\]]+)|is', $post['body'], $attachments, PREG_SET_ORDER ) ) {
				foreach( $attachments as $attachment ) {
					$filename = trim( $attachment[1] );
					$file     = WPF()->folders['default_attachments']['dir'] . DIRECTORY_SEPARATOR . $filename;
					if( file_exists( $file ) ) {
						$posts = WPF()->db->get_var( "SELECT COUNT(*) as posts FROM `" . WPF()->tables->posts . "` WHERE `body` LIKE '%" . esc_sql( $attachment[0] ) . "%'" );
						if( is_numeric( $posts ) && $posts == 1 ) {
							$attachmentid = $this->get_attachment_id( '/' . $filename );
							if( ! wp_delete_attachment( $attachmentid ) ) {
								@unlink( $file );
							}
						}
					}
				}
			}
		}
	}

	public function set_status( $postid, $status ) {
		$postid = wpforo_bigintval( $postid );
		$status = intval( $status );
		if( ! $post = $this->get_post( $postid, false ) ) return false;

		if( intval($post['is_first_post']) ) WPF()->topic->set_status( $post['topicid'], $status );

		if( $r = WPF()->db->update( WPF()->tables->posts, [ 'status' => $status ], [ 'postid' => $postid ], [ '%d' ], [ '%d' ] ) ) {
			if( $status ) {
				do_action( 'wpforo_post_unapprove', $post );
			} else {
				do_action( 'wpforo_post_approve', $post );
			}

			do_action( 'wpforo_post_status_update', $post, $status );
			wpforo_clean_cache( 'post', $postid, $post );
		}

		if( $r !== false ){
			WPF()->notice->add( 'Done!', 'success' );
			return true;
		}

		WPF()->notice->add( 'error: Change Status action', 'error' );
		return false;
	}

	public function next_post( $postid, $topicid = 0 ) {
		$next_postid = 0;
		if( ! $topicid ) $topicid = wpforo_post( $postid, 'topicid' );
		if( $topicid ) $next_postid = WPF()->db->get_var( "SELECT `postid` FROM `" . WPF()->tables->posts . "` WHERE `topicid` = " . intval( $topicid ) . " AND `postid` > " . intval( $postid ) . " AND `status` = 0 ORDER BY `created` ASC LIMIT 1" );

		return intval( $next_postid );
	}

	public function get_liked_posts( $args, &$items_count ) {
		$default = [
			'userid'    => null,
			'order'     => 'DESC',
			'offset'    => null,
			'row_count' => null,
			'where'     => null,
			'var'       => null,
		];

		$posts = [];
		if( ! wpfval( $args, 'userid' ) ) return [];
		$args = wpforo_parse_args( $args, $default );
		if( is_array( $args ) && ! empty( $args ) ) {
			extract( $args, EXTR_OVERWRITE );
			if( $row_count === 0 ) return [];
			$items_count = WPF()->reaction->get_count( ['userid' => $userid, 'type' => 'up'] );
			$liked_postids = WPF()->reaction->get_reactions_col( 'postid', ['userid' => $userid, 'type' => 'up', 'orderby' => "`reactionid` $order", 'offset' => $offset, 'row_count' => $row_count] );
			if( ! empty( $liked_postids ) ) {
				if( $var === 'postid' ) {
					return $liked_postids;
				} else {
					$liked_postids = implode( ',', $liked_postids );
					$post_args   = [ 'include' => $liked_postids, 'status' => 0, 'private' => 0 ];
					$posts       = $this->get_posts( $post_args );
				}
			}
		}

		return $posts;
	}

	public function get_unread_posts( $args, $limit = 10 ) {

		$unread_posts = [];

		//If the unread post logging is disabled return an empty array.
		if( ! wpforo_setting( 'logging', 'view_logging' ) ) return $unread_posts;

		//If there is no information about last read post.
		//Max number recent posts to search unread posts in.
		$args['row_count'] = apply_filters( 'wpforo_max_number_of_unread_posts', 100 );

		//Find the last unread postid, if so, add 'where' condition.
		$last_read_postid = WPF()->log->get_all_read( 'post' );
		if( $last_read_postid ) {
			$args['where'] = '`postid` > ' . intval( $last_read_postid );
		}

		//Find unread posts based on last read postid's in topics
		$posts       = $this->get_posts( $args );
		$read_topics = WPF()->log->get_read_topics();
		if( ! empty( $posts ) ) {
			if( ! empty( $read_topics ) ) {
				foreach( $posts as $key => $post ) {
					if( $key == $limit ) break;
					if( ! wpfkey( $post, 'topicid' ) && $post ) {
						$post_ids = explode( ',', $post );
						if( ! empty( $post_ids ) ) {
							foreach( $post_ids as $post_id ) {
								$topicid = wpforo_post( $post_id, 'topicid' );
								if( $topicid == wpfval( WPF()->current_object, 'topicid' ) ) continue;
								if( wpfkey( $read_topics, $topicid ) ) {
									$last_read_postid = $read_topics[ $topicid ];
									if( (int) $post_id > (int) $last_read_postid ) {
										$unread_posts[] = $post_id;
									}
								} else {
									$unread_posts[] = $post_id;
								}
							}
						}
					} elseif( wpfkey( $post, 'topicid' ) && wpfkey( $read_topics, $post['topicid'] ) ) {
						$last_read_postid = $read_topics[ $post['topicid'] ];
						if( (int) $post['postid'] > (int) $last_read_postid ) {
							$unread_posts[] = $post;
						}
					} else {
						$unread_posts[] = $post;
					}
				}
			} else {
				$unread_posts = $posts;
			}
		}

		return $unread_posts;
	}

	public function reset_fields( $type = null ) {
		if( is_null( $type ) ) {
			$this->fields = [];
		} else {
			unset( $this->fields, $type );
		}
	}

	private function init_fields( $type, $forum = [] ) {
		if( $fields = (array) wpfval( $this->fields, $type ) ) return;
		$all_groupids = WPF()->usergroup->get_usergroups( 'groupid' );
		$all_groupids = array_map( 'intval', $all_groupids );

		$fields = apply_filters( 'wpforo_post_before_init_fields', $fields );

		$fields['title'] = [
			'fieldKey'       => 'title',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Topic Title', false ),
			'title'          => wpforo_phrase( 'Title', false ),
			'placeholder'    => wpforo_phrase( 'Enter title here', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-pen-alt',
			'name'           => 'title',
			'cantBeInactive' => [ 'topic' ],
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1,
		];

		/*$fields['slug'] = array(
			'fieldKey'       => 'slug',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Slug', false ),
			'title'          => wpforo_phrase( 'Slug', false ),
			'placeholder'    => wpforo_phrase( 'Slug', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-link',
			'name'           => 'slug',
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);*/

		$fields['body'] = [
			'fieldKey'       => 'body',
			'type'           => 'tinymce',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'title'          => wpforo_phrase( 'Body', false ),
			'placeholder'    => wpforo_phrase( 'Body', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => '',
			'name'           => 'body',
			'cantBeInactive' => [ 'topic', 'post', 'comment', 'reply' ],
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1,
		];

		$fields['name'] = [
			'fieldKey'        => 'name',
			'type'            => 'text',
			'isDefault'       => 1,
			'isRemovable'     => 0,
			'isRequired'      => 0,
			'isEditable'      => 1,
			'label'           => wpforo_phrase( 'Author Name', false ),
			'title'           => wpforo_phrase( 'Author Name', false ),
			'placeholder'     => wpforo_phrase( 'Your name', false ),
			'minLength'       => 0,
			'maxLength'       => 0,
			'faIcon'          => 'fas fa-id-card',
			'name'            => 'name',
			'cantBeInactive'  => [],
			'canEdit'         => $all_groupids,
			'canView'         => $all_groupids,
			'can'             => '',
			'isSearchable'    => 0,
			'isOnlyForGuests' => 1,
		];

		$fields['email'] = [
			'fieldKey'        => 'email',
			'type'            => 'text',
			'isDefault'       => 1,
			'isRemovable'     => 0,
			'isRequired'      => 0,
			'isEditable'      => 1,
			'label'           => wpforo_phrase( 'Author Email', false ),
			'title'           => wpforo_phrase( 'Author Email', false ),
			'placeholder'     => wpforo_phrase( 'Your email', false ),
			'minLength'       => 0,
			'maxLength'       => 0,
			'faIcon'          => 'fas fa-at',
			'name'            => 'email',
			'cantBeInactive'  => [],
			'canEdit'         => $all_groupids,
			'canView'         => $all_groupids,
			'can'             => '',
			'isSearchable'    => 0,
			'isOnlyForGuests' => 1,
		];

		/*$fields['tags'] = array(
			'fieldKey'       => 'tags',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Topic Tags', false ) . ' ' . wpforo_phrase( 'Separate tags using a comma', false ),
			'title'          => wpforo_phrase( 'Tags', false ),
			'placeholder'    => wpforo_phrase( 'Start typing tags here (maximum %d tags are allowed)...', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-tag',
			'name'           => 'tags',
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);

		$fields['sticky'] = array(
			'fieldKey'       => 'sticky',
			'type'           => 'checkbox',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Set Topic Sticky', false ),
			'title'          => wpforo_phrase( 'Set Topic Sticky', false ),
			'placeholder'    => wpforo_phrase( 'Set Topic Sticky', false ),
			'faIcon'         => 'fas fa-exclamation',
			'name'           => 'type',
			'values'         => '1 => ' . wpforo_phrase( 'Set Topic Sticky', false ),
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);

		$fields['private'] = array(
			'fieldKey'       => 'private',
			'type'           => 'checkbox',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Private Topic', false ),
			'title'          => wpforo_phrase( 'Only Admins and Moderators can see your private topics.', false ),
			'placeholder'    => wpforo_phrase( 'Private Topic', false ),
			'faIcon'         => 'fas fa-eye-slash',
			'name'           => 'private',
			'values'         => '1 => ' . wpforo_phrase( 'Private Topic', false ),
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);

		$fields['subscribe'] = array(
			'fieldKey'       => 'subscribe',
			'type'           => 'checkbox',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Subscribe to this topic', false ),
			'title'          => wpforo_phrase( 'Subscribe to this topic', false ),
			'placeholder'    => wpforo_phrase( 'Subscribe to this topic', false ),
			'faIcon'         => 'fas fa-eye-slash',
			'name'           => 'wpforo_topic_subs',
			'values'         => '1 => ' . wpforo_phrase( 'Subscribe to this topic', false ),
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 0
		);*/

		$fields = (array) apply_filters( 'wpforo_post_after_init_fields', $fields, $type, $forum );

		$this->fields[ $type ] = array_map( [ $this, 'fix_field' ], $fields );
	}

	public function fix_field( $field ) {
		$field = array_merge( WPF()->form->default, (array) $field );
		if( is_scalar( $field['values'] ) ) $field['values'] = explode( "\n", $field['values'] );

		return $field;
	}

	public function get_fields( $only_defaults = false, $type = 'topic', $forum = [] ) {
		$this->init_fields( $type, $forum );
		$fields = (array) wpfval( $this->fields, $type );
		if( $only_defaults ) foreach( $fields as $k => $v ) if( ! wpfval( $v, 'isDefault' ) ) unset( $fields[ $k ] );

		return $fields;
	}

	public function get_field( $key, $type = 'topic', $forum = [] ) {
		if( is_string( $key ) ) {
			$this->init_fields( $type, $forum );

			return (array) wpfval( $this->fields, $type, $key );
		} elseif( $this->is_field( $key ) ) {
			return $key;
		}

		return [];
	}

	public function is_field( $field ) {
		return wpfval( $field, 'fieldKey' ) && wpfval( $field, 'type' ) && wpfkey( $field, 'isDefault' );
	}

	public function get_field_key( $field ) {
		return is_string( $field ) ? $field : (string) wpfval( $field, 'fieldKey' );
	}

	public function fields_structure_full_array( $fields, &$used_fields = [], $type = 'topic', $forum = [] ) {
		if( is_string( $fields ) ) $fields = maybe_unserialize( $fields );
		$fs = [ [ [] ] ];
		if( ! is_array( $fields ) ) return $fs;
		foreach( $fields as $kr => $row ) {
			if( is_array( $row ) ) {
				foreach( $row as $kc => $cols ) {
					if( is_array( $cols ) ) {
						foreach( $cols as $kf => $field ) {
							$used_fields[]                  = $field_key = $this->get_field_key( $field );
							$fs[ $kr ][ $kc ][ $field_key ] = $this->get_field( $field, $type, $forum );
						}
					}
				}
			}
		}

		return $fs;
	}

	// -- START -- get topic fields
	public function get_topic_fields_structure( $only_defaults = false, $forum = [], $guest = false ) {
		if( $guest ) {
			$fields[0][0][0] = 'name';
			$fields[0][1][0] = 'email';
		}
		$fields[][] = [ 'title', 'body' ];
		if( ! $only_defaults ) $fields = apply_filters( 'wpforo_get_topic_fields_structure', $fields, $forum, $guest );

		return $fields;
	}

	public function get_topic_fields( $forum, $values = [], $guest = false ) {
		$fields = $this->fields_structure_full_array( $this->get_topic_fields_structure( false, $forum, $guest ), $used_fields, 'topic', $forum );
		if( ! in_array( 'title', $used_fields, true ) ) $fields[][][] = $this->get_field( 'title', 'topic', $forum );
		if( ! in_array( 'body', $used_fields, true ) ) $fields[][][] = $this->get_field( 'body', 'topic', $forum );

		/**
		 * apply old options to fields
		 */
		$values = wp_slash( $values );
		foreach( $fields as $kr => $row ) {
			foreach( $row as $kc => $cols ) {
				foreach( $cols as $kf => $field ) {
					if( $field ) {
						$field['value'] = wpfval( $values, $kf );
						switch( $kf ) {
							case 'body':
								if( $field['type'] === 'tinymce' ) {
									$field['wp_editor_settings'] = WPF()->tpl->editor_buttons( 'topic' );
								}
								$field['textareaid'] = uniqid( 'wpf_topic_body_' );
								$field['minLength']  = wpforo_setting( 'posting', 'topic_body_min_length' );
								$field['maxLength']  = wpforo_setting( 'posting', 'topic_body_max_length' );
								$field['form_type']  = 'topic';
								$field['meta']       = [ 'forum' => $forum, 'values' => $values ];
							break;
							case 'title':
								if( intval( $forum['cat_layout'] ) === 3 ) $field['label'] = wpforo_phrase( 'Your question', false );
								$field['minLength'] = wpforo_setting( 'posting', 'topic_title_min_length' );
								$field['maxLength'] = wpforo_setting( 'posting', 'topic_title_max_length' ) ?: 250;
							break;
						}
						if( $field && intval( $field['isOnlyForGuests'] ) || in_array( $kf, [ 'name', 'email' ], true ) ) {
							if( $guest ) {
								if( ! $values ) {
									$g = WPF()->member->get_guest_cookies();
									if( $kf === 'name' ) {
										$field['value'] = $g['name'];
									} elseif( $kf === 'email' ) {
										$field['value'] = $g['email'];
									}
								}
							} else {
								$field = [];
							}
						}
						$field = apply_filters( 'wpforo_topic_field', $field, $forum, $values, $guest );
					}

					if( $field ) {
						$fields[ $kr ][ $kc ][ $kf ] = $field;
					} else {
						unset( $fields[ $kr ][ $kc ][ $kf ] );
						if( ! $fields[ $kr ][ $kc ] ) {
							unset( $fields[ $kr ][ $kc ] );
							if( ! $fields[ $kr ] ) unset( $fields[ $kr ] );
						}
					}

				}
			}
		}

		return $fields;
	}

	public function get_topic_fields_list( $only_defaults = false, $forum = [], $guest = false ) {
		$fields_list      = [ 'title', 'body' ];
		$fields_structure = $this->get_topic_fields_structure( $only_defaults, $forum, $guest );
		foreach( $fields_structure as $r ) {
			foreach( $r as $c ) {
				foreach( $c as $f ) {
					$fields_list[] = $f;
				}
			}
		}

		return array_values( array_unique( $fields_list ) );
	}
	// -- END -- get topic fields

	// -- START -- get post fields
	public function get_post_fields_structure( $only_defaults = false, $forum = [], $guest = false ) {
		if( $guest ) {
			$fields[0][0][0] = 'name';
			$fields[0][1][0] = 'email';
		}
		$fields[][] = [ 'title', 'body' ];
		if( ! $only_defaults ) $fields = apply_filters( 'wpforo_get_post_fields_structure', $fields, $forum, $guest );

		return $fields;
	}

	public function get_post_fields( $forum, $topic, $values = [], $guest = false ) {
		$fields = $this->fields_structure_full_array( $this->get_post_fields_structure( false, $forum, $guest ), $used_fields, 'post', $forum );
		if( ! in_array( 'body', $used_fields, true ) ) $fields[][][] = $this->get_field( 'body', 'post', $forum );

		/**
		 * apply old options to fields
		 */
		$values = wp_slash( $values );
		foreach( $fields as $kr => $row ) {
			foreach( $row as $kc => $cols ) {
				foreach( $cols as $kf => $field ) {
					if( $field ) {
						$field['value'] = wpfval( $values, $kf );
						switch( $kf ) {
							case 'body':
								if( $field['type'] === 'tinymce' ) {
									$field['wp_editor_settings'] = WPF()->tpl->editor_buttons( 'post' );
								}
								$field['textareaid'] = uniqid( 'wpf_post_body_' );
								$field['minLength']  = wpforo_setting( 'posting', 'post_body_min_length' );
								$field['maxLength']  = wpforo_setting( 'posting', 'post_body_max_length' );
								$field['form_type']  = 'post';
								$field['meta']       = [ 'forum' => $forum, 'topic' => $topic, 'values' => $values ];
							break;
							case 'title':
								$prefix_answer   = wpforo_phrase( 'Answer to', false, 'default' );
								$prefix_re       = wpforo_phrase( 'RE', false, 'default' );
								$prefix_patterns = [ $prefix_answer, $prefix_re ];
								$pattern         = array_map( 'preg_quote', $prefix_patterns );
								$pattern         = implode( '|', $pattern );
								$title           = preg_replace( '#^\s*(?:' . $pattern . ')\s*: #isu', '', trim( $field['value'] ), 1 );
								if( WPF()->forum->get_layout( $forum ) === 3 ) {
									$field['label'] = wpforo_phrase( 'Your question', false );
									if( $title ) $field['value'] = $prefix_answer . ': ' . $title;
								} else {
									$field['label'] = wpforo_phrase( 'Title', false );
									if( $title ) $field['value'] = $prefix_re . ': ' . $title;
								}
							break;
						}
						if( $field && intval( $field['isOnlyForGuests'] ) || in_array( $kf, [ 'name', 'email' ], true ) ) {
							if( $guest ) {
								if( ! $values || ( count( $values ) === 1 && wpfkey( $values, 'title' ) ) ) {
									$g = WPF()->member->get_guest_cookies();
									if( $kf === 'name' ) {
										$field['value'] = $g['name'];
									} elseif( $kf === 'email' ) {
										$field['value'] = $g['email'];
									}
								}
							} else {
								$field = [];
							}
						}
						$fields[ $kr ][ $kc ][ $kf ] = apply_filters( 'wpforo_post_field', $field, $forum, $topic, $values, $guest );
					}
				}
			}
		}

		return $fields;
	}

	public function get_post_fields_list( $only_defaults = false, $forum = [], $guest = false ) {
		$fields_list      = [ 'body' ];
		$fields_structure = $this->get_post_fields_structure( $only_defaults, $forum, $guest );
		foreach( $fields_structure as $r ) {
			foreach( $r as $c ) {
				foreach( $c as $f ) {
					$fields_list[] = $f;
				}
			}
		}

		return array_values( array_unique( $fields_list ) );
	}

	// -- END -- get post fields

	public function get_search_fields( $values ) {
		$values = (array) $values;
		$values = wp_slash( $values );

		$topic_fields = WPF()->post->get_topic_fields_list( false, [], ! WPF()->current_userid );
		$topic_fields = array_flip( $topic_fields );
		$fields       = $this->get_fields();
		$fields       = array_intersect_key( $fields, $topic_fields );

		$search_fields = [];
		foreach( $fields as $kf => $field ) {
			if( ! $field['isDefault'] && (int) wpfval( $field, 'isSearchable' ) && ! (int) wpfval( $field, 'isOnlyForGuests' ) && wpfval( $field, 'type' ) !== 'file' ) {
				$field['value'] = wpfval( $values, $kf );
				if( in_array( $field['type'], [ 'text', 'textarea', 'email', 'url' ], true ) ) $field['type'] = 'search';
				$field['isRequired']                       = 0;
				$search_fields[0][0][ $field['fieldKey'] ] = $field;
			}
		}

		return $search_fields;
	}

	public function print_custom_fields( $content, $post ) {
		if( (int) wpfval( $post, 'is_first_post' ) && ( $postmetas = WPF()->postmeta->get_postmeta( $post['postid'], '', true ) ) ) {
			$content .= '<div class="wpf-topic-fields">';
			$content .= apply_filters( 'wpforo_topic_fields_before', '', $post );
			$forum   = WPF()->forum->get_forum( $post['forumid'] );
			$fields  = WPF()->post->get_topic_fields_list( false, $forum, ! WPF()->current_userid );
			foreach( $fields as $field ) {
				if( $postmeta = wpfval( $postmetas, $field ) ) {
					$field = WPF()->post->get_field( $field, 'topic', $forum );
					if( ! (int) wpfval( $field, 'isDefault' ) ) {
						$field['value'] = $postmeta;
						$field          = WPF()->form->prepare_values( WPF()->form->esc_field( $field ) );
						$content        .= sprintf(
							'<div class="wpf-topic-field"><div class="wpf-topic-field-label"> <i class="%1$s"></i> %2$s</div><div class="wpf-topic-field-value">%3$s</div></div>',
							(string) wpfval( $field, 'faIcon' ),
							(string) wpfval( $field, 'label' ),
							(string) wpfval( $field, 'value' )
						);
					}
				}
			}
			$content .= '</div>';
		}

		return $content;
	}

	/**
	 * @param int $postid
	 * @param int $reaction
	 * @param int $userid
	 *
	 * @return bool
	 * @since 2.0.0
	 *
	 */
	public function has_user_voted_post( $postid, $reaction = null, $userid = 0 ) {
		$reaction       = in_array( $reaction, [1,-1], true ) ? $reaction : null;
		$user_post_vote = WPF()->reaction->get_user_reaction_reaction( $postid, $userid );
		if( is_null( $reaction ) ) return (bool) $user_post_vote;

		return $reaction === $user_post_vote;
	}

	/**
	 * @param int $postid
	 * @param int $reaction
	 * @param int $userid
	 *
	 * @return int
	 * @since 2.0.0
	 *
	 */
	public function clear_user_post_votes( $postid, $reaction = null, $userid = 0 ) {
		$reaction = in_array( $reaction, [1,-1], true ) ? $reaction : null;
		if( ! $userid = wpforo_bigintval( $userid ) ) $userid = WPF()->current_userid;
		if(  WPF()->reaction->delete( ['postid' => $postid, 'userid' => $userid, 'reaction_include' => $reaction] ) ) {
			return $this->rebuild_votes_stats( $postid );
		}
		return WPF()->reaction->get_sum( $postid );
	}

	/**
	 * @param int $postid
	 * @param int $reaction
	 * @param int $userid
	 *
	 * @return false|int
	 * @since 2.0.0
	 *
	 */
	public function vote_post( $postid, $reaction, $userid = 0 ) {
		if( in_array( ($reaction = intval($reaction)), [1,-1], true ) ) {
			$postid = wpforo_bigintval( $postid );
			if( ! $userid = wpforo_bigintval( $userid ) ) $userid = WPF()->current_userid;

			if( $post = $this->get_post( $postid, false ) ) {
				if( false !== WPF()->reaction->add( [ 'userid' => $userid, 'postid' => $postid, 'post_userid' => $post['userid'], 'reaction' => $reaction, 'type' => ( $reaction === 1 ? 'up' : 'down' ) ] ) ) {
					$votes = $this->rebuild_votes_stats( $postid );

					do_action( 'wpforo_vote', $reaction, $post, $userid );
					WPF()->notice->add( 'Successfully voted', 'success' );

					return $votes;
				}
			}
		}

		return false;
	}

	/**
	 * @param int $postid
	 *
	 * @return int
	 * @since 2.0.0
	 *
	 */
	public function rebuild_votes_stats( $postid ) {
		if( $postid = wpforo_bigintval( $postid ) ) {
			$votes = WPF()->reaction->get_sum( $postid );

			$tu = WPF()->db->update( WPF()->tables->topics, [ 'votes' => $votes ], [ 'first_postid' => $postid ], [ '%d' ], [ '%d' ] );

			$pu = WPF()->db->update( WPF()->tables->posts, [ 'votes' => $votes ], [ 'postid' => $postid ], [ '%d' ], [ '%d' ] );

			if( $tu || $pu ) wpforo_clean_cache( 'post', $postid );

			return $votes;
		}

		return 0;
	}

	public function get_userids_for_forum( $forumid ) {
		$sql = "SELECT DISTINCT `userid`
			FROM `" . WPF()->tables->posts . "`
			WHERE `forumid` = %d
			ORDER BY `created` DESC";
		$sql = WPF()->db->prepare( $sql, $forumid );
		return array_map( 'wpforo_bigintval', WPF()->db->get_col( $sql ) );
	}

	public function get_userids_for_topic( $topicid ) {
		$sql = "SELECT DISTINCT `userid`
			FROM `" . WPF()->tables->posts . "`
			WHERE `topicid` = %d
			AND `is_first_post` = 0
			ORDER BY `created` DESC";
		$sql = WPF()->db->prepare( $sql, $topicid );
		return array_map( 'wpforo_bigintval', WPF()->db->get_col( $sql ) );
	}

	public function get_users_count_for_topic( $topicid ) {
		$sql = "SELECT COUNT( DISTINCT `userid` ) AS quantity 
			FROM `" . WPF()->tables->posts . "` 
			WHERE `topicid` = %d";
		$sql = WPF()->db->prepare( $sql, $topicid );
		return (int) WPF()->db->get_var( $sql );
	}

	public function get_users_stats_for_topic( $topicid ) {
		$sql = "SELECT `userid`, COUNT(`userid`) AS `posts`
			FROM `" . WPF()->tables->posts . "`
			WHERE `topicid` = %d
			GROUP BY `userid`
			ORDER BY `posts` DESC";
		$sql = WPF()->db->prepare( $sql, $topicid );
		return array_map( function( $row ){
			$row['userid'] = wpforo_bigintval( $row['userid'] );
			$row['posts']  = intval( $row['posts'] );
			return $row;
		}, (array) WPF()->db->get_results( $sql, ARRAY_A ) );
	}

	public function get_first_level_replies( $topicid, $row_count = 0, $offset = 0 ) {
		$sql = "SELECT *
			FROM `" . WPF()->tables->posts . "`
			WHERE `topicid` = %d
			AND `parentid` = 0
			AND `is_first_post` = 0
			ORDER BY `created` ASC";
		$sql = WPF()->db->prepare( $sql, $topicid );
		if( $row_count = intval( $row_count ) ) $sql .= WPF()->db->prepare( " LIMIT %d,%d", $offset, $row_count );
		return (array) WPF()->db->get_results( $sql, ARRAY_A );
	}

	public function after_delete_user( $userid, $reassign ) {
		if( $boardids = WPF()->board->get_active_boardids() ){
			if( is_null( $reassign ) ) {

				foreach( $boardids as $boardid ){
					WPF()->change_board( $boardid );
					if( $postids = WPF()->db->get_col( WPF()->db->prepare( "SELECT `postid` FROM `" . WPF()->tables->posts . "` WHERE userid = %d", $userid ) ) ) {
						foreach( $postids as $postid ) $this->delete( $postid, true, true, $exclude, false );
					}
				}

			} else {
				if( $reassign = wpforo_bigintval( $reassign ) ){
					$data   = [ 'userid' => $reassign ];
					$format = [ '%d' ];
				}else{
					$data = [
						'userid' => 0,
						'name'   => wpforo_phrase( 'Anonymous', false ) . " " . $userid,
						'email'  => "anonymous_$userid@example.com",
					];
					$format = [ '%d', '%s', '%s' ];
				}

				foreach( $boardids as $boardid ){
					WPF()->change_board( $boardid );
					WPF()->db->update( WPF()->tables->posts, $data, [ 'userid' => $userid ], $format, ['%d'] );
				}

			}
		}
	}
}
