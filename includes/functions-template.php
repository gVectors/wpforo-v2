<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

register_nav_menus( [ 'wpforo-menu' => esc_html__( 'wpForo Menu', 'wpforo' ), ] );

add_filter(
	'wp_get_nav_menu_items',
	function( $items ) {
		if( ! wpforo_is_admin() ) {
			foreach( $items as $key => $item ) {
				if( isset( $item->url ) ) {
					if( strpos( $item->url, '%wpforo-' ) !== false ) {
						$shortcode = trim( str_replace( [ 'https://', 'http://', '/', '%' ], '', $item->url ) );
						if( isset( WPF()->tpl->menu ) && isset( WPF()->tpl->menu[ $shortcode ] ) ) {
							if( isset( WPF()->tpl->menu[ $shortcode ]['href'] ) ) {
								$item->url = WPF()->tpl->menu[ $shortcode ]['href'];
							}
							if( wpfval( WPF()->tpl->menu[ $shortcode ], 'is_active' ) || ( isset(
								                                                               WPF()->tpl->menu[ $shortcode ]['attr']
							                                                               ) && strpos(
								                                                                    WPF()->tpl->menu[ $shortcode ]['attr'],
								                                                                    'wpforo-active'
							                                                                    ) !== false ) ) {
								$item->classes[] = 'wpforo-active';
							}
						} else {
							unset( $items[ $key ] );
						}
					}
				}
			}
		}

		return $items;
	},
	1
);

function wpforo_menu_nofollow_items( $item_output, $item, $depth, $args ) {
	//if( isset($item->url) && strpos($item->url, '?foro') !== FALSE ) {
	//$item_output = str_replace('<a ', '<a rel="nofollow" ', $item_output);
	//}
	return $item_output;
}

add_filter( 'walker_nav_menu_start_el', 'wpforo_menu_nofollow_items', 1, 4 );

function wpforo_profile_plugin_menu( $user ) {
	$userid = $user['userid'];
	$menu_html = '';

	if( $url = wpforo_has_shop_plugin() ) {
		$menu_html     .= '<div id="wpf-pp-shop-menu" class="wpf-pp-menu">
                <a class="wpf-pp-menu-item" href="' . esc_url( $url ) . '">
                    <i class="fas fa-shopping-cart" title="' . wpforo_phrase(
				'Shop Account',
				false
			) . '"></i> <span>' . wpforo_phrase( 'Shop Account', false ) . '</span>
                </a>
			</div>';
	}
	if( $url = wpforo_has_profile_plugin( $userid ) ) {
		$menu_html     .= '<div id="wpf-pp-site-menu" class="wpf-pp-menu">
            <a class="wpf-pp-menu-item" href="' . esc_url( $url ) . '">
                <i class="fas fa-user" title="' . wpforo_phrase(
				'Site Profile',
				false
			) . '"></i> <span>' . wpforo_phrase( 'Site Profile', false ) . '</span>
            </a>
        </div>';
	}

	$menu_html = apply_filters( 'wpforo_profile_top_bar', $menu_html, $userid );
	if( $menu_html ) echo '<div class="wpf-profile-plugin-menu">' . $menu_html . '<div class="wpf-clear"></div></div>';
}

//add_action( 'wpforo_template_profile_action_buttons_left', 'wpforo_profile_plugin_menu' );

function wpforo_post_edited( $post, $echo = true ) {
	$edit_html = '';
	if( ! empty( $post ) ) {
		$created  = wpforo_date( $post['created'], 'd/m/Y g:i a', false );
		$modified = wpforo_date( $post['modified'], 'd/m/Y g:i a', false );
		if( isset( $modified ) && $created != $modified ) {
			if( $post['is_first_post'] && wpforo_setting( 'posting', 'edit_topic' ) ) {
				$edit_html = WPF()->activity->build( 'topic', $post['topicid'], 'edit_topic' );
			} elseif( wpforo_setting( 'posting', 'edit_post' ) ) {
				$edit_html = WPF()->activity->build( 'post', $post['postid'], 'edit_post' );
			}
			$edit_html = ( $edit_html ) ? sprintf( '<div class="wpf-post-edit-wrap">%s</div>', $edit_html ) : '';
		}
	}
	if( $echo ) {
		echo $edit_html;
	} else {
		return $edit_html;
	}
}

function wpforo_hide_title( $title, $id = 0 ) {
	if( ! wpforo_setting( 'components', 'page_title' )
        && is_wpforo_page()
        && $id === WPF()->board->get_current( 'pageid' )
        && in_the_loop() && is_page( $id )
    ) $title = '';

	return $title;
}

add_filter( 'the_title', 'wpforo_hide_title', 10, 2 );

function wpforo_validate_gravatar( $email ) {
	$hashkey = md5( strtolower( trim( $email ) ) );
	$uri     = 'http://www.gravatar.com/avatar/' . $hashkey . '?d=404';
	$data    = wp_cache_get( $hashkey );
	if( false === $data ) {
		$response = wp_remote_head( $uri );
		if( is_wp_error( $response ) ) {
			$data = 'not200';
		} else {
			$data = $response['response']['code'];
		}
		wp_cache_set( $hashkey, $data, $group = '', $expire = 60 * 5 );
	}
	if( $data == '200' ) {
		return true;
	} else {
		return false;
	}
}

function wpforo_member_title( $member = [], $echo = true, $before = '', $after = '', $exclude = [] ) {
	$title = [];

	if( empty( $member ) || ! $member['groupid'] ) return '';
	$rating_title_ug_enabled     = in_array( $member['groupid'], wpforo_setting( 'rating', 'rating_title_ug' ), true );
	$usergroup_title_ug_enabled  = in_array( $member['groupid'], wpforo_setting( 'profiles', 'title_groupids' ), true );
	$usergroup_title_sug_enabled = in_array( $member['groupid'], wpforo_setting( 'profiles', 'title_secondary_groupids' ), true );

	if( ! in_array( 'rating-title', $exclude, true ) && wpforo_setting( 'rating', 'rating_title' ) && $rating_title_ug_enabled && isset( $member['rating']['title'] ) ) {
		$title[] = '<span class="wpf-member-title wpfrt" title="' . wpforo_phrase(
				'Rating Title',
				false
			) . '">' . esc_html( $member['rating']['title'] ) . '</span>';
	}
	if( ! in_array( 'custom-title', $exclude, true ) && empty( $title ) && wpforo_setting( 'profiles', 'custom_title_is_on' ) ) {
		$title[] = '<span class="wpf-member-title wpfct" title="' . wpforo_phrase(
				'User Title',
				false
			) . '">' . wpforo_phrase( $member['title'], false ) . '</span>';
	} else {
		$before = $after = '';
	}
	if( ! in_array( 'usergroup', $exclude, true ) && $usergroup_title_ug_enabled ) {
		$class = '';
        if( $member['groupid'] == 1 ) $class = ' wpfbr-b wpfcl-b';
        if( $member['groupid'] == 2 ) $class = ' wpfbr-5 wpfcl-5';
        if( $member['groupid'] == 4 ) $class = ' wpfbg-2 wpfcl-3';
        if( !in_array( $member['groupid'], array(1,2,4)) ) $class = ' wpfbr-7';
		$title[] = '<span class="wpf-member-title wpfut wpfug-' . intval(
				$member['groupid']
			) . $class . '" title="' . wpforo_phrase( 'Usergroup', false ) . '">' . esc_html(
			           $member['group_name']
		           ) . '</span>';
	}
	if( ! in_array( 'usergroup', $exclude, true ) && $usergroup_title_sug_enabled ) {
		$secondary_group_names = $member['secondary_groupids'] ? WPF()->usergroup->get_secondary_group_names( $member['secondary_groupids'] ) : [];
		if( $secondary_group_names ) {
			$title[] = '<span class="wpf-member-title wpfut wpfsut" title="' . wpforo_phrase(
					'Secondary Usergroup',
					false
				) . '">' . esc_html( implode( ', ', $secondary_group_names ) ) . '</span>';
		}
	}
	$title_html = '';
	if( ! empty( $title ) ) {
		$title_html = $before . implode( ' ', $title ) . $after;
		$title_html = apply_filters( 'wpforo_member_title', $title_html, $member );
	}
    if( $echo ) echo $title_html;
    return $title_html;
}

function wpforo_member_badge( $member = [], $sep = '', $type = 'full' ) {
	$rating_badge_ug_enabled = in_array( $member['groupid'], wpforo_setting( 'rating', 'rating_badge_ug' ), true );
	if( wpforo_setting( 'rating', 'rating' ) && $rating_badge_ug_enabled && isset( $member['rating']['level'] ) ): ?>
    <div class="author-rating-<?php echo esc_attr( $type ) ?>"
         style="color:<?php echo esc_attr( $member['rating']['color'] ) ?>"
         title="<?php wpforo_phrase( 'Member Rating Badge' ) ?>">
		<?php echo WPF()->member->rating_badge( $member['rating']['level'], $type ); ?>
        </div><?php if( $sep ): ?><span class="author-rating-sep"><?php echo esc_html( $sep ); ?></span><?php endif; ?>
	<?php endif;

	do_action( 'wpforo_after_member_badge', $member );
}

function wpforo_member_nicename( $member = [], $prefix = '', $bracket = true, $wrap = true, $class = 'wpf-author-nicename', $echo = true ) {
	if( ! wpforo_setting( 'profiles', 'mention_nicknames' ) || empty( $member ) || ! isset( $member['user_nicename'] ) ) {
		return '';
	}
	$nicename = '';
	if( $wrap ) {
		$nicename .= '<div class="' . $class . '" title="' . wpforo_phrase(
				'You can mention a person using @nicename in post content to send that person an email message. When you post a topic or reply, forum sends an email message to the user letting them know that they have been mentioned on the post.',
				false
			) . '">';
	}
	if( $bracket ) $nicename .= '(';
	$nicename .= $prefix . urldecode( $member['user_nicename'] );
	if( $bracket ) $nicename .= ')';
	if( $wrap ) {
		$nicename .= '</div>';
	}
	$nicename = apply_filters( 'wpforo_member_nicename', $nicename, $member );
	if( $echo ) {
		echo $nicename;
	} else {
		return $nicename;
	}
}

function wpforo_get_body_classes() {
	$classes = [
		'wpf-'  . esc_attr( wpforo_setting( 'styles', 'color_style' ) ),
		'wpft-' . esc_attr( WPF()->current_object['template'] ),
		(WPF()->current_userid ? 'wpf-auth' : 'wpf-guest'),
		'wpfu-group-' . WPF()->current_user_groupid,
        'wpf-theme-' . WPF()->tpl->theme,
	];
	if( is_wpforo_page() ) $classes[] = 'wpforo';

	return apply_filters('wpforo_get_body_classes', $classes);
}

add_action( 'wpforo_wrap_class', function() {
	echo implode( ' ', wpforo_get_body_classes() );
});

add_filter( 'body_class', function( $classes ) {
	return array_merge( $classes, wpforo_get_body_classes() );
});

function wpforo_get_postmeta( $postid, $metakeys = '', $single = false ){
    return WPF()->postmeta->get_postmeta( $postid, $metakeys, $single );
}


###############################################################################
########################## THEME API FUNCTIONS ################################
###############################################################################

function wpforo_post( $postid, $var = 'item', $echo = false ) {
	$post = ( $var === 'item' ) ? [] : '';
	if( ! $postid ) return $post;
	$cache = WPF()->cache->on();
	if( $cache ) {
		$post = WPF()->cache->get_item( $postid, 'post' );
	}
	if( empty( $post ) ) {
		$post = [];
		if( ! $cache && $var === 'url' ) {
			$post['url'] = WPF()->post->get_post_url( $postid );
		} elseif( ! $cache && $var === 'is_answered' ) {
			$post['is_answered'] = WPF()->post->is_answered( $postid );
		} elseif( ! $cache && $var === 'votes_sum' ) {
			$post['votes_sum'] = 0; //deprecate
		} elseif( ! $cache && $var === 'likes_count' ) {
			$post['likes_count'] = WPF()->reaction->get_post_reactions_count( $postid );
		} elseif( ! $cache && $var === 'likers_usernames' ) {
			$post['likers_usernames'] = WPF()->reaction->get_post_reactions_user_dnames( $postid );
		} else {
			$post = WPF()->post->get_post( $postid );
			if( ! empty( $post ) ) {
				$post['url']              = WPF()->post->get_post_url( $post );
				$post['is_answered']      = WPF()->post->is_answered( $postid );
				$post['votes_sum']        = $post['votes']; //deprecate
				$post['likes_count']      = WPF()->reaction->get_post_reactions_count( $postid );
				$post['likers_usernames'] = WPF()->reaction->get_post_reactions_user_dnames( $postid );
				if( ! empty( $post ) ) {
					$cache_item = [ $postid => $post ];
					WPF()->cache->create( 'item', $cache_item, 'post' );
				}
			}
		}
	}

	if( $var !== 'item' && $var ) {
		$post = wpfkey( $post, $var ) ? $post[ $var ] : wpforo_get_postmeta( $postid, $var, true );
	}

	if( $echo && is_scalar( $post ) ) echo $post;
	return $post;
}

function wpforo_topic( $topicid, $var = 'item', $echo = false ) {
	$topic = ( $var == 'item' ) ? [] : '';
	if( ! $topicid ) return $topic;
	$cache = WPF()->cache->on();
	if( $cache ) $topic = WPF()->cache->get_item( $topicid, 'topic' );

	if( empty( $topic ) ) {
		$topic = [];
		if( ! $cache && $var == 'url' ) {
			$topic['url'] = WPF()->topic->get_topic_url( $topicid );
		} else {
			$topic = WPF()->topic->get_topic( $topicid );
			if( ! empty( $topic ) ) {
				$topic['url'] = WPF()->topic->get_topic_url( $topic );
				if( ! empty( $topic ) ) {
					$cache_item = [ $topicid => $topic ];
					WPF()->cache->create( 'item', $cache_item, 'topic' );
				}
			}
		}
	}

	if( $var != 'item' && $var ) {
		$topic = ( isset( $topic[ $var ] ) ) ? $topic[ $var ] : '';
	}

	if( $echo ) {
		echo $topic;
	} else {
		return $topic;
	}
}

function wpforo_forum( $forumid, $var = 'item', $echo = false ) {
	$forum = ( $var === 'item' ) ? [] : '';
	$cache = WPF()->cache->on();
	if( ! $forumid ) return $forum;
	if( $cache ) $forum = WPF()->cache->get_item( $forumid, 'forum' );

	if( empty( $forum ) ) {
		$forum = [];
		if( ! $cache && in_array( $var, [ 'childs', 'counts' ], true ) ) {
			$forum['childs']   = WPF()->forum->get_childs( $forumid );
			$forum['childs'][] = $forumid;
			if( $var === 'counts' ) $forum['counts'] = WPF()->forum->get_counts( $forum['childs'] );
		} else {
			$forum = WPF()->forum->get_forum( $forumid );
			if( ! empty( $forum ) ) {
				if( $cache ) {
					$forum['childs']   = WPF()->forum->get_childs( $forum['forumid'] );
					$forum['childs'][] = $forum['forumid'];
					$forum['counts']   = WPF()->forum->get_counts( $forum['childs'] );
				}
				if( ! empty( $forum ) ) {
					$cache_item = [ $forumid => $forum ];
					WPF()->cache->create( 'item', $cache_item, 'forum' );
				}
			}
		}
	}

	if( $var != 'item' && $var ) {
		$forum = ( isset( $forum[ $var ] ) ) ? $forum[ $var ] : '';
	}

	if( $echo ) {
		echo $forum;
	} else {
		return $forum;
	}
}

function wpforo_member( $object, $var = 'item', $echo = false ) {
	$member = null;
	if( !empty( $object ) ){
		if( is_array( $object ) && ! wpforo_bigintval( wpfval($object, 'userid' ) ) ) {
			$member = WPF()->member->get_guest( $object );
		} else {
			$userid = ( is_array( $object ) && isset( $object['userid'] ) ) ? intval( $object['userid'] ) : intval( $object );
			$member = WPF()->member->get_member( $userid );
		}
		if( $var !== 'item' && $var ) $member = wpfval( $member, $var );
    }

	if( $echo ) echo $member;
	return $member;
}

function wpforo_current_usermeta( $key ) {
	if( wpfkey( WPF()->current_usermeta, $key ) ) {
		if( wpfkey( WPF()->current_usermeta[ $key ], 0 ) ) {
			$meta = maybe_unserialize( WPF()->current_usermeta[ $key ][0] );

			return $meta;
		}
	}
}

function wpforo_tag( $tagid, $var = 'item', $echo = false ) {
	$tag = ( $var == 'item' ) ? [] : '';
	if( ! $tagid ) return $tag;
	$cache = WPF()->cache->on();

	if( $cache ) $tag = WPF()->cache->get_item( md5( $tagid ), 'tag' );

	if( empty( $tag ) ) {
		$tag = [];
		if( ! $cache && $var == 'url' && wpfval( $tag, 'tag' ) ) {
			$tag['url'] = wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag['tag'];
		} else {
			$tag = WPF()->topic->get_tag( $tagid );
			if( ! empty( $tag ) ) {
				$tag['url'] = wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag['tag'];
				if( ! empty( $tag ) ) {
					$cache_item = [ md5( $tagid ) => $tag ];
					WPF()->cache->create( 'item', $cache_item, 'tag' );
				}
			}
		}
	}

	if( $var != 'item' && $var ) {
		$tag = ( isset( $tag[ $var ] ) ) ? $tag[ $var ] : '';
	}

	if( $echo ) {
		echo $tag;
	} else {
		return $tag;
	}
}

function wpforo_member_link( $member, $prefix = '', $length = 30, $class = '', $echo = true ) {
	$display_name = ( isset( $member['display_name'] ) && $member['display_name'] ) ? $member['display_name'] : wpforo_phrase(
		'Anonymous',
		false
	);
	$color        = ( isset( $member['group_color'] ) && $member['group_color'] ) ? 'style="color:' . $member['group_color'] . '"' : '';
	$class        = ( $class ) ? 'class="' . $class . '"' : '';
	$title        = ( $display_name ) ? 'title="' . esc_attr( $display_name ) . '"' : '';
	if( wpfval( $member, 'userid' ) && wpfval( $member, 'profile_url' ) ) {
		$link = '<a href="' . esc_url(
				$member['profile_url']
			) . '" ' . $color . ' ' . $class . ' ' . $title . '>' . ( strpos( $prefix, '%s' ) !== false ? sprintf(
				wpforo_phrase( $prefix, false ),
				esc_html( wpforo_text( $display_name, $length, false ) )
			) : ( $prefix ? wpforo_phrase( $prefix, false ) . ' ' : '' ) . ( $length ? esc_html(
					wpforo_text( $display_name, $length, false )
				) : esc_html( $display_name ) ) ) . '</a>';
	} else {
		$link = ( strpos( $prefix, '%s' ) !== false ? sprintf(
			wpforo_phrase( $prefix, false ),
			esc_html( wpforo_text( $display_name, $length, false ) )
		) : ( ( $prefix ? wpforo_phrase( $prefix, false ) . ' ' : '' ) . ( $length ? esc_html(
				wpforo_text( $display_name, $length, false )
			) : esc_html( $display_name ) ) ) );
	}

	if( $echo ) echo $link;
	return $link;
}

add_shortcode( 'wpforo-lostpassword', function() {
	$ob_exists = function_exists( 'ob_start' ) && function_exists( 'ob_get_clean' );
	if( $ob_exists ) ob_start();
	?>
    <p id="wpforo-title"><?php wpforo_phrase( 'Reset Password' ) ?></p>
    <form method="POST">
		<?php wp_nonce_field( 'lostpassword', '_wpfnonce', false ) ?>
        <input type="hidden" name="wpfaction" value="lostpassword">
        <div class="wpforo-login-wrap wpfbg-9">
            <div class="wpforo-login-content">
                <h3><?php wpforo_phrase( 'Forgot Your Password?' ) ?></h3>
                <div class="wpforo-table wpforo-login-table">
                    <div class="wpf-tr row-0">
                        <div class="wpf-td wpfw-1 row_0-col_0" style="padding-top:10px;">
                            <div class="wpf-field wpf-field-type-text">
                                <div class="wpf-field-wrap">
                                    <label for="userlogin"
                                           style="display: block; text-align: center; font-size: 14px; padding-bottom: 10px;"><?php wpforo_phrase(
											'Please Insert Your Email or Username'
										) ?></label>
                                    <input id="userlogin" autofocus required type="text" name="user_login"
                                           class="wpf-login-text"/>
                                    <div style="text-align: center; font-size: 13px; padding-top: 10px; line-height: 18px;"><?php wpforo_phrase(
											'Enter your email address or username and we\'ll send you a link you can use to pick a new password.'
										) ?></div>
                                </div>
                                <div class="wpf-field-cl"></div>
                            </div>
                            <div class="wpf-field wpf-field-type-text wpf-field-hook">
                                <div class="wpf-field-wrap">
									<?php do_action( 'lostpassword_form' ) ?>
                                    <div class="wpf-field-cl"></div>
                                </div>
                                <div class="wpf-field-cl"></div>
                            </div>
                            <div class="wpf-field">
                                <div class="wpf-field-wrap" style="text-align:center; width:100%;">
                                    <input type="submit" value="<?php wpforo_phrase( 'Reset Password' ) ?>"/>
                                </div>
                                <div class="wpf-field-cl"></div>
                            </div>
                            <div class="wpf-field wpf-extra-field-end">
                                <div class="wpf-field-wrap" style="text-align:center; width:100%;">
									<?php do_action( 'wpforo_lostpass_form_end' ) ?>
                                    <div class="wpf-field-cl"></div>
                                </div>
                            </div>
                            <div class="wpf-cl"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
	<?php
	return ( $ob_exists ) ? trim( ob_get_clean() ) : '';
} );

add_shortcode( 'wpforo-resetpassword', function() {
	$ob_exists = function_exists( 'ob_start' ) && function_exists( 'ob_get_clean' );
	if( $ob_exists ) ob_start();
	?>
    <p id="wpforo-title"><?php wpforo_phrase( 'Reset Password' ) ?></p>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="wpfaction" value="resetpassword">
        <div class="wpforo-login-wrap">
            <div class="wpforo-login-content">
                <div class="wpforo-table wpforo-login-table">
                    <div class="wpf-tr row-0">
                        <div class="wpf-td wpfw-1 row_0-col_0" style="padding-top:10px;">
                            <div class="wpf-field wpf-field-type-text">
                                <div class="wpf-field-wrap">
                                    <label for="pass1"
                                           style="display: block; text-align: center; font-size: 14px; padding-bottom: 10px;"><?php wpforo_phrase(
											'New password'
										) ?></label>
                                    <input type="password" name="pass1" id="pass1" class="input" size="20" value=""
                                           autocomplete="off" required autofocus/>
                                </div>
                                <div class="wpf-field-cl"></div>
                            </div>
                            <div class="wpf-field wpf-field-type-text">
                                <div class="wpf-field-wrap">
                                    <label for="pass2"
                                           style="display: block; text-align: center; font-size: 14px; padding-bottom: 10px;"><?php wpforo_phrase(
											'Repeat new password'
										) ?></label>
                                    <input type="password" name="pass2" id="pass2" class="input" size="20" value=""
                                           autocomplete="off" required/>
                                </div>
                                <div class="wpf-field-cl"></div>
                            </div>
                            <div class="wpf-field wpf-field-type-text">
                                <div class="wpf-field-wrap">
									<?php printf(
										wpforo_phrase(
											'Password length must be between %d characters and %d characters.',
											false
										),
										WPF()->member->pass_min_length,
										WPF()->member->pass_max_length
									); ?>
                                </div>
                                <div class="wpf-field-cl"></div>
                            </div>
                            <div class="wpf-field">
                                <div class="wpf-field-wrap" style="text-align:center; width:100%;">
                                    <input type="submit" value="<?php wpforo_phrase( 'Reset Password' ); ?>"/>
                                </div>
                                <div class="wpf-field-cl"></div>
                            </div>
                            <div class="wpf-field wpf-extra-field-end">
                                <div class="wpf-field-wrap" style="text-align:center; width:100%;">
									<?php do_action( 'wpforo_resetpass_form_end' ) ?>
                                    <div class="wpf-field-cl"></div>
                                </div>
                            </div>
                            <div class="wpf-cl"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
	<?php
	return ( $ob_exists ) ? trim( ob_get_clean() ) : '';
} );

add_shortcode( 'wpforo-login-form', function() {
	$ob_exists = function_exists( 'ob_start' ) && function_exists( 'ob_get_clean' );
	if( $ob_exists ) ob_start();
	if( $path = WPF()->tpl->get_template_path( 'login' ) ) include( $path );

	return ( $ob_exists ) ? trim( ob_get_clean() ) : '';
} );

#############################################################################################
/**
 * Generates according page form fields using tpl->form_fields() function
 *
 * @param array $fields arguments
 * @param boolean $echo
 *
 * @return    string        form fields HTML
 * @since 1.4.0
 *
 */
function wpforo_fields( $fields, $echo = true ) {
	if( empty( $fields ) ) return '';
	$fields = apply_filters( 'wpforo_form_fields', $fields );
	$html   = WPF()->form->build( $fields );
	if( $echo ) {
		echo $html;
	} else {
		return $html;
	}
}

function wpforo_user_avatar( $user, $size, $attr = '', $lastmod = false ) {
	$avatar_html = '';
	if( is_numeric( $user ) && $user ) {
		$avatar_html = ( $size ) ? get_avatar( $user, $size ) : get_avatar( $user );
		if( $attr ) $avatar_html = str_replace( '<img', '<img ' . $attr, $avatar_html );
	} elseif( is_array( $user ) && ! empty( $user ) ) {
		$avatar_html = WPF()->member->avatar( $user, $attr, $size );
	}

	if( $lastmod ) {
		$url = wpforo_avatar_url( $avatar_html );
		if( $url ) {
			if( strpos( $url, '?' ) === false ) {
				$avatar_html = str_replace( $url, $url . '?lm=' . time(), $avatar_html );
			}
		}
	}

	return $avatar_html;
}

function wpforo_signature( $member, $args = [] ) {

	if( is_numeric( $member ) ) $member = wpforo_member( $member );
	if( WPF()->current_userid != wpfval( $member, 'userid' ) && ! WPF()->usergroup->can( 'vms' ) ) return '';

	$signature = '';
	$default   = [ 'nofollow' => 1, 'kses' => 1, 'echo' => 1 ];
	if( empty( $args ) ) {
		$args = $default;
	} else {
		$args = wpforo_parse_args( $args, $default );
	}

	if( is_array( $member ) && ! empty( $member ) ) {
		$signature = ( isset( $member['signature'] ) ) ? $member['signature'] : '';
	} elseif( is_string( $member ) ) {
		$signature = $member;
	}

	$signature = stripslashes( $signature );

	if( ! empty( $args ) ) {
		extract( $args, EXTR_OVERWRITE );
		if( isset( $kses ) && $kses ) $signature = wpforo_kses( $signature, 'user_description' );
		if( isset( $nofollow ) && $nofollow ) $signature = wpforo_nofollow_tag( $signature );
	} else {
		$signature = wpforo_nofollow( wpforo_kses( $signature, 'user_description' ) );
	}

	$length    = apply_filters( 'wpforo_signature_length', 0 );
	$signature = wpforo_text( $signature, $length, false, false, false, false );
	$signature = wpautop( $signature );

	if( $args['echo'] ) {
		echo $signature;
	} else {
		return $signature;
	}
}

function wpforo_register_fields() {
	$fields = WPF()->member->get_register_fields();
	do_action( 'wpforo_register_page_start', $fields );

	return $fields;
}

function wpforo_account_fields() {
	$fields = WPF()->member->get_account_fields();
	do_action( 'wpforo_account_page_start', $fields );

	return $fields;
}

function wpforo_profile_fields() {
	$fields = WPF()->member->get_profile_fields();
	do_action( 'wpforo_profile_page_start', $fields );

	return $fields;
}

function wpforo_search_fields() {
	$fields = WPF()->member->get_search_fields();
	do_action( 'wpforo_search_page_start', $fields );

	if( wpforo_setting( 'members', 'search_type' ) === 'search' ) {
		$fields = [
			[
				[
					[
						'type'           => 'search',
						'isDefault'      => 1,
						'isRemovable'    => 0,
						'isRequired'     => 0,
						'isEditable'     => 1,
						'class'          => 'wpf-member-search-field',
						'label'          => wpforo_phrase( 'Find a member', false ),
						'title'          => wpforo_phrase( 'Find a member', false ),
						'placeholder'    => wpforo_phrase( 'Display Name or Nicename', false ),
						'faIcon'         => 'fas fa-search',
						'name'           => 'wpfms',
						'cantBeInactive' => [ 'search' ],
						'can'            => '',
						'isSearchable'   => 1,
					],
				],
			],
		];
	}

	return $fields;
}

function wpforo_unread( $itemid, $item, $echo = true, $postid = 0 ) {
	$class  = '';
	$unread = false;
	$login  = is_user_logged_in();
	$login  = apply_filters( 'wpforo_unread_logging_for_guests', $login );
	if( $login ) {
		if( $item === 'forum' ) {
			$class  = 'wpf-unread-forum';
			$unread = WPF()->log->unread( $itemid, 'forum' );
			$unread = apply_filters( 'wpforo_unread_forum', $unread, $itemid );
		} elseif( $item === 'topic' ) {
			$class  = 'wpf-unread-topic';
			$unread = WPF()->log->unread( $itemid, 'topic' );
			$unread = apply_filters( 'wpforo_unread_topic', $unread, $itemid );
		} elseif( $item === 'post' ) {
			$class  = 'wpf-unread-post';
			$unread = WPF()->log->unread( $itemid, 'post', $postid );
			$unread = apply_filters( 'wpforo_unread_post', $unread, $itemid, $postid );
		}
	}
	$class = ( $unread ) ? apply_filters( 'wpforo_unread_class', $class, $itemid, $item ) : '';

	if( $echo ) echo $class;
	return $class;
}

function wpforo_unread_forum( $logid, $return = 'class', $echo = true ) {
	$unread = WPF()->log->unread( $logid, 'forum' );
	if( $return === 'class' ) {
		$log = $unread ? 'wpf_forum_unread' : '';
	} else {
		$log = (bool) $unread;
	}

	if( $echo ) echo $log;
	return $log;
}

function wpforo_unread_topic( $logid, $return = 'class', $echo = true ) {
	$unread = WPF()->log->unread( $logid, 'topic' );
	if( $return === 'class' ) {
		$log = $unread ? 'wpf_topic_unread' : '';
	} else {
		$log = (bool) $unread;
	}

	if( $echo ) echo $log;
	return $log;
}

if( ! function_exists( 'custom_wpforo_get_account_fields' ) ) {
	function custom_wpforo_get_account_fields( $fields ) {
		$hide = [
			'user_email',
			'user_nicename',
		];

		foreach( $fields as $row_key => $row ) {
			foreach( $row as $col_key => $col ) {
				foreach( $col as $key => $field ) {
					if( in_array( $field['fieldKey'], $hide ) ) {
						unset( $fields[ $row_key ][ $col_key ][ $key ] );
					}
				}
			}
		}

		return $fields;
	}
}

function wpforo_moderation_tools() {
	if( empty( WPF()->current_object['forumid'] ) || empty( WPF()->current_object['topicid'] ) ) return;
	?>
    <div id="wpf_moderation_tools" class="wpf-tools">
		<?php
		$tabs = [];
		if( is_user_logged_in() && WPF()->perm->forum_can( 'mt' ) ) {
			$posts  = (int) wpfval( WPF()->current_object, 'topic', 'posts' );
			$tabs[] = [
				'title' => wpforo_phrase( 'Move Topic', false ),
				'id'    => 'topic_move_form',
				'class' => 'wpft-move',
				'icon'  => 'far fa-share-square',
			];
			if( $posts > 1 ) {
				$tabs[] = [
					'title' => wpforo_phrase( 'Move Reply', false ),
					'id'    => 'reply_move_form',
					'class' => 'wpft-reply-move',
					'icon'  => 'far fa-share-square',
				];
			}
			$tabs[] = [
				'title' => wpforo_phrase( 'Merge Topics', false ),
				'id'    => 'topic_merge_form',
				'class' => 'wpft-merge',
				'icon'  => 'fas fa-code-branch',
			];
			if( $posts > 1 ) {
				$tabs[] = [
					'title' => wpforo_phrase( 'Split Topic', false ),
					'id'    => 'topic_split_form',
					'class' => 'wpft-split',
					'icon'  => 'fas fa-cut',
				];
			}
		}
		WPF()->tpl->topic_moderation_tabs( $tabs );
		?>
    </div>
	<?php
}

/**
 * Add an activity item.
 *
 * @param array $args {
 *     An array of arguments.
 *
 * @type string $action Optional. The activity action/description, typically something like "Joe posted an update".
 * @type string $title Optional. The title of the activity item.
 * @type string $content Optional. The content of the activity item.
 * @type string $component The unique name of the component associated with the activity item - 'activity', etc.
 * @type string $type The specific activity type, used for directory filtering. 'wpforo_topic', 'wpforo_post', etc.
 * @type string $primary_link Optional. The URL for this item, as used in RSS feeds. Defaults to the URL for this activity item's permalink page.
 * @type int|bool $user_id Optional. The ID of the user associated with the activity item. May be set to false or 0 if the item is not related to any user. Default: the ID of the currently logged-in user.
 * @type string $date_recorded Optional. The GMT time, in Y-m-d h:i:s format, when the item was recorded. Defaults to the current time.
 * }
 * @return NULL
 * @since 1.4.6
 */
function wpforo_activity( $args = [] ) {
	$default = [
		'action'        => '',
		'title'         => '',
		'content'       => '',
		'component'     => 'community',
		'type'          => '',
		'primary_link'  => '',
		'user_id'       => '',
		'item_id'       => '',
		'date_recorded' => '',
	];
	$args    = wpforo_parse_args( $args, $default );

	//BuddyPress Member Activity
	if( wpforo_setting( 'buddypress', 'activity' ) && function_exists( 'wpforo_bp_activity' ) ) {
		wpforo_bp_activity( $args );
	}
}

function wpforo_activity_delete( $args = [] ) {

	$default = [
		'action'        => '',
		'title'         => '',
		'content'       => '',
		'component'     => 'community',
		'type'          => '',
		'primary_link'  => '',
		'user_id'       => '',
		'item_id'       => '',
		'date_recorded' => '',
	];
	$args    = wpforo_parse_args( $args, $default );

	//Delete BuddyPress Member Activity
	if( wpforo_setting( 'buddypress', 'activity' ) && function_exists( 'wpforo_bp_activity_delete' ) ) {
		wpforo_bp_activity_delete( $args );
	}
}

function wpforo_activity_content( $item = [] ) {
	$args = [];
	if( empty( $item ) ) return false;
	if( ( isset( $item['status'] ) && $item['status'] ) || ( isset( $item['private'] ) && $item['private'] ) ) return false;
	if( isset( $item['forumid'] ) && $item['forumid'] ) {
		$private_for_usergroups = [ 3, 4, 5 ];
		$private_for_usergroups = apply_filters( 'wpforo_activity_private_for_usergroups', $private_for_usergroups );
		if( ! empty( $private_for_usergroups ) && WPF()->forum->private_forum(
				$item['forumid'],
				$private_for_usergroups
			) ) {
			return false;
		}
	}

	if( isset( $item['first_postid'] ) && $item['first_postid'] ) {
		$args['item_id'] = $item['first_postid'];
	} elseif( isset( $item['postid'] ) && $item['postid'] ) {
		$args['item_id'] = $item['postid'];
	}
	$args['user_id'] = ( isset( $item['userid'] ) && $item['userid'] ) ? $item['userid'] : $args['user_id'] = WPF()->current_userid;
	$member          = wpforo_member( $args['user_id'] );
	if( isset( $item['topicurl'] ) ) {
		$args['type']         = 'wpforo_topic';
		$args['content']      = ( wpfval( $item, 'body' ) ) ? $item['body'] : '';
		$args['primary_link'] = $item['topicurl'];
		if( isset( $item['title'] ) ) $args['title'] = $item['title'];
		if( $args['title'] ) $args['title'] = ' "' . esc_html( $args['title'] ) . '"';
		$args['action'] = sprintf( wpforo_phrase( '%s posted a new topic %s', false ), '', '' );
	} elseif( isset( $item['posturl'] ) ) {
		$args['type']         = 'wpforo_post';
		$args['content']      = ( wpfval( $item, 'body' ) ) ? $item['body'] : '';
		$args['primary_link'] = $item['posturl'];
		if( isset( $item['title'] ) ) $args['title'] = preg_replace( '|^.+?\:\s*|is', '', $item['title'] );
		if( $args['title'] ) $args['title'] = ' "' . esc_html( $args['title'] ) . '"';
		$args['action'] = sprintf( wpforo_phrase( '%s replied to the topic %s', false ), '', '' );
	}
	if( $args['content'] ) {
		$content_words     = explode( ' ', $args['content'] );
		$content_words     = count( $content_words );
		$content_words_cut = apply_filters( 'wpforo_activity_content_words', '40' );
		if( (int) $content_words_cut < (int) $content_words && $args['primary_link'] ) {
			$more            = '... &nbsp; <a href="' . $args['primary_link'] . '">' . wpforo_phrase(
					'read more',
					false
				) . '&raquo;</a>';
			$args['content'] = wp_trim_words( $args['content'], 40, $more );
		}
	}
	wpforo_activity( $args );
}

function wpforo_activity_content_delete( $item = [] ) {
	$args = [];
	if( empty( $item ) ) return false;
	if( wpfval( $item, 'first_postid' ) ) {
		$args['item_id'] = $item['first_postid'];
		$args['type']    = 'wpforo_topic';
	} elseif( wpfval( $item, 'is_first_post' ) ) {
		$args['item_id'] = $item['postid'];
		$args['type']    = 'wpforo_topic';
	} elseif( wpfval( $item, 'postid' ) ) {
		$args['item_id'] = $item['postid'];
		$args['type']    = 'wpforo_post';
	}
	if( wpfval( $args, 'item_id' ) && wpfval( $args, 'type' ) ) wpforo_activity_delete( $args );
}

function wpforo_activity_content_on_post_status_change( $post, $status = 0 ) {
	if( ! empty( $post ) ) {
		$post['status']  = $status;
		$post['posturl'] = WPF()->post->get_post_url( $post['postid'] );
		if( ! (int) wpfval( $post, 'is_first_post' ) ) {
			if( $status ) {
				wpforo_activity_content_delete( $post );
			} else {
				wpforo_activity_content( $post );
			}
		}
	}
}
add_action( 'wpforo_post_status_update', 'wpforo_activity_content_on_post_status_change', 9, 2 );

function wpforo_activity_content_on_topic_status_change( $topic, $status = 0 ) {
	if( ! empty( $topic ) ) {
		$topic['status']   = $status;
		$topic['topicurl'] = WPF()->topic->get_topic_url( $topic['topicid'] );
		if( $status ) {
			wpforo_activity_content_delete( $topic );
		} else {
			wpforo_activity_content( $topic );
		}
	}
}
add_action( 'wpforo_topic_status_update', 'wpforo_activity_content_on_topic_status_change', 9, 2 );

function wpforo_activity_like( $item = [] ) {
	$args = [];
	if( empty( $item ) ) return false;
	if( ( isset( $item['status'] ) && $item['status'] ) || ( isset( $item['private'] ) && $item['private'] ) ) return false;
	if( isset( $item['forumid'] ) && $item['forumid'] ) {
		$private_for_usergroups = [ 3, 4, 5 ];
		$private_for_usergroups = apply_filters( 'wpforo_activity_private_for_usergroups', $private_for_usergroups );
		if( ! empty( $private_for_usergroups ) && WPF()->forum->private_forum(
				$item['forumid'],
				$private_for_usergroups
			) ) {
			return false;
		}
	}
	if( isset( $item['postid'] ) && $item['postid'] ) $args['item_id'] = $item['postid'];
	$args['user_id'] = WPF()->current_userid;
	$member          = wpforo_member( $args['user_id'] );
	$args['type']    = 'wpforo_like';
	$item            = wpforo_post( $item['postid'] );
	if( isset( $item['url'] ) && $item['url'] ) $args['primary_link'] = $item['url'];
	if( isset( $item['title'] ) ) $args['title'] = preg_replace( '|^.+?\:\s*|is', '', $item['title'] );
	if( $args['title'] ) $args['title'] = ' "' . esc_html( $args['title'] ) . '"';
	$args['action'] = sprintf( wpforo_phrase( '%s liked forum post %s', false ), '', '' );
	wpforo_activity( $args );
}

function wpforo_activity_like_delete( $item = [] ) {
	$args = [];
	if( empty( $item ) ) return false;
	if( isset( $item['postid'] ) && $item['postid'] ) {
		$args['item_id'] = $item['postid'];
		$args['type']    = 'wpforo_like';
	}
	if( $args['item_id'] && $args['type'] ) wpforo_activity_delete( $args );
}

add_action( 'wpforo_after_add_topic', 'wpforo_activity_content', 9 );
add_action( 'wpforo_after_add_post', 'wpforo_activity_content', 9 );
add_action( 'wpforo_like', 'wpforo_activity_like', 9 );
add_action( 'wpforo_after_delete_post', 'wpforo_activity_content_delete', 9 );
add_action( 'wpforo_after_delete_post', 'wpforo_activity_like_delete', 9 );

function wpforo_user_field( $field = '', $userid = 0, $echo = true ) {
	$userid = ( ! $userid ) ? WPF()->current_userid : $userid;
	if( ! $field || ! $userid ) return false;
	$field = wpforo_member( $userid, $field );
	$field = apply_filters( 'wpforo_user_field', $field, $userid );
	if( ! is_array( $field ) && $field ) {
		if( $echo ) {
			echo $field;
		} else {
			return $field;
		}
	}
}

/**
 * @param array $post
 * @param bool $echo
 *
 * @return string|void
 */
function wpforo_content( $post, $echo = true ) {
	$content = '';
	if( is_array( $post ) && isset( $post['body'] ) ) {
		$content = apply_filters( 'wpforo_content_before', $post['body'], $post );
		$content = wpforo_kses( $content, 'post' );
		$content = apply_filters( 'wpforo_content', $content, $post );
		$content = wpforo_content_filter( $content );
		$content = apply_filters( 'wpforo_content_after', $content, $post );
	}
	if( ! $echo ) return $content;
	echo $content;
}

function wpforo_share_toggle( $url = '', $text = '', $location = 'side', $custom = false ) {
	$position = in_array( wpforo_setting( 'social', 'sb_location_toggle' ), [ 'left', 'right' ] ) ? 'side' : wpforo_setting( 'social', 'sb_location_toggle' );
	if( ! wpforo_setting( 'social', 'sb_toggle_on' ) || ( $position !== $location && ! $custom ) ) return;
	$location_class = $custom ? $location : wpforo_setting( 'social', 'sb_location_toggle' );
	?>
    <div class="wpf-sb wpf-sb-<?php echo esc_attr( $location_class ) ?> wpf-sb-<?php echo esc_attr( wpforo_setting( 'social', 'sb_toggle' ) ) ?> sb-tt-<?php echo esc_attr( wpforo_setting( 'social', 'sb_toggle_type' ) ) ?>">
        <div class="wpf-sb-toggle"><i class="fas fa-share-alt" title="<?php wpforo_phrase( 'Share this post' ) ?>"></i>
        </div>
        <div class="wpf-sb-buttons" style="display: <?php if( wpforo_setting( 'social', 'sb_toggle_type' ) === 'collapsed' ) {
			echo 'none';
		}
		?>;">
			<?php do_action( 'wpforo_share_toggle_before', $url, $text, $location, $custom ) ?>
			<?php WPF()->api->share_toggle( $url, $text ); ?>
			<?php do_action( 'wpforo_share_toggle_after', $url, $text, $location, $custom ) ?>
        </div>
    </div>
	<?php
}

function wpforo_share_buttons( $location = 'bottom', $url = '', $custom = false ) {
	if( ! wpforo_setting( 'social', 'sb_on' ) || ( ! wpforo_setting( 'social', 'sb_location', $location ) && ! $custom ) ) {
		return;
	} ?>
    <div class="wpf-sbtn wpf-sb-<?php echo esc_attr( $location ) ?> wpf-sb-style-<?php echo esc_attr( wpforo_setting( 'social', 'sb_style' ) ) ?>" style="display: block">
        <div class="wpf-sbtn-title"><i class="fas fa-share-alt"></i> <span><?php wpforo_phrase( 'Share:' ) ?></span>
        </div>
        <div class="wpf-sbtn-wrap">
			<?php do_action( 'wpforo_share_buttons_before', $location, $url, $custom ) ?>
			<?php WPF()->api->share_buttons( $url ); ?>
			<?php do_action( 'wpforo_share_buttons_after', $location, $url, $custom ) ?>
        </div>
        <div class="wpf-clear"></div>
    </div>
	<?php
}

function wpforo_page() {
	$page_template = ( wpfval( $_GET, 'view' ) ) ? sanitize_title( $_GET['view'] ) : false;
	do_action( 'wpforo_page', $page_template );
}

function wpforo_admin_note() {

	$display    = false;
	$templates  = WPF()->tools_misc['admin_note_pages'];
	$usergroups = WPF()->tools_misc['admin_note_groups'];

	if( ! wpfval( WPF()->tools_misc, 'admin_note_pages' ) ) return false;
	if( ! wpfval( WPF()->tools_misc, 'admin_note_groups' ) ) return false;

	if( in_array( WPF()->current_user_groupid, $usergroups ) ) {
		if( wpfval( WPF()->current_object, 'template' ) && in_array( WPF()->current_object['template'], $templates ) ) {
			$display = true;
		} else {
			$display = false;
		}
	}
	if( $display ) {
		$note = wpforo_kses( wpforo_unslashe( trim( WPF()->tools_misc['admin_note'] ) ) );
		$note = apply_filters( 'wpforo_admin_note', $note );
		if( $note ) {
			?>
            <div class="wpforo-admin-note"><?php echo wpautop( $note ) ?>
            <div class="wpf-clear"></div></div><?php
		}
	}

}

add_action( 'wpforo_header_hook', 'wpforo_admin_note', 1 );

function wpforo_topic_icon( $topic, $type = 'all', $color = true, $echo = true, $wrap = '%s' ) {
	$html = '';
	if( is_numeric( $topic ) ) $topic = wpforo_topic( $topic );
	if( $type == 'mixed' ) {
		if( ! $icon = WPF()->tpl->icon( 'topic', $topic, false ) ) {
			$icon = WPF()->tpl->icon_base( $topic['posts'] );
			$icon = implode( ' ', $icon );
		}
		$html = sprintf( $wrap, '<i class="fa-1x ' . esc_attr( $icon ) . '"></i>' );
	} else {
		if( ( $type == 'all' || $type == 'base' ) && wpfkey( $topic, 'posts' ) ) {
			$icon = WPF()->tpl->icon_base( $topic['posts'] );
			$icon = implode( ' ', $icon );
			$html .= sprintf( $wrap, '<i class="fa-1x ' . esc_attr( $icon ) . '"></i>' );
		}
		if( $type == 'all' || $type == 'status' ) {
			$icon = WPF()->tpl->icon_status( $topic );
			if( ! empty( $icon ) ) {
				$html = '';
				foreach( $icon as $i ) {
					if( ! $color ) $i['color'] = '';
					$classes = $i['class'] . ' ' . $i['color'];
					$html    .= sprintf(
						$wrap,
						'<i class="fa-1x ' . esc_attr( $classes ) . '" title="' . esc_attr(
							$i['title']
						) . '"></i>'
					);
				}
			}
		}
	}

	if( $echo ) echo $html; else return $html;
}

function wpforo_topic_icons( $topic, $type = 'all' ) {
	$icon = [];
	if( is_numeric( $topic ) ) $topic = wpforo_topic( $topic );
	if( $type == 'mixed' ) {
		$icon = WPF()->tpl->icon( 'topic', $topic, false );
	} else {
		if( ( $type == 'all' || $type == 'base' ) && wpfkey( $topic, 'posts' ) ) {
			$icon_base = WPF()->tpl->icon_base( $topic['posts'] );
			if( ! empty( $icon_base ) && is_array( $icon_base ) ) $icon = [ 'base' => $icon_base ];
		}
		if( $type == 'all' || $type == 'status' ) {
			$icon_status = WPF()->tpl->icon_status( $topic );
			if( ! empty( $icon_status ) && is_array( $icon_status ) ) $icon = $icon_status;
		}
		$icon = array_filter( $icon );
	}

	return $icon;
}

function wpforo_tags( $topic, $wrap = true, $type = 'medium', $count = false ) {
	if( is_numeric( $topic ) && $topic > 0 ) {
		$topic = wpforo_topic( $topic );
	}
	if( wpfval( $topic, 'tags' ) ) {
		$tags = WPF()->topic->sanitize_tags( $topic['tags'], true );
		if( ! empty( $tags ) ) {
			if( $wrap ) {
				?>
                <div class="wpforo-post wpforo-tags wpfcl-1">
					<?php if( $type != 'text' ): ?>
                        <div class="wpf-tags-title">
                            <i class="fas fa-tag"></i> <span class="wpf-ttt"><?php wpforo_phrase(
									'Topic Tags'
								); ?></span>
                        </div>
					<?php endif; ?>
                    <div class="<?php if( $type != 'text' ) echo 'wpf-tags'; ?> wpf-tags-<?php echo esc_attr(
						$type
					) ?>">
						<?php if( $type == 'text' ): ?><i class="fas fa-tag"></i> <span
                                class="wpf-ttt"><?php wpforo_phrase( 'Topic Tags' ); ?>:&nbsp; <?php endif; ?></span>
						<?php foreach( $tags as $tag ): ?>
							<?php $item = wpforo_tag( $tag ) ?>
                        <tag wpf-tooltip="<?php echo esc_attr( wpforo_phrase( 'Topic Tag', false ) ); ?>"><a
                                    href="<?php echo wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag ?>"><?php echo esc_html(
									$tag
								); ?><?php if( $count && wpfval(
										$item,
										'count'
									) && ! $topic['status'] ) {
									echo ' (' . $item['count'] . ')';
								} ?></a>
                            </tag><?php if( $type == 'text' ) echo '<sep>,</sep> '; ?>
						<?php endforeach; ?>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
				<?php
			} else {
				?>
                <div class="<?php if( $type != 'text' ) echo 'wpf-tags'; ?> wpf-tags-<?php echo esc_attr( $type ) ?>">
					<?php foreach( $tags as $tag ): ?>
						<?php $item = wpforo_tag( $tag ) ?>
                        <tag wpf-tooltip="<?php echo esc_attr( wpforo_phrase( 'Topic Tag', false ) ); ?>"><a
                                    href="<?php echo wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag ?>"><?php echo esc_html(
									$tag
								); ?><?php if( $count && wpfval(
										$item,
										'count'
									) && ! $topic['status'] ) {
									echo ' (' . $item['count'] . ')';
								} ?></a></tag>
					<?php endforeach; ?>
                </div>
				<?php
			}
		}
	}
}

function wpforo_topic_rel( $topic ) {
	if( ! empty( $topic ) ) {
		if( wpfval( $topic, 'tags' ) ) {
			$html   = '';
			$args   = [];
			$wheres = [];
			$tags   = WPF()->topic->sanitize_tags( $topic['tags'], true );
			if( ! empty( $tags ) ) {
				foreach( $tags as $tag ) {
					if( $tag ) $wheres[] = "FIND_IN_SET('" . esc_sql( $tag ) . "', tags)";
				}
				if( $wheres ) $args['where'] = '(' . implode( ' OR ', $wheres ) . ')';

				$args['order']     = 'DESC';
				$args['row_count'] = 5;
				$args['orderby']   = 'modified';
				$args['forumid']   = apply_filters( 'wpforo_related_topics_by_forums', $topic['forumid'] );
				$args['exclude']   = [ $topic['topicid'] ];
				$args              = apply_filters( 'wpforo_related_topics_args', $args );
				$topics            = WPF()->topic->get_topics( $args );
				if( ! empty( $topics ) ) {
					$html .= '<div class="wpf-rel-title"><i class="fas fa-clone"></i> ' . wpforo_phrase(
							'Related Topics',
							false
						) . '</div><ul class="wpf-rel-topics">';
					foreach( $topics as $item ) {
						$data = wpforo_topic( $item['topicid'] );
						$html .= '<li>' . wpforo_topic_icon( $item, 'all', true, false ) . ' 
                                <a href="' . esc_url( $data['url'] ) . '" title="' . esc_attr(
								$item['title']
							) . '">' . esc_html( $item['title'] ) . '</a> 
                                <div class="wpf-rel-date">' . wpforo_date( $item['modified'], 'ago', false ) . '</div>
                                <div class="wpf-clear"></div>
                             </li>';
					}
					$html .= '</ul>';
					echo '<div class="wpf-rel-wrap">' . $html . '</div>';
				} else {
					echo '<div class="wpf-no-rel"></div>';
				}
			}
		}
	}
}

function wpforo_topic_navi( $topic ) {
	if( ! empty( $topic ) ) {
		if( wpfval( $topic, 'topicid' ) && wpfval( $topic, 'forumid' ) ) {
			$all_html    = '';
			$prev_html   = '';
			$next_html   = '';
			$navi_topics = WPF()->db->get_col(
				"SELECT `topicid` FROM `" . WPF()->tables->topics . "` WHERE ( `topicid` = IFNULL((SELECT min(`topicid`) FROM `" . WPF()->tables->topics . "` WHERE `topicid` > " . intval( $topic['topicid'] ) . " AND `forumid` = " . intval(
					$topic['forumid']
				) . " AND `status` = 0 AND `private` = 0),0) OR `topicid` = IFNULL((SELECT max(`topicid`) FROM `" . WPF()->tables->topics . "` WHERE `topicid` < " . intval( $topic['topicid'] ) . " AND `forumid` = " . intval(
					$topic['forumid']
				) . " AND `status` = 0 AND `private` = 0),0) ) "
			);
			if( ! empty( $navi_topics ) ) {
				$prev = ( wpfkey( $navi_topics, 0 ) ) ? $navi_topics[0] : false;
				$next = ( wpfkey( $navi_topics, 1 ) ) ? $navi_topics[1] : false;
				if( $prev && ! $next ) {
					if( $topic['topicid'] < $prev ) {
						$next = $prev;
						$prev = false;
					}
				}
				if( $prev ) {
					$prev_data = wpforo_topic( $prev );
					if( ! empty( $prev_data ) ) {
						$prev_html = '<a href="' . esc_url( $prev_data['url'] ) . '" title="' . esc_attr(
								$prev_data['title']
							) . '"><i class="fas fa-chevron-left"></i>&nbsp; ' . wpforo_phrase(
							             'Previous Topic',
							             false
						             ) . '</a>';
					}
				}
				if( $next ) {
					$next_data = wpforo_topic( $next );
					if( ! empty( $next_data ) ) {
						$next_html = '<a href="' . esc_url( $next_data['url'] ) . '" title="' . esc_attr(
								$next_data['title']
							) . '">' . wpforo_phrase(
							             'Next Topic',
							             false
						             ) . ' &nbsp;<i class="fas fa-chevron-right"></i></a>';
					}
				}
				if( wpfval( WPF()->current_object, 'forum' ) && wpfval( WPF()->current_object, 'forum', 'url' ) ) {
					$forum_url   = WPF()->current_object['forum']['url'];
					$forum_title = WPF()->current_object['forum']['title'];
				} else {
					$forum_url   = wpforo_forum( $topic['forumid'], 'url' );
					$forum_title = wpforo_forum( $topic['forumid'], 'title' );
				}
				if( $forum_url ) {
					$all_html = '<a href="' . esc_url( $forum_url ) . '" title="' . esc_attr(
							$forum_title
						) . '"><i class="fas fa-list"></i>&nbsp; ' . wpforo_phrase(
						            'All forum topics',
						            false
					            ) . '</a>';
				}
				if( $prev_html || $next_html ) {
					$html = '<div class="wpf-topic-all wpf-navi-item">' . $all_html . '</div>
                                  <div class="wpf-topic-prnx">
                                        <div class="wpf-topic-prev wpf-navi-item">' . $prev_html . '</div>
                                        <div class="wpf-topic-next wpf-navi-item">' . $next_html . '</div>
                             </div><div class="wpf-clear"></div>';
					echo '<div class="wpf-navi-wrap">' . $html . '</div>';
				}
			}
		}
	}
}

function wpforo_topic_visitors( $topic ) {
	if( ! empty( $topic ) ) {
		$html          = '';
		$users         = '';
		$guests        = '';
		$users_visited = '';
		$visitors      = WPF()->log->visitors( $topic );
		if( ! empty( $visitors ) ) {
			if( wpfval( $visitors, 'users' ) ) {
				if( wpfval( $visitors, 'users', 'viewing' ) && wpforo_setting( 'logging', 'display_topic_current_viewers' ) ) {
					$count = count( $visitors['users']['viewing'] );
					if( $count ) {
						if( $count > 1 ) {
							$users = wpforo_phrase( '%d users ( %s )', false );
						} elseif( $count == 1 ) {
							$users = wpforo_phrase( '%d user ( %s )', false );
						}
						$user_html = [];
						foreach( $visitors['users']['viewing'] as $user ) {
							if( wpfval( $user, 'userid' ) ) {
								$member = wpforo_member( $user['userid'] );
								if( wpfval( $member, 'display_name' ) ) {
									$user_html[] = wpforo_member_link(
										$member,
										'',
										30,
										'wpf-topic-visitor-link',
										false
									);
								}
							}
						}
						$user_html = implode( ', ', $user_html );
						$users     = sprintf( $users, $count, $user_html );
					}
					$users = apply_filters( 'wpforo_topic_viewing_users', $users, $visitors['users']['viewing'] );
				}
				if( wpfval( $visitors, 'users', 'viewed' ) && wpforo_setting( 'logging', 'display_recent_viewers' ) ) {
					$users_visited    = wpforo_phrase( 'Recently viewed by users: %s.', false );
					$track_users_link = [];
					foreach( $visitors['users']['viewed'] as $user ) {
						if( wpfval( $user, 'userid' ) ) {
							$member = wpforo_member( $user['userid'] );
							if( wpfval( $member, 'display_name' ) ) {
								$track_users_link[] = wpforo_member_link(
									                      $member,
									                      '',
									                      30,
									                      'wpf-topic-visitor-link',
									                      false
								                      ) . ' ' . wpforo_date( $user['time'], 'ago', false );
							}
						}
					}
					$track_users_link = implode( ', ', $track_users_link );
					$users_visited    = sprintf( $users_visited, $track_users_link );
					$users_visited    = '<p class="wpf-viewed-users"><i class="fas fa-walking"></i> ' . $users_visited . '</p>';
					$users_visited    = apply_filters(
						'wpforo_topic_viewed_users',
						$users_visited,
						$visitors['users']['viewed']
					);
				}
			}
			if( wpfval( $visitors, 'guests' ) && wpforo_setting( 'logging', 'display_topic_current_viewers' ) ) {
				$count = count( $visitors['guests'] );
				if( $count > 1 ) {
					$guests = sprintf( wpforo_phrase( '%s guests', false ), $count );
				} elseif( $count == 1 ) {
					$guests = sprintf( wpforo_phrase( '%s guest', false ), $count );
				}
			}
			if( $users || $guests ) {
				$and  = ( $users && $guests ) ? wpforo_phrase( 'and', false, 'lower' ) : '';
				$html .= '<p class="wpf-viewing-users"><i class="fas fa-male"></i> ' . sprintf(
						wpforo_phrase( 'Currently viewing this topic %s %s %s.', false ),
						$users,
						$and,
						$guests
					) . '</p>';
			}
			$html .= $users_visited;
			$html = apply_filters( 'wpforo_topic_visitors_info', $html, $visitors );
		}
		echo $html;
	}
}

function wpforo_viewing( $item, $echo = true ) {
	if( ! empty( $item ) && wpforo_setting( 'logging', 'display_forum_current_viewers' ) ) {
		$phrase   = wpforo_phrase( '(%d viewing)', false, 'lower' );
		$visitors = WPF()->log->visitors( $item );
		$users    = ( wpfval( $visitors, 'users', 'viewing' ) ) ? count( $visitors['users']['viewing'] ) : 0;
		$guests   = ( wpfval( $visitors, 'guests' ) ) ? count( $visitors['guests'] ) : 0;
		$viewing  = (int) $users + (int) $guests;
		if( $viewing > 0 ) {
			$phrase = '<span class="wpf-viewing">' . sprintf( $phrase, $viewing ) . '</span>';
			if( $echo ) {
				echo $phrase;
			} else {
				return $phrase;
			}
		}
	}
}

function wpforo_topic_footer() {
	if( wpfval( WPF()->current_object, 'topic' ) && wpfval( WPF()->current_object, 'template' ) && WPF()->current_object['template'] == 'post' ) {
		$topic = WPF()->current_object['topic'];
		?>
        <div class="wpforo-topic-footer wpfbg-9">
            <div class="wpf-topic-navi">
				<?php wpforo_topic_navi( $topic ) ?>
            </div>
            <div class="wpf-topic-rel">
				<?php wpforo_topic_rel( $topic ) ?>
            </div>
            <div class="wpf-tag-list">
				<?php wpforo_tags( $topic, true, 'text', true ) ?>
            </div>
            <div class="wpf-topic-visitors">
				<?php wpforo_topic_visitors( $topic ) ?>
            </div>
        </div>
		<?php
	}
}

add_action( 'wpforo_post_list_footer', 'wpforo_topic_footer' );

function wpforo_thread( $topicid ){

    $thread = wpforo_topic($topicid);

    $thread['icons'] = '';
    $thread['icons_html'] = '';
    $thread['users_html'] = '';
    $thread['status_html'] = '';
    $thread['forum'] = wpforo_forum( $thread['forumid'] );
    $thread['user'] = wpforo_member($thread);
    $thread['last_post'] = (wpfval($thread, 'last_post')) ? wpforo_post($thread['last_post']) : [];
    $thread['last_user'] = (wpfval($thread, 'last_post')) ? wpforo_member($thread['last_post']) : [];
    $thread['replies'] = (intval($thread['posts']) - 1);
    $thread['last_post_date'] = wpforo_date($thread['modified'],'ago-date', false);
    $thread['last_post_url'] = wpfval($thread, 'last_post', 'url');
    $thread['user_info'] = esc_attr(sprintf(wpforo_phrase('Created by %s', false), wpfval($thread['user'], 'display_name')));
    $thread['reply_user_info'] = (wpfval($thread, 'last_user', 'display_name')) ? esc_attr(sprintf(wpforo_phrase('Last reply by %s', false), $thread['last_user']['display_name'])) : '';
    $thread['icons'] = wpforo_topic_icons($thread, 'all');
    $thread['wrap'] = (count($thread['icons']) > 3) ? ' style="flex-wrap: wrap;"' : '';

    if(!empty($thread['icons'])){
        $i = 0;
        foreach( $thread['icons'] as $icon ){
            $thread['icons_html'] .= '<span class="wpf-circle wpf-s wpfcl-3 ' . str_replace('wpfcl-', 'wpfbg-', $icon['color']) .'" wpf-tooltip="'. esc_attr(wpforo_phrase($icon['title'], false)) .'" wpf-tooltip-position="top" wpf-tooltip-size="small"><i class="'. esc_attr(str_replace( '-circle', '', $icon['class']) ).'"></i></span>';
            if( $i === 0 ) {
                $thread['status_html'] .= '<span class="wpf-circle wpfsq wpf-s ' . $icon['color'] .'" wpf-tooltip="'. esc_attr(wpforo_phrase($icon['title'], false)) .'" wpf-tooltip-position="top" wpf-tooltip-size="small"><i class="'. esc_attr(str_replace( '-circle', '', $icon['class']) ).'"></i></span>';
            } else {
                $thread['status_html'] .= '<span class="wpf-circle wpfsq wpf-s wpfcl-3 ' . str_replace('wpfcl-', 'wpfbg-', $icon['color']) . '" wpf-tooltip="'. esc_attr(wpforo_phrase($icon['title'], false)) .'" wpf-tooltip-position="top" wpf-tooltip-size="small"><i class="'. esc_attr(str_replace( '-circle', '', $icon['class']) ).'"></i></span>';
            }
            $i++;
        }
    }

    if(!empty($thread['user']) || !empty($thread['last_user'])) {
        $thread['usergroup_can_va'] = WPF()->usergroup->can('va');
        $thread['feature_avatars'] = wpforo_setting( 'profiles', 'avatars' );
        $thread['users_html'] .= '<div class="wpf-thread-users-avatars">';
        if( $thread['usergroup_can_va'] && $thread['feature_avatars'] ) {
            if( !empty( $thread['user'] ) ) {
                $thread['user_avatar'] = WPF()->member->avatar($thread['user'], 'alt="'.esc_attr($thread['user']['display_name']).'"', 40);
                $thread['users_html'] .= '<div class="wpf-circle wpf-m">
                        <a href="' . esc_url( $thread['url'] ) . '" wpf-tooltip="' . $thread['user_info'] . '" wpf-tooltip-position="top" wpf-tooltip-size="medium">' . $thread['user_avatar'] . '</a>
                    </div>';
            }
            if( $thread['replies'] && !empty( $thread['last_user'] ) ) {
                $thread['last_user_avatar'] = WPF()->member->avatar($thread['last_user'], 'alt="'.esc_attr($thread['last_user']['display_name']).'"', 24);
                $thread['users_html'] .= '<div class="wpf-circle wpf-s">
                        <a href="' . esc_url( $thread['last_post_url'] ) . '" wpf-tooltip="' . $thread['reply_user_info'] . '" wpf-tooltip-position="top" wpf-tooltip-size="medium">' . $thread['last_user_avatar'] . '</a>
                    </div>';
            }
        } else {
            $thread['users_html'] .= wpforo_member_link( $thread['user'], 'by', 9, '', false );
        }
        $thread['users_html'] .= '</div>';

        $thread['author_html'] = '<div class="wpf-thread-users-avatars">';
        if( $thread['usergroup_can_va'] && $thread['feature_avatars'] ) {
            if( !empty( $thread['user'] ) ) {
                $thread['user_avatar'] = WPF()->member->avatar($thread['user'], 'alt="'.esc_attr($thread['user']['display_name']).'"', 40);
                $thread['author_html'] .= '<a href="' . esc_url( $thread['url'] ) . '" wpf-tooltip="' . esc_attr(wpfval($thread['user'], 'display_name')) . '" wpf-tooltip-position="top" wpf-tooltip-size="medium">' . $thread['user_avatar'] . '</a>';
            }
        } else {
            $thread['author_html'] .= wpforo_member_link( $thread['user'], 'by', 9, '', false );
        }

        if( $thread['usergroup_can_va'] && $thread['feature_avatars'] ) {
            if( $thread['replies'] && !empty( $thread['last_user'] ) ) {
                $thread['last_user_avatar'] = WPF()->member->avatar($thread['last_user'], 'alt="'.esc_attr($thread['last_user']['display_name']).'"', 40);
                $thread['reply_html'] = '<i class="fas fa-reply fa-rotate-180"></i> <a href="' . esc_url( $thread['last_post_url'] ) . '" wpf-tooltip="' . $thread['reply_user_info'] . '" wpf-tooltip-position="top" wpf-tooltip-size="medium">' . $thread['last_user_avatar'] . '</a>';
            } else {
                $thread['user_avatar'] = WPF()->member->avatar($thread['user'], 'alt="'.esc_attr($thread['user']['display_name']).'"', 40);
                $thread['reply_html'] = '<i class="fas fa-feather-alt"></i> <a href="' . esc_url( $thread['url'] ) . '" wpf-tooltip="' . esc_attr(wpfval($thread['user'], 'display_name')) . '" wpf-tooltip-position="top" wpf-tooltip-size="medium">' . $thread['user_avatar'] . '</a>';
            }
        }
        $thread['author_html'] .= '</div>';
    }

    return $thread;

}

/**
 * @param string $type
 * @param array $buttons
 * @param array $forum
 * @param array $topic
 * @param array $post
 * @param bool $echo
 *
 * @return string
 */
function wpforo_post_buttons( $type = 'icon-text', $buttons = [], $forum = [], $topic = [], $post = [], $echo = true ) {
	$buttons = WPF()->tpl->buttons( $buttons, $forum, $topic, $post, false );
	if( $type === 'icon' ) {
		$buttons = preg_replace( '#</i>[\r\n\t\s\0]*<span[^<>]*>.*?</span>#isu', '</i>', $buttons );
	} elseif( $type === 'text' ) {
		$buttons = preg_replace( '#<i[^<>]*>[\r\n\t\s\0]*</i>[\r\n\t\s\0]*#isu', '', $buttons );
		$buttons = preg_replace( '#\swpf-tooltip=[\'\"][^\'\"]+?[\'\"]#isu', '', $buttons );
	} else {
		$buttons = preg_replace( '#\swpf-tooltip=[\'\"][^\'\"]+?[\'\"]#isu', '', $buttons );
	}
	if( $echo ) echo $buttons;

	return $buttons;
}

function wpforo_thread_breadcrumb( $post = [], $parents = [] ) {
	$html = '';
	$gab  = false;
	if( wpfval( $post, 'parentid' ) ) {
		$html        .= '<i class="fas fa-reply fa-rotate-180"></i>';
		$parent      = wpforo_post( $post['parentid'] );
		$member      = wpforo_member( $parent );
		$parent_url  = ( wpfval( $parent, 'url' ) ) ? $parent['url'] : '#post-' . $parent['parentid'];
		$avatar      = WPF()->member->avatar( $member, 'alt="' . esc_attr( $member['display_name'] ) . '"', 18 );
		$member_name = ( wpfval( $member, 'display_name' ) ) ? $member['display_name'] : wpforo_phrase(
			'Guest',
			false
		);
		$html        .= '<div class="wpf-reply-to wpf-tree-item"><a href="' . esc_url(
				$parent_url
			) . '"><em>' . wpforo_phrase(
			                'Reply to',
			                false
		                ) . '</em>' . $avatar . '<span>' . $member_name . '</span></a></div>';
		if( wpfval( $parents, $post['parentid'] ) ) {
			$topic = wpforo_topic( $post['topicid'] );
			$limit = apply_filters( 'wpforo_thread_breadcrumb_limit', 3 );
			$items = array_reverse( $parents[ $post['parentid'] ] );
			$last  = key( array_slice( $items, - 1, 1, true ) );
			foreach( $items as $key => $parentid ) {
				if( $key < $limit || $key == $last ) {
					$parent  = wpforo_post( $parentid );
					$starter = ( $topic['userid'] == $parent['userid'] ) ? true : false;
					$class   = ( $starter ) ? ' wpf-starter' : '';
					if( ! empty( $parent ) ) {
						$member     = wpforo_member( $parent );
						$name       = ( wpfval( $member, 'display_name' ) ) ? $member['display_name'] : '';
						$tooltip    = ( $starter ) ? ' wpf-tooltip="' . esc_attr(
								wpforo_phrase( 'Topic Author', false ) . ' - ' . $name
							) . '" wpf-tooltip-size="medium"' : ' wpf-tooltip="' . esc_attr(
								wpforo_phrase( 'Reply by', false ) . ' ' . $name
							) . '" wpf-tooltip-size="medium"';
						$parent_url = ( wpfval( $parent, 'url' ) ) ? $parent['url'] : '#post-' . $parent['parentid'];
						$avatar     = WPF()->member->avatar(
							$member,
							'alt="' . esc_attr( $member['display_name'] ) . '"',
							18
						);
						if( ! $gab ) $html .= '<i class="fas fa-angle-right wpf-tree-sep"></i>';
						$html .= '<div class="wpf-tree-item' . $class . '" ' . $tooltip . '><a href="' . esc_url(
								$parent_url
							) . '">' . $avatar . '</a></div>';
					}
				} else {
					if( ! $gab ) $html .= '<i class="fas fa-ellipsis-h"></i>';
					$gab = true;
				}
			}
		}
	}
	echo $html;
}

function wpforo_check_threads( $posts = [] ) {
	$post = array_shift( $posts );
	if( wpfkey( $post, 'root' ) ) {
		if( is_null( $post['root'] ) && wpfval( $post, 'topicid' ) ) {
			WPF()->topic->rebuild_threads( $post['topicid'] );
			wpforo_clean_cache();
		}
	}
}

function wpforo_posts_ordering_dropdown( $orderby = null, $topicid = null ) {
	WPF()->tpl->posts_ordering_dropdown( $orderby, $topicid );
}

function wpforo_template_pagenavi( $class = '', $permalink = true, $paged = null, $items_count = null, $items_per_page = null ) {
	WPF()->tpl->pagenavi( $paged, $items_count, $items_per_page, $permalink, $class );
}

function wpforo_template_add_topic_button( $forumid = null ) {
	if( ! wpforo_is_bot() ) WPF()->tpl->add_topic_button( $forumid );
}

function wpforo_feed_rss2_url( $echo = true, $general = false ) {
	WPF()->feed->rss2_url( $echo, $general );
}

function wpforo_feed_link( $type = 'topic' ) {

	$nofollow = apply_filters( 'wpforo_feed_link_nofollow', false );
	$nofollow = ( $nofollow ) ? ' rel="nofollow" ' : '';

	if( wpforo_setting( 'rss', 'feed' ) ): ?>

		<?php if( $type === 'topic' && wpforo_setting('rss', 'feed_topic') ): ?>
            <a href="<?php WPF()->feed->rss2_url(); ?>" <?php echo $nofollow ?>
               title="<?php wpforo_phrase( 'Topic RSS Feed' ) ?>" target="_blank" class="wpf-button-outlined">
                <span class="">RSS</span> <i class="fas fa-rss wpfsx"></i>
            </a>
		<?php elseif( $type === 'forum' && wpforo_setting('rss', 'feed_forum') ): ?>
            <span class="wpf-feed">
                <a href="<?php wpforo_feed_rss2_url() ?>" <?php echo $nofollow ?> title="<?php wpforo_phrase(
	                'Forum RSS Feed'
                ) ?>" target="_blank" class="wpf-button-outlined">
                    <span style="text-transform: uppercase;">
                        <?php wpforo_phrase( 'RSS' ) ?>
                    </span>
                    <i class="fas fa-rss wpfsx"></i>
                </a>
            </span>
		<?php elseif( $type === 'home' && wpforo_setting('rss', 'feed_general') ): ?>
            <sep> &nbsp;|&nbsp;</sep>
            <span class="wpf-feed-forums">
                <a href="<?php wpforo_feed_rss2_url(
	                true,
	                'forum'
                ) ?>" <?php echo $nofollow ?> title="<?php wpforo_phrase( 'Forums RSS Feed' ) ?>" target="_blank">
                    <span><?php wpforo_phrase( 'Forums' ) ?></span> <i class="fas fa-rss wpfsx"></i>
                </a>
                </span>
            <sep> &nbsp;|&nbsp;</sep>
            <span class="wpf-feed-topics">
                <a href="<?php wpforo_feed_rss2_url(
	                true,
	                'topic'
                ) ?>" <?php echo $nofollow ?> title="<?php wpforo_phrase( 'Topics RSS Feed' ) ?>" target="_blank">
                    <span><?php wpforo_phrase( 'Topics' ) ?></span> <i class="fas fa-rss wpfsx"></i>
                </a>
            </span>
		<?php endif; ?>
	<?php endif;
}

function wpforo_template_topic_portable_form( $forumid = null ) {
	WPF()->tpl->topic_form_forums_selectbox( $forumid );
}

function wpforo_notifications() {
	WPF()->activity->notifications();
}

function wpforo_unread_url( $topicid = 0, $url = '', $echo = true, $force = false ) {
	//Only for loggedin users
	$enabled = ( ! $force ? wpforo_setting( 'logging', 'goto_unread' ) : true );
	if( WPF()->current_userid && $enabled && $topicid && $url ) {
		//Get read topics
		$topics = WPF()->log->get_read_topics();
		if( $last_read_id = wpfval( $topics, $topicid ) ) {
			//Make sure the post still located in current topic. When admin split a topic
			//and move the last reply to other topic, the users unread topic array isn't updated,
			//It's not reasonable to update usermeta of each user, so it's better to check
			//the post topic ID here. if the reply is split and moved to other topic use the
			//topic last post id.
			$last_read_post = wpforo_post( $last_read_id );
			if( $topicid == wpfval( $last_read_post, 'topicid' ) ) {
				$first_unread_id      = 0;
				$jump_to_first_unread = apply_filters( 'wpforo_jump_to_first_unread', true );
				if( $jump_to_first_unread ) {
					//Get first unread postid
					$first_unread_id = WPF()->post->next_post( $last_read_id, $topicid );
				}
				//Decide to whether execute more SQLs and create direct URLs or not
				$direct_url = apply_filters( 'wpforo_build_direct_unread_post_url', false );
				//Create new URLs
				if( $first_unread_id ) {
					//Change URL to first unread post
					if( $direct_url ) {
						$url = wpforo_post( $first_unread_id, 'url' );
					} else {
						$url = ( strpos( $url, '#' ) !== false ) ? preg_replace(
							'|\#.+$|',
							'#post-' . intval( $first_unread_id ),
							$url
						) : $url . '#post-' . intval( $first_unread_id );
					}
				} else {
					//Change URL to last read post
					if( $direct_url ) {
						$url = wpforo_post( $last_read_id, 'url' );
					} else {
						$url = ( strpos( $url, '#' ) !== false ) ? preg_replace(
							'|\#.+$|',
							'#post-' . intval( $last_read_id ),
							$url
						) : $url . '#post-' . intval( $last_read_id );
					}
				}
			} elseif( $last_postid = wpforo_topic( $topicid, 'last_post' ) ) {
				$url = wpforo_post( $last_postid, 'url' );
			}
		}
	} else {
		$direct_topic_url = apply_filters( 'wpforo_direct_topic_url', true );
		if( $direct_topic_url ) {
			$url = preg_replace( '|\#post\-\d+|i', '', $url );
			$url = WPF()->strip_url_paged_var( $url );
		}
	}
	if( ! $echo ) return esc_url( $url );
	echo esc_url( $url );
}

function wpforo_unread_button( $topicid = 0, $url = '', $echo = true, $postid = 0 ) {
	$button = '';
	if( ! $postid && $topicid && $url && WPF()->current_userid && wpforo_setting( 'logging', 'goto_unread_button' ) ) {
		$unread = wpforo_unread( $topicid, 'topic', false );
		if( $unread ) {
			$button_link = apply_filters( 'wpforo_jump_to_unread_button_link', false );
			$button_text = str_replace( [ '{', '}' ], '', wpforo_phrase( '{new}', false ) );
			if( wpforo_setting( 'logging', 'goto_unread' ) ) {
				if( $button_link ) {
					$url = wpforo_unread_url( $topicid, $url, false, true );
				}
			} else {
				$url         = wpforo_unread_url( $topicid, $url, false, true );
				$button_link = true;
			}
			$button = ( $button_link ) ? '<a href="' . $url . '" class="wpf-new-button" title="' . esc_attr(
					wpforo_phrase( 'Go to first unread post', false )
				) . '">' . $button_text . '</a>' : '<span class="wpf-new-button">' . $button_text . '</span>';
		}
	} elseif( $postid && $topicid && WPF()->current_userid ) {
		$unread = wpforo_unread( $topicid, 'post', false, $postid );
		if( $unread ) {
			$button_text = str_replace( [ '{', '}' ], '', wpforo_phrase( '{new}', false ) );
			$button      = '<span class="wpf-new-button">' . $button_text . '</span>';
		}
	}
	if( ! $echo ) return $button;
	echo $button;
}


/**
 * @param array|string $forum
 * @param string $before
 * @param string $after
 * @param bool $echo
 *
 * @return string
 */
function wpforo_forum_title( $forum, $before = '', $after = '', $echo = true ) {
	$title = is_array( $forum ) ? (string) wpfval( $forum, 'title' ) : $forum;
	$title = esc_html( $title );
	$title = apply_filters( 'wpforo_forum_title', $title, $forum );
	if( $title ) $title = $before . $title . $after;
	if( $echo ) echo $title;

	return $title;
}

/**
 * @param array|string $forum
 * @param string $before
 * @param string $after
 * @param bool $echo
 *
 * @return string
 */
function wpforo_forum_description( $forum, $before = '', $after = '', $echo = true ) {
	$description = is_array( $forum ) ? (string) wpfval( $forum, 'description' ) : $forum;
	$description = apply_filters( 'wpforo_forum_description', $description, $forum );
	if( $description ) $description = $before . $description . $after;
	if( $echo ) echo $description;

	return $description;
}

function wpforo_admin_cpanel() {
	if( WPF()->current_object['template'] === 'forum' && wpforo_setting( 'components', 'admin_cp' ) && wpforo_current_user_is( 'admin' ) ) {
		$toggle = get_user_meta( WPF()->current_userid, 'wpf-acp-toggle', true );
		if( ! $toggle || $toggle === 'open' ) {
			$display = 'block';
			$title   = wpforo_phrase( 'Close', false );
			$icon    = '<i class="fas fa-minus-square"></i>';
		} else {
			$display = 'none';
			$title   = wpforo_phrase( 'Open', false );
			$icon    = '<i class="fas fa-plus-square"></i>';
		}
		?>
        <div class="wpf-admincp">
            <div class="wpf-acp-header">
                <div class="wpf-acp-title"><i class="fas fa-cog"></i> <?php wpforo_phrase( 'Admin Control Panel' ) ?>
                </div>
                <div class="wpf-acp-toggle" wpf-tooltip="<?php echo esc_attr( $title ) ?>"><?php echo $icon ?></div>
            </div>
            <div class="wpf-acp-body" style="display: <?php echo $display ?>;">
                <div class="wpf-acp-content">
                    <a href="<?php echo admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'forums' ) ); ?>"
                       class="wpf-button-secondary"><i class="fas fa-plus"></i>&nbsp; <?php wpforo_phrase(
							'Add New Category or Forum'
						) ?></a>
                    <p class="wpf-acp-forum-info">
						<?php $layouts = '(<a href="https://wpforo.com/docs/categories-and-forums/forum-layouts/extended-layout/" target="_blank">Extended</a>, 
                                                <a href="https://wpforo.com/docs/categories-and-forums/forum-layouts/simplified-layout/" target="_blank">Simplified</a>, 
                                                  <a href="https://wpforo.com/docs/categories-and-forums/forum-layouts/question-answer-layout/" target="_blank">Q&A</a>, 
                                                    <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/threaded-layout/" target="_blank">Threaded</a>)';
						$layout        = '<a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/" target="_blank">' . wpforo_phrase(
								'the layout you want',
								false,
								'lower'
							) . '</a>';
						?>
						<?php echo sprintf(
							wpforo_phrase(
								'Please note, that forums can be displayed with different layouts %1$s, just edit the top category (blue panel) and set %2$s. Child forums inherit the top category (blue panel) layout.',
								false
							),
							$layouts,
							$layout
						); ?>
                    </p>
                </div>
                <div class="wpf-acp-footer">
                    <a href="<?php echo admin_url(
						'admin.php?page=' . wpforo_prefix_slug( 'settings' ) . '&tab=styles'
					); ?>" class="wpf-button-secondary"><i class="fas fa-paint-brush"></i> <?php wpforo_phrase(
							'Change Color Style'
						) ?></a>
                    <a href="<?php echo admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'moderations' ) ); ?>"
                       class="wpf-button-secondary"><i class="fas fa-tasks"></i> <?php wpforo_phrase(
							'Post Moderation'
						) ?></a>
                    <a href="<?php echo admin_url( 'admin.php?page=' . wpforo_prefix_slug( 'tools' ) ); ?>"
                       class="wpf-button-secondary"><i class="fas fa-shield-alt"></i> <?php wpforo_phrase(
							'Antispam'
						) ?></a>
					<?php $menu = wp_get_nav_menu_object( 'wpforo-navigation' ); ?>
					<?php if( ! empty( $menu ) && isset( $menu->term_id ) ): ?>
                        <a href="<?php echo admin_url( 'nav-menus.php?action=edit&menu=' . $menu->term_id ); ?>"
                           class="wpf-button-secondary"><i class="fas fa-bars"></i> <?php wpforo_phrase(
								'Forum Menu'
							) ?></a>
					<?php endif; ?>
                    <a href="<?php echo admin_url( 'widgets.php' ); ?>" class="wpf-button-secondary"><i
                                class="far fa-window-restore"></i> <?php wpforo_phrase( 'Forum Widgets' ) ?></a>
                    <a href="<?php echo wp_nonce_url(
						wpforo_home_url( '?wpfaction=reset_all_caches' ),
						'wpforo_reset_cache'
					) ?>" class="wpf-button-secondary" style="color: #e22d00 !important;"><i
                                class="fas fa-times"></i> <?php wpforo_phrase( 'Delete Forum Cache' ) ?></a>
                </div>
            </div>
        </div>
		<?php
	}
}

/**
 * show member template
 */
function wpforo_member_template() {
	WPF()->tpl->member_template();
}

/**
 * show member menu
 */
function wpforo_member_tabs() {
	WPF()->tpl->member_menu();
}

/**
 * get current object user or empty array
 *
 * @return array
 */
function wpforo_get_current_object_user() {
	return WPF()->current_object['user'];
}

/**
 * @param array|string $template
 *
 * @return bool
 */
function wpforo_is_member_template( $template = null ) {
	if( $template = WPF()->tpl->get_template( $template ) ) return wpfkey( WPF()->tpl->member_templates, $template['key'] );

	return false;
}

/**
 * show wpforo template
 *
 * @param array|string $template
 */
function wpforo_template( $template = null ) {
	$template = apply_filters( 'wpforo_template', $template );
	if( ! $template ) $template = WPF()->current_object['template'] ? WPF()->current_object['template'] : '404';
	if( WPF()->tpl->can_view_template( $template, WPF()->current_object['user'] ) ) {
		if( $path = WPF()->tpl->get_template_path( $template ) ) {
			include( $path );
		} else {
			WPF()->tpl->show_template( $template );
		}
	} else {
		WPF()->tpl->show_msg( wpforo_phrase( 'You do not have permission to view this page', false ) );
	}
}

/**
 * @param string $html
 *
 * @return string
 */
function wpforo_apply_ucf_shortcode( $html ) {
	return preg_replace_callback( '#\[wpfucf\s([^\[\]]+?)\]#iu', function( $shortcode ) {
		if( preg_match( '#(?:^|\s)field=[\'\"]([^\'\"]+?)[\'\"]#iu', $shortcode[1], $field ) ) {
			if( $field_key = trim( $field[1] ) ) {
				$userid = 0;
				if( preg_match(
					'#(?:^|\s)id=[\'\"]([^\'\"]+?)[\'\"]#iu',
					$shortcode[1],
					$id
				) ) {
					$userid = wpforo_bigintval( $id[1] );
				}
				if( ! $userid ) {
					$userid = WPF()->current_object['userid'] ? WPF()->current_object['userid'] : WPF()->current_userid;
				}
				if( $f = WPF()->member->get_field( $field_key ) ) {
					if( wpfval( $f, 'type' ) === 'html' && ( $html = trim( wpfval( $f, 'html' ) ) ) ) {
						return (string) WPF()->form->field_html( $f );
					} elseif( WPF()->form->can_view( $f ) && WPF()->form->can_show_value( $f ) ) {
						$f['value'] = wpforo_member( $userid, $field_key );
						$f          = WPF()->form->prepare_values( WPF()->form->esc_field( $f ), $userid );

						return $f['value'];
					}
				}
			}
		}

		return ''; // if that field has not found
	},                            $html );
}

/**
 * @param array|string $template
 * @param array|int $member
 * @param string $default
 *
 * @return string
 */
function wpforo_member_url( $template = null, $member = null, $default = '' ) {
	$member_url = $default;
	$template   = WPF()->tpl->get_template( $template );
	if( $template && wpforo_is_member_template( $template ) ) {
		if( ! $member ) $member = WPF()->current_object['user'] ? WPF()->current_object['user'] : WPF()->current_user;
		$profile_url = WPF()->member->get_profile_url( $member, $template['key'] );
		if( $profile_url ) $member_url = $profile_url;
	}

	return $member_url;
}

function wpforo_mark_all_read_link() {
	if( ! wpforo_is_bot() ) : ?>
        <a class="wpf-mark-all-read" href="<?php echo wp_nonce_url( '?foro=allread', 'wpforo_mark_all_read', 'foro_n' ); ?>" rel="nofollow">
            <svg xmlns="http://www.w3.org/2000/svg" data-name="Layer 1" viewBox="0 0 24 24"><path d="M22.922,1.7a2.985,2.985,0,0,0-2.458-.648l-6.18,1.123A3.993,3.993,0,0,0,12,3.461,3.993,3.993,0,0,0,9.716,2.172L3.536,1.049A3,3,0,0,0,0,4V20.834l12,2.183,12-2.183V4A2.992,2.992,0,0,0,22.922,1.7ZM11,20.8,2,19.166V4a1,1,0,0,1,1.179-.983L9.358,4.14A2,2,0,0,1,11,6.108Zm11-1.636L13,20.8V6.108A2,2,0,0,1,14.642,4.14l6.179-1.123A1,1,0,0,1,22,4Z"/></svg>
            <?php wpforo_phrase( 'Mark all read' ) ?>
        </a>
	<?php
	endif;
}

function wpforo_login_link() {
	if( ! wpforo_is_bot() ) : ?>
        <a href="<?php echo wpforo_login_url(); ?>"><i class="fas fa-sign-in-alt"></i> <?php wpforo_phrase(
				'Login'
			); ?></a>
	<?php
	endif;
}

function wpforo_register_link() {
	if( ! wpforo_is_bot() ) : ?>
        <a href="<?php echo wpforo_register_url(); ?>"><i class="fas fa-user-plus"></i> <?php wpforo_phrase(
				'Create Account'
			); ?></a>
	<?php
	endif;
}

function wpforo_topic_form_extra( $forumid, $values ) { ?>
    <div class="wpf-extra-fields">
		<?php do_action( 'wpforo_topic_form_extra_fields_before', $forumid, $values ) ?>
        <div class="wpf-main-fields">
			<?php do_action( 'wpforo_topic_form_buttons_hook', $forumid, $values ); ?>
        </div>
		<?php do_action( 'wpforo_topic_form_extra_fields_after', $forumid, $values ) ?>
    </div>
	<?php
}

function wpforo_reply_form_extra( $topic, $values ) { ?>
    <div class="wpf-extra-fields">
		<?php do_action( 'wpforo_reply_form_extra_fields_before', $topic, $values ) ?>
		<?php do_action( 'wpforo_reply_form_buttons_hook', $topic, $values ); ?>
		<?php do_action( 'wpforo_reply_form_extra_fields_after', $topic, $values ) ?>
    </div>
	<?php
}

function wpforo_post_search_form( $values ) {
	WPF()->tpl->post_search_form( $values );
}

function wpforo_member_search_form() {
	WPF()->tpl->member_search_form();
}

function wpforo_member_buttons( $member ) {
	WPF()->tpl->member_buttons( $member );
}

function wpforo_sanitize_search_body( $body, $needle ) {
	$body  = wpforo_content_filter( $body );
	$body  = wpforo_text( $body, 0, false );
	$words = explode( ' ', trim( $needle ) );
	if( ! empty( $words ) ) {
		$body_len = apply_filters( 'wpforo_search_results_body_length', 564 );
		$pos      = mb_stripos( $body, " " . trim( $words[0] ), 0, get_option( 'blog_charset' ) );
		if( strlen( $body ) > $body_len && $pos !== false ) {
			if( $pos > ( $body_len / 2 ) ) {
				$bef_body = "... ";
				$start    = mb_stripos( $body, " ", ( $body_len / 2 ), get_option( 'blog_charset' ) );
			} else {
				$bef_body = "";
				$start    = 0;
			}
			if( ( mb_strlen( $body, get_option( 'blog_charset' ) ) - $start ) > $body_len ) {
				$aft_body = " ...";
			} else {
				$aft_body = "";
			}
			$body = $bef_body . mb_substr( $body, $start, $body_len, get_option( 'blog_charset' ) ) . $aft_body;
		}
		foreach( $words as $word ) {
			$word = trim( $word );
			$body = preg_replace(
				'#(?:^|\s+)' . preg_quote( esc_html( $word ) ) . '#iu',
				' <span class="wpf-sword wpfcl-b">' . esc_html( $word ) . '</span>',
				$body
			);
		}
	}

	return $body;
}

function wpforo_topic_starter( $topic, $post, $type = 'full' ) {

	$starter = false;

	$topic_user  = (int) wpfval( $topic, 'userid' );
	$post_user   = (int) wpfval( $post, 'userid' );
	$topic_guest = wpfval( $topic, 'email' );
	$post_guest  = wpfval( $post, 'email' );

	if( $topic_user && $topic_user === $post_user ) {
		$starter = true;
	} elseif( $topic_guest && $topic_guest === $post_guest ) {
		$starter = true;
	}

	if( $starter ) {
		if( $type === 'full' ) {
			echo '<span class="wpf-post-starter"><i class="fas fa-feather-alt"></i> ' . wpforo_phrase(
					'Topic starter',
					false
				) . '</span>';
		} elseif( $type === 'icon' ) {
			echo '<span class="wpf-post-starter" wpf-tooltip="' . wpforo_phrase(
					'Topic starter',
					false
				) . '"><i class="fas fa-feather-alt"></i></span>';
		} elseif( $type === 'label' ) {
			echo '<span class="wpf-post-starter">' . wpforo_phrase( 'Topic starter', false ) . '</span>';
		}
	}
}

function wpforo_get_not_replied_topicids() {
	$sql = "SELECT `topicid`, COUNT(DISTINCT userid) AS userid_count FROM `" . WPF()->tables->posts . "` GROUP BY `topicid` HAVING userid_count = 1";

	return (array) WPF()->db->get_col( $sql, 0 );
}

function wpforo_topic_title( $topic, $url, $structure = '{i}{au}{t}{/a}{n}{v}', $echo = true, $class = '', $length = 70 ) {
	$html = __( 'No Title', 'wpforo' );
	if( $title = trim( esc_html( wpfval( $topic, 'title' ) ) ) ) {
		$class        = ( $class ) ? ' class="' . esc_attr( $class ) . '"' : '';
		$a            = '<a href="' . esc_url( $url ) . '"' . $class . ' title="' . esc_attr( $topic['title'] ) . '">';
		$topic['url'] = $url;

		// For better performance, make sure the component in demand before getting it.
		$title_cut   = ( strpos( $structure, '{tc}' ) !== false ) ? esc_html(
			wpforo_text( $topic['title'], $length, false )
		) : '';
		$icon_all    = ( strpos( $structure, '{i}' ) !== false ) ? wpforo_topic_icon( $topic, 'all', true, false ) : '';
		$icon_status = ( strpos( $structure, '{is}' ) !== false ) ? wpforo_topic_icon(
			$topic,
			'status',
			true,
			false
		) : '';
		$viewing     = ( strpos( $structure, '{v}' ) !== false ) ? wpforo_viewing( $topic, false ) : '';
		$new         = ( strpos( $structure, '{n}' ) !== false ) ? wpforo_unread_button(
			$topic['topicid'],
			$url,
			false
		) : '';
		$au          = ( strpos( $structure, '{au}' ) !== false ) ? '<a href="' . wpforo_unread_url(
				$topic['topicid'],
				$url,
				false
			) . '" title="' . esc_attr( $topic['title'] ) . '"' . $class . '>' : '';


		$structure  = apply_filters( 'wpforo_topic_title_structure', $structure, $topic );
		$components = [
			'{a}'  => $a,
			'{au}' => $au,
			'{/a}' => '</a>',
			'{i}'  => $icon_all,
			'{is}' => $icon_status,
			'{t}'  => $title,
			'{tc}' => $title_cut,
			'{v}'  => ' ' . $viewing,
			'{n}'  => ' ' . $new,
			'{p}'  => '',
			'{pt}' => '',
		];
		$components = apply_filters( 'wpforo_topic_title_components', $components, $topic );
		$html       = strtr( $structure, $components );
		$html       = apply_filters( 'wpforo_topic_title_html', $html );
	}
	if( ! $echo ) return $html;
	echo $html;
}

function wpforo_single_title( $type = 'post', $item = [], $before = '', $after = '', $echo = true ) {
	$title = '';
	if( $type === 'post' && wpfval( $item, 'title' ) ) {
		$icon_title = WPF()->tpl->icon( 'topic', $item, false, 'title' );
		if( $icon_title ) {
			$title .= '<span class="wpf-status-title">[' . esc_html( $icon_title ) . ']</span> ';
		}
		$title = apply_filters( "wpforo_single_{$type}_pre_title", $title, $item );
		$title .= esc_html( wpforo_text( $item['title'], 0, false ) );
	}
	$title = apply_filters( "wpforo_single_{$type}_title", $title );
	if( $title ) $title = $before . $title . $after;
	if( $echo ) echo $title;

	return $title;
}

function wpforo_schema( $forum, $topic, $post, $type = '' ) {
	$schema = '';

	if( apply_filters( "wpforo_schema", true ) ) {

		if( ! $type ) {
			if( 3 === (int) wpfval( $forum, 'cat_layout' ) ) {
				$type = 'QAPage';
			}
		}
		if( $type === 'QAPage' && wpfval( $topic, 'topicid' ) ) {
			$extended                = apply_filters( "wpforo_schema_qa_extended_best_answer", true );
			$best_answer             = WPF()->post->get_best_answer( $topic['topicid'], $extended );
			$suggested_answers_count = apply_filters( "wpforo_schema_qa_suggested_answers_count", 3 );
			$suggested_answers       = WPF()->post->get_suggested_answers(
				$topic['topicid'],
				$suggested_answers_count
			);
			$schema                  .= '
         <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "QAPage",
                "mainEntity": {
                    "@type": "Question",
                    "name": "' . esc_attr( $post['title'] ) . '",
                    "text": "' . esc_attr( sanitize_text_field( $post['body'] ) ) . '",
                    "answerCount": ' . intval( $topic['answers'] ) . ',
                    "upvoteCount": ' . intval( $post['votes'] ) . ',
                    "dateCreated": "' . date( 'Y-m-d', strtotime( $post['created'] ) ) . 'T' . date(
					'H:i',
					strtotime(
						$post['created']
					)
				) . 'Z",
                    "author": {
                        "@type": "Person",
                        "name": "' . wpforo_member( $post['created'], 'display_name' ) . '"
                    }';
			if( ! empty( $best_answer ) ) {
				$schema .= ',
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "' . esc_attr( sanitize_text_field( $best_answer['body'] ) ) . '",
                        "dateCreated": "' . date( 'Y-m-d', strtotime( $best_answer['created'] ) ) . 'T' . date(
						'H:i',
						strtotime(
							$best_answer['created']
						)
					) . 'Z",
                        "upvoteCount": ' . intval( $best_answer['votes'] ) . ',
                        "url": "' . wpforo_post( $best_answer['postid'], 'url' ) . '",
                        "author": {
                            "@type": "Person",
                            "name": "' . wpforo_member( $best_answer['userid'], 'display_name' ) . '"
                        }
                    }';
			}
			if( ! empty( $suggested_answers ) ) {
				$schema  .= ',
                    "suggestedAnswer": [';
				$answers = '';
				foreach( $suggested_answers as $key => $suggested_answer ) {
					if( wpfval( $best_answer, 'postid' ) === wpfval( $suggested_answer, 'postid' ) ) {
						continue;
					}
					$answers .= '
                        {
                            "@type": "Answer",
                            "text": "' . esc_attr( sanitize_text_field( $suggested_answer['body'] ) ) . '",
                            "dateCreated": "' . date( 'Y-m-d', strtotime( $suggested_answer['created'] ) ) . 'T' . date(
							'H:i',
							strtotime( $suggested_answer['created'] )
						) . 'Z",
                            "upvoteCount": ' . intval( $suggested_answer['votes'] ) . ',
                            "url": "' . wpforo_post( $suggested_answer['postid'], 'url' ) . '",
                            "author": {
                                "@type": "Person",
                                "name": "' . wpforo_member( $suggested_answer['userid'], 'display_name' ) . '"
                            }
                        },';
				}
				$schema .= trim( $answers, ',' ) . '
                    ]';
			}
			$schema .= '
                }
            }
        </script>';
		}
	}

	return $schema;
}

function wpforo_template_profile_board_panel(){
    echo WPF()->tpl->profile_board_panel();
}

function wpforo_template_profile_activity_panel(){
    echo WPF()->tpl->profile_activity_panel();
}

function wpforo_template_profile_favored_panel(){
    echo WPF()->tpl->profile_favored_panel();
}

function wpforo_profile_head_attrs( $user ){
    printf(
        'style="background-image:url(\'%1$s\')"',
        esc_url( trim(wpfval( $user, 'cover' )) ? trim(wpfval( $user, 'cover' )) . '?lm=' . md5($user['cover']) : WPFORO_URL . '/assets/images/profile-bg.jpg' )
    );
}

function wpforo_template_profile_action_buttons( $user ){
    do_action( 'wpforo_template_profile_action_buttons_left', $user ); ?>
    <div class="wpf-grow"></div>
    <?php do_action( 'wpforo_template_profile_action_buttons_right', $user );
    echo WPF()->tpl->get_member_actions_html( $user );
}


function wpforo_get_users_count_for_topic( $topicid = 0 ){
    if( !$topicid ) $topicid = WPF()->current_object['topicid'];
    return WPF()->post->get_users_count_for_topic( $topicid );
}

function wpforo_get_likes_for_topic( $topicid ){
    return WPF()->reaction->get_likes_for_topic( $topicid );
}

function wpforo_display_header( $template = '' ){
    if( !$template ) $template = WPF()->current_object['template'];
    return !wpforo_is_member_template( $template ) || wpforo_setting('profiles', 'profile_header');
}

function wpforo_display_footer( $template = '' ){
    if( !$template ) $template = WPF()->current_object['template'];
    return (
        ( wpforo_is_member_template( $template ) && wpforo_setting('profiles', 'profile_footer') )
        || ( !wpforo_is_member_template( $template ) && wpforo_setting('components', 'footer') )
    );
}

function wpforo_forum_list_need_add_topic_button( $forumid ){
	switch( WPF()->forum->get_layout( $forumid ) ){
		case 2:
			$need_button = wpforo_setting( 'forums', 'layout_simplified_add_topic_button' );
		break;
		case 3:
			$need_button = wpforo_setting( 'forums', 'layout_qa_add_topic_button' );
		break;
		case 4:
			$need_button = wpforo_setting( 'forums', 'layout_threaded_add_topic_button' );
		break;
        default:
	        $need_button = true;
        break;
	}

    return $need_button;
}

function wpforo_get_forum_cover_styles( $forum ){
	$r = [
		'cover'  => '',
		'title'  => '',
		'info'   => '',
		'button' => '',
	];

    if( $forum['cover_url'] ){
	    $r['cover']  = sprintf( 'style="background-image:url(\'%1$s\'); background-position: center; background-size: cover; height:150px;"', esc_attr( $forum['cover_url'] ) );
	    $r['title']  = 'style="background: #eee; opacity: 0.7; display: inline-block; padding: 2px 20px 3px 20px; line-height: 26px; border-radius: 4px; color:#000"';
	    $r['info']   = 'style="font-weight: bold; text-shadow: 0 0 3px #fff; padding-top:5px; padding-bottom: 1px;"';
	    $r['button'] = 'style="background: #eee; opacity: 0.7; padding-top:3px; padding-bottom: 2px; line-height: 26px; border-radius: 0 4px 4px 0;"';
    }

    return $r;
}
