<?php

namespace wpforo\classes;

use stdClass;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

define( 'WPFORO_THEME_DIR', WPFORO_DIR . DIRECTORY_SEPARATOR . 'themes' );
define( 'WPFORO_THEME_URL', WPFORO_URL . '/themes' );

class Template {
	public $default;
	public $theme = '2022';
	public $theme_info;
	public $template_dir;
	public $template_url;
	public $paths        = [];
	public $base_templates;
	public $member_templates;
	public $templates;
	public $menu         = [];

	public function __construct() {
		$this->init_defaults();
		$this->init_base_templates();
        add_action( 'wpforo_wp_init', function(){
               $this->init_member_templates();
               $this->init_templates();
            },
            999
        );
		$this->init_current_theme( wpforo_setting( 'general', 'current_theme' ) );
		add_action( 'wpforo_after_init', function() {
			$this->init_hooks();
			$this->init_nav_menu();
		} );
	}

	private function init_hooks() {
		if( is_wpforo_page() ) {
			add_filter( "mce_external_plugins", [ $this, 'add_mce_external_plugins' ], 15 );
			add_filter( "tiny_mce_plugins", [ $this, 'filter_tinymce_plugins' ], 15 );
			add_filter( "wp_mce_translation", [ $this, 'add_tinymce_translations' ] );
			add_filter( "wpforo_editor_settings", [ $this, 'editor_settings_required_params' ], 999 );

			add_action( 'wpforo_topic_form_extra_fields_after', [ $this, 'add_default_attach_input' ] );
			add_action( 'wpforo_reply_form_extra_fields_after', [ $this, 'add_default_attach_input' ], 10, 1 );
			add_action( 'wpforo_portable_form_extra_fields_after', [ $this, 'add_default_attach_input' ] );

			add_filter( 'is_wpforo_attach_page_templates', function( $templates ) {
				$templates[] = 'add-topic';

				return $templates;
			} );
			add_filter( 'wpforo_emoticons_loading_template', function( $templates ) {
				$templates[] = 'add-topic';

				return $templates;
			} );

			add_filter( 'wpforo_content_after', [ $this, 'do_spoilers' ] );
			add_action( 'wp_footer', [ $this, 'add_footer_html' ], 999999 );

            add_action( 'wpforo_profile_head_right', function( $user ){
                echo $this->change_cover_button( $user );
            } );

			//ajax actions hooks
			add_action( 'wp_ajax_wpforo_active_tab_content_ajax', [ $this, 'ajx_active_tab_content' ] );
		}
		add_action( 'wpforo_register_form_end', function() {
			echo '<input type="hidden" name="wpfaction" value="registration">';
		} );
		add_action( 'wpforo_login_form_end', function() {
			wp_nonce_field( 'login', '_wpfnonce', false );
			echo '<input type="hidden" name="wpfaction" value="login">';
		} );
		add_action( 'wpforo_profile_account_bottom', function() {
			echo '<input type="hidden" name="wpfaction" value="profile_update">';
		} );
	}

	private function init_defaults() {
		$this->default = new stdClass;

		$this->default->editor_settings = [
			'media_buttons'    => false,
			'textarea_name'    => '',
			'textarea_rows'    => 15,
			'tabindex'         => '',
			'editor_height'    => 130,
			'editor_css'       => '',
			'editor_class'     => '',
			'teeny'            => false,
			'dfw'              => false,
			'plugins'          => 'hr,lists,textcolor,paste,wpautoresize,fullscreen',
			'external_plugins' => [
				'wpforo_pre_button'         => WPFORO_URL . '/assets/js/tinymce-pre.js',
				'wpforo_link_button'        => WPFORO_URL . '/assets/js/tinymce-link.js',
				'wpforo_spoiler_button'     => WPFORO_URL . '/assets/js/tinymce-spoiler.js',
				'wpforo_source_code_button' => WPFORO_URL . '/assets/js/tinymce-code.js',
				'emoticons'                 => WPFORO_URL . '/assets/js/tinymce-emoji.js',
			],
			'tinymce'          => [
				'toolbar1'                => 'fontsizeselect,bold,italic,underline,strikethrough,forecolor,bullist,numlist,hr,alignleft,aligncenter,alignright,alignjustify,link,unlink,blockquote,pre,wpf_spoil,undo,redo,pastetext,source_code,emoticons,fullscreen',
				'toolbar2'                => '',
				'toolbar3'                => '',
				'toolbar4'                => '',
				'content_style'           => 'blockquote{border: #cccccc 1px dotted; background: #F7F7F7; padding:10px;font-size:12px; font-style:italic; margin: 20px 10px;} pre{border-left: 3px solid #ccc; outline: none !important; background: #fafcff;padding: 10px;font-size: 14px;margin: 20px 0 0 10px;display: block;width: 100%;}  img.emoji{width: 20px;}',
				'object_resizing'         => false,
				'autoresize_on_init'      => true,
				'wp_autoresize_on'        => true,
				'wp_keep_scroll_position' => true,
				'indent'                  => true,
				'add_unload_trigger'      => false,
				'wpautop'                 => false,
				'setup'                   => 'wpforo_tinymce_setup',
				'content_css'             => '',
				'extended_valid_elements' => 'i[class|style],span[class|style]',
				'custom_elements'         => '',
			],
			'quicktags'        => false,
			'default_editor'   => 'tinymce',
		];

		$this->default->template = [
			'type'                       => 'html',
			'key'                        => '',
			'slug'                       => '',
			'menu_shortcode'             => '',
			'callback_for_can_view_menu' => '',
			'ico'                        => '',
			'title'                      => '',
			'callback_for_page'          => '',
			'callback_for_get_url'       => '',
			'value'                      => '',
			'status'                     => 1,
			'is_default'                 => 0,
			'callback_for_can'           => '',
			'can'                        => '',
			'add_in_member_menu'         => 1,
			'add_in_member_buttons'      => 0,
		];

		$this->default->base_templates = [
			'members'      => [
				'type'                 => 'callback',
				'key'                  => 'members',
                'slug'                 => 'participants',
				'title'                => 'Members',
				'callback_for_get_url' => function() {
					return wpforo_members_url();
				},
				'is_default'           => 1,
				'can'                  => 'vmem',
				'callback_for_page'     => function(){
					require_once wpftpl( 'members.php' );
				}
			],
			'register'     => [
				'type'                       => 'callback',
				'key'                        => 'register',
				'slug'                       => 'sign-up',
				'title'                      => 'Register',
				'callback_for_get_url'       => function() {
					return wpforo_register_url();
				},
				'callback_for_can_view_menu' => function() {
					return ( ! wpforo_is_bot() && ! WPF()->current_userid && ( wpforo_setting( 'authorization', 'user_register' ) || wpforo_setting( 'authorization', 'register_url' ) ) );
				},
				'is_default'                 => 1,
				'callback_for_page'     => function(){
					require_once wpftpl( 'register.php' );
				}
			],
			'login'        => [
				'type'                       => 'callback',
				'key'                        => 'login',
				'slug'                       => 'sign-in',
				'title'                      => 'Login',
				'callback_for_get_url'       => function() {
					return wpforo_login_url();
				},
				'callback_for_can_view_menu' => function() {
					return ! wpforo_is_bot() && ! WPF()->current_userid;
				},
				'is_default'                 => 1,
				'callback_for_page'     => function(){
					require_once wpftpl( 'login.php' );
				},
				'callback_for_can' => function(){
                    return ! wpforo_is_bot() && ! WPF()->current_userid;
                },
			],
			'lostpassword' => [
				'type'                       => 'callback',
				'key'                        => 'lostpassword',
				'slug'                       => 'change-password',
				'title'                      => 'Lost your password',
				'callback_for_get_url'       => function() {
					return wpforo_lostpassword_url();
				},
				'callback_for_can_view_menu' => function() {
					return ! wpforo_is_bot() && ! WPF()->current_userid;
				},
				'is_default'                 => 1,
				'callback_for_page'     => function(){
					if( wpfval( WPF()->GET, 'wpfaction' ) === 'resetpassword_form' ){
						echo do_shortcode( '[wpforo-resetpassword]' );
					}else{
						echo do_shortcode( '[wpforo-lostpassword]' );
                    }
				}
			],
			'logout'       => [
				'type'                       => 'callback',
				'key'                        => 'logout',
				'slug'                       => 'sign-out',
				'title'                      => 'Logout',
				'callback_for_get_url'       => function() {
					return wpforo_logout_url();
				},
				'callback_for_can_view_menu' => function() {
					return ! wpforo_is_bot() && WPF()->current_userid;
				},
				'is_default'                 => 1,
				'callback_for_can' => function(){
					return ! wpforo_is_bot() && WPF()->current_userid;
				}
			],
			'member'       => [
				'type'                  => 'callback',
				'key'                   => 'member',
				'slug'                  => 'participant',
				'ico'                   => '<i class="fas fa-user"></i>',
				'title'                 => 'Profile',
				'is_default'            => 1,
				'can'                   => 'vprf',
				'callback_for_page'     => function(){
					require_once wpftpl( 'profile.php' );
				}
			],
			'cantlogin' => [
				'type'                       => 'callback',
				'key'                        => 'cantlogin',
				'slug'                       => 'login-message',
				'title'                      => 'Can\'t Login',
				'is_default'                 => 1,
				'callback_for_can'           => function() {
                    return (bool) WPF()->ram_cache->get( 'USER_LOGIN_REFERER' );
				},
				'callback_for_page'          => function() {
					echo $this->cantlogin_page();
				},
				'callback_for_can_view_menu' => '__return_false',
				'add_in_member_menu'         => 0,
				'add_in_member_buttons'      => 0,
			],
		];

        $this->default->member_templates = [
	        'profile'       => [
		        'type'                  => 'callback',
		        'key'                   => 'profile',
		        'menu_shortcode'        => 'wpforo-profile-home',
		        'ico'                   => '<i class="fas fa-user"></i>',
		        'title'                 => 'Profile',
		        'is_default'            => 1,
		        'can'                   => 'vprf',
		        'add_in_member_menu'    => 1,
		        'add_in_member_buttons' => 1,
		        'callback_for_page'     => function(){
                    require_once wpftpl( 'profile-home.php' );
                },
	        ],
	        'account'       => [
		        'type'                  => 'callback',
		        'key'                   => 'account',
		        'menu_shortcode'        => 'wpforo-profile-account',
		        'ico'                   => '<i class="fas fa-cog"></i>',
		        'title'                 => 'Account',
		        'is_default'            => 1,
		        'can'                   => 'em',
		        'add_in_member_menu'    => 0,
		        'add_in_member_buttons' => 1,
		        'callback_for_page'     => function(){
			        $min = wpforo_setting( 'antispam', 'min_number_posts_to_edit_account' );
			        if( WPF()->current_object['user_is_same_current_user'] && wpfval(WPF()->current_user, 'posts') < $min ) {
				        printf(
                            '<p class="wpf-p-error">%1$s</p>',
                            sprintf( wpforo_phrase( 'You should have %d or more approved posts to be able edit your profile information', false ), $min )
                        );
			        }else{
				        require_once wpftpl( 'profile-account.php' );
			        }
		        },
	        ],
	        'activity'      => [
		        'type'                  => 'callback',
		        'key'                   => 'activity',
		        'menu_shortcode'        => 'wpforo-profile-activity',
		        'ico'                   => '<i class="fas fa-comments"></i>',
		        'title'                 => 'Activity',
		        'is_default'            => 1,
		        'can'                   => 'vpra',
		        'add_in_member_menu'    => 1,
		        'add_in_member_buttons' => 1,
		        'callback_for_page'     => function(){
			        require_once wpftpl( 'profile-activity.php' );
		        },
	        ],
	        'favored'      => [
		        'type'                  => 'callback',
		        'key'                   => 'favored',
		        'menu_shortcode'        => 'wpforo-profile-favored',
		        'ico'                   => '<i class="fas fa-bookmark"></i>',
		        'title'                 => 'Favored',
		        'is_default'            => 1,
		        'can'                   => 'l',
		        'add_in_member_menu'    => 1,
		        'add_in_member_buttons' => 0,
		        'callback_for_page'     => function(){
			        require_once wpftpl( 'profile-favored.php' );
		        },
	        ],
        ];

		$this->default->templates = [
			'add-topic'    => [
				'type'              => 'callback',
				'key'               => 'add-topic',
				'title'             => 'Add New Topic',
				'is_default'        => 1,
				'callback_for_page' => function() {
					echo '<div class="wpf-add-topic-wrap">';
					printf( '<h2 class="wpf-add-topic-title">%1$s</h2>', wpforo_phrase( 'Add New Topic', false ) );
					$forumid = (int) wpfval( WPF()->current_object['qvars'], 0 );
					$this->portable_topic_form( $forumid );
					echo '</div>';
				},
				'can'               => 'vt_add_topic',
			],
			'recent' => [
				'type'              => 'callback',
				'key'               => 'recent',
				'title'             => 'Recent Posts',
				'is_default'        => 1,
				'callback_for_page' => function() {
					require_once wpftpl( 'recent.php' );
				},
			],
			'tags' => [
				'type'              => 'callback',
				'key'               => 'tags',
				'title'             => 'Tags',
				'is_default'        => 1,
				'callback_for_page' => function() {
					require_once wpftpl( 'tags.php' );
				},
			],
		];
	}

	/**
	 * initialize base templates
	 */
	private function init_base_templates() {
		$this->base_templates = (array) apply_filters( 'wpforo_init_base_templates', $this->default->base_templates );
		do_action( 'wpforo_after_init_base_templates', $this->base_templates );
	}

    /**
	 * initialize templates
	 */
	private function init_member_templates() {
		$this->member_templates = (array) apply_filters( 'wpforo_init_member_templates', $this->default->member_templates );
        do_action( 'wpforo_after_init_member_templates', $this->member_templates );
	}

    /**
	 * initialize templates
	 */
	private function init_templates() {
		$this->templates = (array) apply_filters( 'wpforo_init_templates', $this->default->templates );
        do_action( 'wpforo_after_init_templates', $this->templates );
	}

	/**
	 * initialize templates paths
	 */
	private function init_paths() {
		if( ! $this->paths && function_exists( 'wpftpl' ) ) {
			$this->paths = [
				'404'    => wpftpl( '404.php' ),
				'search' => wpftpl( 'search.php' ),
				'forum'  => wpftpl( 'forum.php' ),
				'topic'  => wpftpl( 'topic.php' ),
				'post'   => wpftpl( 'post.php' ),
			];
			$this->paths = apply_filters( 'wpforo_init_template_paths', $this->paths );
		}
	}

	/**
	 * @param array|string $template
	 *
	 * @return array
	 */
	private function fix_template( $template ) {
		if( is_string( $template ) ) {
			$t                   = $this->default->template;
			$t['key']            = $template;
			$t['menu_shortcode'] = $template;
			$t['title']          = ucfirst( $template );

			return $t;
		} else {
			$template = wpforo_settype( $template, 'array' );
			if( is_array( $template ) ) {
				$template = array_merge( $this->default->template, $template );
				if( ! $template['ico'] = trim( $template['ico'] ) ) $template['ico'] = '<i class="fas fa-user"></i>';

				return $template;
			}
		}

		return $this->default->template;
	}

	/**
	 * @param array $template
	 *
	 * @return bool
	 */
	private function is_template( &$template ) {
		$key = wpfval( $template, 'key' );
		if( wpfval( $template, 'type' ) && $key && is_string( $key ) ) {
			$template = $this->fix_template( $template );

			return true;
		}

		return false;
	}

	/**
	 * @param array|string $template
	 * @param array $member
	 * @param bool $default
	 *
	 * @return bool
	 */
	public function can_view_template( $template, $member = [], $default = false ) {
		if( $template = $this->get_template( $template ) ) {
			if( (int) $template['status'] ) {
				if( ! is_callable( $template['callback_for_can'] ) ) {
					if( $template['can'] ) {
						$userid = wpforo_bigintval( wpfval( $member, 'userid' ) );
						if( $userid && $userid === WPF()->current_userid && wpforo_is_member_template( $template ) ) {
							return true;
						} else {
							return (bool) WPF()->usergroup->can( $template['can'] );
						}
					} else {
						return true;
					}
				} else {
					return (bool) call_user_func( $template['callback_for_can'], $template, $member, $default );
				}
			}
		}

		return $default;
	}

	/**
	 * @param array|string $template
	 *
	 * @return string
	 */
	public function get_key( $template = null ) {
		if( ! $template ) $template = WPF()->current_object['template'];

		return is_string( $template ) ? $template : (string) wpfval( $template, 'key' );
	}

	/**
	 * @param array|string $template
	 *
	 * @return string
	 */
	private function get_menu_shortcode( $template ) {
		$menu_shortcode = '';
		if( $template = $this->get_template( $template ) ) {
			$menu_shortcode = sanitize_title( trim( $template['menu_shortcode'], '%/' ) );
			if( ! $menu_shortcode ) $menu_shortcode = $template['key'];
			if( strpos( $menu_shortcode, 'wpforo-' ) !== 0 ) $menu_shortcode = 'wpforo-' . $menu_shortcode;
		}

		return $menu_shortcode;
	}

	/**
	 * @param array|string $template
	 * @param array $args
	 * @param string $default
	 *
	 * @return string
	 */
	private function get_url( $template, $args = [], $default = '' ) {
		$return = $default ?: '#';
		if( $template = $this->get_template( $template ) ) {
			if( is_callable( $template['callback_for_get_url'] ) ) {
				$return = call_user_func( $template['callback_for_get_url'], $template, $args, $default );
			} elseif( wpfval( $args, 'userid' ) && wpfval( $args, 'user_nicename' ) && wpforo_is_member_template( $template ) ) {
				$return = WPF()->member->get_profile_url( $args, $template['key'] );
			} elseif( ! $default && $slug = wpforo_settings_get_slug( $template['key'] ) ) {
				$return = wpforo_home_url( $slug );
			}
		}

		return $return;
	}

	/**
	 * @param array|string $template
	 *
	 * @return string
	 */
	public function get_template_path( $template ) {
		$this->init_paths();
		$path = (string) wpfval( $this->paths, $this->get_key( $template ) );

		return apply_filters( 'wpforo_get_template_path', $path, $template );
	}

	/**
	 * @param bool $only_enableds
	 * @param bool $only_defaults
	 * @param array $__templates
	 *
	 * @return array
	 */
	public function get_templates( $only_enableds = true, $only_defaults = false, $__templates = [] ) {
		$templates = [];
		if( ! $__templates ) $__templates = array_merge( $this->base_templates, $this->member_templates, $this->templates );
		foreach( $__templates as $key => $template ) {
			if( $this->is_template( $template ) && ( ! $only_enableds || $template['status'] ) && ( ! $only_defaults || $template['is_default'] ) ) {
				$templates[ $key ] = $template;
			}
		}

		return $templates;
	}

	/**
	 * @param bool $only_enableds
	 * @param bool $only_defaults
	 *
	 * @return array
	 */
	public function get_member_templates( $only_enableds = true, $only_defaults = false ) {
		return $this->get_templates( $only_enableds, $only_defaults, $this->member_templates );
	}

	/**
	 * @param bool $only_enableds
	 * @param bool $only_defaults
	 *
	 * @return array
	 */
	public function get_templates_list( $only_enableds = true, $only_defaults = false ) {
		return array_keys( $this->get_templates( $only_enableds, $only_defaults ) );
	}

	/**
	 * @param bool $only_enableds
	 * @param bool $only_defaults
	 *
	 * @return array
	 */
	public function get_member_templates_list( $only_enableds = true, $only_defaults = false ) {
		return array_keys( $this->get_member_templates( $only_enableds, $only_defaults ) );
	}

	/**
	 * @param array|string $template
	 *
	 * @return array
	 */
	public function get_template( $template = null ) {
		$return = [];
		if( ! $template ) $template = WPF()->current_object['template'];
		if( is_string( $template ) ) {
			$templates = $this->get_templates();
			if( $t = (array) wpfval( $templates, $template ) ) {
				$return = $t;
			} else {
				$return = $this->fix_template( $template );
			}
		} elseif( is_array( $template ) ) {
			$return = $this->fix_template( $template );
		}

		return ( $this->is_template( $return ) ? $return : [] );
	}

	/**
	 * show given template
	 *
	 * @param array|string $template
	 */
	public function show_template( $template = null ) {
		if( $template = $this->get_template( $template ) ) {
			if( $template['type'] === 'callback' && is_callable( $template['callback_for_page'] ) ) { ?>
				<?php if( $template['key'] === 'add-topic' ): ?>
					<?php echo call_user_func( $template['callback_for_page'], $template ); ?>
				<?php else: ?>
                    <div class="wpforo-profile-content <?php echo esc_attr('wpf-pt-' . wpfval(WPF()->current_object ,'template')) ?>">
                        <div class="wpf-profile-section wpf-mi-section">
                            <div class="wpf-profile-body">
                                <?php echo call_user_func( $template['callback_for_page'], $template ); ?>
                            </div>
                        </div>
                    </div>
				<?php endif; ?>
				<?php
			} elseif( $template['type'] === 'html' && is_string( $template['value'] ) && $template['value'] ) { ?>
                <div class="wpforo-profile-content <?php echo esc_attr('wpf-pt-' . wpfval(WPF()->current_object ,'template')) ?>">
                    <div class="wpf-profile-section wpf-mi-section">
                        <?php echo wpautop( do_shortcode( wpforo_apply_ucf_shortcode( $template['value'] ) ) ); ?>
                    </div>
                </div>
				<?php
			} elseif( $template['type'] === 'builder' && $fields = $template['value'] ) {
				if( $fields = maybe_unserialize( $fields ) ) : ?>
                    <div class="wpforo-profile-content  <?php echo esc_attr('wpf-pt-' . wpfval(WPF()->current_object ,'template')) ?>">
                        <div class="wpf-profile-section wpf-mi-section">
                            <div class="wpf-table">
								<?php wpforo_fields( $fields ) ?>
                            </div>
                        </div>
                    </div>
				<?php
				endif;
			}
		} else {
			$this->show_msg( wpforo_phrase( 'Your template is not found', false ) );
		}
	}

	public function member_menu() {
		if( $templates = $this->get_member_templates() ) {
			foreach( $templates as $key => $template ) {
				if( $template['add_in_member_menu'] && $this->can_view_template( $template, WPF()->current_object['user'] ) ) {
					printf(
						'<a class="wpf-profile-menu %1$s" href="%2$s">%3$s <span class="wpf-profile-menu-label">%4$s</span></a>',
						WPF()->current_object['template'] === $key ? 'wpforo-active' : '',
						esc_url( $this->get_url( $template, WPF()->current_object['user'], WPF()->member->get_profile_url( WPF()->current_object['user'], $key ) ) ),
						$template['ico'],
						wpforo_phrase( $template['title'], false )
					);
				}
			}
		}
	}

	public function member_buttons( $member ) {
		if( wpforo_bigintval( wpfval( $member, 'userid' ) ) && WPF()->usergroup->can( 'vprf' ) ) {
			if( $templates = $this->get_member_templates() ) {
				foreach( $templates as $template ) {
					if( $template['add_in_member_buttons'] && $this->can_view_template( $template, $member ) ) {
						printf(
							'<a class="wpf-member-profile-button" title="%1$s" href="%2$s">%3$s</a>',
							wpforo_phrase( $template['title'], false ),
							esc_url( $this->get_url( $template, $member, WPF()->member->get_profile_url( $member, $template['key'] ) ) ),
							$template['ico']
						);
					}
				}
			}
			do_action( 'wpforo_member_info_buttons', $member );
		}
	}

	public function get_member_actions_html( $user ) {
        $html = '';
        foreach( WPF()->member->get_actions( $user ) as $key => $action ){
            $data = [];
            if( $action['data'] ) foreach( $action['data'] as $k => $v ) $data[] = "data-$k=\"$v\"";
            $_html = sprintf(
	            '<span class="wpf-ab-%1$s" wpf-tooltip="%2$s" wpf-tooltip-position="top" wpf-tooltip-size="medium" %3$s %4$s>%5$s</span>',
	            $key,
	            $action['label'],
	            implode( ' ', $data ),
	            ( $action['confirm_msg'] ? 'onclick="return wpforo_confirm(\'' . $action['confirm_msg'] . '\', event)"' : '' ),
	            $action['ico']
            );
            $url = ( is_callable( $action['callback_for_get_url'] ) ? call_user_func( $action['callback_for_get_url'] ) : '' );
            $html .= ($url ? sprintf( '<a href="%1$s">%2$s</a>', $url, $_html ) : $_html);
        }
        return $html ? sprintf( '<div class="wpforo-user-tools">%1$s</div>', $html ) : '';
    }

	public function member_template( $template = null ) {
		if( $template = $this->get_template( $template ) ) {
			if( $this->can_view_template( $template, WPF()->current_object['user'] ) ) {
				$this->show_template( $template );
			} else {
				$this->show_msg( wpforo_phrase( 'You do not have permission to view this page', false ) );
			}
		} else {
			$this->show_msg( wpforo_phrase( 'Your template has not found', false ) );
		}
	}

	public function show_msg( $msg ) {
		?>
        <div class="wpfbg-7 wpf-page-message-wrap">
        <div class="wpf-page-message-text"><?php echo $msg ?></div></div><?php
	}

	/**
	 * @param array|string $template
	 * @param bool $default
	 *
	 * @return bool
	 */
	public function can_view_nav_menu( $template, $default = false ) {
		$can_view = $default;
		if( $template = $this->get_template( $template ) ) {
			if( $this->can_view_template( $template, WPF()->current_user ) ) {
				if( ! is_callable( $template['callback_for_can_view_menu'] ) ) {
					if( wpforo_is_member_template( $template ) ) {
						$can_view = (bool) WPF()->current_userid;
					} else {
						$can_view = true;
					}
				} else {
					$can_view = (bool) call_user_func( $template['callback_for_can_view_menu'], $template, $default );
				}
			}
		}

		return $can_view;
	}

	function init_nav_menu() {
		$actives = [];

		$is_active                 = in_array( WPF()->current_object['template'], [ 'forum', 'topic', 'post' ] );
		$this->menu['wpforo-home'] = [
			'href'      => wpforo_home_url(),
			'label'     => wpforo_phrase( 'forums', false ),
			'is_active' => $is_active && ! in_array( true, $actives ),
		];
		$actives['wpforo-home']    = $is_active;

		$is_active                   = WPF()->current_object['template'] === 'recent' && wpfval( WPF()->GET, 'view' ) === 'prefix';
		$this->menu['wpforo-unread'] = [
			'href'      => wpforo_home_url( wpforo_settings_get_slug( 'recent' ) . '?view=unread' ),
			'label'     => wpforo_phrase( 'Unread Posts', false ),
			'is_active' => $is_active && ! in_array( true, $actives ),
		];
		$actives['wpforo-unread']    = $is_active;

		if( $templates = $this->get_templates() ) {
			$cot_is_member_template = wpforo_is_member_template();
			foreach( $templates as $key => $template ) {
				if( $this->can_view_nav_menu( $template ) ) {
					$is_active                = ( $key === WPF()->current_object['template'] && ( ! $cot_is_member_template || WPF()->current_object['user_is_same_current_user'] ) ) || ( $key === 'profile' && $cot_is_member_template && WPF()->current_object['user_is_same_current_user'] );
					$default_url              = wpforo_is_member_template( $template ) ? WPF()->member->get_profile_url( WPF()->current_user, $key ) : '';
					$shortcode                = $this->get_menu_shortcode( $template );
					$this->menu[ $shortcode ] = [
						'href'      => $this->get_url( $template, WPF()->current_user, $default_url ),
						'label'     => wpforo_phrase( $template['title'], false ),
						'is_active' => $is_active && ! in_array( true, $actives ),
					];
					if( $key !== 'profile' ) $actives[ $shortcode ] = $is_active;
				}
			}
		}

		$this->menu = apply_filters( 'wpforo_menu_array_filter', $this->menu );
	}

	function has_menu() {
		return has_nav_menu( 'wpforo-menu' );
	}

	function nav_menu() {
		if( has_nav_menu( 'wpforo-menu' ) ) {
			$defaults = [
				'theme_location'  => 'wpforo-menu',
				'menu'            => '',
				'container'       => '',
				'container_class' => '',
				'container_id'    => '',
				'menu_class'      => 'wpf-menu',
				'menu_id'         => 'wpf-menu',
				'echo'            => true,
				'fallback_cb'     => 'wp_page_menu',
				'before'          => '',
				'after'           => '',
				'link_before'     => '',
				'link_after'      => '',
				'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				'depth'           => 0,
				'walker'          => '',
			];
			wp_nav_menu( $defaults );
		}
	}

	private function init_current_theme( $theme ) {
        if( ! $this->theme_exists( $theme ) ) $theme = '2022';
		$this->theme        = $theme;
		$this->theme_info   = $this->find_theme( $this->theme );
		$this->template_dir = wpforo_fix_dir_sep( WPFORO_THEME_DIR . '/' . $this->theme );
		$this->template_url = WPFORO_THEME_URL . '/' . $this->theme;
	}

	function add_mce_external_plugins() {
		$plugin_array                              = [];
		$plugin_array['wpforo_pre_button']         = WPFORO_URL . '/assets/js/tinymce-pre.js';
		$plugin_array['wpforo_link_button']        = WPFORO_URL . '/assets/js/tinymce-link.js';
		$plugin_array['wpforo_spoiler_button']     = WPFORO_URL . '/assets/js/tinymce-spoiler.js';
		$plugin_array['wpforo_source_code_button'] = WPFORO_URL . '/assets/js/tinymce-code.js';
		$plugin_array['emoticons']                 = WPFORO_URL . '/assets/js/tinymce-emoji.js';

		return $plugin_array;
	}

	function filter_tinymce_plugins() {
		return [ 'hr', 'lists', 'textcolor', 'paste', 'wpautoresize', 'fullscreen' ];
	}

	function add_tinymce_translations( $mce_translation ) {
		$mce_translation['Insert link']            = __( 'Insert link' );
		$mce_translation['Link Text']              = __( 'Link Text' );
		$mce_translation['Open link in a new tab'] = __( 'Open link in a new tab' );
		$mce_translation['Insert Spoiler']         = __( 'Insert Spoiler', 'wpforo' );
		$mce_translation['Spoiler']                = __( 'Spoiler', 'wpforo' );

		return $mce_translation;
	}

	public function add_default_attach_input( $forumid = null ) {
		if( WPF()->perm->can_attach( $forumid ) ) { ?>
            <div class="wpf-default-attachment">
                <label for="wpf_file"><?php wpforo_phrase( 'Attach file:' ) ?> </label> <input id="wpf_file" type="file" name="attachfile"/>
                <p><?php wpforo_phrase( 'Maximum allowed file size is' );
					echo ' ' . wpforo_print_size( wpforo_setting( 'posting', 'max_upload_size' ) ); ?></p>
                <div class="wpf-clear"></div>
            </div>
			<?php
		}
	}

	public function topic_form_forums_selectbox( $forumid = null ) {
		if( wpforo_forum_list_need_add_topic_button( $forumid ) && WPF()->perm->forum_can( 'ct', $forumid ) ) {
			$this->portable_topic_form( null, $forumid );
		}
	}

	public function portable_topic_form( $forumid = null, $parent_forumid = null ) {
		WPF()->current_object['load_tinymce'] = true;
		$uniqid                               = uniqid();
		?>
        <div class="wpf-topic-form-extra-wrap">
            <div class="wpf-topic-forum-field" style="padding: 0 10px 15px; text-align: center; margin: 0 auto 10px;">
                <label for="wpf-choose-forum-<?php echo $uniqid ?>" class="wpf-choose-forum" style="font-size: 15px; padding-bottom: 5px;">
					<?php wpforo_phrase( 'Select Forum' ) ?>
                </label>
                <div class="wpf-topic-forum-wrap" style="width: 70%; margin: 0 auto; padding-bottom: 15px; min-width: 222px; border-bottom: 1px dashed #cccccc;">
                    <select id="wpf-choose-forum-<?php echo $uniqid ?>" class="wpf-topic-form-forumid" style="width: 100%; max-width: 100%;">
                        <option class="wpf-topic-form-no-selected-forum" value="0">-- <?php wpforo_phrase( 'No forum selected' ); ?> --</option>
						<?php WPF()->forum->tree( 'select_box', false, (array) $forumid, true, [], $parent_forumid ); ?>
                    </select>
                </div>
            </div>
            <div class="wpf-topic-form-ajax-wrap"><?php if( (int) $forumid ) $this->topic_form( $forumid ); ?></div>
        </div>
		<?php
	}

	/**
	 * @param array|int $forum
	 * @param array $values
	 */
	function topic_form( $forum = [], $values = [] ) {
		if( ! $forum ) $forum = WPF()->current_object['forum'];
		if( is_scalar( $forum ) ) $forum = WPF()->forum->get_forum( (int) $forum );
		if( ! $forum ) return;
		$forumid                        = intval( $forum['forumid'] );
		$layout                         = WPF()->forum->get_layout( $forum );
		$uniqid                         = uniqid();
		WPF()->form->current['varname'] = 'thread';
		?>
        <div class="wpf-form-wrapper wpf-topic-create">
            <form enctype="multipart/form-data" method="POST" class="wpforoeditor" data-bodyminlength="<?php echo wpforo_setting( 'posting', 'topic_body_min_length' ) ?>" data-bodymaxlength="<?php echo wpforo_setting( 'posting', 'topic_body_max_length' ) ?>">
				<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
                <input type="hidden" name="wpfaction" value="<?php echo ! $values ? 'topic_add' : 'topic_edit' ?>">
                <input type="hidden" name="thread[forumid]" value="<?php echo $forumid ?>">
				<?php if( $values ) : ?>
                    <input type="hidden" name="thread[topicid]" value="<?php echo wpforo_bigintval( wpfval( $values, 'topicid' ) ) ?>">
                    <input type="hidden" name="thread[postid]" value="<?php echo wpforo_bigintval( wpfval( $values, 'postid' ) ) ?>">
				<?php endif ?>

                <div class="wpf-topic-form-wrap">
					<?php wpforo_fields( WPF()->post->get_topic_fields( $forum, $values, ! WPF()->current_userid ) ) ?>
                    <div class="wpf-extra-fields">
						<?php do_action( 'wpforo_topic_form_extra_before', $forumid, $values ) ?>
                        <div class="wpf-main-fields">
							<?php if( ! $values ) : ?>
								<?php if( WPF()->perm->forum_can( 's', $forumid ) ) : ?>
                                    <input id="wpf_t_sticky_<?php echo $uniqid ?>" name="thread[type]" type="checkbox" value="1">&nbsp;&nbsp;
                                    <i class="fas fa-exclamation wpfsx"></i>&nbsp;&nbsp;
                                    <label for="wpf_t_sticky_<?php echo $uniqid ?>" style="padding-bottom:2px; cursor: pointer;"><?php wpforo_phrase( 'Set Topic Sticky' ); ?>&nbsp;</label>
                                    <span class="wpfbs">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
								<?php endif; ?>
								<?php if( WPF()->perm->forum_can( 'p', $forumid ) || WPF()->perm->forum_can( 'op', $forumid ) ) : ?>
                                    <input id="wpf_t_private_<?php echo $uniqid ?>" name="thread[private]" type="checkbox" value="1">&nbsp;&nbsp;
                                    <i class="fas fa-eye-slash wpfsx"></i>&nbsp;&nbsp;
                                    <label for="wpf_t_private_<?php echo $uniqid ?>" style="padding-bottom:2px; cursor: pointer;" title="<?php wpforo_phrase( 'Only Admins and Moderators can see your private topics.' ); ?>"><?php wpforo_phrase( 'Private Topic' ); ?>&nbsp;</label>
								<?php endif; ?>
							<?php endif ?>
							<?php do_action( 'wpforo_topic_buttons_hook', $forumid, $values ); ?>
                        </div>
						<?php do_action( 'wpforo_topic_form_extra_after', $forumid, $values ) ?>
                    </div>
					<?php if( wpforo_is_module_enabled( 'tags' ) && WPF()->perm->forum_can( 'tag', $forumid ) ) : ?>
                        <div class="wpf-topic-tags">
                            <p class="wpf-topic-tags-label">
                                <label for="wpf_tags_<?php echo $uniqid ?>">
                                    <i class="fas fa-tag"></i>
									<?php $layout == 3 ? wpforo_phrase( 'Question Tags' ) : wpforo_phrase( 'Topic Tags' ) ?>
                                    <span>(<?php wpforo_phrase( 'Separate tags using a comma' ) ?>)</span>
                                </label>
                            </p>
                            <input id="wpf_tags_<?php echo $uniqid ?>" class="wpf-tags" placeholder="<?php echo sprintf( wpforo_phrase( 'Start typing tags here (maximum %d tags are allowed)...', false ), wpforo_setting( 'tags', 'max_per_topic' ) ) ?>" name="thread[tags]" autocomplete="off" value="<?php echo wpfval( $values, 'tags' ) ?>" type="text">
                        </div>
					<?php endif; ?>
					<?php do_action( 'wpforo_editor_topic_submit_before', $forum, $values ) ?>
                    <div class="wpf-buttons-wrap">
						<?php if( $values ) : ?>
                            <input type="button" class="wpf-button wpf-button-secondary wpf-edit-post-cancel" value="<?php wpforo_phrase( 'Cancel' ) ?>">
                            <input type="submit" class="wpf-button" value="<?php wpforo_phrase( 'Save' ) ?>" title="Ctrl+Enter">
						<?php else : ?>
                            <input type="submit" class="wpf-button" value="<?php $layout == 3 ? wpforo_phrase( 'Ask a question' ) : wpforo_phrase( 'Add topic' ) ?>" title="Ctrl+Enter">
						<?php endif ?>
                    </div>
					<?php do_action( 'wpforo_editor_topic_submit_after', $forumid, $values ) ?>
                    <div class="wpf-clear"></div>
                </div>
            </form>
        </div>

		<?php
	}

	/**
	 * @param array|int $topic
	 * @param array $values post object edit values
	 */
	function reply_form( $topic = [], $values = [] ) {
		if( ! $topic ) $topic = WPF()->current_object['topic'];
		if( is_scalar( $topic ) ) $topic = wpforo_topic( $topic );
		$forum = WPF()->forum->get_forum( wpfval( $topic, 'forumid' ) );
		if( wpfval( $topic, 'closed' ) ) return;
		$layout = $topic['layout'] = WPF()->forum->get_layout( $forum );

		WPF()->form->current['varname'] = 'post';

		$style    = ( $layout === 3 && ! wpforo_setting( 'posting', 'qa_display_answer_editor' ) ) ? 'style="display: none;"' : '';
		$parentid = ( $layout === 3 && ! WPF()->topic->can_answer( $topic['topicid'] ) ) ? wpforo_bigintval( wpfval( $topic, 'first_postid' ) ) : 0;
		$v        = $values;
		if( ! trim( wpfval( $v, 'title' ) ) ) $v['title'] = $topic['title'];
		?>
        <div class="wpf-form-wrapper wpfel-<?php echo $layout ?>" <?php echo $style ?>>
			<?php if( ! $values ): ?>
                <p class="wpf-reply-form-title"><?php echo apply_filters( 'wpforo_reply_form_head', wpforo_phrase( 'Leave a reply', false ), $topic ) ?></p>
			<?php endif ?>
            <div class="wpf-post-create">
                <form enctype="multipart/form-data" method="POST" class="wpforoeditor <?php echo( ! $values ? 'wpforo-main-form' : '' ) ?>" data-bodyminlength="<?php echo wpforo_setting( 'posting', 'post_body_min_length' ) ?>" data-bodymaxlength="<?php echo wpforo_setting( 'posting', 'post_body_max_length' ) ?>">
					<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
                    <input type="hidden" name="wpfaction" value="<?php echo ! $values ? 'post_add' : 'post_edit' ?>">
                    <input type="hidden" class="wpf-form-forumid" name="post[forumid]" value="<?php echo intval( $topic['forumid'] ) ?>">
                    <input type="hidden" class="wpf-form-topicid" name="post[topicid]" value="<?php echo intval( $topic['topicid'] ) ?>">
                    <input type="hidden" class="wpf-form-post-parentid" name="post[parentid]" value="<?php echo $parentid ?>">
                    <input type="hidden" class="wpf-form-postid" name="post[postid]" value="<?php echo wpforo_bigintval( wpfval( $values, 'postid' ) ) ?>">
					<?php wpforo_fields( WPF()->post->get_post_fields( $forum, $topic, $v, ! WPF()->current_userid ) ); ?>
                    <div class="wpf-extra-fields">
						<?php do_action( 'wpforo_reply_form_extra_before', $topic, $values, $forum ) ?>
						<?php do_action( 'wpforo_reply_buttons_hook', $topic, $values, $forum ); ?>
						<?php do_action( 'wpforo_reply_form_extra_after', $topic, $values, $forum ) ?>
                    </div>
					<?php do_action( 'wpforo_editor_post_submit_before', $topic, $values, $forum ) ?>
                    <div class="wpf-buttons-wrap">
						<?php if( $values ) : ?>
                            <input type="button" class="wpf-button wpf-button-secondary wpf-edit-post-cancel" value="<?php wpforo_phrase( 'Cancel' ) ?>">
                            <input type="submit" class="wpf-button" value="<?php wpforo_phrase( 'Save' ) ?>" title="Ctrl+Enter">
						<?php else : ?>
                            <input type="submit" class="wpf-button" value="<?php $layout === 3 ? wpforo_phrase( 'Answer' ) : wpforo_phrase( 'Add Reply' ) ?>" title="Ctrl+Enter">
						<?php endif ?>
                    </div>
					<?php do_action( 'wpforo_editor_post_submit_after', $topic, $values, $forum ) ?>
                    <div class="wpf-clear"></div>
                </form>
            </div>
        </div>
		<?php
		if( ! $values && in_array( $layout, [ 3, 4 ], true ) ) {
			$is_rich_editor = ( $layout === 3 ? wpforo_setting( 'posting', 'qa_comments_rich_editor' ) : wpforo_setting( 'posting', 'threaded_reply_rich_editor' ) );
			if( $is_rich_editor ) {
				$this->post_form();
			} else {
				$this->post_form_simple();
			}
		}
	}

	public function post_form_simple() {
		if( wpfval( WPF()->current_object, 'topic', 'closed' ) ) return;
		$textareaid    = uniqid( 'wpf_post_body_' );
		$topicid       = wpforo_bigintval( wpfval( WPF()->current_object, 'topicid' ) );
        $topic         = WPF()->current_object['topic'];
        $forum         = WPF()->current_object['forum'];
		$layout        = WPF()->forum->get_layout();
		$bodyminlength = ( $layout == 3 ? wpforo_setting( 'posting', 'comment_body_min_length' ) : wpforo_setting( 'posting', 'post_body_min_length' ) );
		$bodymaxlength = ( $layout == 3 ? wpforo_setting( 'posting', 'comment_body_max_length' ) : wpforo_setting( 'posting', 'post_body_max_length' ) );
		$submit_ico    = ( $layout == 3 ? '<i class="far fa-comment"></i>' : '<i class="fas fa-reply"></i>' );
		$submit_value  = ( $layout == 3 ? wpforo_phrase( 'Add a comment', false ) : wpforo_phrase( 'Reply', false ) );
		$uniqid        = uniqid();
		?>
        <form enctype="multipart/form-data" method="POST" class="wpforo-post-form" style="display: none;" data-textareaid="<?php echo $textareaid ?>" data-bodyminlength="<?php echo $bodyminlength ?>" data-bodymaxlength="<?php echo $bodymaxlength ?>">
			<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
            <input type="hidden" name="wpfaction" value="post_add">
            <input type="hidden" class="wpf_post_forumid" name="post[forumid]" value="<?php echo wpforo_bigintval( wpfval( WPF()->current_object, 'forumid' ) ) ?>">
            <input type="hidden" class="wpf_post_topicid" name="post[topicid]" value="<?php echo wpforo_bigintval( wpfval( WPF()->current_object, 'topicid' ) ) ?>">
            <input type="hidden" class="wpf_post_parentid" name="post[parentid]" value="0">
			<?php if( ! is_user_logged_in() ): ?>
				<?php $guest = WPF()->member->get_guest_cookies(); ?>
                <div class="wpf-post-guest-fields" style="display: table;">
                    <div class="wpf-post-guest-name" style="display: table-row;">
                        <label for="wpf-name-<?php echo $uniqid ?>" style="padding-left:8px; display: table-cell;"> <?php wpforo_phrase( 'Author Name' ) ?> * </label>
                        <input id="wpf-name-<?php echo $uniqid ?>" class="wpf_user_name" style="display: table-cell;" type="text" placeholder="<?php esc_attr( wpforo_phrase( 'Your name' ) ) ?>" name="post[name]" value="<?php echo esc_attr( $guest['name'] ) ?>" required>
                    </div>
                    <div class="wpf-post-guest-email" style="display: table-row;">
                        <label for="wpf-email-<?php echo $uniqid ?>" style="padding-left:8px; display: table-cell;"> <?php wpforo_phrase( 'Author Email' ) ?> * </label>
                        <input id="wpf-email-<?php echo $uniqid ?>" class="wpf_user_email" style="display: table-cell;" type="text" placeholder="<?php esc_attr( wpforo_phrase( 'Your email' ) ) ?>" name="post[email]" value="<?php echo esc_attr( $guest['email'] ) ?>" required>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
			<?php endif; ?>
            <div class="wpf_post_form_textarea_wrap" data-textareatype="simple_editor">
                <label for="<?php echo $textareaid ?>"></label><textarea id="<?php echo $textareaid ?>" required class="wpf_post_body" name="post[body]" placeholder="<?php wpforo_phrase( 'Write here . . .' ) ?>"></textarea>
            </div>
            <div class="wpf-extra-fields">
				<?php do_action( 'wpforo_portable_form_extra_fields_before' ) ?>
				<?php do_action( 'wpforo_portable_form_buttons_hook' ); ?>
				<?php do_action( 'wpforo_portable_form_extra_fields_after' ) ?>
            </div>
			<?php do_action( 'wpforo_portable_editor_post_submit_before', $topic, [], $forum ) ?>
            <button type="submit" name="post[save]" class="wpf-button" title="Ctrl+Enter">
				<?php echo $submit_ico ?>
				<?php echo $submit_value ?>
            </button>
            <button type="reset" class="wpf-button-secondary wpf-button-close-form">
                <i class="fas fa-times"></i>
                <span class="wpf-button-text"><?php wpforo_phrase( 'Cancel' ) ?></span>
            </button>
            <div class="wpf-clear"></div>
			<?php do_action( 'wpforo_portable_editor_post_submit_after' ) ?>
        </form>
		<?php
	}

	public function post_form() {
		if( wpfval( WPF()->current_object, 'topic', 'closed' ) ) return;
		$textareaid    = uniqid( 'wpf_post_body_' );
		$topicid       = wpforo_bigintval( wpfval( WPF()->current_object, 'topicid' ) );
        $topic         = WPF()->current_object['topic'];
        $forum         = WPF()->current_object['forum'];
		$layout        = WPF()->forum->get_layout();
		$bodyminlength = ( $layout == 3 ? wpforo_setting( 'posting', 'comment_body_min_length' ) : wpforo_setting( 'posting', 'post_body_min_length' ) );
		$bodymaxlength = ( $layout == 3 ? wpforo_setting( 'posting', 'comment_body_max_length' ) : wpforo_setting( 'posting', 'post_body_max_length' ) );
		$submit_ico    = ( $layout == 3 ? '<i class="far fa-comment"></i>' : '<i class="fas fa-reply"></i>' );
		$submit_value  = ( $layout == 3 ? wpforo_phrase( 'Add a comment', false ) : wpforo_phrase( 'Reply', false ) );
		$uniqid        = uniqid();
		?>
        <form enctype="multipart/form-data" method="POST" class="wpforo-post-form" style="display: none;" data-textareaid="<?php echo $textareaid ?>" data-bodyminlength="<?php echo $bodyminlength ?>" data-bodymaxlength="<?php echo $bodymaxlength ?>">
			<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
            <input type="hidden" name="wpfaction" value="post_add">
            <input type="hidden" class="wpf_post_forumid" name="post[forumid]" value="<?php echo wpforo_bigintval( wpfval( WPF()->current_object, 'forumid' ) ) ?>">
            <input type="hidden" class="wpf_post_topicid" name="post[topicid]" value="<?php echo $topicid ?>">
            <input type="hidden" class="wpf_post_parentid" name="post[parentid]" value="0">
			<?php if( ! is_user_logged_in() ): ?>
				<?php $guest = WPF()->member->get_guest_cookies(); ?>
                <div class="wpf-post-guest-fields" style="display: table;">
                    <div class="wpf-post-guest-name" style="display: table-row;">
                        <label for="wpf-name-<?php echo $uniqid ?>" style="padding-left:8px; display: table-cell;"> <?php wpforo_phrase( 'Author Name' ) ?> * </label>
                        <input id="wpf-name-<?php echo $uniqid ?>" class="wpf_user_name" style="display: table-cell;" type="text" placeholder="<?php esc_attr( wpforo_phrase( 'Your name' ) ) ?>" name="post[name]" value="<?php echo esc_attr( $guest['name'] ) ?>" required>
                    </div>
                    <div class="wpf-post-guest-email" style="display: table-row;">
                        <label for="wpf-email-<?php echo $uniqid ?>" style="padding-left:8px; display: table-cell;"> <?php wpforo_phrase( 'Author Email' ) ?> * </label>
                        <input id="wpf-email-<?php echo $uniqid ?>" class="wpf_user_email" style="display: table-cell;" type="text" placeholder="<?php esc_attr( wpforo_phrase( 'Your email' ) ) ?>" name="post[email]" value="<?php echo esc_attr( $guest['email'] ) ?>" required>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
			<?php endif; ?>
            <div class="wpf_post_form_textarea_wrap wpf-post-create" data-textareatype="rich_editor">
                <textarea id="<?php echo $textareaid ?>" class="wpf_post_body" name="post[body]"></textarea>
            </div>
            <div class="wpf-extra-fields">
				<?php do_action( 'wpforo_portable_form_extra_fields_before' ) ?>
				<?php do_action( 'wpforo_portable_form_buttons_hook' ); ?>
				<?php do_action( 'wpforo_portable_form_extra_fields_after' ) ?>
            </div>
			<?php do_action( 'wpforo_portable_editor_post_submit_before', $topic, [], $forum ) ?>
            <button type="submit" name="post[save]" class="wpf-button" title="Ctrl+Enter">
				<?php echo $submit_ico ?>
				<?php echo $submit_value ?>
            </button>
            <button type="reset" class="wpf-button-secondary wpf-button-close-form">
                <i class="fas fa-times"></i>
                <span class="wpf-button-text"><?php wpforo_phrase( 'Cancel' ) ?></span>
            </button>
            <div class="wpf-clear"></div>
			<?php do_action( 'wpforo_portable_editor_post_submit_after' ) ?>
            <div class="wpf-clear"></div>
        </form>
		<?php
	}

	public function topic_moderation_tabs( $tabs = [] ) {
		$tabs = apply_filters( 'wpforo_topic_moderation_tabs', $tabs );
		if( ! $tabs ) return;
		$default_tab = [ 'title' => '', 'id' => '', 'class' => '', 'icon' => '', 'active' => false ];
		?>
        <div class="wpf-tool-tabs">
			<?php foreach( $tabs as $tab ) : $tab = wpforo_parse_args( $tab, $default_tab ); ?>
                <div id="<?php echo esc_attr( $tab['id'] ) ?>" class="wpf-tool-tab <?php echo $tab['class'];
				echo( $tab['active'] ? ' wpf-tt-active' : '' ); ?>">
                    <i class="<?php echo $tab['icon'] ?>"></i>&nbsp;
					<?php echo $tab['title'] ?>
                </div>
			<?php endforeach; ?>
        </div>
        <div id="wpf_tool_tab_content_wrap">
            <i class="fas fa-spinner fa-spin wpf-icon-spinner"></i>
        </div>
		<?php
	}

	private function topic_merge_form() {
		?>
        <div class="wpf-tool wpf-tool-merge">
            <h3><i class="fas fa-code-branch"></i></h3>
            <div class="wpf-cl"></div>
            <form method="post" enctype="multipart/form-data" action="">
				<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
                <input type="hidden" name="wpfaction" value="topic_merge">
                <ul>
                    <li class="wpf-target-topic">
                        <label for="target-topic" class="wpf-input-label"><?php wpforo_phrase( 'Target Topic URL' ) ?> <sup>*</sup></label>
                        <div class="wpf-tool-desc">
							<?php wpforo_phrase( 'Please copy the target topic URL from browser address bar and paste in the field below.' ) ?><br/>
                        </div>
                        <input id="target-topic" type="text" required name="wpforo[target_topic_url]" value="" placeholder="https://example.com/community/main-forum/target-topic/"/>
                        <div class="wpf-tool-desc" style="margin: 15px 1px 0 1px;">
							<?php wpforo_phrase( 'All posts will be merged and displayed (ordered) in target topic according to posts dates. If you want to append merged posts to the end of the target topic you should allow to update posts dates to current date by check the option below.' ) ?>
                        </div>
                    </li>
                    <li class="wpf-update-date-and-append"><input id="update-date-and-append" type="checkbox" name="wpforo[update_date_and_append]" value="1"/> <label for="update-date-and-append"><?php wpforo_phrase( 'Update post dates (current date) to allow append posts to the end of the target topic.' ) ?></label></li>
                    <li class="wpf-update-to-target-title"><input id="update-to-target-title" type="checkbox" name="wpforo[to_target_title]" value="1" checked/> <label for="update-to-target-title"><?php wpforo_phrase( 'Update post titles with target topic title.' ) ?></label></li>
                    <li><i class="fas fa-info-circle wpfcl-5" style="font-size: 16px;"></i> &nbsp;<?php wpforo_phrase( 'Topics once merged cannot be unmerged. This topic URL will no longer be available.' ) ?></li>
                    <li class="wpf-submit"><input type="submit" name="wpforo[topic_merge]" value="<?php wpforo_phrase( 'Merge' ) ?>"></li>
                </ul>
            </form>
        </div>
		<?php
	}

	private function reply_move_form() {
		if( ! $posts = WPF()->post->get_posts( [ 'topicid' => WPF()->current_object['topicid'] ] ) ) return;
		if( count( $posts ) < 2 ) return;
		?>
        <div class="wpf-tool wpf-tool-split">
            <h3><i class="far fa-share-square"></i></h3>
            <div class="wpf-cl"></div>
            <form id="wpforo_split_form" method="post" enctype="multipart/form-data" action="">
				<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
                <input type="hidden" name="wpfaction" value="topic_split">
                <ul>
                    <li id="wpf_split_target_url" class="wpf-target-topic">
                        <label for="spl-target-url" class="wpf-input-label"><?php wpforo_phrase( 'Target Topic URL' ) ?> <sup>*</sup></label>
                        <div class="wpf-tool-desc">
							<?php wpforo_phrase( 'Please copy the target topic URL from browser address bar and paste in the field below.' ) ?><br/>
                        </div>
                        <input id="spl-target-url" type="text" name="wpforo[target_topic_url]" required placeholder="https://example.com/community/main-forum/target-topic/"/>
                        <div class="wpf-tool-desc" style="margin: 15px 1px 0 1px;">
							<?php wpforo_phrase( 'All posts will be merged and displayed (ordered) in target topic according to posts dates. If you want to append merged posts to the end of the target topic you should allow to update posts dates to current date by check the option below.' ) ?>
                        </div>
                    </li>
                    <li id="wpf_split_append">
                        <input id="spl-update-date-and-append" type="checkbox" name="wpforo[update_date_and_append]" value="1"/>
                        <label for="spl-update-date-and-append"><?php wpforo_phrase( 'Update post dates (current date) to allow append posts to the end of the target topic.' ) ?></label>
                    </li>
                    <li>
                        <input id="split-update-to-target-title" type="checkbox" name="wpforo[to_target_title]" value="1" checked/>
                        <label for="split-update-to-target-title"><?php wpforo_phrase( 'Update post titles with target topic title.' ) ?></label>
                    </li>
                    <li>
                        <label class="wpf-input-label"><?php wpforo_phrase( 'Select Posts to Split' ) ?> <sup>*</sup></label>
                        <div class="wpf-split-posts">
                            <ul>
								<?php foreach( $posts as $post ) :
									if( $title = wpforo_text( $post['body'], 200, false ) ) {
										$value = wpforo_text( $post['body'], 100, false );
									} else {
										$title = wpforo_text( $post['title'], 200, false );
										$value = wpforo_text( $post['title'], 100, false );
									}
									?>
                                    <li>
                                        <label title="<?php echo $title ?>">
                                            <input type="checkbox" name="wpforo[posts][]" value="<?php echo $post['postid'] ?>">
											<?php echo $value ?>
                                        </label>
                                    </li>
								<?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                    <li style="padding-top: 10px;"><i class="fas fa-info-circle wpfcl-5" style="font-size: 16px;"></i> &nbsp;<?php wpforo_phrase( 'Topic once split cannot be unsplit. The first post of new topic becomes the earliest reply.' ) ?></li>
                    <li class="wpf-submit"><input type="submit" name="wpforo[topic_split]" value="<?php wpforo_phrase( 'Move' ) ?>"></li>
                </ul>
            </form>
        </div>
		<?php
	}

	private function topic_split_form() {
		if( ! $posts = WPF()->post->get_posts( [ 'topicid' => WPF()->current_object['topicid'] ] ) ) return;
		if( count( $posts ) < 2 ) return;
		?>
        <div class="wpf-tool wpf-tool-split">
            <h3><i class="fas fa-cut"></i></h3>
            <div class="wpf-cl"></div>
            <form id="wpforo_split_form" method="post" enctype="multipart/form-data" action="">
				<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
                <input type="hidden" name="wpfaction" value="topic_split">
                <ul>
                    <li>
                        <input id="wpf_split_create_new" type="checkbox" name="wpforo[create_new]" value="1" checked>
                        <label for="wpf_split_create_new" class="wpf-input-label" style="display: inline-block;"><?php wpforo_phrase( 'Create New Topic' ) ?></label>
                        <div class="wpf-tool-desc">
							<?php wpforo_phrase( 'Create new topic with split posts. The first post of new topic becomes the earliest reply.' ) ?><br/>
                        </div>
                    </li>
                    <li id="wpf_split_target_url" class="wpf-target-topic" style="display: none;">
                        <label for="spl-target-url" class="wpf-input-label"><?php wpforo_phrase( 'Target Topic URL' ) ?> <sup>*</sup></label>
                        <div class="wpf-tool-desc">
							<?php wpforo_phrase( 'Please copy the target topic URL from browser address bar and paste in the field below.' ) ?><br/>
                        </div>
                        <input id="spl-target-url" type="text" name="wpforo[target_topic_url]" required disabled placeholder="https://example.com/community/main-forum/target-topic/">
                        <div class="wpf-tool-desc" style="margin: 15px 1px 0 1px;">
							<?php wpforo_phrase( 'All posts will be merged and displayed (ordered) in target topic according to posts dates. If you want to append merged posts to the end of the target topic you should allow to update posts dates to current date by check the option below.' ) ?>
                        </div>
                    </li>
                    <li id="wpf_split_append" style="display: none;">
                        <input id="spl-update-date-and-append" type="checkbox" name="wpforo[update_date_and_append]" value="1"/>
                        <label for="spl-update-date-and-append"><?php wpforo_phrase( 'Update post dates (current date) to allow append posts to the end of the target topic.' ) ?></label>
                    </li>
                    <li id="wpf_split_new_title">
                        <label for="spl-topic-title" class="wpf-input-label"><?php wpforo_phrase( 'New Topic Title' ) ?> <sup>*</sup></label>
                        <input id="spl-topic-title" type="text" name="wpforo[new_topic_title]" required placeholder="<?php wpforo_phrase( 'Topic Title' ) ?>"/>
                    </li>
                    <li id="wpf_split_forumid"><label for="spl-topic-forum" class="wpf-input-label"><?php wpforo_phrase( 'New Topic Forum' ) ?> <sup>*</sup></label>
                        <select id="spl-topic-forum" name="wpforo[new_topic_forumid]"><?php WPF()->forum->tree( 'select_box', false, WPF()->current_object['forumid'] ) ?></select></li>
                    <li>
                        <input id="split-update-to-target-title" type="checkbox" name="wpforo[to_target_title]" value="1" checked/>
                        <label for="split-update-to-target-title"><?php wpforo_phrase( 'Update post titles with target topic title.' ) ?></label>
                    </li>
                    <li>
                        <label class="wpf-input-label"><?php wpforo_phrase( 'Select Posts to Split' ) ?> <sup>*</sup></label>
                        <div class="wpf-split-posts">
                            <ul>
								<?php foreach( $posts as $post ) :
									if( $title = wpforo_text( $post['body'], 200, false ) ) {
										$value = wpforo_text( $post['body'], 100, false );
									} else {
										$title = wpforo_text( $post['title'], 200, false );
										$value = wpforo_text( $post['title'], 100, false );
									}
									?>
                                    <li>
                                        <label title="<?php echo $title ?>">
                                            <input type="checkbox" name="wpforo[posts][]" value="<?php echo $post['postid'] ?>">
											<?php echo $value ?>
                                        </label>
                                    </li>
								<?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                    <li style="padding-top: 10px;"><i class="fas fa-info-circle wpfcl-5" style="font-size: 16px;"></i> &nbsp;<?php wpforo_phrase( 'Topic once split cannot be unsplit. The first post of new topic becomes the earliest reply.' ) ?></li>
                    <li class="wpf-submit"><input type="submit" name="wpforo[topic_split]" value="<?php wpforo_phrase( 'Split' ) ?>"></li>
                </ul>
            </form>
        </div>
		<?php
	}

	public function topic_move_form( $topicid = null ) {
		if( ! $topicid && empty( WPF()->current_object['topicid'] ) ) return;
		if( ! $topicid ) $topicid = WPF()->current_object['topicid'];
		?>
        <div class="wpf-tool wpf-tool-split">
            <h3><i class="far fa-share-square"></i></h3>
            <div class="wpf-cl"></div>
            <form id="wpf_topicmoveform" method="POST" enctype="multipart/form-data" action="">
				<?php wp_nonce_field( 'wpforo_verify_form', '_wpfnonce' ); ?>
                <input type="hidden" name="wpfaction" value="topic_move">
                <input type="hidden" name="topic_move[topicid]" value="<?php echo intval( $topicid ) ?>"/>
                <ul>
                    <li>
                        <div id="wpf_movedialog" title="<?php esc_attr( wpforo_phrase( 'Move topic' ) ) ?>">
                            <div class="form-field">
                                <label for="parent"></label>
                                <label for="spl-target-url" class="wpf-input-label" style="padding-bottom: 7px;"><?php wpforo_phrase( 'Choose Target Forum' ) ?><sup>*</sup></label>
                                <label>
                                    <select name="topic_move[forumid]" class="postform">
										<?php WPF()->forum->tree( 'select_box', false ); ?>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </li>
                    <li style="padding-top: 20px;"><i class="fas fa-info-circle wpfcl-5" style="font-size: 16px;"></i>
                        &nbsp;<?php wpforo_phrase( 'This action changes topic URL. Once the topic is moved to other forum the old URL of this topic will no longer be available.' ) ?>
                    </li>
                    <li class="wpf-submit"><input type="submit" value="<?php wpforo_phrase( 'Move' ) ?>"/></li>
                </ul>
            </form>
        </div>
		<?php
	}

	public function post_search_form( $values ) {
		$type          = wpfval( $values, 'type' );
		$is_tag        = $type === 'tag';
		$needle        = wpfval( $values, 'needle' );
		$date_period   = (int) wpfval( $values, 'date_period' );
		$order         = strtolower( wpfval( $values, 'order' ) );
		$orderby       = (string) wpfval( $values, 'orderby' );
		$forumids      = (array) wpfval( $_GET, 'wpff' );
		$search_fields = WPF()->post->get_search_fields( (array) wpfval( $_GET, 'data' ) );
		?>
        <form action="<?php echo wpforo_home_url() ?>" method="get">
			<?php wpforo_make_hidden_fields_from_url( wpforo_home_url() ) ?>
            <div class="wpforo-table">
                <div class="wpforo-tr">
                    <div class="wpforo-td wpfw-60 wpfltd">
                        <span class="wpf-search-label wpfcl-1">&nbsp;<?php wpforo_phrase( 'Search Phrase' ) ?>:</span><br>
                        <label>
                            <input type="text" name="wpfs" class="wpfs wpfw-90" value="<?php echo esc_attr( $needle ) ?>">
                        </label>
                    </div>
                    <div class="wpforo-td wpfw-40 wpfrtd">
                        <span class="wpf-search-label wpfcl-1">&nbsp;<?php wpforo_phrase( 'Search Type' ) ?>:</span><br>
                        <label>
                            <select name="wpfin" class="wpfw-90 wpfin">
                                <option value="entire-posts" <?php echo $type === 'entire-posts' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Search Entire Posts' ) ?></option>
                                <option value="titles-only" <?php echo $type === 'titles-only' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Search Titles Only' ) ?></option>
                                <option value="tag" <?php echo $type === 'tag' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Find Topics by Tags' ) ?></option>
                                <option value="user-posts" <?php echo $type === 'user-posts' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Find Posts by User' ) ?></option>
                                <option value="user-topics" <?php echo $type === 'user-topics' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Find Topics Started by User' ) ?></option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
            <div class="wpforo-table wpf-toggle-wrap wpf-search-advanced-wrap" <?php if( $is_tag ) echo 'style="display: none;"'; ?>>
                <div class="wpforo-tr">
                    <div class="wpforo-td wpfw-100 wpfrtd wpf-toggle">
                        <span class="wpf-toggle-button wpf-toggle-advanced">
                            <i class="fas fa-chevron-down wpf-ico"></i><?php wpforo_phrase( 'Advanced search options' ) ?>
                        </span>
                    </div>
                </div>
                <div class="wpforo-tr wpf-cfields">
                    <div class="wpforo-td wpfw-100 wpfltd wpf-last">
                        <div class="wpf-search-advanced-fields">
                            <div class="wpforo-table">
                                <div class="wpforo-tr">
                                    <div class="wpforo-td wpfw-60 wpfltd">
                                        <span class="wpf-search-label wpfcl-1">&nbsp;<?php wpforo_phrase( 'Search in Forums' ) ?>:</span><br>
                                        <label>
                                            <select name="wpff[]" class="wpfw-90 wpff" multiple="multiple">
												<?php WPF()->forum->tree( 'select_box', false, $forumids ) ?>
                                            </select>
                                        </label>
                                    </div>
                                    <div class="wpforo-td wpfw-40 wpfrtd">
                                        <span class="wpf-search-label wpfcl-1">&nbsp;<?php wpforo_phrase( 'Search in date period' ) ?>:</span><br>
                                        <label>
                                            <select name="wpfd" class="wpfw-90 wpfd">
                                                <option value="0" <?php echo $date_period === 0 ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Any Date' ) ?></option>
                                                <option value="1" <?php echo $date_period === 1 ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Last 24 hours' ) ?></option>
                                                <option value="7" <?php echo $date_period === 7 ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Last Week' ) ?></option>
                                                <option value="30" <?php echo $date_period === 30 ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Last Month' ) ?></option>
                                                <option value="90" <?php echo $date_period === 90 ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Last 3 Months' ) ?></option>
                                                <option value="180" <?php echo $date_period === 180 ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Last 6 Months' ) ?></option>
                                                <option value="365" <?php echo $date_period === 365 ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Last Year ago' ) ?></option>
                                            </select>
                                        </label>
                                        <br>
                                        <span class="wpf-search-label wpfcl-1">&nbsp;<?php wpforo_phrase( 'Sort Search Results by' ) ?>:</span><br>
                                        <label>
                                            <select class="wpfw-90 wpfob" name="wpfob">
                                                <option value="relevancy" <?php echo $orderby === 'relevancy' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Relevancy' ) ?></option>
                                                <option value="date" <?php echo $orderby === 'date' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Date' ) ?></option>
                                                <option value="user" <?php echo $orderby === 'user' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'User' ) ?></option>
                                                <option value="forum" <?php echo $orderby === 'forum' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Forum' ) ?></option>
                                            </select>
                                        </label><br>
                                        <label>
                                            <select class="wpfw-90 wpfo" name="wpfo">
                                                <option value="desc" <?php echo $order === 'desc' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Descending order' ) ?></option>
                                                <option value="asc" <?php echo $order === 'asc' ? 'selected' : '' ?>>&nbsp;<?php wpforo_phrase( 'Ascending order' ) ?></option>
                                            </select>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			<?php if( $search_fields && ! $is_tag ) : ?>
                <div class="wpforo-table wpf-toggle-wrap wpf-search-custom-wrap">
                    <div class="wpforo-tr">
                        <div class="wpforo-td wpfw-100 wpfrtd wpf-toggle">
                            <span class="wpf-toggle-button wpf-toggle-custom">
                                <i class="fas fa-chevron-down wpf-ico"></i><?php wpforo_phrase( 'Filter by custom fields' ) ?>
                            </span>
                        </div>
                    </div>
                    <div class="wpforo-tr wpf-cfields">
                        <div class="wpforo-td wpfw-100 wpfltd wpf-last">
                            <div class="wpf-search-custom-fields">
								<?php wpforo_fields( $search_fields ) ?>
                            </div>
                        </div>
                    </div>
                </div>
			<?php endif ?>
            <div class="wpforo-table">
                <div class="wpforo-tr">
                    <div class="wpforo-td wpfw-100 wpfrtd wpf-last">
                        <input type="submit" class="wpf-search" value="<?php wpforo_phrase( 'Search' ) ?>">
                    </div>
                </div>
            </div>
        </form>
		<?php
	}

	public function member_search_form() {
		?>
        <form action="<?php echo wpforo_members_url() ?>" method="get">
			<?php wpforo_make_hidden_fields_from_url( wpforo_home_url() ) ?>
			<?php wpforo_fields( wpforo_search_fields() ); ?>
            <div class="wpf-tr">
                <div class="wpf-td wpfw-1">
                    <div class="wpf-field wpf-field-type-submit">
                        <a href="<?php echo wpforo_members_url() ?>">
                            <input type="button" class="wpf-member-search wpfbg-7 wpfcl-1" value="<?php wpforo_phrase( 'Reset Search' ) ?>">
                        </a>
						<?php if( wpforo_setting( 'members', 'search_type' ) === 'filter' ): ?>
                            <a href="<?php echo wpforo_members_url() ?>">
                                <input type="reset" class="wpf-member-search wpfbg-7 wpfcl-1" value="<?php wpforo_phrase( 'Reset Fields' ) ?>">
                            </a>
						<?php endif; ?>
                        <input type="submit" class="wpf-member-search" name="_wpfms" value="<?php wpforo_phrase( 'Search' ) ?>"/>
                    </div>
                    <div class="wpf-field-cl"></div>
                </div>
                <div class="wpf-cl"></div>
            </div>
        </form>
		<?php
	}

	function pagenavi( $paged = null, $items_count = null, $items_per_page = null, $permalink = true, $class = '' ) {
		if( is_null( $paged ) ) $paged = WPF()->current_object['paged'];
		if( is_null( $items_count ) ) $items_count = WPF()->current_object['items_count'];
		if( is_null( $items_per_page ) ) $items_per_page = WPF()->current_object['items_per_page'];

		if( $items_count <= $items_per_page ) return;

		$pages_count = ceil( $items_count / $items_per_page );

		$current_url           = ( WPF()->current_url ? WPF()->current_url : wpforo_get_request_uri() );
		$sanitized_current_url = trim( WPF()->strip_url_paged_var( $current_url ), '/' );

		if( $permalink ) {
			$rtrimed_url     = '';
			$url_append_vars = '';
			if( preg_match( '#^(.+?)(/?[\?\&].*)?$#isu', $sanitized_current_url, $match ) ) {
				if( wpfval( $match, 1 ) ) $rtrimed_url = rtrim( $match[1], '/\\' );
				if( wpfval( $match, 2 ) ) $url_append_vars = '?' . trim( $match[2], '?&/\\' );
			}
			$url = str_replace( '%', '%%', $rtrimed_url . '/' . wpforo_settings_get_slug( 'paged' ) ) . '/%1$d/' . str_replace( '%', '%%', $url_append_vars );
		} else {
			$url = str_replace( '%', '%%', $sanitized_current_url ) . '&wpfpaged=%1$d';
		}
		?>

        <div class="wpf-navi <?php echo esc_attr( $class ) ?>">
            <div class="wpf-navi-wrap">
                <span class="wpf-page-info">
                    <?php wpforo_phrase( 'Page' ) ?> <?php echo intval( $paged ) ?> / <?php echo intval( $pages_count ) ?>
                </span>
				<?php if( $paged - 1 > 0 ): $prev_url = ( ( $paged - 1 ) == 1 ? $sanitized_current_url : sprintf( $url, $paged - 1 ) ); ?>
                    <a href="<?php echo esc_url( WPF()->user_trailingslashit( $prev_url ) ) ?>" class="wpf-prev-button" rel="prev">
                        <i class="fas fa-chevron-left fa-sx"></i> <?php wpforo_phrase( 'prev' ) ?>
                    </a>
				<?php endif ?>
                <select class="wpf-navi-dropdown" onchange="if (this.value) window.location.assign(this.value)" title="<?php esc_attr( wpforo_phrase( 'Select Page' ) ) ?>">
                    <option value="<?php echo esc_url( WPF()->user_trailingslashit( $sanitized_current_url ) ) ?>" <?php wpfo_check( $paged, 1, 'selected' ) ?>>1</option>
					<?php for( $i = 2; $i <= $pages_count; $i ++ ) : ?>
                        <option value="<?php echo esc_url( WPF()->user_trailingslashit( sprintf( $url, $i ) ) ) ?>" <?php wpfo_check( $paged, $i, 'selected' ) ?>>
							<?php echo $i ?>
                        </option>
					<?php endfor; ?>
                </select>
				<?php if( $paged + 1 <= $pages_count ): ?>
                    <a href="<?php echo esc_url( WPF()->user_trailingslashit( sprintf( $url, $paged + 1 ) ) ) ?>" class="wpf-next-button" rel="next">
						<?php wpforo_phrase( 'next' ) ?> <i class="fas fa-chevron-right fa-sx"></i>
                    </a>
				<?php endif ?>
            </div>
        </div>

		<?php
	}

	/**
	 * Get actions buttons
	 *
	 * @param array $buttons names function will return buttons by this array
	 *
	 * @param array $forum required
	 *
	 * @param array $topic required
	 *
	 * @param array $post required
	 *
	 * @param bool $echo
	 *
	 * $buttons = array( 'reply', 'answer', 'comment', 'quote', 'like', 'report', 'sticky', 'close', 'edit', 'delete', 'link' );
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	function buttons( $buttons, $forum = [], $topic = [], $post = [], $echo = true ) {
		$buttons  = (array) $buttons;
		$is_topic = (bool) wpfval( $post, 'is_first_post' );

		$button_html = [];
		$login       = is_user_logged_in();

		$forumid = ( isset( $forum['forumid'] ) ) ? $forum['forumid'] : 0;
		$topicid = ( isset( $topic['topicid'] ) ) ? $topic['topicid'] : 0;
		$postid  = ( isset( $post['postid'] ) ) ? $post['postid'] : 0;

		if( $post ) {
			$userid   = wpforo_bigintval( wpfval( $post, 'userid' ) );
			$is_owner = (int) ( WPF()->current_userid && wpforo_is_owner( $userid ) );
			$mention  = (string) ( ! $is_owner && wpforo_setting( 'profiles', 'mention_nicknames' ) ? wpforo_member( $post, 'user_nicename' ) : '' );
		} else {
			$userid   = 0;
			$is_owner = 0;
			$mention  = '';
		}

		$is_sticky  = ( isset( $topic['type'] ) ) ? $topic['type'] : 0;
		$is_closed  = ( isset( $topic['closed'] ) ) ? $topic['closed'] : 0;
		$is_private = ( isset( $topic['private'] ) ) ? $topic['private'] : 0;
		$is_solved  = ( isset( $topic['solved'] ) ) ? $topic['solved'] : 0;
		$is_approve = ( isset( $post['status'] ) ) ? $post['status'] : 0;

		foreach( $buttons as $button ) {
			switch( $button ) {
				case 'reply':
					if( $is_closed || $is_approve || ( $is_topic && ! wpforo_setting( 'topics', 'layout_threaded_first_post_reply' ) ) ) break;
					if( WPF()->perm->forum_can( 'cr', $forumid ) || ( wpforo_is_owner( wpforo_bigintval( wpfval( $topic, 'userid' ) ), (string) wpfval( $topic, 'email' ) ) && WPF()->perm->forum_can( 'ocr', $forumid ) ) ) {
						$layout        = WPF()->forum->get_layout( $forumid );
						$layout_class  = 'wpforo_layout_' . $layout;
						$button_html[] = '<span id="parentpostid' . wpforo_bigintval( $postid ) . '" class="wpforo-reply wpf-action ' . $layout_class . '" data-mention="' . esc_attr( $mention ) . '"><i class="fas fa-reply fa-rotate-180"></i><span class="wpf-button-text">' . wpforo_phrase( 'Reply', false ) . '</span></span>';
					} else {
						$button_html[] = ( $login ) ? '' : '<span class="wpf-action not_reg_user"><i class="fas fa-reply fa-rotate-180"></i><span class="wpf-button-text">' . wpforo_phrase( 'Reply', false ) . '</span></span>';
					}
				break;
				case 'answer':
					if( $is_closed || $is_approve ) break;
					if( WPF()->perm->forum_can( 'cr', $forumid ) || ( wpforo_is_owner( wpforo_bigintval( wpfval( $topic, 'userid' ) ), (string) wpfval( $topic, 'email' ) ) && WPF()->perm->forum_can( 'ocr', $forumid ) ) ) {
						if( WPF()->topic->can_answer( $topicid ) ) {
							$button_html[] = '<span class="wpforo-answer wpf-button" data-phrase="' . esc_attr( wpforo_phrase( 'Answer', false ) ) . '"  data-mention="' . esc_attr( $mention ) . '"><i class="fas fa-pencil-alt"></i><span class="wpf-button-text">' . wpforo_phrase( 'Answer', false ) . '</span></span>';
						}
					} else {
						$button_html[] = ( $login ) ? '' : '<span class="wpf-button not_reg_user" data-phrase="' . esc_attr( wpforo_phrase( 'Answer', false ) ) . '"><i class="fas fa-pencil-alt"></i><span class="wpf-button-text">' . wpforo_phrase( 'Answer', false ) . '</span></span>';
					}
				break;
				case 'comment':
					if( $is_closed || $is_approve || ( $is_topic && ! wpforo_setting( 'topics', 'layout_qa_first_post_reply' ) ) ) break;
					$title = wpforo_phrase( 'Use comments to ask for more information or suggest improvements. Avoid answering questions in comments.', false );
					if( WPF()->perm->forum_can( 'cr', $forumid ) || ( wpforo_is_owner( wpforo_bigintval( wpfval( $topic, 'userid' ) ), (string) wpfval( $topic, 'email' ) ) && WPF()->perm->forum_can( 'ocr', $forumid ) ) ) {
						$button_html[] = '<span id="parentpostid' . wpforo_bigintval( $postid ) . '" class="wpforo-qa-comment wpf-button" title="' . esc_attr( $title ) . '" data-phrase="' . esc_attr( wpforo_phrase( 'Add a comment', false ) ) . '"  data-mention="' . esc_attr( $mention ) . '"><i class="far fa-comment"></i><span class="wpf-button-text">' . wpforo_phrase( 'Add a comment', false ) . '</span></span>';
					} else {
						$button_html[] = ( $login ) ? '' : '<span class="not_reg_user wpf-button" title="' . esc_attr( $title ) . '" data-phrase="' . esc_attr( wpforo_phrase( 'Add a comment', false ) ) . '"><i class="far fa-comment"></i><span class="wpf-button-text">' . wpforo_phrase( 'Add a comment', false ) . '</span></span>';
					}
				break;
				case 'comment_raw':
					if( $is_closed || $is_approve || ( $is_topic && ! wpforo_setting( 'topics', 'layout_qa_first_post_reply' ) ) ) break;
					$title = wpforo_phrase( 'Use comments to ask for more information or suggest improvements. Avoid answering questions in comments.', false );
					if( WPF()->perm->forum_can( 'cr', $forumid ) || ( wpforo_is_owner( wpforo_bigintval( wpfval( $topic, 'userid' ) ), (string) wpfval( $topic, 'email' ) ) && WPF()->perm->forum_can( 'ocr', $forumid ) ) ) {
						$button_html[] = '<a class="wpforo-qa-comment" title="' . esc_attr( $title ) . '" data-phrase="' . esc_attr( wpforo_phrase( 'Add a comment', false, 'lower' ) ) . '"  data-mention="' . esc_attr( $mention ) . '"><i class="far fa-comment"></i><span class="wpf-button-text">' . wpforo_phrase( 'Add a comment', false, 'lower' ) . '</span></a>';
					} else {
						$button_html[] = ( $login ) ? '' : '<a class="not_reg_user" title="' . esc_attr( $title ) . '" data-phrase="' . esc_attr( wpforo_phrase( 'Add a comment', false, 'lower' ) ) . '"><i class="far fa-comment"></i><span class="wpf-button-text">' . wpforo_phrase( 'Add a comment', false, 'lower' ) . '</span></a>';
					}
				break;
				case 'quote':
					if( $is_closed || $is_approve ) break;
					if( WPF()->perm->forum_can( 'cr', $forumid ) || ( wpforo_is_owner( wpforo_bigintval( wpfval( $topic, 'userid' ) ), (string) wpfval( $topic, 'email' ) ) && WPF()->perm->forum_can( 'ocr', $forumid ) ) ) {
						$button_html[] = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( 'Quote', false ) ) . '" class="wpf-action wpforo-quote" data-postid="' . wpforo_bigintval( $postid ) . '"  data-mention="' . esc_attr( $mention ) . '" data-userid="' . esc_attr( $userid ) . '" data-isowner="' . esc_attr( $is_owner ) . '"><i class="fas fa-quote-left wpfsx"></i><span class="wpf-button-text">' . wpforo_phrase( 'Quote', false ) . '</span></span>';
					} else {
						$button_html[] = ( $login ) ? '' : '<span wpf-tooltip="' . esc_attr( wpforo_phrase( 'Quote', false ) ) . '" class="wpf-action not_reg_user"><i class="fas fa-quote-left wpfsx"></i><span class="wpf-button-text">' . wpforo_phrase( 'Quote', false ) . '</span></span>';
					}
				break;
				case 'report':
					if( WPF()->perm->forum_can( 'r', $forumid ) && $login ) {
						$button_html[] = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( 'Report', false ) ) . '" class="wpf-action wpforo-report" data-postid="' . wpforo_bigintval( $postid ) . '"><i class="fas fa-exclamation-triangle"></i><span class="wpf-button-text">' . wpforo_phrase( 'Report', false ) . '</span></span>';
					}
				break;
				case 'sticky':
					$sticky_status = ( $is_sticky ? 'wpforo-unsticky' : 'wpforo-sticky' );
					if( WPF()->perm->forum_can( 's', $forumid ) ) {
						$button_html[] = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( str_replace( 'wpforo-', '', $sticky_status ), false ) ) . '" class="wpf-action ' . $sticky_status . '" data-topicid="' . wpforo_bigintval( $topicid ) . '"><i class="fas fa-thumbtack wpfsx"></i><span class="wpforo-sticky-txt">' . wpforo_phrase( str_replace( 'wpforo-', '', $sticky_status ), false ) . '</span></span>';
					}
				break;
				case 'private':
					if( $login ) {
						if( WPF()->perm->forum_can( 'p', $forumid ) || ( WPF()->current_userid == $post['userid'] && WPF()->perm->forum_can( 'op', $forumid ) ) ) {
							$private_status = ( $is_private ? 'wpforo-public' : 'wpforo-private' );
							$private_icon   = ( $private_status == 'wpforo-public' ) ? 'eye' : 'eye-slash';
							$button_html[]  = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( str_replace( 'wpforo-', '', $private_status ), false ) ) . '" id="wpfprivate' . intval( $topicid ) . '" class="wpf-action ' . $private_status . '"><i id="privateicon' . intval( $topicid ) . '"  class="fas fa-' . esc_attr( $private_icon ) . ' wpfsx"></i><span id="privatetext' . intval( $topicid ) . '">' . wpforo_phrase( str_replace( 'wpforo-', '', $private_status ), false ) . '</span></span>';
						}
					}
				break;
				case 'solved':
					$solved_status = ( $is_solved ? 'wpforo-unsolved' : 'wpforo-solved' );
					if( WPF()->perm->forum_can( 'sv', $forumid ) || ( WPF()->current_userid == $post['userid'] && WPF()->perm->forum_can( 'osv', $forumid ) ) ) {
						$button_html[] = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( str_replace( 'wpforo-', '', $solved_status ), false ) ) . '" id="wpfsolved' . wpforo_bigintval( $postid ) . '" class="wpf-action ' . $solved_status . '"><i class="fas fa-check-circle wpfsx"></i><span id="solvedtext' . wpforo_bigintval( $postid ) . '">' . wpforo_phrase( str_replace( 'wpforo-', '', $solved_status ), false ) . '</span></span>';
					}
				break;
				case 'approved':
					if( WPF()->perm->forum_can( 'au', $forumid ) && $login ) {
						$approve_status = ( ! $is_approve ? 'wpforo-unapprove' : 'wpforo-approve' );
						$approve_icon   = ( $approve_status == 'wpforo-unapprove' ) ? 'fa-exclamation-circle' : 'fa-check';
						$button_html[]  = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( str_replace( 'wpforo-', '', $approve_status ), false ) ) . '" id="wpfapprove' . wpforo_bigintval( $postid ) . '" class="wpf-action ' . $approve_status . '"><i id="approveicon' . wpforo_bigintval( $postid ) . '"   class="fas ' . esc_attr( $approve_icon ) . ' wpfsx"></i><span id="approvetext' . wpforo_bigintval( $postid ) . '">' . wpforo_phrase(
								str_replace( 'wpforo-', '', $approve_status ),
								false
							) . '</span></span>';
					}
				break;
				case 'close':
					if( WPF()->perm->forum_can( 'cot', $forumid ) && $login ) {
						$open_status   = ( $is_closed ? 'wpforo-open' : 'wpforo-close' );
						$open_icon     = ( $open_status == 'wpforo-open' ) ? 'unlock' : 'lock';
						$button_html[] = '<span  wpf-tooltip="' . esc_attr( wpforo_phrase( str_replace( 'wpforo-', '', $open_status ), false ) ) . '" id="wpfclose' . intval( $topicid ) . '" class="wpf-action ' . $open_status . '"><i id="closeicon' . intval( $topicid ) . '" class="fas fa-' . esc_attr( $open_icon ) . ' wpfsx"></i><span id="closetext' . intval( $topicid ) . '">' . wpforo_phrase( str_replace( 'wpforo-', '', $open_status ), false ) . '</span></span>';
					}
				break;
				case 'tools':
					if( WPF()->perm->forum_can( 'mt', $forumid ) && $login ) {
						$button_html[] = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( 'Tools: Move, Split, Merge', false ) ) . '" wpf-tooltip-size="medium" class="wpf-action wpf-button-outlined wpforo-tools"><i class="fas fa-cog"></i><span class="wpf-button-text">' . wpforo_phrase( 'Tools', false ) . '</span></span>';
					}
				break;
				case 'edit':
					if( $is_closed ) break;
					$diff  = current_time( 'timestamp', 1 ) - strtotime( $post['created'] );
					$_durr = $is_topic ? wpforo_setting( 'posting', 'edit_own_topic_durr' ) : wpforo_setting( 'posting', 'edit_own_post_durr' );
					if( ! $login && isset( $post['email'] ) && wpforo_is_owner( $post['userid'], $post['email'] ) && WPF()->perm->forum_can( ( $is_topic ? 'eot' : 'eor' ), $forumid ) && $diff < $_durr ) {
						$a             = ( $is_topic ) ? 'wpfedittopicpid' : '';
						$b             = $postid;
						$button_html[] = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( 'Edit', false ) ) . '" id="' . esc_attr( $a . $b ) . '" class="wpforo-edit wpf-action"><i class="fas fa-edit wpfsx"></i><span class="wpf-button-text">' . wpforo_phrase( 'Edit', false ) . '</span></span>';
					} elseif( $login ) {
						if( WPF()->perm->forum_can( ( $is_topic ? 'et' : 'er' ), $forumid ) || ( WPF()->current_userid == $post['userid'] && WPF()->perm->forum_can( ( $is_topic ? 'eot' : 'eor' ), $forumid ) && ( $_durr === 0 || $diff < $_durr ) ) ) {
							$a             = ( $is_topic ) ? 'wpfedittopicpid' : '';
							$b             = $postid;
							$button_html[] = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( 'Edit', false ) ) . '" id="' . esc_attr( $a . $b ) . '" class="wpforo-edit wpf-action"><i class="fas fa-edit wpfsx"></i><span class="wpf-button-text">' . wpforo_phrase( 'Edit', false ) . '</span></span>';
						}
					}
				break;
				case 'delete':
					if( $login ) {
						/*if( WPF()->member->current_user_is_new() && $post['status'] ){
							New registered user's unapproved topic/post | No Delete button.
						}else{*/
						$diff  = current_time( 'timestamp', 1 ) - strtotime( $post['created'] );
						$_durr = $is_topic ? wpforo_setting( 'posting', 'delete_own_topic_durr' ) : wpforo_setting( 'posting', 'delete_own_post_durr' );
						if( WPF()->perm->forum_can( ( $is_topic ? 'dt' : 'dr' ), $forumid ) || ( WPF()->current_userid == $post['userid'] && WPF()->perm->forum_can( ( $is_topic ? 'dot' : 'dor' ), $forumid ) && ( $_durr === 0 || $diff < $_durr ) ) ) {
							$a             = ( $is_topic ) ? 'wpftopicdelete' : 'wpfreplydelete';
							$b             = ( $is_topic ) ? $topicid : $postid;
							$button_html[] = '<span wpf-tooltip="' . esc_attr( wpforo_phrase( 'Delete', false ) ) . '" id="' . esc_attr( $a . $b ) . '" class="wpf-action wpforo-delete"><i class="fas fa-trash-alt wpfsx"></i><span class="wpf-button-text">' . wpforo_phrase( 'Delete', false ) . '</span></span>';
						}
						//}
					}
				break;
				case 'link':
					$full_url      = esc_url( wpforo_post( $postid, 'url' ) );
					$short_url     = esc_url( wpforo_home_url( '/' . wpforo_settings_get_slug( 'postid' ) . '/' . $postid . '/' ) );
					$title         = esc_attr( wpforo_phrase( 'Post link', false ) );
					$button_html[] = sprintf(
						'<span class="wpf-action" title="%1$s" data-copy-wpf-furl="%2$s" data-copy-wpf-shurl="%3$s"><i class="fas fa-link wpfsx"></i></span>',
						$title,
						$full_url,
						$short_url
					);
				break;
				case 'positivevote':
					if( WPF()->perm->forum_can( 'v', $forumid ) && $login ) {
						$button_html[] = '<i class="wpforo-voteup fas fa-play fa-rotate-270 wpfcl-0 ' . ( WPF()->reaction->get_user_reaction_reaction( $postid ) === 1 ? 'wpf-vote-active' : '' ) . '" data-type="' . ( $is_topic ? 'topic' : 'reply' ) . '" data-postid="' . wpforo_bigintval( $postid ) . '"></i>';
					} else {
						$button_html[] = '<i class="not_reg_user fas fa-play fa-rotate-270 wpfcl-0"></i>';
					}
				break;
				case 'negativevote':
					if( WPF()->perm->forum_can( 'v', $forumid ) && $login ) {
						$button_html[] = '<i class="wpforo-votedown fas fa-play fa-rotate-90 wpfcl-0 ' . ( WPF()->reaction->get_user_reaction_reaction( $postid ) === - 1 ? 'wpf-vote-active' : '' ) . '" data-type="' . ( $is_topic ? 'topic' : 'reply' ) . '" data-postid="' . wpforo_bigintval( $postid ) . '"></i>';
					} else {
						$button_html[] = '<i class="not_reg_user fas fa-play fa-rotate-90 wpfcl-0"></i>';
					}
				break;
				case 'isanswer':
					if( ! $is_topic ) {
						$has_is_answer_post = WPF()->topic->has_is_answer_post( $post['topicid'] );
						$is_answer          = (bool) WPF()->post->is_answered( $postid );
						$class              = ( $is_answer ) ? '' : '-not';
						if( ! $has_is_answer_post || $is_answer ) {
							if( $login ) {
								$button_html[] = '<div class="wpf-toggle' . esc_attr( $class ) . '-answer" data-postid="' . wpforo_bigintval( $postid ) . '"><i class="fas fa-check"></i></div>';
							} else {
								$button_html[] = '<div class="wpf-toggle' . esc_attr( $class ) . '-answer not_reg_user"><i class="fas fa-check"></i></div>';
							}
						}
					}
				break;
                default:
                    $bh = apply_filters( 'wpforo_template_buttons', '', $button, $forum, $topic, $post );
	                if( $bh ) $button_html[] = $bh;
                break;
			} //switch
		} //foreach

		$before = '<span class="wpforo-action-buttons-wrap">';
		$after  = '</span>';
		$html   = $before . implode( '', $button_html ) . $after;

        if( $echo ) echo $html;
		return $html;
	}

	/**
	 * display QA Layout Votes count for loop current post
	 *
	 * @param array $post loop current post array
	 *
	 * @return void
	 */
	public function vote_count( $post ) {
		$votes = ( ! empty( $post['votes'] ) ? $post['votes'] : 0 );
		printf( '<span class="wpfvote-num wpfcl-0">%d</span>', $votes );
	}

	function breadcrumb( $current_object = null ) {
		if( ! $current_object ) $current_object = WPF()->current_object;

		$lenght = apply_filters( 'wpforo_breadcrumb_text_length', 19 ); ?>

        <style>.wpf-item-element {
                display: inline;
            }</style>
        <div class="wpf-breadcrumb" itemscope="" itemtype="https://schema.org/BreadcrumbList">
			<?php switch( $current_object['template'] ) :
				case 'search': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Search' ) ?></span></div>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'signup': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Register' ) ?></span></div>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'signin': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Login' ) ?></span></div>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'members': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
					<?php if( isset( $_GET['wpfms'] ) ) : ?>
                        <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo wpforo_members_url() ?>"><?php wpforo_phrase( 'Members' ) ?></a>
                            <meta itemprop="position" content="2">
                        </div>
                        <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Search' ) ?></span></div>
					<?php else : ?>
                        <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Members' ) ?></span></div>
					<?php endif ?>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'recent': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
					<?php if( wpfval( $_GET, 'view' ) === 'unread' ): ?>
                        <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Unread Posts' ) ?></span></div>
					<?php elseif( wpfval( $_GET, 'view' ) === 'prefix' ): ?>
                        <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Topic Prefix' ) ?></span></div>
					<?php else: ?>
                        <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Recent Posts' ) ?></span></div>
					<?php endif; ?>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'tags': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Tags' ) ?></span></div>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'profile': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo wpforo_members_url() ?>"><span itemprop="name"><?php wpforo_phrase( 'Members' ) ?></span></a>
                        <meta itemprop="position" content="2">
                    </div>
					<?php if( $current_object['user'] ) : ?>
                        <div class="wpf-item-element active"><span><?php @wpforo_text( wpforo_user_dname( $current_object['user'] ), $lenght ) ?></span></div>
					<?php endif ?>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'account': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo wpforo_members_url() ?>"><span itemprop="name"><?php wpforo_phrase( 'Members' ) ?></span></a>
                        <meta itemprop="position" content="2">
                    </div>
					<?php if( $current_object['user'] ) : ?>
                        <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo esc_url( $current_object['user']['profile_url'] ) ?>"><span itemprop="name"><?php wpforo_text( wpforo_user_dname( $current_object['user'] ), $lenght ) ?></span></a>
                            <meta itemprop="position" content="3">
                        </div>
					<?php endif ?>
                    <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Account' ) ?></span></div>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'activity': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo wpforo_members_url() ?>"><span itemprop="name"><?php wpforo_phrase( 'Members' ) ?></span></a>
                        <meta itemprop="position" content="2">
                    </div>
					<?php if( $current_object['user'] ) : ?>
                        <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo esc_url( $current_object['user']['profile_url'] ) ?>"><span itemprop="name"><?php wpforo_text( wpforo_user_dname( $current_object['user'] ), $lenght ) ?></span></a>
                            <meta itemprop="position" content="3">
                        </div>
					<?php endif ?>
                    <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Activity' ) ?></span></div>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'subscriptions': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo wpforo_members_url() ?>"><span itemprop="name"><?php wpforo_phrase( 'Members' ) ?></span></a>
                        <meta itemprop="position" content="2">
                    </div>
					<?php if( $current_object['user'] ) : ?>
                        <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo esc_url( $current_object['user']['profile_url'] ) ?>"><span itemprop="name"><?php wpforo_text( wpforo_user_dname( $current_object['user'] ), $lenght ) ?></span></a>
                            <meta itemprop="position" content="3">
                        </div>
					<?php endif ?>
                    <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Subscriptions' ) ?></span></div>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'messages': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo wpforo_members_url() ?>"><span itemprop="name"><?php wpforo_phrase( 'Members' ) ?></span></a>
                        <meta itemprop="position" content="2">
                    </div>
					<?php if( $current_object['user'] ) : ?>
                        <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo esc_url( $current_object['user']['profile_url'] ) ?>"><span itemprop="name"><?php wpforo_text( wpforo_user_dname( $current_object['user'] ), $lenght ) ?></span></a>
                            <meta itemprop="position" content="3">
                        </div>
					<?php endif ?>
                    <div class="wpf-item-element active"><span><?php wpforo_phrase( 'Messages' ) ?></span></div>
                    <a class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'topic': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root<?php echo( ! $current_object['forumid'] ? ' active' : '' ) ?>"><a itemprop="item" href="<?php echo( ! $current_object['forumid'] ? '#' : wpforo_home_url() ) ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
					<?php if( $current_object['forumid'] ) : ?>
						<?php $relative_ids = [];
						WPF()->forum->get_parents( $current_object['forumid'], $relative_ids );
						foreach( $relative_ids as $key => $rel_forumid ) : ?>
							<?php $forum = wpforo_forum( $rel_forumid ) ?>
							<?php if( ! empty( $forum ) ): ?>
								<?php if( $key != ( count( $relative_ids ) - 1 ) ) : ?>
                                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo esc_url( $forum['url'] ) ?>" title="<?php echo esc_attr( $forum['title'] ) ?>"><span itemprop="name"><?php wpforo_text( $forum['title'], $lenght ) ?></span></a>
                                        <meta itemprop="position" content="<?php echo $key + 2; ?>">
                                    </div>
								<?php else : ?>
                                    <div class="wpf-item-element active"><span><?php wpforo_text( $forum['title'], $lenght ) ?></span></div>
								<?php endif ?>
							<?php endif ?>
						<?php endforeach ?>
					<?php endif ?>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				case 'post': ?>
                    <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root<?php echo( ! $current_object['forumid'] ? ' active' : '' ) ?>"><a itemprop="item" href="<?php echo( ! $current_object['forumid'] ? '#' : wpforo_home_url() ) ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                        <meta itemprop="position" content="1">
                    </div>
					<?php if( $current_object['forumid'] ) : ?>
						<?php $relative_ids = [];
						WPF()->forum->get_parents( $current_object['forumid'], $relative_ids );
						foreach( $relative_ids as $key => $rel_forumid ) : ?>
							<?php $forum = wpforo_forum( $rel_forumid ) ?>
							<?php if( ! empty( $forum ) ): ?>
                                <div class="wpf-item-element" itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem"><a itemprop="item" href="<?php echo esc_url( $forum['url'] ) ?>" title="<?php echo esc_attr( $forum['title'] ) ?>"><span itemprop="name"><?php wpforo_text( $forum['title'], $lenght ) ?></span></a>
                                    <meta itemprop="position" content="<?php echo $key + 2; ?>">
                                </div>
							<?php endif ?>
						<?php endforeach ?>
					<?php endif ?>
					<?php if( $current_object['topic'] ) : ?>
                        <div class="wpf-item-element active"><span><?php wpforo_text( $current_object['topic']['title'], $lenght ) ?></span></div>
					<?php endif ?>
                    <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php break;
				default: ?>
					<?php if( wpforo_is_member_template( $current_object['template'] ) && ( $template = $this->get_template( $current_object['template'] ) ) ) : ?>
                        <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                            <meta itemprop="position" content="1">
                        </div>
                        <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo wpforo_members_url() ?>"><span itemprop="name"><?php wpforo_phrase( 'Members' ) ?></span></a>
                            <meta itemprop="position" content="2">
                        </div>
						<?php if( $current_object['user'] ) : ?>
                            <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element"><a itemprop="item" href="<?php echo esc_url( $current_object['user']['profile_url'] ) ?>"><span itemprop="name"><?php wpforo_text( wpforo_user_dname( $current_object['user'] ), $lenght ) ?></span></a>
                                <meta itemprop="position" content="3">
                            </div>
						<?php endif ?>
                        <div class="wpf-item-element active"><span><?php wpforo_phrase( $template['title'] ) ?></span></div>
                        <a class="wpf-end" rel="nofollow">&nbsp;</a>
					<?php else : ?>
						<?php if( $current_object['forumid'] ): ?>
                            <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><i class="fas fa-home"></i><span itemprop="name" style="display:none;"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                                <meta itemprop="position" content="1">
                            </div>
                            <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
						<?php else: ?>
                            <div itemprop="itemListElement" itemscope="" itemtype="https://schema.org/ListItem" class="wpf-item-element wpf-root active"><a itemprop="item" href="<?php echo wpforo_home_url() ?>" title="<?php esc_attr( wpforo_phrase( 'Forums' ) ) ?>"><span itemprop="name"><?php wpforo_phrase( 'Forums' ) ?></span></a>
                                <meta itemprop="position" content="1">
                            </div>
                            <a href="#" class="wpf-end" rel="nofollow">&nbsp;</a>
						<?php endif ?>
					<?php endif ?>
				<?php endswitch; ?>
        </div>
		<?php
	}

	function icon( $type, $item = [], $echo = true, $data = 'icon' ) {

		$icon   = [];
		$status = false;

		if( isset( $item['status'] ) && $item['status'] ) {
			$icon['class'] = 'fas fa-exclamation-circle';
			$icon['color'] = 'wpfcl-5';
			$icon['title'] = wpforo_phrase( 'Unapproved', false );
			if( $echo ) {
				$status = true;
				echo ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
			} else {
				return ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
			}
		}

		if( isset( $item['type'] ) ) {

			if( $type == 'topic' ) {
				if( WPF()->topic->is_private( $item['topicid'] ) ) {
					$icon['class'] = 'fas fa-eye-slash';
					$icon['color'] = 'wpfcl-1';
					$icon['title'] = wpforo_phrase( 'Private', false );
					if( $echo ) {
						$status = true;
						echo ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
					} else {
						return ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
					}
				}
				if( wpforo_topic( $item['topicid'], 'solved' ) ) {
					$icon['class'] = 'fas fa-check-circle';
					$icon['color'] = 'wpfcl-8';
					$icon['title'] = wpforo_phrase( 'Solved', false );
					if( $echo ) {
						$status = true;
						echo ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
					} else {
						return ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
					}
				}
			}

			if( $item['closed'] && $item['type'] == 1 ) {
				$icon['class'] = 'fas fa-lock';
				$icon['color'] = 'wpfcl-1';
				$icon['title'] = wpforo_phrase( 'Closed', false );
				if( $echo ) {
					$status = true;
					echo ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
				} else {
					return ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
				}
			} elseif( $item['closed'] && $item['type'] != 1 ) {
				$icon['class'] = 'fas fa-lock';
				$icon['color'] = 'wpfcl-1';
				$icon['title'] = wpforo_phrase( 'Closed', false );
				if( $echo ) {
					$status = true;
					echo ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
				} else {
					return ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
				}
			} elseif( ! $item['closed'] && $item['type'] == 1 ) {
				$icon['class'] = 'fas fa-thumbtack';
				$icon['color'] = 'wpfcl-10';
				$icon['title'] = wpforo_phrase( 'Sticky', false );
				if( $echo ) {
					$status = true;
					echo ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
				} else {
					return ( $data == 'icon' ) ? implode( ' ', $icon ) : $icon['title'];
				}
			}

			if( ! $status ) {
				if( $type == 'forum' ) {
					$icon['class'] = 'fas fa-comments';
					$icon['color'] = 'wpfcl-2';
				} elseif( $type == 'topic' ) {
					$icon = $this->icon_status( $item['posts'] );
				}
				if( $echo ) {
					echo ( $data == 'icon' ) ? implode( ' ', $icon ) : ( wpfval( $icon, 'title' ) ? $icon['title'] : '' );
				} else {
					return ( $data == 'icon' ) ? implode( ' ', $icon ) : ( wpfval( $icon, 'title' ) ? $icon['title'] : '' );
				}
			}

		}

		return '';
	}

	function icon_status( $item ) {
		$icon = [];
		if( wpfval( $item, 'type' ) ) {
			$icon['sticky']['class'] = 'fas fa-thumbtack';
			$icon['sticky']['color'] = 'wpfcl-10';
			$icon['sticky']['title'] = wpforo_phrase( 'Sticky', false );
		}
		if( wpfval( $item, 'topicid' ) && wpforo_topic( $item['topicid'], 'solved' ) ) {
			$icon['is_answer']['class'] = 'fas fa-check-circle';
			$icon['is_answer']['color'] = 'wpfcl-8';
			$icon['is_answer']['title'] = wpforo_phrase( 'Solved', false );
		}
		if( wpfval( $item, 'closed' ) ) {
			$icon['closed']['class'] = 'fas fa-lock';
			$icon['closed']['color'] = 'wpfcl-1';
			$icon['closed']['title'] = wpforo_phrase( 'Closed', false );
		}
		if( wpfval( $item, 'status' ) ) {
			$icon['status']['class'] = 'fas fa-exclamation-circle';
			$icon['status']['color'] = 'wpfcl-5';
			$icon['status']['title'] = wpforo_phrase( 'Unapproved', false );
		}
		if( wpfval( $item, 'private' ) ) {
			$icon['private']['class'] = 'fas fa-eye-slash';
			$icon['private']['color'] = 'wpfcl-1';
			$icon['private']['title'] = wpforo_phrase( 'Private', false );
		}

		return $icon;
	}

	function icon_base( $post_count ) {
		$icon = [];
		if( $post_count < 2 ) {
			$icon['class'] = 'far fa-file';
			$icon['color'] = 'wpfcl-2';
			$icon['title'] = wpforo_phrase( 'Not Replied', false );
		} elseif( $post_count <= 5 ) {
			$icon['class'] = 'far fa-file-alt';
			$icon['color'] = 'wpfcl-2';
			$icon['title'] = wpforo_phrase( 'Replied', false );
		} elseif( $post_count <= 20 ) {
			$icon['class'] = 'fas fa-file-alt';
			$icon['color'] = 'wpfcl-2';
			$icon['title'] = wpforo_phrase( 'Active', false );
		} else {
			$icon['class'] = 'fa-brands fa-hotjar';
			$icon['color'] = 'wpfcl-5';
			$icon['title'] = wpforo_phrase( 'Hot', false );
		}

		return $icon;
	}

	public function member_social_buttons( $member ) {
		$socnets = [];
		if( empty( $member ) ) return;
		$social_access = (bool) WPF()->usergroup->can( 'vmsn' );

		if( $social_access ) {

			if( isset( $member['facebook'] ) && $member['facebook'] ) {
				$socnets['facebook']['set']      = $member['facebook'];
				$member['facebook']              = ( strpos( $member['facebook'], 'facebook.com' ) === false ) ? 'https://www.facebook.com/' . trim( $member['facebook'], '/' ) : $member['facebook'];
				$socnets['facebook']['value']    = $member['facebook'];
				$socnets['facebook']['protocol'] = 'https://';
				$socnets['facebook']['title']    = wpforo_phrase( 'Facebook', false );
			}

			if( isset( $member['twitter'] ) && $member['twitter'] ) {
				$socnets['twitter']['set']      = $member['twitter'];
				$member['twitter']              = ( strpos( $member['twitter'], 'twitter.com' ) === false ) ? 'https://twitter.com/' . trim( $member['twitter'], '/' ) : $member['twitter'];
				$socnets['twitter']['value']    = $member['twitter'];
				$socnets['twitter']['protocol'] = 'https://';
				$socnets['twitter']['title']    = wpforo_phrase( 'Twitter', false );
			}

			if( isset( $member['skype'] ) && $member['skype'] ) {
				$socnets['skype']['set']      = $member['skype'];
				$socnets['skype']['value']    = $member['skype'];
				$socnets['skype']['protocol'] = 'skype:';
				$socnets['skype']['title']    = wpforo_phrase( 'Skype', false );
			}

			?>
            <div class="wpf-member-socnet-wrap">
				<?php if( ! empty( $socnets ) ): ?>
					<?php foreach( $socnets as $key => $socnet ): ?>
						<?php if( ! $socnet['set'] ) continue; ?>
						<?php $title = $member['display_name'] . ' - ' . $socnet['title']; ?>
						<?php $url = ( $key == 'skype' ) ? 'skype:' . esc_attr( $socnet['value'] ) : esc_url( $socnet['protocol'] . str_replace( [ 'https://', 'http://', 'skype:', 'mailto:' ], '', $socnet['value'] ) ); ?>
                        <a href="<?php echo $url ?>" class="wpf-member-socnet-button" title="<?php echo esc_attr( $title ) ?>">
                            <img src="<?php echo esc_url( WPFORO_URL ) ?>/assets/images/sn/<?php echo $key ?>.png" alt="<?php echo esc_attr( $title ) ?>" title="<?php echo esc_attr( $title ) ?>"/>
                        </a>
					<?php endforeach; ?>
				<?php endif; ?>
				<?php do_action( 'wpforo_member_socnet_buttons', $member ); ?>
            </div>
			<?php
		}
	}

	/**
	 *
	 * Checks in current active theme options if certain layout exists.
	 *
	 * @param mixed $identifier Layout id (folder name) OR @layout variable in header ( 1 or Extended )
	 * @param string $identifier_type The type of first parameter 'id' OR 'name' (@layout)
	 *
	 * @return boolean                        true/false
	 *
	 **@since 1.0.0
	 *
	 */
	function layout_exists( $identifier, $identifier_type = 'id' ) {
		$layouts = $this->theme_info['layouts'];
		if( $identifier_type === 'id' ) {
			return ( isset( $layouts[ $identifier ] ) && ! empty( $layouts[ $identifier ] ) );
		} elseif( $identifier_type === 'name' ) {
			foreach( $layouts as $layout ) {
				if( ! isset( $layout['name'] ) && $layout['name'] === $identifier ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 *
	 * Finds and returns all layouts information in array from theme's /layouts/ folder
	 *
	 * @param string $theme Theme id ( folder name ) e.g. 'classic'
	 *
	 * @return array
	 *
	 **@since 1.0.0
	 *
	 */
	function find_layouts( $theme = '' ) {
		if( ! $theme ) $theme = $this->theme;
		$layout_data = [];
		$layouts     = $this->find_themes( '/' . $theme . '/layouts', 'php', 'layout' );
		if( ! empty( $layouts ) ) {
			foreach( $layouts as $layout ) {
				$lid                                = trim( basename( dirname( $layout['file']['value'] ) ), '/' );
				$layout_data[ $lid ]['id']          = intval( $lid );
				$layout_data[ $lid ]['name']        = esc_html( $layout['name']['value'] );
				$layout_data[ $lid ]['version']     = $layout['version']['value'];
				$layout_data[ $lid ]['description'] = $layout['description']['value'];
				$layout_data[ $lid ]['author']      = $layout['author']['value'];
				$layout_data[ $lid ]['url']         = $layout['layout_url']['value'];
				$layout_data[ $lid ]['file']        = $layout['file']['value'];
			}
		}

		return $layout_data;
	}

	function show_layout_selectbox( $layoutid = 0 ) {
		$layouts = $this->find_layouts();
		if( ! empty( $layouts ) ) {
			foreach( $layouts as $layout ) : ?>
                <option value="<?php echo esc_attr( trim( $layout['id'] ) ) ?>" <?php echo( $layoutid == $layout['id'] ? 'selected' : '' ); ?> ><?php echo esc_html( $layout['name'] ) ?></option>
			<?php
			endforeach;
		}
	}

	/**
	 * @param string $theme theme dir name if empty use current theme dir
	 *
	 * @return string dynamicly generated css
	 */
	public function generate_dynamic_css( $theme = '' ) {
		$css     = '';
		$search  = [];
		$replace = [];

		$color_style  = wpforo_setting( 'styles', 'color_style' );
		$color_styles = wpforo_setting( 'styles', 'color_styles' );
		if( $colors = wpfval( $color_styles, $color_style ) ) {
			foreach( $colors as $colorid => $color ) {
				if( $color ) {
					$search[]  = '__WPFCOLOR_' . $colorid . '__';
					$replace[] = $color;
				}
			}
		}

		$css_matrix = ( $theme ? WPFORO_THEME_DIR . '/' . $theme : $this->template_dir ) . '/styles/matrix.css';
		if( file_exists( $css_matrix ) ) $css = wpforo_get_file_content( $css_matrix );

		$dynamic_css = apply_filters( 'wpforo_dynamic_css_filter', '' );
		$dynamic_css = wpforo_add_wrapper( $dynamic_css );

		$css .= "\r\n" . $dynamic_css;
		$css = apply_filters( 'wpforo_dynamic_css', $css );

		$css = str_replace( $search, $replace, $css );

		return trim( $css );
	}

	/**
	 *
	 * Finds and returns styles array from theme's /styles/colors.php file
	 *
	 * @param string $theme Theme id ( folder name ) e.g. 'classic'
	 *
	 * @return array
	 *
	 **@since 1.0.0
	 *
	 */
	function find_styles( $theme ) {
		$colors     = [];
		$color_file = WPFORO_THEME_DIR . '/' . $theme . '/styles/colors.php';
		if( file_exists( $color_file ) ) {
			include( $color_file );
		}

		return $colors;
	}

	/**
	 *
	 * Scans certain theme directory and returns all information in array ( theme header, layouts, styles ).
	 *
	 * @param string $theme_file Theme folder name or main css file base path ( 'classic' OR classic/style.css' )
	 *
	 * @return array
	 *
	 **@since 1.0.0
	 *
	 */
	function find_theme( $theme_file ) {
		$theme      = [];
		$theme_file = trim( trim( $theme_file, '/' ) );

		if( preg_match( '|\.[\w\d]{2,4}$|is', $theme_file ) ) {
			$theme_folder = trim( basename( dirname( $theme_file ) ), '/' );
		} else {
			$theme_folder = $theme_file;
			$theme_file   = $theme_file . '/style.css';
		}

		if( ! is_readable( WPFORO_THEME_DIR . '/' . $theme_file ) ) {
			$theme['error'] = __( 'Theme file not readable', 'wpforo' ) . ' (' . $theme_file . ')';
		} else {
			$theme_data           = $this->find_theme_headers( WPFORO_THEME_DIR . '/' . $theme_file );
			$theme['id']          = $theme_folder;
			$theme['name']        = $theme_data['name']['value'];
			$theme['version']     = $theme_data['version']['value'];
			$theme['description'] = $theme_data['description']['value'];
			$theme['author']      = $theme_data['author']['value'];
			$theme['url']         = $theme_data['theme_url']['value'];
			$theme['file']        = $theme_file;
			$theme['folder']      = $theme_folder;
			$theme['layouts']     = $this->find_layouts( $theme_folder );
			$styles               = $this->find_styles( $theme_folder );
			if( ! empty( $styles ) ) {
				reset( $styles );
				$theme['style']  = key( $styles );
				$theme['styles'] = $styles;
			}
		}

		return $theme;
	}

	/**
	 *
	 * Scans wpForo themes (wpf-themes) folder, reads main files' headers and returns information about all themes in array.
	 * This function can also be used to scan and get information about layouts in each theme /layouts/ folder.
	 *
	 * @param string $base_dir Absolute path to scan directory (e.g. /home/public_html/wp-content/plugins/wpforo/themes/)
	 * @param string $ext File extension which may contain header information
	 * @param string $mode 'theme' or 'layout'
	 *
	 * @return array
	 *
	 **@since 1.0.0
	 *
	 */
	function find_themes( $base_dir = '', $ext = 'css', $mode = 'theme' ) {
		$themes      = [];
		$themes_dir  = @opendir( WPFORO_THEME_DIR . $base_dir );
		$theme_files = [];
		if( $themes_dir ) {
			while( ( $file = readdir( $themes_dir ) ) !== false ) {
				if( substr( $file, 0, 1 ) == '.' ) continue;
				if( is_dir( WPFORO_THEME_DIR . $base_dir . '/' . $file ) ) {
					$themes_subdir = @opendir( WPFORO_THEME_DIR . $base_dir . '/' . $file );
					if( $themes_subdir ) {
						while( ( $subfile = readdir( $themes_subdir ) ) !== false ) {
							if( substr( $subfile, 0, 1 ) == '.' ) continue;
							if( substr( $subfile, - 4 ) == '.' . $ext ) $theme_files[] = "$file/$subfile";
						}
						closedir( $themes_subdir );
					}
				} else {
					if( substr( $file, - 4 ) == '.' . $ext ) $theme_files[] = $file;
				}
			}
			closedir( $themes_dir );
		}
		if( empty( $theme_files ) ) return $themes;
		foreach( $theme_files as $theme_file ) {
			if( ! is_readable( WPFORO_THEME_DIR . $base_dir . '/' . $theme_file ) ) continue;
			if( $mode === 'theme' ) {
				$theme_data = $this->find_theme_headers( WPFORO_THEME_DIR . $base_dir . '/' . $theme_file );
			} elseif( $mode === 'layout' ) {
				$theme_data = $this->find_layout_headers( WPFORO_THEME_DIR . $base_dir . '/' . $theme_file );
			}
			if( empty( $theme_data['name']['value'] ) ) continue;
			$themes[ wpforo_clear_basename( $theme_file ) ] = $theme_data;
		}

		return $themes;
	}

	/**
	 *
	 * Reads theme main file's header variables and returns information in array.
	 *
	 * @param string $file Absolute path to file (e.g. /home/public_html/wp-content/plugins/wpforo/themes/style.css)
	 *
	 * @return array
	 *
	 **@since 1.0.0
	 *
	 */
	function find_theme_headers( $file ) {
		$theme_headers = [];
		$headers       = [
			'name'        => 'Theme Name',
			'version'     => 'Version',
			'description' => 'Description',
			'author'      => 'Author',
			'theme_url'   => 'Theme URI',
		];
		$fp            = fopen( $file, 'r' );
		$data          = fread( $fp, 8192 );
		fclose( $fp );
		$data = str_replace( "\r", "\n", $data );
		foreach( $headers as $header_key => $header_name ) {
			$theme_headers[ $header_key ]['name'] = $header_name;
			if( preg_match( '|^[\s\t\/*#@]*' . preg_quote( $header_name, '|' ) . ':(.*)$|mi', $data, $match ) && $match[1] ) {
				$theme_headers[ $header_key ]['value'] = trim( preg_replace( "/\s*(?:\*\/|\?>).*/", '', $match[1] ) );
			} else {
				$theme_headers[ $header_key ]['value'] = '';
			}
		}
		$theme_headers['file']['name']  = 'file';
		$theme_headers['file']['value'] = $file;

		return $theme_headers;
	}

	/**
	 *
	 * Reads layout main file's header variables and returns information in array.
	 *
	 * @param string $file Absolute path to file (e.g. /home/public_html/wp-content/plugins/wpforo/themes/layouts/1/forum.php)
	 *
	 * @return array
	 *
	 **@since 1.0.0
	 *
	 */
	function find_layout_headers( $file ) {
		$theme_headers = [];
		$headers       = [
			'name'        => 'layout',
			'version'     => 'version',
			'description' => 'description',
			'author'      => 'author',
			'layout_url'  => 'url',
		];
		$fp            = fopen( $file, 'r' );
		$data          = fread( $fp, 8192 );
		fclose( $fp );
		$data = str_replace( "\r", "\n", $data );
		foreach( $headers as $header_key => $header_name ) {
			$theme_headers[ $header_key ]['name'] = $header_name;
			if( preg_match( '|^[\s\t\/*#@]*' . preg_quote( $header_name, '|' ) . ':(.*)$|mi', $data, $match ) && $match[1] ) {
				$theme_headers[ $header_key ]['value'] = trim( preg_replace( "/\s*(?:\*\/|\?>).*/", '', $match[1] ) );
			} else {
				$theme_headers[ $header_key ]['value'] = '';
			}
		}
		$theme_headers['file']['name']  = 'file';
		$theme_headers['file']['value'] = trim( str_replace( WPFORO_THEME_DIR, '', $file ), '/' );

		return $theme_headers;
	}

	public function theme_exists( $theme ) {
        $_theme = $this->find_theme( $theme );
        return ! wpfkey($_theme, 'error');
    }

	public function copyright() {
		if( wpforo_setting( 'components', 'copyright' ) ): ?>
            <div id="wpforo-poweredby">
                <p class="wpf-by">
                    <span onclick='document.getElementById("bywpforo").style.display = "inline";document.getElementById("awpforo").style.display = "none";' id="awpforo"> <img title="<?php esc_attr( wpforo_phrase( 'Powered by' ) ) ?> wpForo version <?php echo esc_html( WPFORO_VERSION ) ?>" alt="Powered by wpForo" class="wpdimg" src="<?php echo WPFORO_URL ?>/assets/images/wpforo-info.png"> </span><a id="bywpforo" target="_blank" href="https://wpforo.com/">&nbsp;<?php wpforo_phrase(
							'Powered by'
						) ?> wpForo version <?php echo esc_html( WPFORO_VERSION ) ?></a>
                </p>
            </div>
		<?php
		endif;
	}

	public function member_error() {
		echo apply_filters( 'wpforo_member_error_filter', wpforo_phrase( 'Members not found', false ) );
	}

	public function ajx_active_tab_content() {
		if( ! empty( $_POST['active_tab_id'] ) ) {
			$active_tab_id = sanitize_textarea_field( $_POST['active_tab_id'] );
			switch( $active_tab_id ) {
				case 'topic_merge_form':
					$this->topic_merge_form();
					exit();
				case 'reply_move_form':
					$this->reply_move_form();
					exit();
				case 'topic_split_form':
					$this->topic_split_form();
					exit();
				case 'topic_move_form':
					$this->topic_move_form();
					exit();
			}
		}
		echo 0;
		exit();
	}

	public function add_footer_html() {
		$this->dialog();
		$this->spinner();
		if( apply_filters( 'wpforo_message_bubble', true ) ) $this->msg_box();
		if( WPF()->current_object['template'] === 'post' ) $this->report_form();
	}

	public function posts_ordering_dropdown( $orderby = null, $topicid = null ) {
		if( is_null( $topicid ) ) $topicid = wpforo_bigintval( wpfval( WPF()->current_object, 'topicid' ) );
		if( $topicid && $topic_url = wpforo_topic( $topicid, 'url' ) ) {
			if( is_null( $orderby ) ) $orderby = WPF()->current_object['orderby'];
			?>
            <label>
                <select onchange="window.location.assign(this.value)">
                    <option value="<?php echo $topic_url ?>?orderby=votes" <?php wpfo_check( $orderby, 'votes', 'selected' ) ?>>
						<?php wpforo_phrase( 'Most Voted' ) ?>
                    </option>
                    <option value="<?php echo $topic_url ?>?orderby=oldest" <?php wpfo_check( $orderby, 'oldest', 'selected' ) ?>>
						<?php wpforo_phrase( 'Oldest' ) ?>
                    </option>
                    <option value="<?php echo $topic_url ?>?orderby=newest" <?php wpfo_check( $orderby, 'newest', 'selected' ) ?>>
						<?php wpforo_phrase( 'Newest' ) ?>
                    </option>
                </select>
            </label>

			<?php
		}
	}

	public function editor_buttons( $editor = 'post' ) {
		global $wp_styles;
		if( ! empty( $wp_styles ) && ( $fa = wpfval( $wp_styles->registered, 'wpforo-font-awesome' ) ) && ( $fa_rtl = wpfval( $wp_styles->registered, 'wpforo-font-awesome-rtl' ) ) ) {
			$content_css = $fa->src . ( is_rtl() ? ',' . $fa_rtl->src : '' );
		} else {
			$content_css = '';
		}
		$settings = [
			'topic' => [
				'media_buttons'  => false,
				'textarea_name'  => 'topic[body]',
				'textarea_rows'  => get_option( 'default_post_edit_rows', 20 ),// rows = "..."
				'tabindex'       => '',
				'editor_height'  => 180,
				'editor_css'     => '',
				'editor_class'   => '',
				'teeny'          => false,
				'dfw'            => false,
				'tinymce'        => [
					'toolbar1'                => 'fontsizeselect,bold,italic,underline,strikethrough,forecolor,bullist,numlist,hr,alignleft,aligncenter,alignright,alignjustify,link,unlink,blockquote,pre,wpf_spoil,undo,redo,pastetext,source_code,emoticons,fullscreen',
					'toolbar2'                => '',
					'toolbar3'                => '',
					'toolbar4'                => '',
					'content_style'           => 'blockquote{border: #cccccc 1px dotted; background: #F7F7F7; padding:10px;font-size:12px; font-style:italic; margin: 20px 10px;} pre{border-left: 3px solid #ccc; outline: none !important; background: #fafcff;padding: 10px;font-size: 14px;margin: 20px 0 0 10px;display: block;width: 100%;}  img.emoji{width: 20px;}',
					'object_resizing'         => false,
					'autoresize_on_init'      => true,
					'wp_autoresize_on'        => true,
					'wp_keep_scroll_position' => true,
					'indent'                  => true,
					'add_unload_trigger'      => false,
					'wpautop'                 => false,
					'setup'                   => 'wpforo_tinymce_setup',
					'content_css'             => $content_css,
					'extended_valid_elements' => 'i[class|style],span[class|style]',
					'custom_elements'         => '',
				],
				'quicktags'      => false,
				'default_editor' => 'tinymce',
			],
			'post'  => [
				'media_buttons'  => false,
				'textarea_name'  => 'post[body]',
				'textarea_rows'  => get_option( 'default_post_edit_rows', 5 ),
				'editor_class'   => 'wpeditor',
				'teeny'          => false,
				'dfw'            => false,
				'editor_height'  => 150,
				'tinymce'        => [
					'toolbar1'                => 'fontsizeselect,bold,italic,underline,strikethrough,forecolor,bullist,numlist,hr,alignleft,aligncenter,alignright,alignjustify,link,unlink,blockquote,pre,wpf_spoil,undo,redo,pastetext,source_code,emoticons,fullscreen',
					'toolbar2'                => '',
					'toolbar3'                => '',
					'toolbar4'                => '',
					'content_style'           => 'blockquote{border: #cccccc 1px dotted; background: #F7F7F7; padding:10px;font-size:12px; font-style:italic; margin: 20px 10px;} pre{border-left: 3px solid #ccc; outline: none !important; background: #fafcff;padding: 10px;font-size: 14px;margin: 20px 0 0 10px;display: block;width: 100%;}  img.emoji{width: 20px;}',
					'object_resizing'         => false,
					'autoresize_on_init'      => true,
					'wp_autoresize_on'        => true,
					'wp_keep_scroll_position' => true,
					'indent'                  => true,
					'add_unload_trigger'      => false,
					'wpautop'                 => false,
					'setup'                   => 'wpforo_tinymce_setup',
					'content_css'             => $content_css,
					'extended_valid_elements' => 'i[class|style],span[class|style]',
					'custom_elements'         => '',
				],
				'quicktags'      => false,
				'default_editor' => 'tinymce',
			],
		];

		return apply_filters( 'wpforo_editor_settings', array_merge( $this->default->editor_settings, $settings[ $editor ] ), $editor );
	}

	public function editor_settings_required_params( $settings ) {
		if( ! wpfkey( $settings, 'tinymce' ) ) $settings['tinymce'] = [];
		$settings['tinymce']['setup'] = 'wpforo_tinymce_setup';

		if( is_rtl() ) {
			$settings['tinymce']['rtl_ui']         = true;
			$settings['plugins']                   .= ',directionality';
			$settings['tinymce']['toolbar1']       .= ',ltr';
			$settings['tinymce']['directionality'] = 'rtl';

			$settings['plugins']             = trim( $settings['plugins'], ',' );
			$settings['tinymce']['toolbar1'] = trim( $settings['tinymce']['toolbar1'], ',' );
		}

		return $settings;
	}

	public function add_topic_button( $forumid = null ) {
		$phrase = ( WPF()->forum->get_layout( $forumid ) === 3 ? wpforo_phrase( 'Ask a Question', false ) : wpforo_phrase( 'Add Topic', false ) );
		if( WPF()->current_object['template'] === 'forum' ) :
            if( wpforo_forum_list_need_add_topic_button( $forumid ) ) :
	            if( WPF()->perm->forum_can( 'ct', $forumid ) ) : WPF()->current_object['load_tinymce'] = true; ?>
                    <div class="wpf-head-bar-right">
                        <button class="wpf-button add_wpftopic" data-phrase="<?php echo $phrase ?>">
                            <i class="fas fa-feather-alt"></i> <span><?php echo $phrase ?></span>
                        </button>
                    </div>
	            <?php elseif( WPF()->current_user_groupid === 4 ) : ?>
                    <div class="wpf-head-bar-right">
                        <a href="<?php echo wpforo_login_url() ?>" class="wpf-button"><i class="fas fa-feather-alt"></i> <span><?php echo $phrase ?></span></a>
                    </div>
	            <?php endif;
            endif;
		else :
			if( WPF()->perm->forum_can( 'ct', $forumid ) ): ?>
                <button id="add_wpftopic" class="wpf-button" data-phrase="<?php echo $phrase ?>">
                    <i class="fas fa-feather-alt"></i> <span><?php echo $phrase ?></span>
                </button>
			<?php elseif( WPF()->current_user_groupid === 4 ) : ?>
                <a href="<?php echo wpforo_login_url() ?>" class="wpf-button"><i class="fas fa-feather-alt"></i> <span><?php echo $phrase ?></span></a>
			<?php endif;
		endif;
	}

	private function msg_box() { ?>
        <div id="wpf-msg-box"></div>
		<?php
	}

	private function spinner() { ?>
        <div id="wpforo-load" class="wpforo-load">
            <div class="wpf-load-ico-wrap"><i class="fas fa-3x fa-spinner fa-spin"></i></div>
            <div class="wpf-load-txt-wrap"><span class="loadtext"></span></div>
        </div>
		<?php
	}

	private function dialog() { ?>
        <div id="wpforo-dialog-extra-wrap">
            <div id="wpforo-dialog-wrap">
                <div id="wpforo-dialog">
                    <div id="wpforo-dialog-header">
                        <strong id="wpforo-dialog-title"></strong>
                        <i id="wpforo-dialog-close" class="fas fa-window-close fa-2x"></i>
                    </div>
                    <div id="wpforo-dialog-body"></div>
                </div>
            </div>
            <div id="wpforo-dialog-backups"></div>
        </div>
		<?php
	}

	private function report_form() { ?>
        <form id="wpforo-report" data-title="<?php echo esc_attr( wpforo_phrase( 'Report to Administration', false ) ) ?>">
            <input type="hidden" id="wpforo-report-postid">
            <label for="wpforo-report-content"></label><textarea id="wpforo-report-content" required placeholder="<?php esc_attr( wpforo_phrase( 'Write message' ) ) ?>"></textarea>
            <input id="wpforo-report-send" type="button" value="<?php wpforo_phrase( 'Send Report' ) ?>" title="Ctrl+Enter">
        </form>
		<?php
	}

	public function do_spoilers( $text ) {
		$text = preg_replace(
			'#(?:<(p|a|span|blockquote|b|i|pre|font|del|strike|strong|em|div|u|center|marquee|table|tr|td|th|tt|sup|sub|s|ul|ol|li|small|code|h\d)(?:[\r\n\t\s\0]+[^<>]*?)?>[\r\n\t\s\0]*)\[[\r\n\t\s\0]*spoiler(?:[\r\n\t\s\0]*title[\r\n\t\s\0]*=[\r\n\t\s\0]*\"([^\"]+)\")?[\r\n\t\s\0]*\](?:[\r\n\t\s\0]*</\1>)#isu',
			'<div class="wpf-spoiler-wrap"><div class="wpf-spoiler-head"><i class="wpf-spoiler-chevron-name">' . wpforo_phrase( 'Spoiler', false ) . '</i><i class="wpf-spoiler-chevron fas fa-chevron-down"></i><div class="wpf-spoiler-title">$2</div></div><div class="wpf-spoiler-body">',
			$text,
			- 1,
			$count1
		);
		$text = preg_replace( '#\[[\r\n\t\s\0]*spoiler(?:[\r\n\t\s\0]*title[\r\n\t\s\0]*=[\r\n\t\s\0]*\"([^\"]+)\")?[\r\n\t\s\0]*\]#isu', '<div class="wpf-spoiler-wrap"><div class="wpf-spoiler-head"><i class="wpf-spoiler-chevron-name">' . wpforo_phrase( 'Spoiler', false ) . '</i><i class="wpf-spoiler-chevron fas fa-chevron-down"></i><div class="wpf-spoiler-title">$1</div></div><div class="wpf-spoiler-body">', $text, - 1, $count2 );
		$text = preg_replace( '#(?:<(p|a|span|blockquote|b|i|pre|font|del|strike|strong|em|div|u|center|marquee|table|tr|td|th|tt|sup|sub|s|ul|ol|li|small|code|h\d)(?:[\r\n\t\s\0]+[^<>]*?)?>[\r\n\t\s\0]*)\[[\r\n\t\s\0]*/[\r\n\t\s\0]*spoiler[\r\n\t\s\0]*\](?:[\r\n\t\s\0]*</\1>)#isu', '</div></div>', $text, $count1 );

		return preg_replace( '#\[[\r\n\t\s\0]*/[\r\n\t\s\0]*spoiler[\r\n\t\s\0]*\]#isu', '</div></div>', $text, $count2 );
	}

	public function please_login() {
		if( ! wpforo_is_bot() ) {
			$html = sprintf(
				'<div class="wpf-please-login">%1$s %2$s</div>',
				wpforo_get_login_or_register_notice_text(),
				wpforo_phrase( 'to reply to this topic.', false, 'lower' )
			);
			echo apply_filters( 'wpforo_login_message_in_topic', $html );
		}
	}

	public function profile_board_panel( $template = '', $active_boardid = null ) {
        if( !wpforo_is_multiboard() ) return '';
        if( ! $template ) $template = WPF()->current_object['template'];
        if( is_null( $active_boardid ) ) $active_boardid = ( wpfkey( WPF()->GET, 'boardid' ) ? (int) WPF()->GET['boardid'] : WPF()->board->get_current( 'boardid' ) );
        $links = '';
        foreach( WPF()->board->get_boards( ['status' => true] ) as $board ){
            if( $template === 'subscriptions' && !wpforo_is_module_enabled( 'subscriptions', $board ) ) continue;
            if( $active_boardid === $board['boardid'] ){
	            $links .= sprintf(
                    '<span class="wpfb-active" title="%1$s">%2$s</span>',
                    $board['title'],
                    wpforo_text($board['title'], 10, false)
                );
            }else{
	            $links .= sprintf(
		            '<span><a href="%1$s" title="%2$s">%3$s</a></span>',
                    '?boardid=' . $board['boardid'],
		            $board['title'],
		            wpforo_text($board['title'], 10, false)
	            );
            }
        }
        return sprintf(
        '<div class="wpf-board-panel">
                <div class="wpf-board-panel-left"><span>%1$s</span></div>
                <div class="wpf-board-panel-right">%2$s</div>
            </div>',
            wpforo_phrase('Board', false),
            $links
        );
    }

	public function profile_activity_panel( $user = [], $boardid = null, $active_filter = '' ) {
        if( is_numeric( $user ) ) $user = WPF()->member->get_member( $user );
        if( !$user ) $user = WPF()->current_object['user'];
        if( is_null( $boardid ) ) $boardid       = wpfkey( WPF()->GET, 'boardid' ) ? (int) WPF()->GET['boardid'] : WPF()->board->get_current( 'boardid' );
        if( !$active_filter )     $active_filter = (string) wpfval( WPF()->GET, 'filter'  );
		$filters = [
			''        => wpforo_phrase( 'All',     false ),
			'topics'  => wpforo_phrase( 'Topics',  false ),
			'replies' => wpforo_phrase( 'Replies', false ),
		];
        $links = '';
        foreach( $filters as $filter => $label ) {
	        $links .= sprintf(
                '<span %1$s><a href="%2$s">%3$s</a></span>',
                ( $active_filter === $filter ? 'class="wpfa-active"' : ''),
                WPF()->member->get_activity_url( $filter, $boardid, $user ),
                $label
            );
        }
        return sprintf(
            '<div class="wpf-activity-panel">
                <div class="wpf-activity-panel-left">
                    <span>%1$s</span>
                    <span>/</span>
                    <span>%2$s</span>
                </div>
                <div class="wpf-activity-panel-right">%3$s</div>
            </div>',
            wpforo_phrase('Topics',  false) . ': ' . $user['topics'],
            wpforo_phrase('Replies', false) . ': ' . ( $user['posts'] - $user['topics'] ),
            $links
        );
    }

    public function profile_favored_panel( $user = [], $boardid = null, $active_filter = '' ) {
        if( is_numeric( $user ) ) $user = WPF()->member->get_member( $user );
        if( !$user ) $user = WPF()->current_object['user'];
        if( is_null( $boardid ) ) $boardid       = wpfkey( WPF()->GET, 'boardid' ) ? (int) WPF()->GET['boardid'] : WPF()->board->get_current( 'boardid' );
	    if( !$active_filter )     $active_filter = (string) wpfval( WPF()->current_object, 'filter'  );
	    $filters = [
		    'bookmarks' => wpforo_phrase( 'Bookmarks', false ),
		    'likes'     => wpforo_phrase( 'Likes', false ),
		    'dislikes'  => wpforo_phrase( 'Dislikes', false ),
	    ];
        $links = '';
        foreach( $filters as $filter => $label ) {
	        $links .= sprintf(
                '<span %1$s><a href="%2$s">%3$s</a></span>',
                ( $active_filter === $filter ? 'class="wpfa-active"' : ''),
                WPF()->member->get_favored_url( $filter, $boardid, $user ),
                $label
            );
        }
        return sprintf(
            '<div class="wpf-activity-panel">
                <div class="wpf-activity-panel-left">
                    <span>%1$s</span>
                </div>
                <div class="wpf-activity-panel-right">%2$s</div>
            </div>',
            wpforo_phrase('Total Posts',  false) . ': ' . WPF()->current_object['items_count'],
            $links
        );
    }

	private function cantlogin_page() {
        $contact_form_phrase = ( wpforo_setting( 'authorization', 'manually_approval_contact_form' ) ) ? wpforo_phrase('Please use the contact form below if you want to contact the website administrator.', false ) : '';
        $html = sprintf(
	        '<div class="wpflm-text">%1$s %2$s</div>',
	        wpforo_phrase( 'You have been automatically signed out from the website. Your account is awaiting approval.', false ),
            $contact_form_phrase
        );
        if( wpforo_setting( 'authorization', 'manually_approval_contact_form' ) ){
            $html .= sprintf(
	            '<div class="wpflm-form"><form method="POST" action="%1$s">
                        <input type="hidden" name="wpfaction" value="cantlogin_contact">
                        <input type="hidden" name="user_login" value="%2$s">
                    <textarea name="msg" placeholder="%3$s"></textarea>
                    <input type="submit" value="%4$s">
                </form></div>',
                wpforo_login_url(),
	            WPF()->ram_cache->get( 'USER_LOGIN_REFERER' ),
	            wpforo_phrase( 'Your message to the website administrator...', false ),
	            wpforo_phrase( 'Send', false )
            );
        }

        return '<div class="wpf-logout-message">' . apply_filters( 'wpforo_logout_message', $html ) . '</div>';
    }

	public function change_cover_button( $user ) {
        if( WPF()->current_userid && ( wpforo_bigintval( wpfval($user, 'userid') ) === WPF()->current_userid || WPF()->usergroup->can( 'em' ) ) ){
	        return sprintf(
		        '<div class="wpf-edit-cover" wpf-tooltip="%1$s" wpf-tooltip-position="top" wpf-tooltip-size="small"><i class="fas fa-images"></i></div>',
		        wpforo_phrase('Change Cover Image', false)
	        );
        }

        return '';
    }

}
