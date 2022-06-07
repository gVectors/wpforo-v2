<?php

namespace wpforo\classes;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class Topics {
	static $cache = [ 'topics' => [], 'tags' => [], 'item' => [], 'topic' => [], 'tag' => [], 'forum_slug' => [] ];

	function __construct() {
		$this->init_hooks();
	}

	public function get_cache( $var ) {
		if( isset( self::$cache[ $var ] ) ) return self::$cache[ $var ];

		return [];
	}

	public function reset() {
		self::$cache = [ 'topics' => [], 'tags' => [], 'item' => [], 'topic' => [], 'tag' => [], 'forum_slug' => [] ];
	}

	private function init_hooks() {
		add_action( 'wpforo_after_add_post',     [ $this, 'after_add_post' ], 10, 2 );
		add_action( 'wpforo_after_delete_post',  [ $this, 'after_delete_post' ] );
		add_action( 'wpforo_post_status_update', [ $this, 'after_post_status_update' ] );
		add_action( 'wpforo_after_delete_user',  [ $this, 'after_delete_user' ], 11, 2 );
	}

	private function unique_slug( $slug ) {
		$new_slug = wpforo_text( $slug, 250, false );
		$i        = 2;
		while( ! WPF()->can_use_this_slug( $new_slug ) || WPF()->db->get_var( "SELECT `topicid` FROM " . WPF()->tables->topics . " WHERE `slug` = '" . esc_sql( $new_slug ) . "'" ) ) {
			$new_slug = wpforo_text( $slug, 250, false ) . '-' . $i;
			$i ++;
		}

		return $new_slug;
	}

	public function add( $args = [] ) {

		if( empty( $args ) && empty( $_REQUEST['thread'] ) ) return false;
		if( empty( $args ) && ! empty( $_REQUEST['thread'] ) ) $args = $_REQUEST['thread'];
		if( ! isset( $args['body'] ) || ! $args['body'] ) {
			WPF()->notice->add( 'Post is empty', 'error' );

			return false;
		}
		$args['name']  = ( isset( $args['name'] ) ? strip_tags( $args['name'] ) : '' );
		$args['email'] = ( isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '' );

		if( ! isset( $args['forumid'] ) || ! $args['forumid'] = intval( $args['forumid'] ) ) {
			WPF()->notice->add( 'Add Topic error: No forum selected', 'error' );

			return false;
		}

		if( ! $forum = WPF()->forum->get_forum( $args['forumid'] ) ) {
			WPF()->notice->add( 'Add Topic error: No forum selected', 'error' );

			return false;
		}

		if( ! WPF()->perm->forum_can( 'ct', $args['forumid'] ) ) {
			WPF()->notice->add( 'You don\'t have permission to create topic into this forum', 'error' );

			return false;
		}

		if( ! WPF()->perm->can_post_now() ) {
			WPF()->notice->add( 'You are posting too quickly. Slow down.', 'error' );

			return false;
		}

		if( ! isset( $args['title'] ) || ! $args['title'] = trim( strip_tags( $args['title'] ) ) ) {
			WPF()->notice->add( 'Please insert required fields!', 'error' );

			return false;
		}

		if( ! is_user_logged_in() ) {
			if( $args['name'] && $args['email'] ) {
				WPF()->member->set_guest_cookies( $args );
			} else {
				$uqid = uniqid();
				if( ! trim( $args['name'] ) ) $args['name'] = wpforo_phrase( 'Anonymous', false );
				if( ! trim( $args['email'] ) ) $args['email'] = "anonymous_$uqid@example.com";
			}
		}

		do_action( 'wpforo_start_add_topic', $args, $forum );

		$root_exists   = wpforo_root_exist();
		$args['title'] = wpforo_text( $args['title'], 250, false );
		$args['body']  = preg_replace( '#</pre>[\r\n\t\s\0]*<pre>#isu', "\r\n", $args['body'] );
		$args['slug']  = ( isset( $args['slug'] ) && $args['slug'] ) ? sanitize_title( $args['slug'] ) : ( ( isset( $args['title'] ) ) ? sanitize_title( $args['title'] ) : md5( time() ) );
		if( ! trim( $args['slug'] ) ) $args['slug'] = md5( time() );
		$args['slug']    = $this->unique_slug( $args['slug'] );
		$args['created'] = ( isset( $args['created'] ) ? sanitize_text_field( $args['created'] ) : current_time( 'mysql', 1 ) );
		$args['userid']  = ( isset( $args['userid'] ) ? intval( $args['userid'] ) : WPF()->current_userid );
		$args['name']    = ( isset( $args['name'] ) ? $args['name'] : '' );
		$args['email']   = ( isset( $args['email'] ) ? $args['email'] : '' );
		$args['tags']    = ( isset( $args['tags'] ) ? $args['tags'] : '' );

		$args = apply_filters( 'wpforo_add_topic_data_filter', $args, $forum );

		if( empty( $args ) ) return false;

		extract( $args, EXTR_OVERWRITE );

		if( isset( $title ) ) $title = sanitize_text_field( trim( $title ) );
		if( isset( $created ) ) $created = sanitize_text_field( $created );
		if( isset( $userid ) ) $userid = intval( $userid );
		$type    = ( isset( $type ) && $type ? 1 : 0 );
		$status  = ( isset( $status ) && $status ? 1 : 0 );
		$private = ( isset( $private ) && $private ? 1 : 0 );
		if( isset( $meta_key ) ) $meta_key = sanitize_text_field( $meta_key );
		if( isset( $meta_desc ) ) $meta_desc = sanitize_text_field( $meta_desc );
		if( isset( $name ) ) $name = strip_tags( trim( $name ) );
		if( isset( $email ) ) $email = strip_tags( trim( $email ) );
		if( isset( $body ) ) $body = wpforo_kses( trim( $body ) );
		if( isset( $tags ) ) {
			if( wpforo_is_module_enabled( 'tags' ) && WPF()->perm->forum_can( 'tag', $forum['forumid'] ) ) {
				$tags = $this->sanitize_tags( $tags, false, true );
			} else {
				$tags = '';
			}
		}
		$views      = ( isset( $views ) ? intval( $views ) : 0 );
		$meta_key   = ( isset( $meta_key ) ? $meta_key : '' );
		$meta_desc  = ( isset( $meta_desc ) ? $meta_desc : '' );
		$has_attach = ( isset( $has_attach ) && $has_attach ) ? 1 : ( ( strpos( $body, '[attach]' ) !== false ) ? 1 : 0 );
		$layout     = WPF()->forum->get_layout( $forum );
		$posts      = ( $layout == 3 ) ? 0 : 1;
		do_action( 'wpforo_before_add_topic', $args );

		if( WPF()->db->insert(
			WPF()->tables->topics,
           [
               'title'      => stripslashes( $title ),
               'slug'       => $slug,
               'forumid'    => $forum['forumid'],
               'userid'     => $userid,
               'type'       => $type,
               'status'     => $status,
               'private'    => $private,
               'created'    => $created,
               'modified'   => $created,
               'last_post'  => 0,
               'views'      => $views,
               'posts'      => $posts,
               'meta_key'   => $meta_key,
               'meta_desc'  => $meta_desc,
               'has_attach' => $has_attach,
               'name'       => $name,
               'email'      => $email,
               'tags'       => $tags,
           ],
           ['%s','%s','%d','%d','%d','%d','%d','%s','%s','%d','%d','%d','%s','%s','%d','%s','%s','%s']
		) ) {
			$topicid = WPF()->db->insert_id;
			$fields  = [
				'forumid'       => $forum['forumid'],
				'topicid'       => $topicid,
				'userid'        => $userid,
				'title'         => stripslashes( $title ),
				'body'          => stripslashes( $body ),
				'created'       => $created,
				'modified'      => $created,
				'is_first_post' => 1,
				'status'        => $status,
				'private'       => $private,
				'name'          => $name,
				'email'         => $email,
				'root'          => - 1,
			];

			$values = [ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d' ];

			if( ! $root_exists ) {
				unset( $fields['root'] );
				unset( $fields[13] );
			}

			if( WPF()->db->insert(
				WPF()->tables->posts,
				$fields,
				$values
			) ) {
				$first_postid = WPF()->db->insert_id;
				if( false !== WPF()->db->update( WPF()->tables->topics, [ 'first_postid' => $first_postid, 'last_post' => $first_postid ], [ 'topicid' => $topicid ], [ '%d', '%d' ], [ '%d' ] ) ) {
					$args['topicid']      = $topicid;
					$args['first_postid'] = $first_postid;
					$args['type']         = $type;
					$args['status']       = $status;
					$args['private']      = $private;
					$args['url'] = $args['topicurl'] = $this->get_topic_url( $topicid );
					if( $tags && ! $status && ! $private ) $this->add_tags( $tags );

					do_action( 'wpforo_after_add_topic', $args, $forum );

					wpforo_clean_cache( 'topic', $topicid, $args );
					if( $status ) {
						WPF()->notice->add( 'Your topic successfully added and awaiting moderation', 'success' );
					} else {
						WPF()->notice->add( 'Your topic successfully added', 'success' );
					}

					return $topicid;
				}
			}

		}

		WPF()->notice->add( 'Topic add error', 'error' );
		return false;
	}

	public function edit( $args = [] ) {

		if( empty( $args ) && empty( $_REQUEST['thread'] ) ) return false;
		if( ! isset( $args['topicid'] ) && isset( $_GET['id'] ) ) $args['topicid'] = intval( $_GET['id'] );
		if( empty( $args ) && ! empty( $_REQUEST['thread'] ) ) $args = $_REQUEST['thread'];
		if( isset( $args['name'] ) ) {
			$args['name'] = strip_tags( $args['name'] );
		}
		if( isset( $args['email'] ) ) {
			$args['email'] = sanitize_email( $args['email'] );
		}


		if( ! $topic = $this->get_topic( $args['topicid'] ) ) {
			WPF()->notice->add( 'Topic not found.', 'error' );

			return false;
		}

		if( ! $forum = WPF()->forum->get_forum( $topic['forumid'] ) ) {
			WPF()->notice->add( 'Forum not found.', 'error' );

			return false;
		}

		do_action( 'wpforo_start_edit_topic', $args, $forum );

		if( ! is_user_logged_in() ) {
			if( ! isset( $topic['email'] ) || ! $topic['email'] ) {
				WPF()->notice->add( 'Permission denied', 'error' );

				return false;
			} elseif( ! wpforo_current_guest( $topic['email'] ) ) {
				WPF()->notice->add( 'You are not allowed to edit this post', 'error' );

				return false;
			}
		}

		$args['status'] = $topic['status'];
		$args['userid'] = $topic['userid'];

		$args = apply_filters( 'wpforo_edit_topic_data_filter', $args, $forum );
		if( empty( $args ) ) return false;

		extract( $args, EXTR_OVERWRITE );

		if( isset( $topicid ) ) $topicid = intval( $topicid );
		if( isset( $title ) ) $title = sanitize_text_field( trim( $title ) );
		if( isset( $type ) ) $type = intval( $type );
		if( isset( $status ) ) $status = intval( $status );
		if( isset( $private ) ) $private = intval( $private );
		if( isset( $name ) ) $name = strip_tags( trim( $name ) );
		if( isset( $email ) ) $email = strip_tags( trim( $email ) );
		if( isset( $body ) ) $body = wpforo_kses( trim( $body ) );
		if( isset( $tags ) ) {
			if( isset( $topic['forumid'] ) && wpforo_is_module_enabled( 'tags' ) && WPF()->perm->forum_can( 'tag', $topic['forumid'] ) ) {
				$tags = $this->sanitize_tags( $tags, false, true );
			} else {
				$tags = '';
			}
		}


		if( ! isset( $topicid ) ) {
			WPF()->notice->add( 'Topic edit error', 'error' );

			return false;
		}
		if( ! isset( $title ) || ! $title = trim( strip_tags( $title ) ) ) {
			WPF()->notice->add( 'Please insert required fields!', 'error' );

			return false;
		}

		$title = wpforo_text( $title, 250, false );
		if( isset( $body ) ) $body = preg_replace( '#</pre>[\r\n\t\s\0]*<pre>#isu', "\r\n", $body );

		$diff = current_time( 'timestamp', 1 ) - strtotime( $topic['created'] );
		if( ! ( WPF()->perm->forum_can( 'et', $topic['forumid'] ) || ( WPF()->current_userid == $topic['userid'] && WPF()->perm->forum_can( 'eot', $topic['forumid'] ) ) ) ) {
			WPF()->notice->add( 'You have no permission to edit this topic', 'error' );

			return false;
		}

		if( ! WPF()->perm->forum_can( 'et', $topic['forumid'] ) && wpforo_setting( 'posting', 'edit_own_topic_durr' ) !== 0 && $diff > wpforo_setting( 'posting', 'edit_own_topic_durr' ) ) {
			WPF()->notice->add( 'The time to edit this topic is expired', 'error' );

			return false;
		}

		$title      = ( isset( $title ) ? stripslashes( $title ) : stripslashes( $topic['title'] ) );
		$type       = ( isset( $type ) ? $type : intval( $topic['type'] ) );
		$status     = ( isset( $status ) ? $status : intval( $topic['status'] ) );
		$private    = ( isset( $private ) ? $private : intval( $topic['private'] ) );
		$has_attach = ( isset( $body ) ? ( strpos( $body, '[attach]' ) !== false ? 1 : 0 ) : $topic['has_attach'] );
		$name       = ( isset( $name ) ? stripslashes( $name ) : stripslashes( $topic['name'] ) );
		$email      = ( isset( $email ) ? stripslashes( $email ) : stripslashes( $topic['email'] ) );
		$tags       = ( isset( $tags ) ? $tags : '' );

		$t_update = WPF()->db->update( WPF()->tables->topics,
		                               [
			                               'title'      => $title,
			                               'type'       => $type,
			                               'status'     => $status,
			                               'private'    => $private,
			                               'has_attach' => $has_attach,
			                               'name'       => $name,
			                               'email'      => $email,
			                               'tags'       => $tags,
		                               ],
		                               [ 'topicid' => $topicid ],
		                               [ '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s' ],
		                               [ '%d' ] );

		if( isset( $topic['first_postid'] ) ) {
			if( ! $post = WPF()->post->get_post( $topic['first_postid'] ) ) {
				WPF()->notice->add( 'Topic first post data not found.', 'error' );

				return false;
			}
		} else {
			WPF()->notice->add( 'Topic first post not found.', 'error' );

			return false;
		}

		$body = ( ( isset( $body ) && $body ) ? stripslashes( $body ) : stripslashes( $post['body'] ) );

		$p_update = WPF()->db->update( WPF()->tables->posts,
		                               [
			                               'title'    => $title,
			                               'body'     => $body,
			                               'modified' => current_time(
				                               'mysql',
				                               1
			                               ),
			                               'status'   => $status,
			                               'private'  => $private,
			                               'name'     => $name,
			                               'email'    => $email,
		                               ],
		                               [ 'postid' => intval( $topic['first_postid'] ) ],
		                               [ '%s', '%s', '%s', '%d', '%d', '%s', '%s' ],
		                               [ '%d' ] );

		if( $t_update !== false && $p_update !== false ) {

			if( isset( $tags ) ) $this->edit_tags( $tags, $topic );

			$a = [
				'userid'       => $topic['userid'],
				'forumid'      => $topic['forumid'],
				'topicid'      => $topicid,
				'postid'       => $topic['first_postid'],
				'first_postid' => $topic['first_postid'],
				'title'        => $title,
				'body'         => $body,
				'status'       => $status,
				'private'      => $private,
				'name'         => $name,
				'email'        => $email,
				'type'         => $type,
				'has_attach'   => $has_attach,
				'tags'         => $tags,
			];
			do_action( 'wpforo_after_edit_topic', $a, $args, $forum );

			wpforo_clean_cache( 'topic', $topicid, $topic );
			WPF()->notice->add( 'Topic successfully updated', 'success' );

			return $topicid;
		}

		WPF()->notice->add( 'Topic edit error', 'error' );

		return false;
	}

	#################################################################################

	/**
	 * Delete topic from DB
	 *
	 * Returns true if successfully deleted or false.
	 *
	 * @param int $topicid
	 * @param bool $delete_cache
	 *
	 * @return    bool
	 * @since 1.0.0
	 *
	 */
	function delete( $topicid = 0, $delete_cache = true, $check_permissions = true ) {
		$topicid = intval( $topicid );
		if( ! $topicid && isset( $_REQUEST['id'] ) ) $topicid = intval( $_REQUEST['id'] );

		if( ! $topic = $this->get_topic( $topicid ) ) return true;

		do_action( 'wpforo_before_delete_topic', $topic );

		if( $check_permissions ){
			$diff = current_time( 'timestamp', 1 ) - strtotime( $topic['created'] );
			if( ! ( WPF()->perm->forum_can( 'dt', $topic['forumid'] ) || ( WPF()->current_userid == $topic['userid'] && WPF()->perm->forum_can( 'dot', $topic['forumid'] ) ) ) ) {
				WPF()->notice->add( 'You don\'t have permission to delete topic from this forum.', 'error' );

				return false;
			}

			if( ! WPF()->perm->forum_can( 'dt', $topic['forumid'] ) && wpforo_setting( 'posting', 'delete_own_topic_durr' ) !== 0 && $diff > wpforo_setting( 'posting', 'delete_own_topic_durr' ) ) {
				WPF()->notice->add( 'The time to delete this topic is expired.', 'error' );

				return false;
			}
		}

		// START delete topic posts include first post
		if( $postids = WPF()->db->get_col(
			WPF()->db->prepare( "SELECT `postid` FROM `" . WPF()->tables->posts . "` WHERE `topicid` = %d ORDER BY `is_first_post`", $topicid )
		) ) {
			foreach( $postids as $postid ) {
				if( $postid == $topic['first_postid'] ) {
					return WPF()->post->delete( $postid, false, false, $exclude, false );
				} else {
					WPF()->post->delete( $postid, false, false, $exclude, false );
				}
			}
		}
		// END delete topic posts include first post

		if( WPF()->db->delete( WPF()->tables->topics, [ 'topicid' => $topicid ], [ '%d' ] ) ) {
			if( wpfval( $topic, 'tags' ) ) $this->remove_tags( $topic['tags'] );

			do_action( 'wpforo_after_delete_topic', $topic );

			if( $delete_cache ) wpforo_clean_cache( 'topic', $topicid, $topic );
			WPF()->notice->add( 'This topic successfully deleted', 'success' );
			return true;
		}

		WPF()->notice->add( 'Topics delete error', 'error' );

		return false;
	}

	#################################################################################

	/**
	 * array get_topic(array or id(num))
	 *
	 * Returns array from defined and default arguments.
	 *
	 * @param mixed $args defined arguments array for returning
	 * @param bool $protect
	 *
	 * @return    array
	 * @since 1.0.0
	 *
	 */

	public function _get_topic( $args = [], $protect = true ) {
		if( ! $args ) return [];

		if( is_array( $args ) ) {
			$default = [
				'topicid' => null,
				'slug'    => '',
			];
		} elseif( is_numeric( $args ) ) {
			$default = [
				'topicid' => $args,
				'slug'    => '',
			];
		} else {
			$default = [
				'topicid' => null,
				'slug'    => $args,
			];
		}

		$args = wpforo_parse_args( $args, $default );

		$sql    = "SELECT * FROM `" . WPF()->tables->topics . "`";
		$wheres = [];
		if( $topicid = wpforo_bigintval( $args['topicid'] ) ) $wheres[] = "`topicid` = " . $topicid;
		if( $args['slug'] ) $wheres[] = "`slug` = '" . esc_sql( $args['slug'] ) . "'";
		if( ! empty( $wheres ) ) $sql .= " WHERE " . implode( " AND ", $wheres );

		$topic = (array) WPF()->db->get_row( $sql, ARRAY_A );

		if( $protect ) {
			if( isset( $topic['forumid'] ) && $topic['forumid'] && ! WPF()->perm->forum_can( 'vf', $topic['forumid'] ) ) {
				return [];
			}
			if( isset( $topic['private'] ) && $topic['private'] && ! wpforo_is_owner( $topic['userid'], $topic['email'] ) ) {
				if( isset( $topic['forumid'] ) && $topic['forumid'] && ! WPF()->perm->forum_can( 'vp', $topic['forumid'] ) ) {
					return [];
				}
			}
			if( isset( $topic['status'] ) && $topic['status'] && ! wpforo_is_owner( $topic['userid'], $topic['email'] ) ) {
				if( isset( $topic['forumid'] ) && $topic['forumid'] && ! WPF()->perm->forum_can( 'au', $topic['forumid'] ) ) {
					WPF()->current_object['status'] = 'unapproved';

					return [];
				}
			}
		}

		if( $topic ) $topic['url'] = $this->get_topic_url( $topic );

		return $topic;
	}

	public function get_topic( $args = [], $protect = true ) {
		return wpforo_ram_get( [ $this, '_get_topic' ], $args, $protect );
	}

	/**
	 * array get_topic(array or id(num))
	 * Returns merged arguments array from defined and default arguments.
	 *
	 * @param array        defined arguments array for returning
	 *
	 * @return array where count is topic count and other numeric arrays with topic
	 * @since 1.0.0
	 *
	 */
	function get_topics( $args = [], &$items_count = 0, $count = true ) {
		$cache = WPF()->cache->on();

		$default = [
			'include'   => [],        // array( 2, 10, 25 )
			'exclude'   => [],        // array( 2, 10, 25 )
			'forumids'  => [],
			'forumid'   => null,
			'userid'    => null,            // user id in DB
			'type'      => null,            //0, 1, etc . . .
			'solved'    => null,
			'closed'    => null,
			'status'    => null,            //0, 1, etc . . .
			'private'   => null,            //0, 1, etc . . .''
			'pollid'    => null,
			'orderby'   => 'type, topicid',    // type, topicid, modified, created
			'order'     => 'DESC',        // ASC DESC
			'offset'    => null,        // this use when you give row_count
			'row_count' => null,        // 4 or 1 ...
			'permgroup' => null,        //Checks permissions based on attribute value not on current user usergroup
			'read'      => null,       //true / false
			'prefix'    => null,       //23 / 23,24,50
			'where'     => null,
		];

		$args = wpforo_parse_args( $args, $default );

		extract( $args, EXTR_OVERWRITE );

		if( $row_count === 0 ) return [];

		$include  = wpforo_parse_args( $include );
		$exclude  = wpforo_parse_args( $exclude );
		$forumids = wpforo_parse_args( $forumids );

		$guest  = [];
		$wheres = [];

		if( ! is_null( $prefix ) ) {
			$prefixes = explode( ',', $prefix );
			if( ! empty( $prefixes ) ) {
				foreach( $prefixes as $prefixid ) {
					$wheres[] = " FIND_IN_SET('" . intval( $prefixid ) . "', `prefix`) ";
				}
			}
		}

		if( ! is_null( $read ) ) {
			$last_read_postid = WPF()->log->get_all_read( 'post' );
			if( $read ) {
				if( $last_read_postid ) {
					$wheres[] = "`last_post` <= " . intval( $last_read_postid );
				}
				$include_read = WPF()->log->get_read();
				$include      = array_merge( $include, $include_read );
			} else {
				if( $last_read_postid ) {
					$wheres[] = "`last_post` > " . intval( $last_read_postid );
				}
				$exclude_read = WPF()->log->get_read();
				$exclude      = array_merge( $exclude, $exclude_read );
			}
		}

		if( ! empty( $include ) ) $wheres[] = "`topicid` IN(" . implode( ', ', array_map( 'intval', $include ) ) . ")";
		if( ! empty( $exclude ) ) $wheres[] = "`topicid` NOT IN(" . implode( ', ', array_map( 'intval', $exclude ) ) . ")";
		if( ! empty( $forumids ) ) $wheres[] = "`forumid` IN(" . implode( ', ', array_map( 'intval', $forumids ) ) . ")";
		if( ! is_null( $forumid ) ) $wheres[] = "`forumid` = " . intval( $forumid );
		if( ! is_null( $userid ) ) $wheres[] = "`userid` = " . wpforo_bigintval( $userid );
		if( ! is_null( $solved ) ) $wheres[] = "`solved` = " . intval( $solved );
		if( ! is_null( $closed ) ) $wheres[] = "`closed` = " . intval( $closed );
		if( ! is_null( $type ) ) $wheres[] = "`type` = " . intval( $type );
		if( ! is_null( $where ) ) $wheres[] = $where;

		if( ! is_user_logged_in() ) $guest = WPF()->member->get_guest_cookies();

		if( empty( $forumids ) ) {
			if( isset( $forumid ) && ! WPF()->perm->forum_can( 'vf', $forumid, $permgroup ) ) {
				return [];
			}
		}

		if( isset( $forumid ) && $forumid ) {
			if( WPF()->perm->forum_can( 'vp', $forumid, $permgroup ) ) {
				if( ! is_null( $private ) ) $wheres[] = " `private` = " . intval( $private );
			} elseif( isset( WPF()->current_userid ) && WPF()->current_userid ) {
				$wheres[] = " ( `private` = 0 OR (`private` = 1 AND `userid` = " . WPF()->current_userid . ") )";
			} elseif( wpfval( $guest, 'email' ) ) {
				$wheres[] = " ( `private` = 0 OR (`private` = 1 AND `email` = '" . sanitize_email( $guest['email'] ) . "') )";
			} else {
				$wheres[] = " `private` = 0";
			}
		} else {
			if( ! is_null( $private ) ) $wheres[] = " `private` = " . intval( $private );
		}

		if( isset( $forumid ) && $forumid ) {
			if( WPF()->perm->forum_can( 'au', $forumid, $permgroup ) ) {
				if( ! is_null( $status ) ) $wheres[] = " `status` = " . intval( $status );
			} elseif( isset( WPF()->current_userid ) && WPF()->current_userid ) {
				$wheres[] = " ( `status` = 0 OR (`status` = 1 AND `userid` = " . WPF()->current_userid . ") )";
			} elseif( wpfval( $guest, 'email' ) ) {
				$wheres[] = " ( `status` = 0 OR (`status` = 1 AND `email` = '" . sanitize_email( $guest['email'] ) . "') )";
			} else {
				$wheres[] = " `status` = 0";
			}
		} else {
			if( ! is_null( $status ) ) $wheres[] = " `status` = " . intval( $status );
		}

		if( function_exists( 'WPF_POLL' ) ) {
			if( ! is_null( $pollid ) ) $wheres[] = " `pollid` <> 0";
		}

		$sql = "SELECT * FROM `" . WPF()->tables->topics . "`";
		if( ! empty( $wheres ) ) {
			$sql .= " WHERE " . implode( " AND ", $wheres );
		}

		if( $count ) {
			$item_count_sql = preg_replace( '#SELECT.+?FROM#isu', 'SELECT count(*) FROM', $sql );
			if( $item_count_sql ) $items_count = WPF()->db->get_var( $item_count_sql );
		}

		$sql .= " ORDER BY " . str_replace( ',', ' ' . esc_sql( $order ) . ',', esc_sql( $orderby ) ) . " " . esc_sql( $order );

		if( ! is_null( $row_count ) ) {
			if( ! is_null( $offset ) ) {
				$sql .= esc_sql( " LIMIT $offset,$row_count" );
			} else {
				$sql .= esc_sql( " LIMIT $row_count" );
			}
		}

		if( $cache ) {
			$object_key   = md5( $sql . WPF()->current_user_groupid );
			$object_cache = WPF()->cache->get( $object_key );
			if( ! empty( $object_cache ) ) {
				if( ! empty( $object_cache['items'] ) ) {
					$access_filter = apply_filters( 'wpforo_topic_access_filter_cache', true );
					if( $access_filter ) {
						return $this->access_filter( $object_cache['items'], [ 'groupids' => ( array_filter( array_map( 'intval', (array) $permgroup ) ) ?: null )] );
					} else {
						return $object_cache['items'];
					}
				}
			}
		}

		$topics = WPF()->db->get_results( $sql, ARRAY_A );
		$topics = apply_filters( 'wpforo_get_topics', $topics );

		if( $cache && isset( $object_key ) && ! empty( $topics ) ) {
			self::$cache['topics'][ $object_key ]['items']       = $topics;
			self::$cache['topics'][ $object_key ]['items_count'] = $items_count;
		}

		if( ! empty( $forumids ) || ! $forumid ) {
			$topics = $this->access_filter( $topics, [ 'groupids' => ( array_filter( array_map( 'intval', (array) $permgroup ) ) ?: null )] );
		}

		return $topics;
	}

	function access_filter( $topics, $user = null ) {
		if( ! empty( $topics ) ) {
			foreach( $topics as $key => $topic ) {
				if( ! $this->view_access( $topic, $user ) ) unset( $topics[ $key ] );
			}
		}

		return $topics;
	}

	function view_access( $topic, $user = null ) {
		$groupids = wpfval( $user, 'groupids' );
		if( ! WPF()->perm->forum_can( 'vf', $topic['forumid'], $groupids ) ) return false;
		if(
			! wpforo_is_users_same( wpforo_member( $topic ), $user )
			&& (
				( (int) wpfval( $topic, 'private' ) && ! WPF()->perm->forum_can( 'vp', $topic['forumid'], $groupids ) )
				|| ( (int) wpfval( $topic, 'status'  ) && ! WPF()->perm->forum_can( 'au', $topic['forumid'], $groupids ) )
			)
		) return false;

		return true;
	}

	/**
	 * Search in your chosen column and return array with needles
	 *
	 * @param string $needle
	 *
	 * @param array $fields can be ( slug, title, body )
	 *
	 * @return array with  matches
	 * @since 1.0.0
	 *
	 */
	function search( $needle = '', $fields = [ 'title', 'body' ] ) {
		if( $needle !== '' ) {
			$needle = stripslashes( $needle );
			$fields = (array) $fields;

			$topicids = [];
			foreach( $fields as $field ) {
				if( $field === 'body' ) {
					$matches = WPF()->db->get_col( "SELECT `topicid` FROM " . WPF()->tables->posts . " WHERE `" . esc_sql( $field ) . "` LIKE '%" . esc_sql( sanitize_text_field( $needle ) ) . "%'" );
				} else {
					$matches = WPF()->db->get_col( "SELECT `topicid` FROM " . WPF()->tables->topics . " WHERE `" . esc_sql( $field ) . "`LIKE '%" . esc_sql( sanitize_text_field( $needle ) ) . "%'" );
				}
				$topicids = array_merge( $topicids, $matches );
			}

			return array_unique( $topicids );
		}

		return [];
	}

	function get_sum_answer( $forumids ) {
		$sum = WPF()->db->get_var( "SELECT SUM(`answers`) FROM `" . WPF()->tables->topics . "` WHERE `forumid` IN(" . implode( ', ', array_map( 'intval', $forumids ) ) . ")" );
		if( $sum ) return $sum;

		return 0;
	}

	function get_forumslug( $forumid ) {
		$slug = WPF()->db->get_var( "SELECT `slug` FROM " . WPF()->tables->forums . " WHERE `forumid` = " . intval( $forumid ) );
		if( $slug ) return $slug;

		return 0;
	}

	function get_forumslug_byid( $topicid ) {
		$slug = WPF()->db->get_var( "SELECT `slug` FROM " . WPF()->tables->forums . " WHERE `forumid` =(SELECT forumid FROM `" . WPF()->tables->topics . "` WHERE `topicid` =" . intval( $topicid ) . ")" );
		if( $slug ) return $slug;

		return 0;
	}

	function is_sticky( $topicid ) {
		if( $topicid ) {
			if( WPF()->cache->on() ) {
				$is_sticky = wpforo_topic( $topicid, 'type' );
			} else {
				$sql       = "SELECT `type` FROM " . WPF()->tables->topics . " WHERE `topicid` = %d";
				$is_sticky = WPF()->db->get_var( WPF()->db->prepare( $sql, $topicid ) );
			}

			return (bool) $is_sticky;
		}

		return false;
	}

	function is_private( $topicid ) {
		if( $topicid ) {
			if( WPF()->cache->on() ) {
				$private = wpforo_topic( $topicid, 'private' );
			} else {
				$sql     = "SELECT `private` FROM " . WPF()->tables->topics . " WHERE `topicid` = %d";
				$private = WPF()->db->get_var( WPF()->db->prepare( $sql, $topicid ) );
			}

			return (bool) $private;
		}

		return false;
	}

	function is_unapproved( $topicid ) {
		if( WPF()->cache->on() ) {
			$status = wpforo_topic( $topicid, 'status' );
		} else {
			$status = WPF()->db->get_var( "SELECT `status` FROM " . WPF()->tables->topics . " WHERE `topicid` = " . intval( $topicid ) );
		}
		if( $status == 1 ) return true;

		return false;
	}

	function is_closed( $topicid ) {
		if( $topicid ) {
			if( WPF()->cache->on() ) {
				$closed = wpforo_topic( $topicid, 'closed' );
			} else {
				$sql    = "SELECT `closed` FROM " . WPF()->tables->topics . " WHERE `topicid` = %d";
				$closed = WPF()->db->get_var( WPF()->db->prepare( $sql, $topicid ) );
			}

			return (bool) $closed;
		}

		return false;
	}

	function is_solved( $topicid ) {
		if( $topicid ) {
			$sql    = "SELECT `solved` FROM " . WPF()->tables->topics . " WHERE `topicid` = %d";
			$solved = WPF()->db->get_var( WPF()->db->prepare( $sql, $topicid ) );

			return (bool) $solved;
		}

		return false;
	}

	/**
	 * Move topic to another forum
	 *
	 * @param int $topicid
	 * @param int $forumid
	 *
	 * @return int|false $topicid on success, otherwise false
	 * @since 1.0.0
	 *
	 */
	function move( $topicid, $forumid ) {
		$topic = $this->get_topic( $topicid );
		if( WPF()->db->query( "UPDATE `" . WPF()->tables->topics . "` SET `forumid` = " . intval( $forumid ) . " WHERE `topicid` = " . intval( $topicid ) ) ) {
			WPF()->db->query( "UPDATE `" . WPF()->tables->posts . "` SET `forumid` = " . intval( $forumid ) . " WHERE `topicid` = " . intval( $topicid ) );

			do_action( 'wpforo_after_move_topic', $topic, $forumid );

			wpforo_clean_cache( 'topic', $topicid, $topic );
			WPF()->notice->add( 'Done!', 'success' );

			return $topicid;
		}

		WPF()->notice->add( 'Topic Move Error', 'error' );

		return false;
	}

	/**
	 * merge topic with target topic
	 *
	 * @param array $target target topic array
	 * @param array $current current topic array
	 * @param array $postids current topic postids array
	 * @param int $to_target_title Update post titles with target topic title
	 * @param int $append Update post dates and append to end
	 *
	 * @return bool true|false true on success, otherwise false
	 */
	public function merge( $target, $current = [], $postids = [], $to_target_title = 0, $append = 0 ) {
		if( ! $current ) $current = WPF()->current_object['topic'];

		$sql = "UPDATE `" . WPF()->tables->posts . "` SET `topicid` = %d, `forumid` = %d, `private` = %d,`is_first_post` = 0";
		$sql = WPF()->db->prepare( $sql, $target['topicid'], $target['forumid'], (int) wpfval( $target, 'private' ) );

		if( $append ) {
			$sql .= ", `modified` = %s, `created` = %s";
			$sql = WPF()->db->prepare( $sql, current_time( 'mysql', 1 ), current_time( 'mysql', 1 ) );
		}

		if( $to_target_title ) {
			$layout = WPF()->forum->get_layout( $target['forumid'] );
			$phrase = ( $layout == 3 ? wpforo_phrase( 'Answer to', false ) : wpforo_phrase( 'RE', false ) );
			$title  = $phrase . ': ' . $target['title'];
			$sql    .= ", `title` = %s";
			$sql    = WPF()->db->prepare( $sql, $title );
		}

		$sql .= " WHERE `topicid` = %d";
		$sql = WPF()->db->prepare( $sql, $current['topicid'] );

		if( $postids ) {
			$postids = (array) $postids;
			$postids = array_map( 'wpforo_bigintval', $postids );

			$sql .= " AND `postid` IN(" . implode( ',', $postids ) . ")";
		}

		do_action( 'wpforo_before_merge_topic', $target, $current, $postids, $to_target_title, $append );

		$db_resp = WPF()->db->query( $sql );

		if( $db_resp !== false ) {
			$sql = "SELECT COUNT(*) FROM `" . WPF()->tables->posts . "` WHERE `topicid` = %d";
			$sql = WPF()->db->prepare( $sql, $current['topicid'] );
			if( ! WPF()->db->get_var( $sql ) ) {
				$this->delete( $current['topicid'], true, false );
			} else {
				$this->rebuild_first_last( $current );
				$this->rebuild_stats( $current );
				$this->rebuild_threads( $current );
				wpforo_clean_cache( 'topic', $current['topicid'], $current );
			}

			$this->rebuild_first_last( $target );
			$this->rebuild_stats( $target );
			$this->rebuild_threads( $target );

			do_action( 'wpforo_after_merge_topic', $target, $current, $postids, $to_target_title, $append );

			WPF()->notice->clear();
			WPF()->notice->add( 'Done!', 'success' );

			wpforo_clean_cache();

			return true;
		}

		WPF()->notice->add( 'Data merging error', 'error' );
		return false;
	}

	public function split( $args, $to_target_title = 0 ) {
		if( ! $args ) return false;

		$args['name']  = ( isset( $args['name'] ) ? strip_tags( $args['name'] ) : '' );
		$args['email'] = ( isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '' );

		if( ! isset( $args['forumid'] ) || ! $args['forumid'] = intval( $args['forumid'] ) ) {
			WPF()->notice->add( 'Please select a target forum', 'error' );

			return false;
		}

		if( ! isset( $args['title'] ) || ! $args['title'] = trim( strip_tags( $args['title'] ) ) ) {
			WPF()->notice->add( 'Please insert required fields', 'error' );

			return false;
		}

		if( empty( $args['postids'] ) ) {
			WPF()->notice->add( 'Please select at least one post to split', 'error' );

			return false;
		}

		$args['postids'] = array_values( $args['postids'] );

		if( $fpost = WPF()->post->get_post( $args['postids'][0] ) ) {
			$args['title'] = wpforo_text( $args['title'], 250, false );
			$args['slug']  = ( isset( $args['slug'] ) && $args['slug'] ) ? sanitize_title( $args['slug'] ) : ( ( isset( $args['title'] ) ) ? sanitize_title( $args['title'] ) : md5( time() ) );
			if( ! trim( $args['slug'] ) ) $args['slug'] = md5( time() );
			$args['slug'] = $this->unique_slug( $args['slug'] );


			$args['body']    = $fpost['body'];
			$args['created'] = $fpost['created'];
			$args['userid']  = $fpost['userid'];
			$args['name']    = $fpost['name'];
			$args['email']   = $fpost['email'];

			extract( $args );

			if( isset( $forumid ) ) $forumid = intval( $forumid );
			if( isset( $title ) ) $title = sanitize_text_field( trim( $title ) );
			if( isset( $slug ) ) $slug = sanitize_title( $slug );
			if( isset( $created ) ) $created = sanitize_text_field( $created );
			if( isset( $userid ) ) $userid = intval( $userid );
			$type    = ( isset( $type ) && $type ? 1 : 0 );
			$status  = ( isset( $status ) && $status ? 1 : 0 );
			$private = ( isset( $private ) && $private ? 1 : 0 );
			if( isset( $name ) ) $name = strip_tags( trim( $name ) );
			if( isset( $email ) ) $email = strip_tags( trim( $email ) );
			$has_attach = ( isset( $has_attach ) && $has_attach ) ? 1 : ( ( strpos( $body, '[attach]' ) !== false ) ? 1 : 0 );

			if( WPF()->db->insert(
				WPF()->tables->topics,
               [
                   'title'      => stripslashes(
                       $title
                   ),
                   'slug'       => $slug,
                   'forumid'    => $forumid,
                   'userid'     => $userid,
                   'type'       => $type,
                   'status'     => $status,
                   'private'    => $private,
                   'created'    => $created,
                   'modified'   => $created,
                   'last_post'  => 0,
                   'views'      => 0,
                   'posts'      => 1,
                   'has_attach' => $has_attach,
                   'name'       => $name,
                   'email'      => $email,
               ],
                [ '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' ]
			) ) {
				$args['topicid'] = $topicid = WPF()->db->insert_id;

				if( $this->merge( $args, WPF()->current_object['topic'], $args['postids'], $to_target_title ) ) {
					WPF()->notice->clear();
					WPF()->notice->add( 'Done!', 'success' );

					return $topicid;
				}
			}
		}

		WPF()->notice->add( 'Topic splitting error', 'error' );

		return false;
	}

	public function _get_topic_url( $topic, $forum = [] ) {
		if( ! is_array( $topic ) ) $topic = $this->get_topic( $topic );
		if( $topic ) {
			if( is_array( $forum ) && ! empty( $forum ) ) {
				$forum_slug = $forum['slug'];
			} else {
				$forum_slug = '';
				if( wpfval( $topic, 'forumid' ) ) {
					$forum_slug = $this->get_forumslug( $topic['forumid'] );
				} elseif( wpfval( $topic, 'topicid' ) ) {
					$forum_slug = $this->get_forumslug_byid( $topic['topicid'] );
				}
			}
			if( wpfval( $topic, 'slug' ) ) return wpforo_home_url( $forum_slug . '/' . $topic['slug'] );
		}

		return wpforo_home_url();
	}

	public function get_topic_url( $topic, $forum = [] ) {
		return wpforo_ram_get( [ $this, '_get_topic_url' ], $topic, $forum );
	}


	function get_count( $args = [] ) {
		$sql = "SELECT SQL_NO_CACHE COUNT(*) FROM `" . WPF()->tables->topics . "`";
		if( ! empty( $args ) ) {
			$wheres = [];
			foreach( $args as $key => $value ) $wheres[] = "`$key` = '" . esc_sql( $value ) . "'";
			if( $wheres ) $sql .= " WHERE " . implode( ' AND ', $wheres );
		}

		return WPF()->db->get_var( $sql );
	}

	public function set_status( $topicid, $status ) {
		$topicid = wpforo_bigintval( $topicid );
		$status  = intval( $status );
		if( ! $topic = $this->get_topic( $topicid, false ) ) return false;

		if( $r = WPF()->db->update( WPF()->tables->topics, [ 'status' => $status ], [ 'topicid' => $topicid ], [ '%d' ], [ '%d' ] ) ) {
			if( $status ) {
				do_action( 'wpforo_topic_unapprove', $topic );
				if( wpfval( $topic, 'tags' ) ) $this->remove_tags( $topic['tags'] );
			} else {
				do_action( 'wpforo_topic_approve', $topic );
				if( wpfval( $topic, 'tags' ) ) $this->add_tags( $topic['tags'] );
			}

			do_action( 'wpforo_topic_status_update', $topic, $status );
			wpforo_clean_cache( 'topic', $topicid );
		}

		if( $r !== false ){
			WPF()->notice->add( 'Done!', 'success' );
			return true;
		}

		WPF()->notice->add( 'Status changing error', 'error' );
		return false;
	}

	public function wprivate( $topicid, $private ) {
		WPF()->db->update( WPF()->tables->topics, [ 'private' => $private ], [ 'topicid' => $topicid ], [ '%d' ], [ '%d' ] );

		WPF()->db->update( WPF()->tables->posts, [ 'private' => $private ], [ 'topicid' => $topicid ], [ '%d' ], [ '%d' ] );

		do_action( 'wpforo_topic_private_update', $topicid, $private );
		wpforo_clean_cache();

		return true;
	}

	public function delete_attachments( $topicid ) {
		$args  = [ 'topicid' => $topicid ];
		$posts = WPF()->post->get_posts( $args );
		if( ! empty( $posts ) ) {
			foreach( $posts as $post ) {
				WPF()->post->delete_attachments( $post['postid'] );
			}
		}
	}

	public function rebuild_stats( $topic ) {
		if( ! $topic ) return false;
		if( is_numeric( $topic ) ) $topic = $this->get_topic( $topic );
		if( ! is_array( $topic ) || ! $topic ) return false;

		$posts = WPF()->post->get_count( [ 'topicid' => $topic['topicid'], 'status' => 0 ] );

		$data        = [ 'posts' => $posts, 'answers' => 0 ];
		$data_format = [ '%d', '%d' ];

		$layout = WPF()->forum->get_layout( $topic['forumid'] );
		if( $layout === 3 ) {
			$data['answers'] = WPF()->post->get_count( [ 'topicid' => $topic['topicid'], 'status' => 0, 'parentid' => 0, 'is_first_post' => 0 ] );
			$data['posts']   = $posts - 1;
		}

		if( $r = WPF()->db->update( WPF()->tables->topics, $data, [ 'topicid' => $topic['topicid'] ], $data_format, [ '%d' ] ) ) {
			wpforo_clean_cache( 'topic', $topic['topicid'], $topic );
		}

		return $r !== false;
	}

	public function rebuild_first_last( $topic ) {
		if( ! $topic ) return false;
		if( is_numeric( $topic ) ) $topic = $this->get_topic( $topic );
		if( ! is_array( $topic ) || ! $topic ) return false;

		$sql = "SELECT `postid` FROM `" . WPF()->tables->posts . "` WHERE `topicid` = %d ORDER BY `is_first_post` DESC, `created` ASC, `postid` ASC LIMIT 1";
		if( $first_postid = WPF()->db->get_var( WPF()->db->prepare( $sql, $topic['topicid'] ) ) ) {
			$sql = "UPDATE `" . WPF()->tables->posts . "` SET `is_first_post` = 1 WHERE `postid` = %d";
			WPF()->db->query( WPF()->db->prepare( $sql, $first_postid ) );

			do_action( 'wpforo_after_is_first_post_update', $first_postid, 1 );
		} else {
			$first_postid = 0;
		}

		$sql = "SELECT `postid`, `created` 
			FROM `" . WPF()->tables->posts . "` 
			WHERE `topicid` = %d 
			ORDER BY `is_first_post` ASC, `created` DESC, `postid` DESC LIMIT 1";
		if( ! $last_post = WPF()->db->get_row( WPF()->db->prepare( $sql, $topic['topicid'] ), ARRAY_A ) ) {
			$last_post = [ 'postid' => 0, 'created' => $topic['modified'] ];
		}

		if( $r = WPF()->db->update(
			WPF()->tables->topics,
			[
				'first_postid' => $first_postid,
				'last_post'    => $last_post['postid'],
				'modified'     => $last_post['created'],
			],
			[ 'topicid' => $topic['topicid'] ],
			[ '%d', '%d', '%s' ],
			[ '%d' ]
		) ) {
			wpforo_clean_cache( 'topic' );
		}

		return $r !== false;
	}

	function rebuild_threads( $topicid, $root = null ) {
		if( ! is_null( $root ) && $root <= 0 ) return;
		if( is_array( $topicid ) && wpfval( $topicid, 'topicid' ) ) $topicid = $topicid['topicid'];
		if( $topicid ) {
			$posts = WPF()->db->get_results( "SELECT * FROM `" . WPF()->tables->posts . "` WHERE `topicid` = " . intval( $topicid ), ARRAY_A );
			if( ! empty( $posts ) ) {
				$threads = [];
				foreach( $posts as $post ) {
					$threads[ $post['postid'] ] = [ 'postid' => $post['postid'], 'parentid' => $post['parentid'] ];
				}
				unset( $posts );
				foreach( $threads as $item ) {
					if( ! is_null( $root ) && $root != $item['postid'] ) continue;
					if( ! $item['parentid'] ) {
						$thread = WPF()->post->build_thread_data( $item['postid'], $threads );
						if( wpfval( $thread, 'children' ) ) {
							$children = implode( ',', $thread['children'] );
							WPF()->db->query( "UPDATE `" . WPF()->tables->posts . "` SET `root` = 0 WHERE `postid` = " . intval( $item['postid'] ) );
							WPF()->db->query( "UPDATE `" . WPF()->tables->posts . "` SET `root` = " . intval( $item['postid'] ) . " WHERE `postid` IN(" . esc_sql( $children ) . ")" );
						}
					} elseif( ! wpfkey( $threads, $item['parentid'] ) ) {
						WPF()->db->query( "UPDATE `" . WPF()->tables->posts . "` SET `root` = 0, `parentid` = 0 WHERE `postid` = " . intval( $item['postid'] ) );
					}
				}
			}
		}
	}

	function rebuild_forum_threads( $forumid = 0 ) {
		if( ! $forumid ) {
			$args   = [ 'layout' => 4 ];
			$forums = WPF()->forum->get_forums( $args );
			if( ! empty( $forums ) ) {
				foreach( $forums as $forum ) {
					$args   = [ 'forumid' => $forum['forumid'] ];
					$topics = $this->get_topics( $args );
					if( ! empty( $topics ) ) {
						foreach( $topics as $topic ) {
							$this->rebuild_threads( $topic['topicid'] );
						}
					}
				}
			}
		} else {
			$args   = [ 'forumid' => $forumid ];
			$topics = $this->get_topics( $args );
			if( ! empty( $topics ) ) {
				foreach( $topics as $topic ) {
					$this->rebuild_threads( $topic['topicid'] );
				}
			}
		}
	}

	public function members( $topicid, $limit = 0 ) {
		if( ! $topicid ) return [];
		$members = [];
		$args    = [
			'topicid'    => $topicid,
			'orderby'    => 'created',
			'order'      => 'ASC',
			'private'    => 0,
			'status'     => 0,
			'cache_type' => 'args',
		];
		$posts   = WPF()->post->get_posts( $args );
		foreach( $posts as $post ) {
			if( wpfval( $post, 'userid' ) ) {
				$members[ $post['userid'] ] = wpforo_member( $post['userid'] );
				if( $limit && count( $members ) >= $limit ) break;
			}
		}

		return array_filter( $members );
	}

	public function can_answer( $topicid ) {
		if( ! $topicid ) return false;
		$topic = wpforo_topic( $topicid );
		if( wpfval( $topic, 'topicid' ) ) {
			if( wpfval( $topic, 'userid' ) ) {
				if( ! WPF()->perm->forum_can( 'aot', $topic['forumid'] ) && WPF()->current_userid == $topic['userid'] ) {
					return false;
				}
			} else {
				$guest = WPF()->member->get_guest_cookies();
				if( wpfval( $topic, 'email' ) && wpfval( $guest, 'email' ) ) {
					if( ! WPF()->perm->forum_can( 'aot', $topic['forumid'] ) && $topic['email'] == $guest['email'] ) {
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * @param $topicid
	 *
	 * @return bool
	 */
	public function has_is_answer_post( $topicid ) {
		if( ! $topicid ) return false;
		$sql = "SELECT EXISTS ( SELECT * FROM `" . WPF()->tables->posts . "` WHERE `is_answer` = 1 AND `topicid` = %d) AS is_exists";
		$sql = WPF()->db->prepare( $sql, $topicid );

		return (bool) WPF()->db->get_var( $sql );
	}

	public function get_postids( $topicid ) {
		$sql = "SELECT `postid` 
			FROM `" . WPF()->tables->posts . "` 
			WHERE `topicid` = %d";
		$sql = WPF()->db->prepare( $sql, $topicid );
		return array_map( 'wpforo_bigintval', WPF()->db->get_col( $sql ) );
	}

	public function add_tags( $tags ) {
		if( $tags ) {
			$tags = $this->sanitize_tags( $tags, true );
			if( ! empty( $tags ) ) {
				$tags = array_slice( $tags, 0, wpforo_setting( 'tags', 'max_per_topic' ) );
				foreach( $tags as $tag ) {
					$count = (int) WPF()->db->get_var( "SELECT `count` FROM `" . WPF()->tables->tags . "` WHERE `tag` = '" . esc_sql( $tag ) . "'" );
					if( $count ) {
						WPF()->db->update( WPF()->tables->tags, [ 'count' => $count + 1 ], [ 'tag' => $tag ], [ '%d' ], [ '%s' ] );
					} else {
						$this->add_tag( $tag, 1 );
					}
					wpforo_clean_cache( 'tag', $tag );
				}
				wpforo_clean_cache( 'tag' );
			}
		}
	}

	public function add_tag( $tag, $count = 0 ) {
		$tagid = 0;
		if( $tag ) {
			$tag = $this->sanitize_tag( $tag );
			if( ! WPF()->db->get_var( "SELECT `tagid` FROM `" . WPF()->tables->tags . "` WHERE `tag` = '" . esc_sql( $tag ) . "'" ) ) {
				$tagid = WPF()->db->insert( WPF()->tables->tags, [ 'tag' => $tag, 'prefix' => 0, 'count' => intval( $count ) ], [ '%s', '%d', '%d' ] );
			}
		}

		return $tagid;
	}

	public function edit_tags( $tags, $topic = [] ) {
		$old_tags = ( wpfval( $topic, 'tags' ) ) ? $this->sanitize_tags( $topic['tags'], true ) : false;
		if( $tags ) {
			$tags = $this->sanitize_tags( $tags, true );
			$tags = array_slice( $tags, 0, wpforo_setting( 'tags', 'max_per_topic' ) );
			if( ! empty( $tags ) ) {
				if( wpfval( $topic, 'topicid' ) ) {
					if( ! empty( $old_tags ) ) {
						foreach( $old_tags as $old_tag ) {
							if( ! in_array( $old_tag, $tags ) ) {
								$this->remove_tags( $old_tag );
							}
						}
					}
				}
				if( ! wpfval( $topic, 'status' ) && ! wpfval( $topic, 'private' ) ) {
					foreach( $tags as $tag ) {
						$count = (int) WPF()->db->get_var( "SELECT `count` FROM `" . WPF()->tables->tags . "` WHERE `tag` = '" . esc_sql( $tag ) . "'" );
						if( ! $count ) {
							WPF()->db->insert( WPF()->tables->tags, [ 'tag' => $tag, 'prefix' => 0, 'count' => 1 ], [ '%s', '%d', '%d' ] );
						} elseif( empty( $old_tags ) || ! in_array( $tag, $old_tags ) ) {
							WPF()->db->update( WPF()->tables->tags, [ 'count' => ( $count + 1 ) ], [ 'tag' => $tag ], [ '%d' ], [ '%s' ] );
						}
						wpforo_clean_cache( 'tag', $tag );
					}
				}
			}
		} else {
			if( ! empty( $old_tags ) && wpfval( $topic, 'topicid' ) ) {
				WPF()->db->update( WPF()->tables->topics, [ 'tags' => '' ], [ 'topicid' => $topic['topicid'] ], [ '%s' ], [ '%d' ] );
				$this->remove_tags( $old_tags );
			}
		}
		wpforo_clean_cache( 'tag' );
	}

	public function edit_tag( $tag, $old_tag = '', $disconnect = false ) {

		$tag_id   = wpfval( $tag, 'tagid' );
		$tag_name = wpfval( $tag, 'tag' );
		$tag_name = $this->sanitize_tag( $tag_name );

		if( false !== WPF()->db->update( WPF()->tables->tags, [ 'tag' => $tag_name ], [ 'tagid' => intval( $tag_id ) ], [ '%s' ], [ '%d' ] ) ) {
			if( $old_tag ) {
				if( $disconnect ) {
					$this->remove_tag_from_topics( $old_tag );
					WPF()->db->update( WPF()->tables->tags, [ 'count' => 0 ], [ 'tag' => $tag_name ], [ '%d' ], [ '%s' ] );
				} else {
					if( $old_tag != $tag_name ) {
						$this->update_tag_in_topics( $tag_name, $old_tag );
					}
					$count = WPF()->db->get_var( "SELECT COUNT(*) FROM `" . WPF()->tables->topics . "` WHERE FIND_IN_SET('" . esc_sql( $tag_name ) . "', tags)" );
					WPF()->db->update( WPF()->tables->tags, [ 'count' => intval( $count ) ], [ 'tag' => $tag_name ], [ '%d' ], [ '%s' ] );
				}
			}
			wpforo_clean_cache( 'tag', $tag_name );

			return true;
		}

		return false;
	}

	public function remove_tags( $tags ) {
		if( $tags ) {
			$tags = $this->sanitize_tags( $tags, true );
			foreach( $tags as $tag ) {
				$count = (int) WPF()->db->get_var( "SELECT `count` FROM `" . WPF()->tables->tags . "` WHERE `tag` = '" . esc_sql( $tag ) . "'" );
				if( $count === 1 ) {
					WPF()->db->query( "DELETE FROM `" . WPF()->tables->tags . "` WHERE `tag` = '" . esc_sql( $tag ) . "'" );
				} else {
					WPF()->db->update( WPF()->tables->tags, [ 'count' => ( $count - 1 ) ], [ 'tag' => $tag ], [ '%d' ], [ '%s' ] );
				}
				wpforo_clean_cache( 'tag', $tag );
			}
		}
		wpforo_clean_cache( 'tag' );
	}

	public function remove_tag( $tag ) {
		if( $tag ) {
			$this->remove_tag_from_topics( $tag );
			$sql_delete_tag = "DELETE FROM `" . WPF()->tables->tags . "` WHERE `tag` = '" . esc_sql( $tag ) . "'";
			WPF()->db->query( $sql_delete_tag );
			wpforo_clean_cache( 'tag', $tag );
		}
	}

	public function remove_tag_from_topics( $tag ) {
		if( $tag ) {
			WPF()->db->query(
				"UPDATE `" . WPF()->tables->topics . "`
                                            SET tags = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', tags, ','), '," . esc_sql( $tag ) . ",', ','))
                                                    WHERE FIND_IN_SET('" . esc_sql( $tag ) . "', tags)"
			);
		}
	}

	public function update_tag_in_topics( $new_tag, $old_tag ) {
		if( $new_tag && $old_tag ) {

			WPF()->db->query(
				"UPDATE `" . WPF()->tables->topics . "` 
                                    SET tags = '" . esc_sql( $new_tag ) . "' 
                                        WHERE tags LIKE '" . esc_sql( $old_tag ) . "'"
			);

			WPF()->db->query(
				"UPDATE `" . WPF()->tables->topics . "` 
                                    SET tags = REPLACE(tags, '," . esc_sql( $old_tag ) . ",', '," . esc_sql( $new_tag ) . ",') 
                                           WHERE tags LIKE '%," . esc_sql( $old_tag ) . ",%'"
			);

			$topics = WPF()->db->get_results(
				"SELECT `topicid`, `tags` 
                                                        FROM `" . WPF()->tables->topics . "` 
                                                            WHERE FIND_IN_SET('" . esc_sql( $old_tag ) . "', tags)",
				ARRAY_A
			);
			if( ! empty( $topics ) ) {
				foreach( $topics as $topic ) {
					$tags = explode( ',', $topic['tags'] );
					if( ! empty( $tags ) ) {
						foreach( $tags as $key => $tag ) {
							if( $tag === $old_tag ) $tags[ $key ] = $new_tag;
						}
						$tags = implode( ',', $tags );
						WPF()->db->update( WPF()->tables->topics, [ 'tags' => $tags ], [ 'topicid' => $topic['topicid'] ], [ '%s' ], [ '%d' ] );
					}
				}
			}
		}
	}

	public function get_tag( $args = [] ) {
		if( $args ) {
			$default = [
				'tagid' => null,
				'tag'   => null,
			];
			if( is_numeric( $args ) ) {
				$args = [ 'tagid' => $args ];
			} elseif( is_string( $args ) ) {
				$args = [ 'tag' => $args ];
			}

			$args   = wpforo_parse_args( $args, $default );
			$sql    = "SELECT * FROM `" . WPF()->tables->tags . "`";
			$wheres = [];
			if( $args['tagid'] ) $wheres[] = "`tagid` = " . intval( $args['tagid'] );
			if( $args['tag'] ) $wheres[] = "`tag` = '" . esc_sql( $args['tag'] ) . "'";
			if( $wheres ) {
				$sql .= " WHERE " . implode( " AND ", $wheres );

				if( WPF()->ram_cache->exists( $sql ) ) {
					$tag = WPF()->ram_cache->get( $sql );
				} else {
					$tag = WPF()->db->get_row( $sql, ARRAY_A );
					WPF()->ram_cache->set( $sql, $tag );
				}

				return $tag;
			}
		}

		return [];
	}

	public function get_tags( $args = [], &$items_count = 0 ) {
		$cache   = WPF()->cache->on();
		$default = [
			'tag'       => '',
			'prefix'    => 0,
			'count'     => null,
			'orderby'   => 'count',
			'order'     => 'DESC',
			'offset'    => null,
			'row_count' => null,
		];

		$args     = wpforo_parse_args( $args, $default );
		$sql      = "SELECT * FROM `" . WPF()->tables->tags . "`";
		$wheres   = [];
		$wheres[] = " `prefix` = " . intval( $args['prefix'] );
		if( ! empty( $wheres ) ) $sql .= " WHERE " . implode( " AND ", $wheres );
		$item_count_sql = preg_replace( '#SELECT.+?FROM#isu', 'SELECT count(*) FROM', $sql );
		if( $item_count_sql ) $items_count = WPF()->db->get_var( $item_count_sql );

		$sql .= " ORDER BY `" . esc_sql( $args['orderby'] ) . "` " . esc_sql( $args['order'] );

		if( $args['row_count'] != null ) {
			if( $args['offset'] != null ) {
				$sql .= " LIMIT " . intval( $args['offset'] ) . ',' . intval( $args['row_count'] );
			} else {
				$sql .= " LIMIT " . intval( $args['row_count'] );
			}
		}

		if( $cache ) {
			$object_key   = md5( $sql . WPF()->current_user_groupid );
			$object_cache = WPF()->cache->get( $object_key, 'loop', 'tag' );
			if( ! empty( $object_cache ) ) {
				$items_count = $object_cache['items_count'];

				return $object_cache['items'];
			}
		}

		$tags = WPF()->db->get_results( $sql, ARRAY_A );
		$tags = apply_filters( 'wpforo_get_tags', $tags );

		if( $cache && isset( $object_key ) && ! empty( $tags ) ) {
			self::$cache['tags'][ $object_key ]['items']       = $tags;
			self::$cache['tags'][ $object_key ]['items_count'] = $items_count;
			WPF()->cache->create( 'loop', self::$cache, 'tag' );
		}

		return $tags;
	}

	public function sanitize_tags( $tags, $array = false, $limit = false ) {
		if( $tags ) {
			$lowcase = wpforo_setting( 'tags', 'lowercase' );
			if( ! is_array( $tags ) ) {
				$tags = wp_unslash( $tags );
				$tags = explode( ',', $tags );
			}
			if( $lowcase ) {
				if( function_exists( 'mb_strtolower' ) ) {
					$tags = array_map( 'mb_strtolower', $tags );
				} else {
					$tags = array_map( 'strtolower', $tags );
				}
			}
			$length    = wpforo_setting( 'tags', 'length' );
			$mb_substr = function_exists( 'mb_substr' );
			foreach( $tags as $key => $tag ) {
				if( $mb_substr ) {
					$tags[ $key ] = mb_substr( $tag, 0, $length );
				} else {
					$tags[ $key ] = substr( $tag, 0, $length );
				}
			}
			$tags = array_map( 'trim', $tags );
			$tags = array_map( 'sanitize_text_field', $tags );
			$tags = array_filter( $tags );
			$tags = array_unique( $tags );
			if( $limit ) {
				$tags = array_slice( $tags, 0, wpforo_setting( 'tags', 'max_per_topic' ) );
			}
			if( $array ) {
				return $tags;
			} else {
				return implode( ',', $tags );
			}
		}

		if( $array ) {
			return [];
		} else {
			return '';
		}
	}

	public function sanitize_tag( $tag ) {
		$tag     = trim( $tag );
		$tag     = wp_unslash( $tag );
		$tag     = sanitize_text_field( $tag );
		$length  = wpforo_setting( 'tags', 'length' );
		$tag     = ( function_exists( 'mb_substr' ) ) ? mb_substr( $tag, 0, $length ) : substr( $tag, 0, $length );
		$lowcase = wpforo_setting( 'tags', 'lowercase' );
		if( $lowcase ) {
			$tag = ( function_exists( 'mb_strtolower' ) ) ? mb_strtolower( $tag ) : strtolower( $tag );
		}

		return $tag;
	}

	public function after_add_post( $post, $topic ) {
		if( !intval( $post['status'] ) ){
			$this->rebuild_first_last( $topic );
			$this->rebuild_stats( $topic );
		}
	}

	public function after_delete_post( $post ) {
		if( !intval( $post['status'] ) ){
			$this->rebuild_first_last( $post['topicid'] );
			$this->rebuild_stats( $post['topicid'] );
		}
	}

	public function after_post_status_update( $post ) {
		if( $topic = $this->get_topic( $post['topicid'] ) ){
			$this->rebuild_first_last( $topic );
			$this->rebuild_stats( $topic );
		}
	}

	public function after_delete_user( $userid, $reassign ) {
		if( $boardids = WPF()->board->get_active_boardids() ){
			if( is_null( $reassign ) ) {

				foreach( $boardids as $boardid ){
					WPF()->change_board( $boardid );
					if( $topicids = WPF()->db->get_col( WPF()->db->prepare( "SELECT `topicid` FROM `" . WPF()->tables->topics . "` WHERE userid = %d", $userid ) ) ) {
						foreach( $topicids as $topicid ) $this->delete( $topicid, false, false );
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
					WPF()->db->update( WPF()->tables->topics, $data, [ 'userid' => $userid ], $format, ['%d'] );
				}

			}
		}
	}
}
