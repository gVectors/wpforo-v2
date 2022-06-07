<?php

namespace wpforo\classes;

use stdClass;
use WP_Error;
use wpforo\admin\listtables\Members as MembersListTable;
use wpforo\modules\reactions\Reactions;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


class Members {
    private $default;
	private $fields;
	private $countries;
	private $timezones;
	public  $login_min_length;
	public  $login_max_length;
	public  $pass_min_length;
	public  $pass_max_length;
	public  $list_table;
    private $touched_userids = [];

	function __construct() {
		$this->init_defaults();
		$this->init_hooks();
	}

	private function init_defaults() {
		$this->login_min_length = 3;
		$this->login_max_length = 30;
		$this->pass_min_length  = 6;
		$this->pass_max_length  = 20;
		$this->default = new stdClass();
		$this->default->member = [
			'user_login'          => '',
			'user_nicename'       => '',
			'user_email'          => '',
			'user_url'            => '',
			'user_registered'     => '0000-00-00 00:00:00',
			'user_activation_key' => '',
			'display_name'        => '',

            'first_name'          => '',
            'last_name'           => '',

			'userid'              => 0,
			'title'               => '',
			'groupid'             => 0,
            'secondary_groupids'  => [],
			'groupids'            => [],
			'avatar'              => '',
			'cover'               => '',

			'posts'               => 0,
			'topics'              => 0,
			'questions'           => 0,
			'answers'             => 0,
			'comments'            => 0,
            'reactions_in'        => [],
            'reactions_out'       => [],
			'points'              => 0,
			'custom_points'       => 0,

			'online_time'         => 0,
			'timezone'            => 'UTC+0',
			'location'            => '',
			'signature'           => '',
			'occupation'          => '',
			'about'               => '',
			'status'              => 'inactive',
			'is_email_confirmed'  => 0,
            'is_mention_muted'    => 0,

			'fields'              => [],

			'group_name'          => '',
			'group_color'         => '',

			'profile_url' => '',
			'rating'      => [
				'level'   => 0,
				'percent' => 0,
				'color'   => $this->rating( 0, 'color' ),
				'title'   => $this->rating( 0, 'title' ),
				'badge'   => $this->rating( 0, 'icon' ),
			],
        ];
		$this->default->action = [
			'label'                => 'Action Button',
			'ico'                  => '<i class="fas fa-user"></i>',
			'callback_for_can'     => '',
			'callback_for_get_url' => '',
			'data'                 => [],
			'confirm_msg'          => '',
		];
	}

	private function init_hooks() {
		if( is_admin() ) add_action( 'wpforo_after_init', [ $this, 'init_list_table' ] );
		add_action( 'delete_user_form',     [ $this, 'show_delete_form' ], 10, 2 );
		add_action( 'register_new_user',    [ $this, 'after_register_new_user' ] );
		add_action( 'after_password_reset', [ $this, 'after_password_reset' ] );
		add_action( 'wp_login',             [ $this, 'wp_login' ], 10, 2 );
		add_action( 'set_current_user',     [ $this, 'init_current_user' ] );
		add_action( 'init', function() { if( ! WPF()->current_userid ) $this->init_current_user(); } );
		add_action( 'set_logged_in_cookie', function( $logged_in_cookie ) {
			$cookie              = explode( '|', $logged_in_cookie );
			WPF()->session_token = (string) wpfval( $cookie, 2 );
		} );

        add_action( 'wpforo_after_add_topic',        [$this, 'after_add_topic'] );
        add_action( 'wpforo_after_delete_topic',     [$this, 'after_delete_topic'] );
		add_action( 'wpforo_topic_status_update',    [$this, 'after_topic_status_update'] );
		add_action( 'wpforo_before_merge_topic',     [$this, 'before_merge_topic'], 10, 3 );
        add_action( 'wpforo_after_merge_topic',      [$this, 'after_merge_topic'] );
        add_action( 'wpforo_after_move_topic',       [$this, 'after_move_topic'] );

		add_action( 'wpforo_after_add_post',         [$this, 'after_add_post'] );
		add_action( 'wpforo_after_delete_post',      [$this, 'after_delete_post'] );
		add_action( 'wpforo_post_status_update',     [$this, 'after_post_status_update'] );

        add_action( 'wpforo_after_add_reaction',     [$this, 'after_add_reaction'] );
        add_action( 'wpforo_after_edit_reaction',    [$this, 'after_edit_reaction'], 10, 2 );
        add_action( 'wpforo_before_delete_reaction', [$this, 'before_delete_reaction'], 10, 2 );
        add_action( 'wpforo_after_delete_reaction',  [$this, 'after_delete_reaction'] );

		add_action( 'wpforo_after_delete_user',      [$this, 'after_delete_user'], 11, 2 );
	}

	public function init_list_table() {
		if( wpfval( $_GET, 'page' ) === wpforo_prefix_slug( 'members' ) ) {
			$this->list_table = new MembersListTable();
			$this->list_table->prepare_items();
		}
	}

	/**
	 * @param array $member
	 *
	 * @return array
	 */
	public function decode( $member ) {
		$reaction_types               = array_map( '__return_zero', array_flip( Reactions::get_type_list() ) );
		$member                       = array_merge( $this->default->member, (array) $member );
		$member['userid']             = wpforo_bigintval( $member['userid'] );
		$member['first_name']         = trim( strip_tags( $member['first_name'] ) );
		$member['last_name']          = trim( strip_tags( $member['last_name'] ) );
		$member['groupid']            = intval( $member['groupid'] );
		$member['secondary_groupids'] = array_map( 'intval', (array) ( is_scalar( $member['secondary_groupids'] ) ? explode( ',', $member['secondary_groupids'] ) : $member['secondary_groupids'] ) );
		$member['secondary_groupids'] = array_diff( $member['secondary_groupids'], (array) $member['groupid'] );
		$member['groupids']           = array_unique( array_filter( array_merge( (array) $member['groupid'], $member['secondary_groupids'] ) ) );
		$member['avatar']             = preg_replace( '#^https?://#iu', '//', esc_url( $member['avatar'] ) );
		$member['cover']              = preg_replace( '#^https?://#iu', '//', esc_url( $member['cover'] ) );
		$member['topics']             = intval( $member['topics'] );
		$member['posts']              = intval( $member['posts'] );
		$member['questions']          = intval( $member['questions'] );
		$member['answers']            = intval( $member['answers'] );
		$member['comments']           = intval( $member['comments'] );

		$member['reactions_in'] = array_map( 'intval', (array) ( wpforo_is_json( $member['reactions_in'] ) ? json_decode( $member['reactions_in'], true ) : $member['reactions_in'] ) );
		$member['reactions_in']['__ALL__'] = 0;
        foreach( $member['reactions_in'] as $r ) $member['reactions_in']['__ALL__'] += $r;
		$member['reactions_in'] = array_merge( $reaction_types, $member['reactions_in'] );

		$member['reactions_out'] = array_map( 'intval', (array) ( wpforo_is_json( $member['reactions_out'] ) ? json_decode( $member['reactions_out'], true ) : $member['reactions_out'] ) );
		$member['reactions_out']['__ALL__'] = 0;
		foreach( $member['reactions_out'] as $r ) $member['reactions_out']['__ALL__'] += $r;
		$member['reactions_out'] = array_merge( $reaction_types, $member['reactions_out'] );

		$member['custom_points'] = floatval( $member['custom_points'] );
		$member['points'] = $member['custom_points'] ?: $this->calc_points( $member );
		$member['rating'] = $this->calc_rating( $member['points'] );

		$member['online_time']        = (int) ( !is_numeric( $member['online_time'] ) ? strtotime( $member['online_time'] ) : $member['online_time'] );
		$member['is_email_confirmed'] = (bool) (int) $member['is_email_confirmed'];
		$member['is_mention_muted']   = (bool) (int) $member['is_mention_muted'];

		$member['title']       = trim( strip_tags( $member['title'] ) );
		$member['profile_url'] = $this->get_profile_url( $member );

		$member['fields'] = (array) ( wpforo_is_json( $member['fields'] ) ? json_decode( $member['fields'], true ) : $member['fields'] );
		$member           = wpforo_array_ordered_intersect_key( $member, $this->default->member );

		return array_merge( $member['fields'], $member );
	}

	/** @TODO use this function for member all insert|update queries
	 * @param $member
	 *
	 * @return array
	 */
	private function encode( $member ) {
		$member                       = $this->decode( $member );
		$member['secondary_groupids'] = implode( ',', $member['secondary_groupids'] );
		$member['groupids']           = implode( ',', $member['groupids'] );
        unset( $member['reactions_in']['__ALL__'], $member['reactions_out']['__ALL__'] );
		$member['reactions_in']       = json_encode( $member['reactions_in'] );
		$member['reactions_out']      = json_encode( $member['reactions_out'] );
		$member['is_email_confirmed'] = intval( $member['is_email_confirmed'] );
		$member['is_mention_muted']   = intval( $member['is_mention_muted'] );
		$member['fields']             = json_encode( $member['fields'] );

		return $member;
	}

	private function fix_action( $action ){
		return array_merge( $this->default->action, $action );
	}

	private function _get_actions( $user ) {
		$userid = wpforo_bigintval( $user['userid'] );
		$actions = [
			'edit' => [
				'label'            => wpforo_phrase( 'Edit Account Information', false ),
				'ico'              => '<i class="fas fa-user-gear"></i>',
				'callback_for_can' => function(){
					return WPF()->usergroup->can('em');
				},
				'callback_for_get_url' => function() use ( $user ){
					return WPF()->member->get_profile_url( $user, 'account' );
				}
			],
			'edit_dash'    => [
				'label'            => wpforo_phrase( 'Edit User in Dashboard', false ),
				'ico'              => '<i class="fas fa-user-pen"></i>',
				'callback_for_can' => function(){
					return current_user_can( 'administrator' );
				},
				'callback_for_get_url' => function() use ( $userid ) {
					return admin_url( "user-edit.php?user_id=" . $userid );
				}
			],
			'ban'          => [
				'label'            => ( $user['status'] === 'banned' ? wpforo_phrase( 'Unban User', false ) : wpforo_phrase( 'Ban User', false ) ),
				'ico'              => '<i class="fas fa-user-lock"></i>',
				'callback_for_can' => function() use ( $userid ){
					return $userid !== WPF()->current_userid && (WPF()->usergroup->can('bm') || wpforo_current_user_is( 'admin' ));
				},
				'callback_for_get_url' => function() use ( $userid ){
					return '';
				},
				'data' => [
					'currentstate'   => (int) ($user['status'] === 'banned'),
                    'active_label'   => wpforo_phrase( 'Unban User', false ),
                    'inactive_label' => wpforo_phrase( 'Ban User', false ),
				],
				'confirm_msg' => wpforo_phrase( 'Please confirm you want to do this action?', false ),
			],
			'delete'       => [
				'label'            => wpforo_phrase( 'Delete Account', false ),
				'ico'              => '<i class="fas fa-user-xmark"></i>',
				'callback_for_can' => function() use ($userid){
					return (
						       ( current_user_can( 'administrator' ) || WPF()->usergroup->can( 'dm' ) )
						       && WPF()->perm->user_can_manage_user( WPF()->current_userid, $userid )
					       )
					       && (
						       $userid !== WPF()->current_userid
						       || ( !is_super_admin( $userid ) && apply_filters('wpforo_can_user_self_delete', true) )
					       );
				},
				'callback_for_get_url' => function() use ( $userid ){
					return trim( WPF()->member->get_profile_url( $userid ), '/' ) . '/?wpfaction=user_delete';
				},
				'confirm_msg' => wpforo_phrase( 'Please confirm you want to do this action?', false ),
			],
			'delete_dash'       => [
				'label'            => wpforo_phrase( 'Delete User in Dashboard', false ),
				'ico'              => '<i class="fa-solid fa-user-large-slash"></i>',
				'callback_for_can' => function() use ($userid){
                    return (current_user_can('administrator')
                           && WPF()->perm->user_can_manage_user( WPF()->current_userid, $userid )
                           && $userid !== WPF()->current_userid);
				},
				'callback_for_get_url' => function() use ( $userid ){
					return admin_url( wp_nonce_url( "users.php?action=delete&user=" . $userid, 'bulk-users' ) );
				}
			],
		];

		return array_map( [$this, 'fix_action'], apply_filters( 'wpforo_member_get_actions', $actions, $user ) );
	}

	public function get_action( $user, $key ) {
		$actions = $this->_get_actions( $user );
		return wpfval( $actions, $key );
	}

	public function get_actions( $user ) {
		$actions = $this->_get_actions( $user );
		return array_filter($actions, function( $action ) use ( $user ){
			if( is_callable( $action['callback_for_can'] ) ) return call_user_func( $action['callback_for_can'] );
			return true;
		});
	}

	private function add_profile( $args ) {
		if( !$userid = wpforo_bigintval( wpfval( $args, 'userid' ) ) ) return false;
		$sql = "INSERT IGNORE INTO `" . WPF()->tables->profiles . "` (`userid`, `title`, `groupid`, `timezone`, `about`) VALUES ( %d, %s, %d, %s, %s )";
		$sql = WPF()->db->prepare(
			$sql,
			$userid,
			sanitize_text_field( wpfval( $args, 'title' ) ) ?: wpforo_setting( 'profiles', 'default_title' ),
			(int) wpfval( $args, 'groupid' ) ?: WPF()->usergroup->default_groupid,
			sanitize_text_field( wpfval( $args, 'timezone' ) ) ?: 'UTC+0',
			stripslashes( wpforo_kses( trim( wpfval( $args, 'about' ) ), 'user_description' ) ) ?: ''
		);

		$r = WPF()->db->query( $sql );
		$this->reset( $userid );
		return $r;
	}

	function create( $data ) {
		if( ! wpforo_setting( 'authorization', 'user_register' ) ) {
			WPF()->notice->add( 'User registration is disabled.', 'error' );

			return false;
		}

		$user_fields = [];
		if( ! empty( $data ) ) {
			if( wpfval( $data, 'wpfreg' ) ) {
				$user_fields = $data['wpfreg'];
			} else {
				$user_fields = $data;
			}
		}

		//-- START -- copied from update code
		//Define $user
		$user = $user_fields;

		//Define $userid
		$userid = intval( wpfval( $user, 'userid' ) );

		//Check custom fields and merge to $user array
		if( wpfval( $data, 'data' ) && is_array( $data['data'] ) && ! empty( $data['data'] ) ) {
			$custom_fields = $data['data'];
			$user          = array_merge( $custom_fields, $user );
		}

		//Check file uploading custom fields and merge to $user array
		$file_data   = isset( $_FILES['data']['name'] ) && $_FILES['data']['name'] && is_array( $_FILES['data']['name'] ) ? array_filter( $_FILES['data']['name'] ) : [];
		$file_fields = WPF()->form->prepare_file_args( $file_data, $userid );
		if( wpfval( $file_fields, 'fields' ) ) {
			$user = array_merge( $file_fields['fields'], $user );
		}

		//Validate fields
		$form_fields = $this->get_register_fields();
		$result      = WPF()->form->validate( $user, $form_fields );
		if( wpfval( $result, 'error' ) ) {
			if( wpforo_is_admin() && wpfval( $result['error'], 0 ) ) {
				wp_die( $result['error'][0] );
			} else {
				WPF()->notice->add( $result['error'], 'error' );

				return false;
			}
		}
		//-- END -- copied from update code

		$user_fields = apply_filters( 'wpforo_create_profile', $user_fields );

		if( ! $user_fields ) {
			WPF()->notice->add( 'Empty fields', 'error' );

			return false;
		} elseif( wpfval( $user_fields, 'error' ) ) {
			WPF()->notice->add( sanitize_text_field( $user_fields['error'] ), 'error' );

			return false;
		}

		$this->login_min_length = apply_filters( 'wpforo_login_min_length', $this->login_min_length );
		$this->login_max_length = apply_filters( 'wpforo_login_max_length', $this->login_max_length );
		$this->pass_min_length  = apply_filters( 'wpforo_pass_min_length', $this->pass_min_length );
		$this->pass_max_length  = apply_filters( 'wpforo_pass_max_length', $this->pass_max_length );

		if( ! wpforo_setting( 'authorization', 'user_register_email_confirm' ) && ! empty( $user_fields ) && is_array( $user_fields ) && ! empty( $user_fields['user_pass1'] ) ) {

			remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
			remove_action( 'register_new_user', 'wpforo_send_new_user_notifications' );
			add_action( 'register_new_user', function( $user_id ) {
				wpforo_send_new_user_notifications( $user_id, 'admin' );
			} );

			do_action( 'wpforo_create_profile_before', $user_fields );

			$errors = new WP_Error();

			extract( $user_fields, EXTR_OVERWRITE );
			$sanitized_user_login = sanitize_user( $user_login );
			$user_email           = apply_filters( 'user_registration_email', $user_email );
			$user_pass1           = trim( substr( $user_pass1, 0, 100 ) );
			$user_pass2           = trim( substr( $user_pass2, 0, 100 ) );
			$illegal_user_logins  = array_map( 'strtolower', (array) apply_filters( 'illegal_user_logins', [] ) );

			if( $sanitized_user_login == '' ) {
				$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter a username.' ) );
				WPF()->notice->add( 'Username is missed.', 'error' );

				return false;
			} elseif( ! validate_username( $user_login ) ) {
				$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.' ) );
				WPF()->notice->add( 'Illegal character in username.', 'error' );

				return false;
			} elseif( strlen( $user_login ) < $this->login_min_length || strlen( $user_login ) > $this->login_max_length ) {
				WPF()->notice->add( 'Username length must be between %d characters and %d characters.', 'error', [ $this->login_min_length, $this->login_max_length ] );

				return false;
			} elseif( username_exists( $sanitized_user_login ) ) {
				$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered. Please choose another one.' ) );
				WPF()->notice->add( 'Username exists. Please insert another.', 'error' );

				return false;
			} elseif( in_array( strtolower( $sanitized_user_login ), $illegal_user_logins ) ) {
				$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: Sorry, that username is not allowed.' ) );
				WPF()->notice->add( 'ERROR: invalid_username. Sorry, that username is not allowed. Please insert another.', 'error' );

				return false;
			} elseif( $user_email == '' ) {
				$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please type your email address.' ) );
				WPF()->notice->add( 'Insert your Email address.', 'error' );

				return false;
			} elseif( ! is_email( $user_email ) ) {
				$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.' ) );
				WPF()->notice->add( 'Invalid Email address', 'error' );

				return false;
			} elseif( email_exists( $user_email ) ) {
				$errors->add( 'email_exists', __( '<strong>ERROR</strong>: This email is already registered, please choose another one.' ) );
				WPF()->notice->add( 'Email address exists. Please insert another.', 'error' );

				return false;
			} elseif( strlen( $user_pass1 ) < $this->pass_min_length || strlen( $user_pass1 ) > $this->pass_max_length ) {
				WPF()->notice->add( 'Password length must be between %d characters and %d characters.', 'error', [ $this->pass_min_length, $this->pass_max_length ] );

				return false;
			} elseif( $user_pass1 != $user_pass2 ) {
				WPF()->notice->add( 'Password mismatch.', 'error' );

				return false;
			} else {
				do_action( 'register_post', $sanitized_user_login, $user_email, $errors );
				$errors = apply_filters( 'registration_errors', $errors, $sanitized_user_login, $user_email );
				if( $errors->get_error_code() ) {
					$user_fields = [];
					foreach( $errors->errors as $u_err ) $user_fields[] = $u_err[0];
					WPF()->notice->add( $user_fields, 'error' );

					return false;
				}
				$user_id = wp_create_user( $sanitized_user_login, $user_pass1, $user_email );
				if( ! is_wp_error( $user_id ) && $user_id ) {
					do_action( 'register_new_user', $user_id );
					do_action( 'wpforo_create_user_after', $data );
					wp_signon( [ 'user_login' => $sanitized_user_login, 'user_password' => $user_pass1 ] );
					WPF()->notice->clear();
					WPF()->notice->add( 'Success!', 'success' );

					return $user_id;
				}
			}
		} elseif( wpforo_setting( 'authorization', 'user_register_email_confirm' ) && ! empty( $user_fields['user_login'] ) && ! empty( $user_fields['user_email'] ) ) {
			if( strlen( $user_fields['user_login'] ) < $this->login_min_length || strlen( $user_fields['user_login'] ) > $this->login_max_length ) {
				WPF()->notice->add( 'Username length must be between %d characters and %d characters.', 'error', [ $this->login_min_length, $this->login_max_length ] );

				return false;
			}
			if( is_multisite() && apply_filters( 'wpforo_mu_signup', false ) ) {
				wpmu_signup_user( $user_fields['user_login'], $user_fields['user_email'] );
				WPF()->notice->clear();
				WPF()->notice->add( 'Success! Please check your mail for confirmation.', 'success' );

				return true;
			} else {
				$user_id = register_new_user( $user_fields['user_login'], $user_fields['user_email'] );
			}
			if( ! is_wp_error( $user_id ) && $user_id ) {
				do_action( 'wpforo_create_user_after', $data );
				WPF()->notice->clear();
				WPF()->notice->add( 'Success! Please check your mail for confirmation.', 'success' );

				return $user_id;
			}
		}

		if( ! empty( $user_id->errors ) ) {
			$user_fields = [];
			foreach( $user_id->errors as $u_err ) $user_fields[] = $u_err[0];
			WPF()->notice->add( $user_fields, 'error' );

			return false;
		}

		WPF()->notice->add( 'Registration Error', 'error' );

		return false;
	}

	/**
	 * Updates user data
	 *
	 * @param array $data - User data as a simple array( field => value ) OR
	 *                      $data['member'] = array( field => value )   user and profile fields - Account form
	 *                      $data['wpfreg'] = array( field => value )   user and profile fields - Registration form
	 *                      $data['data']   = array( field => value )   user custom fields
	 *
	 *                      $data['member']['userid'] or $data['userid'] is required
	 *
	 * @param string|array $type User data update types (comma separated)
	 *                      $type = 'full'           user fields, profile fields, custom fields
	 *                      $type = 'user'           only user fields (wp_users table)
	 *                      $type = 'profile'        only profile fields (wp_wpforo_profiles table)
	 *                      $type = 'custom_fields'  only custom fields (wp_wpforo_profiles table > fields column)
	 *                      $type = 'profile, custom_fields'
	 *
	 * @param boolean $check_permissions Whether check the current editor permissions or not
	 *
	 * @return  false|array  wpForo User array
	 * @since  1.5.0
	 *
	 */
	public function update( $data, $type = 'full', $check_permissions = true ) {
		$type = (array) $type;

		switch( WPF()->current_object['template'] ) {
			case 'register':
				$form        = 'wpfreg';
				$form_fields = $this->get_register_fields();
			break;
			default:
				$form        = 'member';
				$form_fields = $this->get_account_fields();
			break;
		}
		if( ! $form_fields ) {
			WPF()->notice->add( 'Form template not found', 'error' );

			return false;
		}

		if( ! wpfkey( $data, $form ) ) {
			if( in_array( 'custom_fields', $type ) && count( $type ) === 1 ) {
				if( ! wpfval( $data, 'data' ) ) {
					$data['data'] = $data;
					if( wpfkey( $data, 'data', 'userid' ) ) unset( $data['data']['userid'] );
				}
			} else {
				$data[ $form ] = $data;
			}
		}
		if( wpfval( $data, 'userid' ) && ! wpfval( $data, $form, 'userid' ) ) $data[ $form ]['userid'] = $data['userid'];

		if( wpfval( $data, $form, 'userid' ) ) {
			$result_user    = true;
			$result_fields  = true;
			$result_profile = true;
			$custom_fields  = [];

			//Define $user
			$user = $data[ $form ];

			//Define $userid
			$userid = intval( $data[ $form ]['userid'] );

			//Check profile editor permissions
			if( $check_permissions ) WPF()->perm->can_edit_user( $userid );

			//Check custom fields and merge to $user array
			if( wpfkey( $data, 'data' ) && is_array( $data['data'] ) && ! empty( $data['data'] ) ) {
				$custom_fields = $data['data'];
				$user          = array_merge( $custom_fields, $user );
			}

			//Check file uploading custom fields and merge to $user array
			$file_data   = isset( $_FILES['data']['name'] ) && $_FILES['data']['name'] && is_array( $_FILES['data']['name'] ) ? array_filter( $_FILES['data']['name'] ) : [];
			$file_fields = WPF()->form->prepare_file_args( $file_data, $userid );
			if( wpfval( $file_fields, 'fields' ) ) {
				$user          = array_merge( $file_fields['fields'], $user );
				$custom_fields = ( ! empty( $custom_fields ) ) ? array_merge( $custom_fields, $file_fields['fields'] ) : $file_fields['fields'];
			}

			//Hooks
			$user = apply_filters( 'wpforo_update_profile', $user );
			do_action( 'wpforo_update_profile_before', $user );
			if( wpfval( $user, 'error' ) || empty( $user ) ) {
				$error_message = ( wpfval( $user, 'error_message' ) ) ? sanitize_text_field( $user['error_message'] ) : 'Unknown error in profile editing hook. Please disable all plugins and check it again.';
				WPF()->notice->add( $error_message, 'error' );

				return false;
			}

			//Validate fields
			$result = WPF()->form->validate( $user, $form_fields );
			if( wpfval( $result, 'error' ) ) {
				if( is_admin() && wpfval( $result['error'], 0 ) ) {
					wp_die( $result['error'][0] );
				} else {
					WPF()->notice->add( $result['error'], 'error' );

					return false;
				}
			}

			//Sanitize fields
			$user = WPF()->form->sanitize( $user, $form_fields );

			//Update User Fields
			if( ! empty( $user ) && ( in_array( 'full', $type ) || in_array( 'user', $type ) ) ) {
				$result_user = $this->update_user_fields( $userid, $user, false );
			}

			//Update Profile Fields
			if( ! empty( $user ) && ( in_array( 'full', $type ) || in_array( 'profile', $type ) ) ) {
				$result_profile = $this->update_profile_fields( $userid, $user, false );
			}

			//Password field validation and update
			$result_password = WPF()->form->validate_password( $user );
			if( $result_password === false ) {
				$result_password = true;
			}
			if( wpfval( $result_password, 'error' ) ) {
				WPF()->notice->add( $result_password['error'], 'error' );
				$result_password = false;
			}
			if( $result_password && wpfval( $user, 'old_pass' ) && wpfval( $user, 'user_pass1' ) ) {
				$result_password = $this->change_password( $user['old_pass'], $user['user_pass1'], $userid );
			}

			//Upload avatar
			if( wpfval( $user, 'avatar_type' ) === 'custom' ) {
				$this->upload_avatar( $userid );
			}

			//Update Custom Fields
			if( ! empty( $custom_fields ) && ( in_array( 'full', $type ) || in_array( 'custom_fields', $type ) ) ) {
				$result_fields = $this->update_custom_fields( $userid, $custom_fields, false );
			}

			//Upload Files from Custom Fields
			if( wpfval( $file_fields, 'files' ) ) {
				$this->upload_files( $file_fields['files'] );
			}

			//Reset this user cache
			$this->reset( $userid );

			if( $result_user === false || $result_profile === false || $result_fields === false || $result_password === false ) {
				return false;
			} else {
				WPF()->notice->add( 'Profile updated successfully', 'success' );
				do_action( 'wpforo_update_profile_after', $user );

				return $user;
			}
		}

		return false;
	}

	public function update_user_fields( $userid, $data, $check_permissions = true ) {
		$result_user = true;
		if( $check_permissions ) WPF()->perm->can_edit_user( $userid );

		//User Fields
		if( wpfkey( $data, 'display_name' ) ) {
			$user_fields['display_name'] = $data['display_name'];
			$user_fields_types[]         = '%s';
		}
		if( wpfkey( $data, 'user_email' ) ) {
			$user_fields['user_email'] = $data['user_email'];
			$user_fields_types[]       = '%s';
		}
		if( wpfkey( $data, 'user_nicename' ) ) {
			if( ! wpfval( $data, 'user_nicename' ) ) {
				$user_info             = get_userdata( $userid );
				$data['user_nicename'] = sanitize_title( sanitize_user( $user_info->user_nicename, true ) );
			}
			$user_fields['user_nicename'] = $data['user_nicename'];
			$user_fields_types[]          = '%s';
			update_user_meta( $userid, 'nickname', $data['user_nicename'] );
		}
		if( wpfkey( $data, 'first_name' ) ) {
			update_user_meta( $userid, 'first_name', $data['first_name'] );
		}
		if( wpfkey( $data, 'last_name' ) ) {
			update_user_meta( $userid, 'last_name', $data['last_name'] );
		}
		if( wpfkey( $data, 'user_url' ) ) {
			$user_fields['user_url'] = $data['user_url'];
			$user_fields_types[]     = '%s';
		}

		if( ! empty( $user_fields ) && ! empty( $user_fields_types ) ) {
			$result_user = WPF()->db->update( WPF()->db->users, $user_fields, [ 'ID' => $userid ], $user_fields_types, [ '%d' ] );
			if( false === $result_user ) {
				WPF()->notice->add( 'User data update failed', 'error' );
				if( WPF()->db->last_error ) {
					WPF()->notice->add( sanitize_text_field( WPF()->db->last_error ), 'error' );
				}
			} else {
				clean_user_cache( $userid );
			}
		}

        if( $result_user ) $this->reset( $userid );

		return $result_user;
	}

	public function update_profile_field( $userid, $key, $value ) {
		$r = false;
		if( $key && ! is_null( $value ) ) {
            $member = $this->encode( [ $key => $value ] );
            if( wpfkey( $member, $key ) ){
	            $r = WPF()->db->update(
		            WPF()->tables->profiles,
		            [ $key     => $value ],
		            [ 'userid' => $userid ],
		            [ wpforo_db_data_format( $member[ $key ] ) ],
		            [ '%d' ]
	            );

	            if( $r ) $this->reset( $userid );
            }
		}

		return $r;
	}

	public function update_profile_fields( $userid, $data, $check_permissions = true ) {

		$result_profile = true;

		if( $check_permissions ) {
			WPF()->perm->can_edit_user( $userid );
		}

        $member = $this->encode( $data );

		$profile_fields       = [];
		$profile_fields_types = [];

		//Profile Fields
		if( wpfkey( $data, 'groupid' ) ) {
			$profile_fields['groupid'] = $member['groupid'];
			$profile_fields_types[]    = '%d';
		}
		if( wpfkey( $data, 'title' ) ) {
			$profile_fields['title'] = $member['title'];
			$profile_fields_types[]  = '%s';
		}
		if( wpfkey( $data, 'signature' ) ) {
			$profile_fields['signature'] = $member['signature'];
			$profile_fields_types[]      = '%s';
		}
		if( wpfkey( $data, 'about' ) ) {
			$profile_fields['about'] = $member['about'];
			$profile_fields_types[]  = '%s';
			update_user_meta( $userid, 'description', $member['about'] );
		}
		if( wpfkey( $data, 'occupation' ) ) {
			$profile_fields['occupation'] = $member['occupation'];
			$profile_fields_types[]       = '%s';
		}
		if( wpfkey( $data, 'location' ) ) {
			$profile_fields['location'] = $member['location'];
			$profile_fields_types[]     = '%s';
		}
		if( wpfkey( $data, 'timezone' ) ) {
			$profile_fields['timezone'] = $member['timezone'];
			$profile_fields_types[]     = '%s';
		}
		if( wpfkey( $data, 'avatar_type' ) && $data['avatar_type'] !== 'gravatar' && wpfval( $data, 'avatar_url' ) ) {
			$profile_fields['avatar'] = esc_url( trim( $data['avatar_url'] ) );
			$profile_fields_types[]   = '%s';
		}
		if( wpfkey( $data, 'avatar_type' ) && $data['avatar_type'] === 'gravatar' ) {
			$profile_fields['avatar'] = '';
			$profile_fields_types[]   = '%s';
		}
		if( wpfkey( $data, 'cover' ) ) {
			$profile_fields['cover'] = $member['cover'];
			$profile_fields_types[]  = '%s';
		}
		if( wpfkey( $data, 'secondary_groupids' ) ) {
			$profile_fields['secondary_groupids'] = $member['secondary_groupids'];
			$profile_fields_types[]               = '%s';
		}
		if( wpfkey( $data, 'custom_points' ) ) {
			$profile_fields['custom_points'] = $member['custom_points'];
			$profile_fields_types[] = '%d';
		}
		if( wpfkey( $data, 'online_time' ) ) {
			$profile_fields['online_time'] = $member['online_time'];
			$profile_fields_types[] = '%d';
		}
		if( wpfkey( $data, 'status' ) ) {
			$profile_fields['status'] = $member['status'];
			$profile_fields_types[]   = '%s';
		}
		if( wpfkey( $data, 'is_email_confirmed' ) ) {
			$profile_fields['is_email_confirmed'] = $member['is_email_confirmed'];
			$profile_fields_types[]               = '%d';
		}
        if( wpfkey( $data, 'is_mention_muted' ) ) {
			$profile_fields['is_mention_muted'] = $member['is_mention_muted'];
			$profile_fields_types[]             = '%d';
		}

		$profile_fields = apply_filters( 'wpforo_before_update_profile_fields', $profile_fields, $userid, $data, $check_permissions );

		if( ! empty( $profile_fields ) ) {
			$result_profile = WPF()->db->update( WPF()->tables->profiles, $profile_fields, [ 'userid' => intval( $userid ) ], $profile_fields_types, [ '%d' ] );
			if( false === $result_profile ) {
				WPF()->notice->add( 'User profile update failed', 'error' );
				if( WPF()->db->last_error ) {
					WPF()->notice->add( sanitize_text_field( WPF()->db->last_error ), 'error' );
				}
			}
		}
		do_action( 'wpforo_update_profile_fields', $userid, $data, $result_profile );

        if( $result_profile ) $this->reset( $userid );

		return $result_profile;
	}

	public function update_custom_field( $userid, $field_name, $field_value = null ) {
		$result = false;
		$fields = $this->get_custom_fields( $userid );
		if( ! empty( $fields ) && $field_name && ! is_null( $field_value ) ) {
			foreach( $fields as $name => $value ) {
				if( $name == $field_name ) {
					$fields[ $name ] = $field_value;
				}
			}
			$custom_fields = array_filter( $fields );
			$custom_fields = wpforo_unslashe( $custom_fields );
			$custom_fields = wpforo_decode( $custom_fields );
			$custom_fields = wpforo_encode( $custom_fields );
			$fields_json   = json_encode( $custom_fields, JSON_UNESCAPED_UNICODE );
			$sql           = "UPDATE `" . WPF()->tables->profiles . "` SET `fields` = %s WHERE `userid` = %d;";
			$sql           = WPF()->db->prepare( $sql, $fields_json, $userid );
			$result        = WPF()->db->query( $sql );
		}

        if( $result ) $this->reset( $userid );

		return $result;
	}

	public function update_custom_fields( $userid, $custom_fields, $check_permissions = true ) {

		$result_fields = true;

		if( ! empty( $custom_fields ) ) {
			if( $check_permissions ) {
				WPF()->perm->can_edit_user( $userid );
			}
			$custom_fields = wpforo_unslashe( $custom_fields );
			$custom_fields = wpforo_decode( $custom_fields );
			$custom_fields = wpforo_encode( $custom_fields );
			$data_old      = $this->get_custom_fields( $userid );
			if( $data_old && is_array( $data_old ) ) {
				$custom_fields = wp_parse_args( $custom_fields, $data_old );
			}
			$fields_json   = json_encode( $custom_fields, JSON_UNESCAPED_UNICODE );
			$sql           = "UPDATE `" . WPF()->tables->profiles . "` SET `fields` = %s WHERE `userid` = %d;";
			$sql           = WPF()->db->prepare( $sql, $fields_json, $userid );
			$result_fields = WPF()->db->query( $sql );
			if( false === $result_fields ) {
				WPF()->notice->add( 'User custom field update failed', 'error' );
				if( WPF()->db->last_error ) {
					WPF()->notice->add( sanitize_text_field( WPF()->db->last_error ), 'error' );
				}
			}
		}

        if( $result_fields ) $this->reset( $userid );

		return $result_fields;
	}

	public function upload_avatar( $userid = 0 ) {
		$userid = intval( $userid );

		if( wpfval( WPF()->current_object, 'template' ) ) {
			$template = WPF()->current_object['template'];
			if( $template === 'account' ) {
				if( ! WPF()->usergroup->can( 'upa' ) ) return false;
			}
		}

		if( ! $user = $this->get_member( $userid ) ) return false;
		$user_nicename = urldecode( $user['user_nicename'] );
		if( isset( $_FILES['avatar'] ) && ! empty( $_FILES['avatar'] ) && isset( $_FILES['avatar']['name'] ) && $_FILES['avatar']['name'] ) {
			$name     = sanitize_file_name( $_FILES['avatar']['name'] );      //myimg.png
			$tmp_name = sanitize_text_field( $_FILES['avatar']['tmp_name'] ); //D:\wamp\tmp\php986B.tmp
			$error    = sanitize_text_field( $_FILES['avatar']['error'] );    //0
			$size     = intval( $_FILES['avatar']['size'] );                  //6112

			$upload_max_filesize = apply_filters( 'wpforo_avatar_upload_max_filesize', 2 * 1048576 );
			if( $size > $upload_max_filesize ) {
				WPF()->notice->clear();
				WPF()->notice->add( 'Avatar image is too big maximum allowed size is %s', 'error', wpforo_print_size( $upload_max_filesize ) );

				return false;
			}

			if( $error ) {
				$error = wpforo_file_upload_error( $error );
				WPF()->notice->clear();
				WPF()->notice->add( $error, 'error' );

				return false;
			}

			$avatar_dir = WPF()->folders['avatars']['dir'];
			if( ! is_dir( $avatar_dir ) ) wp_mkdir_p( $avatar_dir );

			$ext = pathinfo( $name, PATHINFO_EXTENSION );
			if( ! wpforo_is_image( $ext ) ) {
				WPF()->notice->clear();
				WPF()->notice->add( 'Incorrect file format. Allowed formats: jpeg, jpg, png, gif.', 'error' );

				return false;
			}

			$fnm = pathinfo( $user_nicename, PATHINFO_FILENAME );
			$fnm = str_replace( ' ', '-', $fnm );
			while( strpos( $fnm, '--' ) !== false ) $fnm = str_replace( '--', '-', $fnm );
			$fnm = preg_replace( "/[^-a-zA-Z0-9]/", "", $fnm );
			$fnm = trim( $fnm, "-" );

			$avatar_fname      = $fnm . ( $fnm ? '_' : '' ) . $userid . "." . strtolower( $ext );
			$avatar_fname_orig = $fnm . ( $fnm ? '_' : '' ) . $userid . "." . $ext;
			$avatar_path       = $avatar_dir . DIRECTORY_SEPARATOR . $avatar_fname;
			$avatar_path_orig  = $avatar_dir . DIRECTORY_SEPARATOR . $avatar_fname_orig;

			if( is_dir( $avatar_dir ) ) {
				if( move_uploaded_file( $tmp_name, $avatar_path ) ) {
					$image = wp_get_image_editor( $avatar_path );
					if( ! is_wp_error( $image ) ) {
						$image->resize( 150, 150, true );
						$saved = $image->save( $avatar_path );
						if( ! is_wp_error( $saved ) && $avatar_fname != $avatar_fname_orig ) {
							if( defined( PHP_OS ) && strtoupper( substr( PHP_OS, 0, 3 ) ) !== 'WIN' ) unlink( $avatar_path_orig );
						}
					}
					WPF()->db->update( WPF()->tables->profiles, [ 'avatar' => WPF()->folders['avatars']['url//'] . "/" . $avatar_fname ], [ 'userid' => intval( $userid ) ], [ '%s' ], [ '%d' ] );
					$this->reset( $userid );

					return true;
				}
			}
		}

		return false;
	}

	public function upload_files( $file_fields ) {
		if( ! empty( $file_fields ) ) {
			foreach( $file_fields as $field_name => $file_path ) {
				if( wpfval( $_FILES, 'data', 'tmp_name', $field_name ) && ! move_uploaded_file( $_FILES['data']['tmp_name'][ $field_name ], $file_path ) ) {
					WPF()->notice->add( 'Sorry, there was an error uploading attached file', 'error' );
				}
			}
		}
	}

	public function get_custom_field( $userid, $field_name ) {
		$field_value = '';
		if( $userid ) {
			$sql    = WPF()->db->prepare( "SELECT `fields` FROM `" . WPF()->tables->profiles . "` WHERE userid = %d", $userid );
			$fields = WPF()->db->get_var( $sql );
			if( $fields ) {
				$data = (array) json_decode( $fields, true );
				if( ! empty( $data ) ) {
					$data = wpforo_unslashe( $data );
					if( wpfkey( $data, $field_name ) ) {
						$field_value = $data[ $field_name ];
					}
				}
			}
		}

		return $field_value;
	}

	public function get_custom_fields( $userid ) {
		$data = [];
		if( $userid ) {
			$sql    = WPF()->db->prepare( "SELECT `fields` FROM `" . WPF()->tables->profiles . "` WHERE userid = %d", $userid );
			$fields = WPF()->db->get_var( $sql );
			if( $fields ) {
				$data = (array) json_decode( $fields, true );
			}
			$data = wpforo_unslashe( $data );
		}

		return $data;
	}

	public function change_password( $old_passw, $new_passw, $userid ) {
		if( ! $userid = wpforo_bigintval( $userid ) ) {
			WPF()->notice->clear();
			WPF()->notice->add( 'Userid is wrong', 'error' );

			return false;
		}

		$user = $this->get_member( $userid );
		if( ! apply_filters( 'wpforo_change_password_validate', true, $old_passw, $new_passw, $user ) ) return false;

		if( wp_check_password( $old_passw, $user['user_pass'], $userid ) ) {
			wp_set_password( $new_passw, $userid );
			if( ! wpforo_is_owner( $userid ) ) $this->inactive_to_active( $userid );

			/**
			 *  Login user after change password with new pass
			 */
			if( wpforo_is_owner( $userid ) ) {
				wp_signon( [ 'user_login' => sanitize_user( $user['user_login'] ), 'user_password' => $new_passw ] );
			}

			WPF()->notice->add( 'Password successfully changed', 'success' );

			return true;
		}

		WPF()->notice->clear();
		WPF()->notice->add( 'Old password is wrong', 'error' );

		return false;
	}

	public function get_status( $userid ) {
		return WPF()->db->get_var(
            WPF()->db->prepare(
                "SELECT `status` 
                    FROM " . WPF()->tables->profiles . " 
                    WHERE `userid` = %d",
                $userid
            )
        );
    }

	public function inactive_to_active( $userid, $user_login = '', $raw = false ) {
		if( $this->get_status( $userid ) === 'inactive' ) {
            if( $raw || !wpforo_setting( 'authorization', 'manually_approval' ) ){
	            $this->update_profile_fields( $userid, [ 'status' => 'active' ], false );
            }else{
	            wp_safe_redirect( wpforo_url( '', 'cantlogin' ) );
	            exit();
            }
		}
	}

	public function synchronize_user( $userid, $roles_usergroups = [] ) {
		$groupid = false;
		if( ! $userid ) return false;
		$user        = get_userdata( $userid );
		$user->roles = (array) $user->roles;

		//Don't synchronize User Roles with Usergroups if the option is disabled
		if( wpforo_setting( 'authorization', 'role_synch' ) ) {
			if( ! $roles_usergroups ) $roles_usergroups = WPF()->usergroup->get_role_usergroup_relation();
			if( ! empty( $roles_usergroups ) && ! empty( $user->roles ) ) {
				foreach( $user->roles as $role ) {
					if( isset( $roles_usergroups[ $role ] ) ) {
						$groupid = $roles_usergroups[ $role ];
						break;
					}
				}
			}
		}

		if( ! $groupid ) {
			if( is_super_admin( $userid ) || in_array( 'administrator', $user->roles ) ) {
				$groupid = 1;
			} elseif( in_array( 'editor', $user->roles ) ) {
				$groupid = 2;
			} elseif( in_array( 'customer', $user->roles ) ) {
				$groupid = 5;
			} else {
				$groupid = WPF()->usergroup->default_groupid;
			}
		}
		$insert_groupid  = ( isset( $_POST['wpforo_usergroup'] ) && ! wpforo_setting( 'authorization', 'role_synch' ) ) ? intval( $_POST['wpforo_usergroup'] ) : $groupid;
		$insert_timezone = ( isset( $_POST['wpforo_usertimezone'] ) ) ? sanitize_text_field( $_POST['wpforo_usertimezone'] ) : '';
		$about           = get_user_meta( $userid, 'description', true );
		$return          = $this->add_profile( [
			                                       'userid'     => wpforo_bigintval( $userid ),
			                                       'groupid'    => intval( $insert_groupid ),
			                                       'timezone'   => sanitize_text_field( $insert_timezone ),
			                                       'about'      => stripslashes( wpforo_kses( trim( $about ), 'user_description' ) ),
		                                       ] );

		if( $return !== false && ( $secondary_groupids = wpfval( $_POST, 'wpforo_secondary_groupids' ) ) ) {
			$this->set_secondary_groupids( $userid, $secondary_groupids );
		}

		return $return;
	}

	public function synchronize_users( $limit = null ) {

		if( is_multisite() ) {
			$sql = "SELECT `user_id` FROM `" . WPF()->db->usermeta . "` WHERE `meta_key` LIKE '" . WPF()->blog_prefix . "capabilities' AND `user_id` NOT IN( SELECT `userid` FROM `" . WPF()->tables->profiles . "` ) ORDER BY `user_id` ASC";
		} else {
			$sql = "SELECT `ID` as user_id FROM `" . WPF()->db->users . "` WHERE `ID` NOT IN( SELECT `userid` FROM `" . WPF()->tables->profiles . "` ) ORDER BY `ID` ASC";
		}
		if( ! is_null( $limit ) ) {
			$sql .= " LIMIT " . intval( $limit );
		}

		$userids = WPF()->db->get_col( $sql );
		if( ! empty( $userids ) ) {
			$roles_usergroups = WPF()->usergroup->get_role_usergroup_relation();
			foreach( $userids as $userid ) {
				$this->synchronize_user( $userid, $roles_usergroups );
			}

			return false;
		}

		## -- START -- delete profiles where not participant on multisite blog
		if( is_multisite() ) {
			$sql = "DELETE FROM `" . WPF()->tables->profiles . "` WHERE `userid` NOT IN( SELECT `user_id` FROM `" . WPF()->db->usermeta . "` WHERE `meta_key` LIKE '" . WPF()->blog_prefix . "capabilities' )";
			WPF()->db->query( $sql );
		}

		## -- END -- delete profiles where not participant on multisite blog

		return true;
	}

	public function _get_member( $args ) {
		if( ! $args ) return $this->get_guest();

		$default = [
			'userid'        => null, // $userid
			'user_nicename' => '' // $user_nicename
		];

		if( is_numeric( $args ) ) {
			$args = [ 'userid' => $args ];
		} elseif( ! is_array( $args ) ) {
			$args = [ 'user_nicename' => $args ];
		}

		$args = wpforo_parse_args( $args, $default );

		extract( $args );

		$userid = wpforo_bigintval( $userid );

		$user_meta_obj = true;
		if( $user_nicename ) {
			$user_obj = get_user_by( 'user_nicename', $user_nicename );
			if( ! empty( $user_obj ) ) $userid = $user_obj->ID;
		}
		$member = get_user_meta( $userid, '_wpf_member_obj', true );

		if( empty( $member ) ) {
			$user_meta_obj = false;
			$sql = "SELECT *, 
                ug.`name` AS group_name, 
                ug.`color` AS group_color,
                fn.`meta_value` AS first_name,
                ln.`meta_value` AS last_name
                FROM `" . WPF()->db->users . "` u 
                INNER JOIN `" . WPF()->tables->profiles . "` p ON p.`userid` = u.`ID`
                LEFT JOIN `" . WPF()->tables->usergroups . "` ug ON ug.`groupid` = p.`groupid`
                LEFT JOIN `" . WPF()->db->usermeta . "` fn ON fn.`user_id` = u.`ID` AND fn.`meta_key` LIKE 'first_name'
                LEFT JOIN `" . WPF()->db->usermeta . "` ln ON ln.`user_id` = u.`ID` AND fn.`meta_key` LIKE 'last_name'";
			$wheres        = [];
			if( $userid ) $wheres[] = "`ID` = $userid";
			if( $user_nicename ) $wheres[] = "`user_nicename` = '" . esc_sql( $user_nicename ) . "'";
			if( ! empty( $wheres ) ) $sql .= " WHERE " . implode( " AND ", $wheres );
			$member = WPF()->db->get_row( $sql, ARRAY_A );
		}

		if( ! empty( $member ) && ! $user_meta_obj ) update_user_meta( $userid, '_wpf_member_obj', $member );

		if( $member ) $member = $this->decode( $member );

		return $member;
	}

	public function get_member( $args ) {
        return wpforo_ram_get( [$this, '_get_member'], $args );
	}

	public function get_members( $args = [], &$items_count = 0 ) {
		$default = [
			'include'     => [],                                  // array( 2, 10, 25 )
			'exclude'     => [],                                  // array( 2, 10, 25 )
			'status'      => [ 'active', 'inactive', 'banned' ],  // 'active', 'blocked', 'trashed', 'spamer'
			'groupid'     => null,                                     // groupid
			'online_time' => null,                                     // groupid
			'orderby'     => 'userid',                                 //
			'order'       => 'ASC',                                    // ASC DESC
			'offset'      => 0,                                        // OFFSET
			'row_count'   => null,                                     // ROW COUNT
			'groupids'    => [],                                  // array( 1, 2 )
		];

		$args = wpforo_parse_args( $args, $default );
		extract( $args, EXTR_OVERWRITE );

		$include = wpforo_parse_args( $include );
		$exclude = wpforo_parse_args( $exclude );

		$sql = "SELECT *, 
		    ug.`name` AS group_name, 
		    ug.`color` AS group_color,
		    fn.`meta_value` AS first_name,
            ln.`meta_value` AS last_name
		    FROM `" . WPF()->db->users . "` u 
				INNER JOIN `" . WPF()->tables->profiles . "` p ON p.`userid` = u.`ID`
				LEFT JOIN `" . WPF()->tables->usergroups . "` ug ON ug.`groupid` = p.`groupid`
				LEFT JOIN `" . WPF()->db->usermeta . "` fn ON fn.`user_id` = u.`ID` AND fn.`meta_key` LIKE 'first_name'
                LEFT JOIN `" . WPF()->db->usermeta . "` ln ON ln.`user_id` = u.`ID` AND fn.`meta_key` LIKE 'last_name'";
		$wheres = [];
		if( ! empty( $include ) ) $wheres[] = " u.`ID` IN(" . implode( ', ', array_map( 'intval', $include ) ) . ")";
		if( ! empty( $exclude ) ) $wheres[] = " u.`ID` NOT IN(" . implode( ', ', array_map( 'intval', $exclude ) ) . ")";
		if( ! empty( $status ) ) $wheres[] = " p.`status` IN('" . implode( "','", array_map( 'esc_sql', array_map( 'sanitize_text_field', $status ) ) ) . "')";
		if( ! empty( $groupids ) ) $wheres[] = " (p.`groupid` IN(" . implode( ', ', array_map( 'intval', $groupids ) ) . ") OR CONCAT(',', p.`secondary_groupids`, ',') REGEXP ',(" . implode( '|', array_map( 'intval', $groupids ) ) . "),' )";
		if( ! is_null( $groupid ) ) $wheres[] = " (p.`groupid` = " . intval( $groupid ) . " OR FIND_IN_SET(" . intval( $groupid ) . ", p.`secondary_groupids`) ) ";
		if( ! is_null( $online_time ) ) $wheres[] = " p.`online_time` > " . intval( $online_time );

		if( ! empty( $wheres ) ) $sql .= " WHERE " . implode( " AND ", $wheres );

		$item_count_sql = preg_replace( '#SELECT.+?FROM#isu', 'SELECT count(*) FROM', $sql );
		$item_count_sql = preg_replace( '#ORDER.+$#is', '', $item_count_sql );
		if( $item_count_sql ) $items_count = WPF()->db->get_var( $item_count_sql );

		if( $orderby === 'groupid' ) $orderby = 'p.`groupid`';
		$sql .= esc_sql( " ORDER BY $orderby " . $order );
		if( $row_count ) $sql .= esc_sql( " LIMIT $offset,$row_count" );

		return array_map( [$this, 'decode'], WPF()->db->get_results( $sql, ARRAY_A ) );
	}

	public function search( $needle, $fields = [], $limit = null ) {
		if( $needle ) {
			$needle = sanitize_text_field( $needle );
			if( empty( $fields ) ) {
				$fields = [
					'title',
					'user_nicename',
					'user_email',
					'signature',
				];
			}

			$sql    = "SELECT `ID` FROM `" . WPF()->db->users . "` u 
			    INNER JOIN `" . WPF()->tables->profiles . "` p ON p.`userid` = u.`ID`";
			$wheres = [];

			foreach( $fields as $field ) {
				$f     = $this->get_field( $field );
				$field = sanitize_text_field( $field );
				if( $f['isDefault'] ) {
					$wheres[] = "`" . esc_sql( $field ) . "` LIKE '%" . esc_sql( $needle ) . "%'";
				} else {
					$needle = preg_quote( preg_quote( $needle ) );
					if( in_array( $f['type'], [ 'text', 'search', 'textarea' ], true ) ) {
						$wheres[] = "`fields` REGEXP '[{,]\"" . $field . "\":(\\\[[^\\\[]*)?\"[^\"]*" . esc_sql( $needle ) . "[^\"]*\"'";
					} else {
						$wheres[] = "`fields` REGEXP '[{,]\"" . $field . "\":(\\\[[^\\\[]*)?\"" . esc_sql( $needle ) . "\"'";
					}
				}
			}

			if( ! empty( $wheres ) ) {
				$sql .= " WHERE " . implode( " OR ", $wheres );
				if( $limit ) $sql .= " LIMIT " . intval( $limit );

				return WPF()->db->get_col( $sql );
			} else {
				return [];
			}
		} else {
			return [];
		}

	}

	public function filter( $args, $limit = null ) {
		if( $args && is_array( $args ) ) {
			$sql    = "SELECT `ID` FROM `" . WPF()->db->users . "` u 
			    INNER JOIN `" . WPF()->tables->profiles . "` p ON p.`userid` = u.`ID`";
			$wheres = [];

			foreach( $args as $field => $needle ) {
				$f     = $this->get_field( $field );
				$field = sanitize_text_field( $field );
				if( $f['isDefault'] ) {
					if( is_scalar( $needle ) ) {
						$needle   = sanitize_text_field( $needle );
						$wheres[] = "`" . esc_sql( $field ) . "` LIKE '%" . esc_sql( $needle ) . "%'";
					} elseif( is_array( $needle ) ) {
						foreach( $needle as $n ) {
							$n        = sanitize_text_field( $n );
							$wheres[] = "`" . esc_sql( $field ) . "` LIKE '%" . esc_sql( $n ) . "%'";
						}
					}
				} else {
					if( in_array( $f['type'], [ 'text', 'search', 'textarea' ], true ) ) {
						if( is_scalar( $needle ) ) {
							$needle   = preg_quote( preg_quote( wpforo_encode( $needle ) ) );
							$wheres[] = "`fields` REGEXP '[{,]\"" . $field . "\":(\\\[[^\\\[]*)?\"[^\"]*" . esc_sql( $needle ) . "[^\"]*\"'";
						} elseif( is_array( $needle ) ) {
							foreach( $needle as $n ) {
								$n        = preg_quote( preg_quote( wpforo_encode( $n ) ) );
								$wheres[] = "`fields` REGEXP '[{,]\"" . $field . "\":(\\\[[^\\\[]*)?\"[^\"]*" . esc_sql( $n ) . "[^\"]*\"'";
							}
						}
					} else {
						if( is_scalar( $needle ) ) {
							$needle   = preg_quote( preg_quote( wpforo_encode( $needle ) ) );
							$wheres[] = "`fields` REGEXP '[{,]\"" . $field . "\":(\\\[[^\\\[]*)?\"" . esc_sql( $needle ) . "\"'";
						} elseif( is_array( $needle ) ) {
							foreach( $needle as $n ) {
								$n        = preg_quote( preg_quote( wpforo_encode( $n ) ) );
								$wheres[] = "`fields` REGEXP '[{,]\"" . $field . "\":(\\\[[^\\\[]*)?\"" . esc_sql( $n ) . "\"'";
							}
						}
					}
				}
			}

			if( $wheres ) {
				$sql .= " WHERE " . implode( " AND ", $wheres );
				if( $limit ) $sql .= " LIMIT " . intval( $limit );

				return WPF()->db->get_col( $sql );
			}
		}

		return [];
	}

	public function ban( $userid ) {
		if( $userid == WPF()->current_userid ) {
			WPF()->notice->add( 'You can\'t make yourself banned user', 'error' );

			return false;
		}
		if( ! WPF()->usergroup->can( 'bm' ) || ! WPF()->perm->user_can_manage_user( WPF()->current_userid, intval( $userid ) ) ) {
			WPF()->notice->add( 'Permission denied for this action', 'error' );

			return false;
		}
		if( false !== WPF()->db->update( WPF()->tables->profiles, [ 'status' => 'banned' ], [ 'userid' => intval( $userid ) ], [ '%s' ], [ '%d' ] ) ) {
			do_action( 'wpforo_after_ban_user', $userid );
			$this->reset( $userid );
			WPF()->notice->add( 'User successfully banned from wpforo', 'success' );

			return true;
		}

		WPF()->notice->add( 'User ban action error', 'error' );

		return false;
	}

	public function unban( $userid ) {
		if( ! WPF()->usergroup->can( 'bm' ) || ! WPF()->perm->user_can_manage_user( WPF()->current_userid, intval( $userid ) ) ) {
			WPF()->notice->add( 'Permission denied for this action', 'error' );

			return false;
		}
		if( false !== WPF()->db->update( WPF()->tables->profiles, [ 'status' => 'active' ], [ 'userid' => intval( $userid ) ], [ '%s' ], [ '%d' ] ) ) {
			do_action( 'wpforo_after_unban_user', $userid );
			$this->reset( $userid );
			WPF()->notice->add( 'User successfully unbanned from wpforo', 'success' );

			return true;
		}

		WPF()->notice->add( 'User unban action error', 'error' );

		return false;
	}

    public function activate( $userid ) {
		if( false !== WPF()->db->update( WPF()->tables->profiles, [ 'status' => 'active' ], [ 'userid' => intval( $userid ) ], [ '%s' ], [ '%d' ] ) ) {
			do_action( 'wpforo_after_activate_user', $userid );
			$this->reset( $userid );
			WPF()->notice->add( 'User successfully activated from wpforo', 'success' );
			return true;
		}

		WPF()->notice->add( 'User activate action error', 'error' );
		return false;
	}

	public function deactivate( $userid ) {
		if( false !== WPF()->db->update( WPF()->tables->profiles, [ 'status' => 'inactive' ], [ 'userid' => intval( $userid ) ], [ '%s' ], [ '%d' ] ) ) {
			do_action( 'wpforo_after_deactivate_user', $userid );
			$this->reset( $userid );
			WPF()->notice->add( 'User successfully deactivated from wpforo', 'success' );
			return true;
		}

		WPF()->notice->add( 'User deactivate action error', 'error' );
		return false;
	}

	/**
	 *
	 * @param int $userid
	 * @param int $reassign
	 *
	 * @return bool true | false if user successfully deleted
	 */
	public function delete( $userid, $reassign = null ) {
		if( ! ( $userid = wpforo_bigintval( $userid ) ) ) return false;
		do_action( 'wpforo_before_delete_user', $userid, $reassign );

		if( false !== WPF()->db->delete( WPF()->tables->profiles, [ 'userid' => $userid ], [ '%d' ] ) ) {
			do_action( 'wpforo_after_delete_user', $userid, $reassign );

			WPF()->notice->add( 'User successfully deleted', 'success' );
			return true;
		}

		WPF()->notice->add( 'User delete error', 'error' );
		return false;
	}

	public function _avatar( $member, $attr = '', $size = '' ) {
		if( ! isset( $member['userid'] ) ) return '';

		$src    = $member['avatar'];
		$userid = ( $member['userid'] ?: $member['user_email'] );

		if( $src && wpforo_setting( 'profiles', 'custom_avatars' ) ) {
			$src  = apply_filters( 'wpforo_avatar_url', $src, $member );
			$attr = ( $attr ?: 'height="96" width="96"' );
			$img  = '<img class="avatar" src="' . esc_url( $src ) . '" ' . $attr . ' />';
		} else {
			$img = ( $size ) ? get_avatar( $userid, $size ) : get_avatar( $userid );
			if( $attr ) $img = str_replace( '<img', '<img ' . $attr, $img );
		}

		return $img;
	}

	public function avatar( $member, $attr = '', $size = '' ) {
		return wpforo_ram_get( [ $this, '_avatar' ], $member, $attr, $size );
	}

	public function _get_avatar( $userid, $attr = '', $size = '' ) {
		return $this->get_avatar_html( $this->get_avatar_url( $userid ), $userid, $attr, $size );
	}

	public function get_avatar( $userid, $attr = '', $size = '' ) {
		return wpforo_ram_get( [ $this, '_get_avatar' ], $userid, $attr, $size );
	}

	public function _get_avatar_url( $userid ) {
		$avatar_url = WPF()->db->get_var( WPF()->db->prepare( "SELECT `avatar` FROM `" . WPF()->tables->profiles . "` WHERE `userid` = %d", wpforo_bigintval( $userid ) ) );

		return apply_filters( 'wpforo_avatar_url', $avatar_url, $userid );
	}

	public function get_avatar_url( $userid ) {
		return wpforo_ram_get( [ $this, '_get_avatar_url' ], $userid );
	}

	public function get_avatar_html( $url, $userid = 0, $attr = '', $size = '' ) {
		if( $url && wpforo_setting( 'profiles', 'custom_avatars' ) ) {
			$attr = ( $attr ?: 'height="96" width="96"' );
			$img  = '<img class="avatar" src="' . esc_url( $url ) . '" ' . $attr . ' />';
		} else {
			$img = ( $size ) ? get_avatar( $userid, $size ) : get_avatar( $userid );
			if( $attr ) $img = str_replace( '<img', '<img ' . $attr, $img );
		}

		return $img;
	}

	/**
	 * @param array|int|string $arg
	 * @param string $template
	 *
	 * @return string
	 */
	public function get_profile_url( $arg, $template = 'profile' ) {
		$profile_url = wpforo_home_url();
		$user = is_scalar( $arg ) ? $this->get_member( intval( basename( $arg ) ) ?: [ 'user_nicename' => basename( $arg ) ] ) : $arg;
		if( wpfkey( $user, 'userid' ) && wpfkey( $user, 'user_nicename' ) ){
			$user_slug   = ( wpforo_setting( 'profiles', 'url_structure' ) === 'id' ? $user['userid'] : $user['user_nicename'] );
            if( $template === 'profile' ){
	            $profile_url = wpforo_url( $user_slug, 'member' );
            }else{
	            $template_slug = wpforo_settings_get_slug( $template );
	            $profile_url   = wpforo_url( "$user_slug/$template_slug", 'member' );
            }
		}

		return apply_filters( 'wpforo_member_profile_url', $profile_url, $user, $template );
	}

	/**
	 * @param float $points
	 *
	 * @return array
	 */
	public function calc_rating( $points ) {
		$rating = $this->default->member['rating'];

		$rating['level']   = $this->rating_level( floatval( $points ), false );
		$rating['percent'] = $rating['level'] * 10;
		$rating['title']   = $this->rating( $rating['level'], 'title' );
		$rating['color']   = $this->rating( $rating['level'], 'color' );
		$rating['badge']   = $this->rating( $rating['level'], 'icon' );

		return $rating;
	}

	/**
	 * @param $member
	 *
	 * @return float
	 */
	private function calc_points( $member ) {
		$topic_points   = intval( $member['topics'] )                              * wpforo_setting( 'rating', 'topic_points' );
		$post_points    = intval( $member['posts'] )                               * wpforo_setting( 'rating', 'post_points' );
		$like_points    = intval( wpfval( $member, 'reactions_in', 'up') )   * wpforo_setting( 'rating', 'like_points' );
		$dislike_points = intval( wpfval( $member, 'reactions_in', 'down') ) * wpforo_setting( 'rating', 'dislike_points' );

		$topic_points   = (float) apply_filters( 'wpforo_member_get_points_topic_points',   $topic_points,   $member );
		$post_points    = (float) apply_filters( 'wpforo_member_get_points_post_points',    $post_points,    $member );
		$like_points    = (float) apply_filters( 'wpforo_member_get_points_like_points',    $like_points,    $member );
		$dislike_points = (float) apply_filters( 'wpforo_member_get_points_dislike_points', $dislike_points, $member );

		return $topic_points + $post_points + $like_points + $dislike_points;
	}

	public function get_count( $args = [] ) {
		$sql = "SELECT SQL_NO_CACHE COUNT(*) FROM `" . WPF()->tables->profiles . "` p 
			INNER JOIN `" . WPF()->db->users . "` u ON u.`ID` = p.`userid` WHERE p.`status` NOT LIKE 'trashed'";
		if( $args ) {
			$wheres = [];
			foreach( $args as $key => $value ) {
				if( is_array( $value ) ) {
					$wheres[] = "$key IN('" . implode( "','", array_map( 'esc_sql', $value ) ) . "')";
				} else {
					$wheres[] = "$key = '" . esc_sql( $value ) . "'";
				}
			}
			if( $wheres ) $sql .= " AND " . implode( ' AND ', $wheres );
		}

		return (int) WPF()->db->get_var( $sql );
	}

	public function _is_online( $userid, $duration = null ) {
		if( ! $duration ) $duration = wpforo_setting( 'profiles', 'online_status_timeout' );
		$sql          = "SELECT `online_time` FROM `" . WPF()->tables->profiles . "` WHERE `userid` = %d";
		$sql          = WPF()->db->prepare( $sql, $userid );
		$online_time  = intval( WPF()->db->get_var( $sql ) );
		$current_time = current_time( 'timestamp', 1 );

		return ( $current_time - $online_time ) < $duration;
	}

	public function is_online( $userid, $duration = null ) {
		return wpforo_ram_get( [ $this, '_is_online' ], $userid, $duration );
	}

	public function show_online_indicator( $userid, $ico = true ) {
		if( $this->is_online( $userid ) ) : ?>

			<?php if( $ico ) : ?>
                <i class="fas fa-circle wpfsx wpfcl-8" title="<?php wpforo_phrase( 'Online' ) ?>"></i>
			<?php else : wpforo_phrase( 'Online' ); endif ?>

		<?php else : ?>

			<?php if( $ico ) : ?>
                <i class="fas fa-circle wpfsx wpfcl-0" title="<?php wpforo_phrase( 'Offline' ) ?>"></i>
			<?php else : wpforo_phrase( 'Offline' ); endif ?>

		<?php endif;
	}

	public function online_members_count( $duration = null ) {
		if( ! ( $duration = intval( $duration ) ) ) $duration = (int) wpforo_setting( 'profiles', 'online_status_timeout' );
		$online_timeframe = current_time( 'timestamp', 1 ) - $duration;
		$key              = [ 'member', 'online_members_count', $online_timeframe ];
		if( WPF()->ram_cache->exists( $key ) ) return WPF()->ram_cache->get( $key );
		$sql = "SELECT COUNT(DISTINCT `userid`, `ip`) AS total FROM `" . WPF()->tables->visits . "` WHERE `time` > %d";
		$var = (int) WPF()->db->get_var( WPF()->db->prepare( $sql, $online_timeframe ) );
		if( ! $var ) {
			$sql = "SELECT COUNT(*) FROM `" . WPF()->tables->profiles . "` WHERE `online_time` > %d";
			$var = (int) WPF()->db->get_var( WPF()->db->prepare( $sql, $online_timeframe ) );
		}
		WPF()->ram_cache->set( $key, $var );

		return $var;
	}

	public function get_online_members( $count = 1, $groupids = [], $duration = null ) {
		if( ! $duration ) $duration = wpforo_setting( 'profiles', 'online_status_timeout' );
		$current_time     = current_time( 'timestamp', 1 );
		$online_timeframe = $current_time - $duration;
		$groupids         = array_filter( wpforo_parse_args( $groupids ) );
		$args             = [
			'groupids'    => $groupids,
			'online_time' => $online_timeframe, // $current_time - $duration
			'orderby'     => 'userid', // forumid, order, parentid
			'row_count'   => $count,
			'order'       => 'ASC', // ASC DESC
		];

		return $this->get_members( $args );
	}

	public function levels() {
		return [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ];
	}

	public function rating( $level = null, $var = '' ) {
		if( is_null( $level ) ) return wpforo_setting( 'rating', 'levels' );
        $_levels = wpforo_setting( 'rating', 'levels' );
		if( ! wpfkey( $_levels, $level ) ) $level = 0;
		if( ! $var ) return wpforo_setting( 'rating', 'levels', $level );

		return wpforo_setting( 'rating', 'levels', $level, $var );
	}

	/**
	 * @param int $points
	 * @param bool $percent
	 *
	 * @return int
	 */
	public function rating_level( $points, $percent = true ) {
        $points = intval( $points );
		if( $points < $this->rating( 1, 'points' ) ) {
			$bar = 0;
		} elseif( $points < $this->rating( 2, 'points' ) ) {
			$bar = 10;
		} elseif( $points < $this->rating( 3, 'points' ) ) {
			$bar = 20;
		} elseif( $points < $this->rating( 4, 'points' ) ) {
			$bar = 30;
		} elseif( $points < $this->rating( 5, 'points' ) ) {
			$bar = 40;
		} elseif( $points < $this->rating( 6, 'points' ) ) {
			$bar = 50;
		} elseif( $points < $this->rating( 7, 'points' ) ) {
			$bar = 60;
		} elseif( $points < $this->rating( 8, 'points' ) ) {
			$bar = 70;
		} elseif( $points < $this->rating( 9, 'points' ) ) {
			$bar = 80;
		} elseif( $points < $this->rating( 10, 'points' ) ) {
			$bar = 90;
		} else {
			$bar = 100;
		}

		return $percent ? $bar : (int) floor( $bar / 10 );
	}

	public function rating_badge( $level = 0, $view = 'short' ) {

		$level = ( $level > 10 ) ? floor( $level / 10 ) : $level;

		if( $level == 0 ) {
			return '<i class="' . $this->rating( $level, 'icon' ) . '"></i>';
		} elseif( $level > 0 && $level < 6 ) {
			if( $view == 'full' ) {
				return str_repeat( ' <i class="' . $this->rating( $level, 'icon' ) . '"></i> ', $level );
			} else {
				return '<span>' . esc_html( $level ) . '</span> <i class="' . $this->rating( $level, 'icon' ) . '"></i>';
			}
		} elseif( $level > 5 && $level < 9 ) {
			if( $view == 'full' ) {
				return str_repeat( ' <i class="' . $this->rating( $level, 'icon' ) . '"></i> ', ( $level - 5 ) );
			} else {
				return '<span>' . esc_html( $level - 5 ) . '</span> <i class="' . $this->rating( $level, 'icon' ) . '"></i>';
			}
		} elseif( $level > 8 ) {
			return '<i class="' . $this->rating( $level, 'icon' ) . '"></i>';
		} else {
			return '';
		}
	}

	public function reset( $userid ) {
		if( ! $userid ) return;
		delete_user_meta( intval( $userid ), '_wpf_member_obj' );
		if( wpforo_setting( 'seo', 'seo_profile' ) ) WPF()->seo->clear_cache();
	}

	public function clear_db_cache() {
		WPF()->db->query( "DELETE FROM `" . WPF()->db->usermeta . "` WHERE `meta_key` = '_wpf_member_obj'" );
	}

	private function update_online_time( $userid = null ) {
		if( ! $userid ) $userid = WPF()->current_userid;
		if( ! $userid ) return false;
		$current_timestamp = current_time( 'timestamp', 1 );
		$sql               = "UPDATE `" . WPF()->tables->profiles . "` SET `online_time` = %d WHERE `userid` = %d";
		$sql               = WPF()->db->prepare( $sql, $current_timestamp, wpforo_bigintval( $userid ) );
		if( false !== WPF()->db->query( $sql ) ) return $current_timestamp;

		return false;
	}

	public function init_current_user() {
		WPF()->wp_current_user = $current_user = wp_get_current_user();
		if( $current_user->exists() ) {
			WPF()->current_userid     = $current_user->ID;
			WPF()->current_user_login = $current_user->user_login;
			WPF()->current_user_email = $current_user->user_email;
			WPF()->current_user_display_name = $current_user->display_name;

			$user = $this->get_member( $current_user->ID );
			if( ! wpfkey( $user, 'groupid' ) ) {
				$this->synchronize_user( $current_user->ID );
				$user = $this->get_member( $current_user->ID );
			}
			$user_meta                             = get_user_meta( $current_user->ID );
			WPF()->current_user                    = $user;
			WPF()->current_usermeta                = $user_meta;
			WPF()->current_user_groupid            = WPF()->current_user['groupid'];
			WPF()->current_user_secondary_groupids = WPF()->current_user['secondary_groupids'];
			WPF()->current_user_groupids           = array_unique( array_filter( array_merge( (array) WPF()->current_user_groupid, WPF()->current_user_secondary_groupids ) ) );
			WPF()->current_user_status             = (string) wpfval($user, 'status');
			$this->update_online_time();
		} elseif( $guest = $this->get_guest_cookies() ) {
			WPF()->current_userid                  = 0;
			WPF()->current_user_login              = '';
			WPF()->current_user_email              = $guest['email'];
			WPF()->current_user_display_name       = $guest['name'];
			WPF()->current_user                    = $this->get_guest( $guest );
			WPF()->current_usermeta                = [];
			WPF()->current_user_groupid            = 4;
			WPF()->current_user_groupids           = [ 4 ];
			WPF()->current_user_secondary_groupids = [];
			WPF()->current_user_status             = '';
			WPF()->current_user_accesses           = [];
		}

		WPF()->usergroup->init_current();

		if( function_exists( 'wp_get_session_token' ) && function_exists( 'wp_parse_auth_cookie' ) ) WPF()->session_token = wp_get_session_token();
		if( ! WPF()->session_token ) {
			$secret_key          = defined( 'SECRET_KEY' ) && SECRET_KEY ? SECRET_KEY : 'wpforo';
			WPF()->session_token = hash_hmac( 'sha256', md5( (string) wpfval( $_SERVER, 'HTTP_USER_AGENT' ) ) . md5( (string) wpfval( $_SERVER, 'REMOTE_ADDR' ) ) . md5( (string) wpfval( $_SERVER, 'SERVER_ADDR' ) ), $secret_key );
		}

		do_action( 'wpforo_after_init_current_user', WPF()->current_user, WPF()->current_usermeta, WPF()->current_user_groupid, WPF()->current_user_secondary_groupids, WPF()->current_user_groupids );
	}

	public function blog_posts( $userid ) {
		if( isset( $userid ) && $userid ) return count_user_posts( $userid );

		return 0;
	}

	public function blog_comments( $userid, $user_email ) {
		global $wpdb;
		if( ! $userid || ! $user_email ) return 0;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . $wpdb->comments . " WHERE `user_id` = " . intval( $userid ) . " OR `comment_author_email` = '" . esc_sql( $user_email ) . "'" );
	}

	public function show_delete_form( $current_user, $userids ) {
		if( empty( $current_user ) || empty( $userids ) ) return;

		$userids            = array_diff( $userids, [ $current_user->ID ] );
		$users_have_content = false;
		if( WPF()->db->get_var( "SELECT `postid` FROM `" . WPF()->tables->posts . "` WHERE `userid` IN( " . implode( ',', array_map( 'intval', $userids ) ) . " ) LIMIT 1" ) ) {
			$users_have_content = true;
		}
		?>
        <hr/><strong>#wpForo</strong>
		<?php if( ! $users_have_content ) : ?>
            <input type="hidden" name="wpforo_user_delete_option" value="delete"/>
		<?php else: ?>
			<?php if( 1 == count( $userids ) ) : ?>
                <fieldset>
                <legend><?php _e( 'What should be done with wpForo content owned by this user?', 'wpforo' ); ?></legend>
			<?php else : ?>
                <fieldset>
                <legend><?php _e( 'What should be done with wpForo content owned by these users?', 'wpforo' ); ?></legend>
			<?php endif; ?>
            <ul style="list-style:none;">
                <li><label><input type="radio" id="wpforo_delete_option0" name="wpforo_user_delete_option" value="delete">
						<?php _e( 'Delete all wpForo content.', 'wpforo' ); ?></label></li>
                <li><input type="radio" id="wpforo_delete_option1" name="wpforo_user_delete_option" value="reassign">
                    <label for="wpforo_delete_option1"><?php _e( 'Attribute all content to:' ) ?></label>
					<?php
					wp_dropdown_users( [
						                   'name'    => 'wpforo_reassign_userid',
						                   'exclude' => $userids,
						                   'show'    => 'display_name_with_login',
					                   ] );
					?>
                </li>
            </ul></fieldset>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $('#wpforo_reassign_userid').on('focus', function () {
                        $('#wpforo_delete_option1').prop('checked', true).trigger('change')
                    })
                })
            </script>
		<?php endif;
	}

	public function autoban( $userid ) {
		if( ! WPF()->usergroup->can( 'em' ) ) {
			WPF()->db->update( WPF()->tables->profiles, [ 'status' => 'banned' ], [ 'userid' => intval( $userid ) ], [ '%s' ], [ '%d' ] );
		}
	}

	public function member_approved_posts( $member = [] ) {
		if( is_numeric( $member ) ) {
			if( $userid = wpforo_bigintval( $member ) ) {
                if( $userid === WPF()->current_userid ) return WPF()->current_user['posts'];
				return (int) WPF()->db->get_var(
                    WPF()->db->prepare(
                        "SELECT COUNT(*) as posts 
                        FROM `" . WPF()->tables->posts . "` 
                        WHERE `status` = 0 
                        AND `userid` = %d",
                        $userid
                    )
                );
			}
		}

		return (int) wpfval( $member, 'posts' );
	}

	public function current_user_is_new() {
		if( WPF()->usergroup->can( 'em' ) ) {
			//This is an admin or moderator. The number of posts doesn't matter.
			return false;
		} else {
			$new_user_max_posts = wpforo_setting( 'antispam', 'new_user_max_posts' );
			$posts              = $this->member_approved_posts( WPF()->current_userid );
			if( $new_user_max_posts && $posts <= $new_user_max_posts ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * @return int
	 */
	public function banned_count() {
		$key = [ 'member', 'banned_count' ];
		if( WPF()->ram_cache->exists( $key ) ) return WPF()->ram_cache->get( $key );
		$var = (int) WPF()->db->get_var( "SELECT count(*) FROM `" . WPF()->tables->profiles . "` WHERE `status` = 'banned'" );
		WPF()->ram_cache->set( $key, $var );

		return $var;
	}

	public function _get_guest( $args = [] ) {
		if( !wpfval( $args, 'name' ) )  $args['name']  = wpforo_phrase( 'Anonymous', false );
		if( !wpfval( $args, 'email' ) ) $args['email'] = 'anonymous@example.com';

		$args['name']            = strip_tags( $args['name'] );
		$args['email']           = sanitize_email( $args['email'] );
		$args['user_registered'] = current_time( 'mysql', 1 );
		$args['posts']           = 0;

		if( $args['email'] && $args['email'] !== 'anonymous@example.com' ) {
			if( $posts = WPF()->post->get_posts( [ 'status' => 0, 'email' => $args['email'], 'orderby' => '`created` ASC, `postid` ASC' ] ) ) {
				$args['posts'] = count( $posts );
				if( $user_registered = wpfval( $posts, 0, 'created' ) ) $args['user_registered'] = $user_registered;
			}
		}

		$guest = wpforo_array_args_cast_and_merge(
			[
				'groupid'         => 4,
				'group_name'      => wpforo_phrase( 'Guest', false ),
				'user_login'      => $args['name'],
				'user_nicename'   => sanitize_text_field( $args['name'] ),
				'user_email'      => $args['email'],
				'user_registered' => $args['user_registered'],
				'display_name'    => $args['name'],
				'posts'           => $args['posts'],
				'status'          => 'active',
			],
			$this->default->member
		);

        return $this->decode( $guest );
	}

	public function get_guest( $args = [] ) {
		return wpforo_ram_get( [ $this, '_get_guest' ], $args );
	}

	public function init_fields() {
		if( ! empty( $this->fields ) ) return;

		$this->init_countries();
		$this->init_timezones();

		$this->fields = apply_filters( 'wpforo_member_before_init_fields', $this->fields );

		$usergroupids = [];
		$usergroups   = WPF()->usergroup->get_usergroups();
		foreach( $usergroups as $usergroup ) {
			$usergroupids[] = $usergroup['groupid'];
		}
		$usergroupids_can_edit_fields     = WPF()->usergroup->get_groupids_by_can( 'em' );
		$usergroupids_can_view_social_net = WPF()->usergroup->get_groupids_by_can( 'vmsn' );

		/**
		 * start init tinymce settings
		 */
		$wp_editor_settings                        = WPF()->tpl->editor_buttons();
		$wp_editor_settings['tinymce']['toolbar1'] = 'bold,italic,link,unlink,undo,redo';
		$wp_editor_settings['plugins']             = '';
		unset(
			$wp_editor_settings['external_plugins']['wpforo_pre_button'], $wp_editor_settings['external_plugins']['wpforo_spoiler_button'], $wp_editor_settings['external_plugins']['wpforo_source_code_button'], $wp_editor_settings['external_plugins']['emoticons']
		);
		$wp_editor_settings = apply_filters( 'wpforo_members_init_fields_tinymce_settings', $wp_editor_settings );

		$this->fields['user_login'] = [
			'fieldKey'       => 'user_login',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 0,
			'label'          => wpforo_phrase( 'Username', false ),
			'title'          => wpforo_phrase( 'Username', false ),
			'placeholder'    => wpforo_phrase( 'Username', false ),
			'description'    => wpforo_phrase( 'Length must be between 3 characters and 15 characters.', false ),
			'minLength'      => 3,
			'maxLength'      => 30,
			'faIcon'         => 'fas fa-user',
			'name'           => 'user_login',
			'cantBeInactive' => [ 'register' ],
			'canEdit'        => [],
			'canView'        => WPF()->usergroup->get_groupids_by_can( 'vmu' ),
			'can'            => 'vmu',
			'isSearchable'   => 0,
		];

		$this->fields['user_email'] = [
			'fieldKey'       => 'user_email',
			'type'           => 'email',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Email', false ),
			'title'          => wpforo_phrase( 'Email', false ),
			'placeholder'    => wpforo_phrase( 'Email', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-envelope',
			'name'           => 'user_email',
			'cantBeInactive' => [ 'register' ],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => WPF()->usergroup->get_groupids_by_can( 'vmm' ),
			'can'            => 'vmm',
			'isSearchable'   => 1,
		];

		$this->fields['user_pass'] = [
			'fieldKey'       => 'user_pass',
			'type'           => 'password',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Password', false ),
			'title'          => wpforo_phrase( 'Password', false ),
			'placeholder'    => wpforo_phrase( 'Password', false ),
			'description'    => wpforo_phrase( 'Must be minimum 6 characters.', false ),
			'minLength'      => 6,
			'maxLength'      => 20,
			'faIcon'         => 'fas fa-key',
			'name'           => 'user_pass',
			'cantBeInactive' => [
				'account',
				'register',
			],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => [],
			'can'            => '',
			'isSearchable'   => 0,
		];

		$this->fields['display_name'] = [
			'fieldKey'       => 'display_name',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Display Name', false ),
			'title'          => wpforo_phrase( 'Display Name', false ),
			'placeholder'    => wpforo_phrase( 'Display Name', false ),
			'faIcon'         => 'fas fa-user',
			'name'           => 'display_name',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids,
			'can'            => '',
			'isSearchable'   => 1,
		];

		$this->fields['user_nicename'] = [
			'fieldKey'       => 'user_nicename',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Nickname', false ),
			'title'          => wpforo_phrase( 'Nickname', false ),
			'placeholder'    => wpforo_phrase( 'Nickname', false ),
			'description'    => wpforo_phrase( 'URL Address Identifier', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-link',
			'name'           => 'user_nicename',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids,
			'can'            => '',
			'isSearchable'   => 1,
		];

		$this->fields['first_name'] = [
			'fieldKey'       => 'first_name',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'First Name', false ),
			'title'          => wpforo_phrase( 'First Name', false ),
			'placeholder'    => wpforo_phrase( 'First Name', false ),
			'faIcon'         => 'fas fa-address-card',
			'name'           => 'first_name',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids,
			'can'            => '',
			'isSearchable'   => 0,
		];

		$this->fields['last_name'] = [
			'fieldKey'       => 'last_name',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Last Name', false ),
			'title'          => wpforo_phrase( 'Last Name', false ),
			'placeholder'    => wpforo_phrase( 'Last Name', false ),
			'faIcon'         => 'fas fa-address-card',
			'name'           => 'last_name',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids,
			'can'            => '',
			'isSearchable'   => 0,
		];

		$this->fields['title'] = [
			'fieldKey'       => 'title',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Title', false ),
			'title'          => wpforo_phrase( 'Title', false ),
			'placeholder'    => wpforo_phrase( 'Title', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-user',
			'name'           => 'title',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => WPF()->usergroup->get_groupids_by_can( 'vmt' ),
			'can'            => 'vmt',
			'isSearchable'   => 1,
		];

		$this->fields['groupid'] = [
			'fieldKey'        => 'groupid',
			'type'            => 'usergroup',
			'isDefault'       => 1,
			'isRemovable'     => 0,
			'isRequired'      => 1,
			'isEditable'      => 1,
			'label'           => wpforo_phrase( 'User Group', false ),
			'title'           => wpforo_phrase( 'User Group', false ),
			'placeholder'     => wpforo_phrase( 'User Group', false ),
			'faIcon'          => 'fas fa-users',
			'name'            => 'groupid',
			'allowedGroupIds' => [ 3, 5 ],
			'cantBeInactive'  => [],
			'canEdit'         => $usergroupids_can_edit_fields,
			'canView'         => $usergroupids,
			'can'             => '',
			'isSearchable'    => 1,
		];

		$this->fields['secondary_groupids'] = [
			'fieldKey'        => 'secondary_groupids',
			'type'            => 'secondary_groups',
			'isWrapItem'      => 1,
			'isDefault'       => 1,
			'isRemovable'     => 0,
			'isRequired'      => 0,
			'isEditable'      => 1,
			'label'           => wpforo_phrase( 'User Groups Secondary', false ),
			'title'           => wpforo_phrase( 'User Groups Secondary', false ),
			'placeholder'     => '',
			'faIcon'          => '',
			'values'          => '',
			'name'            => 'secondary_groupids',
			'allowedGroupIds' => WPF()->usergroup->get_secondary_groupids(),
			'cantBeInactive'  => [],
			'canEdit'         => $usergroupids_can_edit_fields,
			'canView'         => $usergroupids,
			'can'             => '',
			'isSearchable'    => 1,
		];

		$this->fields['avatar'] = [
			'fieldKey'       => 'avatar',
			'type'           => 'avatar',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Avatar', false ),
			'title'          => wpforo_phrase( 'Avatar', false ),
			'placeholder'    => wpforo_phrase( 'Avatar', false ),
			'name'           => 'avatar',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => WPF()->usergroup->get_groupids_by_can( 'va' ),
			'can'            => 'va',
			'isSearchable'   => 0,
		];

		$this->fields['user_url'] = [
			'fieldKey'       => 'user_url',
			'type'           => 'url',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Website', false ),
			'title'          => wpforo_phrase( 'Website', false ),
			'placeholder'    => wpforo_phrase( 'Website', false ),
			'faIcon'         => 'fas fa-sitemap',
			'name'           => 'user_url',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => WPF()->usergroup->get_groupids_by_can( 'vmw' ),
			'can'            => 'vmw',
			'isSearchable'   => 1,
		];

		$this->fields['facebook'] = [
			'fieldKey'       => 'facebook',
			'type'           => 'url',
			'isDefault'      => 0,
			'isRemovable'    => 1,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Facebook', false ),
			'title'          => wpforo_phrase( 'Facebook', false ),
			'placeholder'    => wpforo_phrase( 'Facebook', false ),
			'faIcon'         => 'fab fa-facebook',
			'name'           => 'facebook',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 1,
		];

		$this->fields['twitter'] = [
			'fieldKey'       => 'twitter',
			'type'           => 'url',
			'isDefault'      => 0,
			'isRemovable'    => 1,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Twitter', false ),
			'title'          => wpforo_phrase( 'Twitter', false ),
			'placeholder'    => wpforo_phrase( 'Twitter', false ),
			'faIcon'         => 'fab fa-twitter-square',
			'name'           => 'twitter',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 1,
		];

		$this->fields['youtube'] = [
			'fieldKey'       => 'youtube',
			'type'           => 'url',
			'isDefault'      => 0,
			'isRemovable'    => 1,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'YouTube', false ),
			'title'          => wpforo_phrase( 'YouTube', false ),
			'placeholder'    => wpforo_phrase( 'YouTube', false ),
			'faIcon'         => 'fab fa-youtube',
			'name'           => 'youtube',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 1,
		];

		$this->fields['vkontakte'] = [
			'fieldKey'       => 'vkontakte',
			'type'           => 'url',
			'isDefault'      => 0,
			'isRemovable'    => 1,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'VKontakte', false ),
			'title'          => wpforo_phrase( 'VKontakte', false ),
			'placeholder'    => wpforo_phrase( 'VKontakte', false ),
			'faIcon'         => 'fab fa-vk',
			'name'           => 'vkontakte',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 1,
		];

		$this->fields['linkedin'] = [
			'fieldKey'       => 'linkedin',
			'type'           => 'url',
			'isDefault'      => 0,
			'isRemovable'    => 1,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'LinkedIn', false ),
			'title'          => wpforo_phrase( 'LinkedIn', false ),
			'placeholder'    => wpforo_phrase( 'LinkedIn', false ),
			'faIcon'         => 'fab fa-linkedin-in',
			'name'           => 'linkedin',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 1,
		];

		$this->fields['telegram'] = [
			'fieldKey'       => 'telegram',
			'type'           => 'text',
			'isDefault'      => 0,
			'isRemovable'    => 1,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Telegram', false ),
			'title'          => wpforo_phrase( 'Telegram', false ),
			'placeholder'    => wpforo_phrase( 'Telegram', false ),
			'faIcon'         => 'fab fa-telegram-plane',
			'name'           => 'telegram',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 1,
		];

		$this->fields['instagram'] = [
			'fieldKey'       => 'instagram',
			'type'           => 'url',
			'isDefault'      => 0,
			'isRemovable'    => 1,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Instagram', false ),
			'title'          => wpforo_phrase( 'Instagram', false ),
			'placeholder'    => wpforo_phrase( 'Instagram', false ),
			'faIcon'         => 'fab fa-instagram',
			'name'           => 'instagram',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 1,
		];

		$this->fields['skype'] = [
			'fieldKey'       => 'skype',
			'type'           => 'text',
			'isDefault'      => 0,
			'isRemovable'    => 1,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Skype', false ),
			'title'          => wpforo_phrase( 'Skype', false ),
			'placeholder'    => wpforo_phrase( 'Skype', false ),
			'faIcon'         => 'fab fa-skype',
			'name'           => 'skype',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 1,
		];

		$this->fields['location'] = [
			'fieldKey'       => 'location',
			'type'           => 'select',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Location', false ),
			'title'          => wpforo_phrase( 'Location', false ),
			'placeholder'    => wpforo_phrase( 'Location', false ),
			'faIcon'         => 'fas fa-globe',
			'values'         => $this->countries,
			'name'           => 'location',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => WPF()->usergroup->get_groupids_by_can( 'vml' ),
			'can'            => 'vml',
			'isSearchable'   => 1,
		];

		$this->fields['timezone'] = [
			'fieldKey'       => 'timezone',
			'type'           => 'select',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Timezone', false ),
			'title'          => wpforo_phrase( 'Timezone', false ),
			'placeholder'    => wpforo_phrase( 'Timezone', false ),
			'faIcon'         => 'fas fa-globe',
			'values'         => $this->timezones,
			'name'           => 'timezone',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids,
			'can'            => '',
			'isSearchable'   => 1,
		];

		$this->fields['occupation'] = [
			'fieldKey'       => 'occupation',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Occupation', false ),
			'title'          => wpforo_phrase( 'Occupation', false ),
			'placeholder'    => wpforo_phrase( 'Occupation', false ),
			'faIcon'         => 'fas fa-address-card',
			'name'           => 'occupation',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => WPF()->usergroup->get_groupids_by_can( 'vmo' ),
			'can'            => 'vmo',
			'isSearchable'   => 1,
		];

		$this->fields['signature'] = [
			'fieldKey'           => 'signature',
			'type'               => 'tinymce',
			'wp_editor_settings' => $wp_editor_settings,
			'isDefault'          => 1,
			'isRemovable'        => 0,
			'isRequired'         => 0,
			'isEditable'         => 1,
			'label'              => wpforo_phrase( 'Signature', false ),
			'title'              => wpforo_phrase( 'Signature', false ),
			'placeholder'        => wpforo_phrase( 'Signature', false ),
			'faIcon'             => 'fas fa-address-card',
			'name'               => 'signature',
			'cantBeInactive'     => [],
			'canEdit'            => $usergroupids_can_edit_fields,
			'canView'            => WPF()->usergroup->get_groupids_by_can( 'vms' ),
			'can'                => 'vms',
			'isSearchable'       => 1,
		];

		$this->fields['about'] = [
			'fieldKey'           => 'about',
			'type'               => 'tinymce',
			'wp_editor_settings' => $wp_editor_settings,
			'isDefault'          => 1,
			'isRemovable'        => 0,
			'isRequired'         => 0,
			'isEditable'         => 1,
			'label'              => wpforo_phrase( 'About Me', false ),
			'title'              => wpforo_phrase( 'About Me', false ),
			'placeholder'        => wpforo_phrase( 'About Me', false ),
			'faIcon'             => 'fas fa-address-card',
			'name'               => 'about',
			'cantBeInactive'     => [],
			'canEdit'            => $usergroupids_can_edit_fields,
			'canView'            => WPF()->usergroup->get_groupids_by_can( 'vmam' ),
			'can'                => 'vmam',
			'isSearchable'       => 1,
		];

		$this->fields['html_soc_net'] = [
			'fieldKey'       => 'html_soc_net',
			'type'           => 'html',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Social Networks', false ),
			'title'          => wpforo_phrase( 'Social Networks', false ),
			'placeholder'    => wpforo_phrase( 'Social Networks', false ),
			'description'    => wpforo_phrase( 'Social Networks', false ),
			'html'           => '<div class="wpf-label">' . wpforo_phrase( 'Social Networks', false ) . '</div>',
			'name'           => 'html_soc_net',
			'cantBeInactive' => [],
			'canEdit'        => $usergroupids_can_edit_fields,
			'canView'        => $usergroupids_can_view_social_net,
			'can'            => 'vmsn',
			'isSearchable'   => 0,
		];

		$this->fields = apply_filters( 'wpforo_member_after_init_fields', $this->fields );
	}

	private function init_countries() {
		$this->countries = [
			"Afghanistan",
			"Åland Islands",
			"Albania",
			"Algeria",
			"American Samoa",
			"Andorra",
			"Angola",
			"Anguilla",
			"Antarctica",
			"Antigua and Barbuda",
			"Argentina",
			"Armenia",
			"Aruba",
			"Australia",
			"Austria",
			"Azerbaijan",
			"Bahamas",
			"Bahrain",
			"Bangladesh",
			"Barbados",
			"Belarus",
			"Belgium",
			"Belize",
			"Benin",
			"Bermuda",
			"Bhutan",
			"Bolivia",
			"Bosnia and Herzegovina",
			"Botswana",
			"Bouvet Island",
			"Brazil",
			"British Indian Ocean Territory",
			"Brunei Darussalam",
			"Bulgaria",
			"Burkina Faso",
			"Burundi",
			"Cambodia",
			"Cameroon",
			"Canada",
			"Cape Verde",
			"Cayman Islands",
			"Central African Republic",
			"Chad",
			"Chile",
			"China",
			"Christmas Island",
			"Cocos (Keeling) Islands",
			"Colombia",
			"Comoros",
			"Congo",
			"Congo, The Democratic Republic of The",
			"Cook Islands",
			"Costa Rica",
			"Cote D'ivoire",
			"Croatia",
			"Cuba",
			"Cyprus",
			"Czech Republic",
			"Denmark",
			"Djibouti",
			"Dominica",
			"Dominican Republic",
			"Ecuador",
			"Egypt",
			"El Salvador",
			"Equatorial Guinea",
			"Eritrea",
			"Estonia",
			"Ethiopia",
			"Falkland Islands (Malvinas)",
			"Faroe Islands",
			"Fiji",
			"Finland",
			"France",
			"French Guiana",
			"French Polynesia",
			"French Southern Territories",
			"Gabon",
			"Gambia",
			"Georgia",
			"Germany",
			"Ghana",
			"Gibraltar",
			"Greece",
			"Greenland",
			"Grenada",
			"Guadeloupe",
			"Guam",
			"Guatemala",
			"Guernsey",
			"Guinea",
			"Guinea-bissau",
			"Guyana",
			"Haiti",
			"Heard Island and Mcdonald Islands",
			"Holy See (Vatican City State)",
			"Honduras",
			"Hong Kong",
			"Hungary",
			"Iceland",
			"India",
			"Indonesia",
			"Iran, Islamic Republic of",
			"Iraq",
			"Ireland",
			"Isle of Man",
			"Israel",
			"Italy",
			"Jamaica",
			"Japan",
			"Jersey",
			"Jordan",
			"Kazakhstan",
			"Kenya",
			"Kiribati",
			"Korea, Democratic People's Republic of",
			"Korea, Republic of",
			"Kuwait",
			"Kyrgyzstan",
			"Lao People's Democratic Republic",
			"Latvia",
			"Lebanon",
			"Lesotho",
			"Liberia",
			"Libyan Arab Jamahiriya",
			"Liechtenstein",
			"Lithuania",
			"Luxembourg",
			"Macao",
			"Macedonia, The Former Yugoslav Republic of",
			"Madagascar",
			"Malawi",
			"Malaysia",
			"Maldives",
			"Mali",
			"Malta",
			"Marshall Islands",
			"Martinique",
			"Mauritania",
			"Mauritius",
			"Mayotte",
			"Mexico",
			"Micronesia, Federated States of",
			"Moldova, Republic of",
			"Monaco",
			"Mongolia",
			"Montenegro",
			"Montserrat",
			"Morocco",
			"Mozambique",
			"Myanmar",
			"Namibia",
			"Nauru",
			"Nepal",
			"Netherlands",
			"Netherlands Antilles",
			"New Caledonia",
			"New Zealand",
			"Nicaragua",
			"Niger",
			"Nigeria",
			"Niue",
			"Norfolk Island",
			"Northern Mariana Islands",
			"Norway",
			"Oman",
			"Pakistan",
			"Palau",
			"Palestinian Territory, Occupied",
			"Panama",
			"Papua New Guinea",
			"Paraguay",
			"Peru",
			"Philippines",
			"Pitcairn",
			"Poland",
			"Portugal",
			"Puerto Rico",
			"Qatar",
			"Reunion",
			"Romania",
			"Russian Federation",
			"Rwanda",
			"Saint Helena",
			"Saint Kitts and Nevis",
			"Saint Lucia",
			"Saint Pierre and Miquelon",
			"Saint Vincent and The Grenadines",
			"Samoa",
			"San Marino",
			"Sao Tome and Principe",
			"Saudi Arabia",
			"Senegal",
			"Serbia",
			"Seychelles",
			"Sierra Leone",
			"Singapore",
			"Slovakia",
			"Slovenia",
			"Solomon Islands",
			"Somalia",
			"South Africa",
			"South Georgia and The South Sandwich Islands",
			"Spain",
			"Sri Lanka",
			"Sudan",
			"Suriname",
			"Svalbard and Jan Mayen",
			"Swaziland",
			"Sweden",
			"Switzerland",
			"Syrian Arab Republic",
			"Taiwan, Province of China",
			"Tajikistan",
			"Tanzania, United Republic of",
			"Thailand",
			"Timor-leste",
			"Togo",
			"Tokelau",
			"Tonga",
			"Trinidad and Tobago",
			"Tunisia",
			"Turkey",
			"Turkmenistan",
			"Turks and Caicos Islands",
			"Tuvalu",
			"Uganda",
			"Ukraine",
			"United Arab Emirates",
			"United Kingdom",
			"United States",
			"United States Minor Outlying Islands",
			"Uruguay",
			"Uzbekistan",
			"Vanuatu",
			"Venezuela",
			"Viet Nam",
			"Virgin Islands, British",
			"Virgin Islands, U.S.",
			"Wallis and Futuna",
			"Western Sahara",
			"Yemen",
			"Zambia",
			"Zimbabwe",
		];
	}

	private function init_timezones() {
		$this->timezones = timezone_identifiers_list();
		$offset_range    = [
			- 12,
			- 11.5,
			- 11,
			- 10.5,
			- 10,
			- 9.5,
			- 9,
			- 8.5,
			- 8,
			- 7.5,
			- 7,
			- 6.5,
			- 6,
			- 5.5,
			- 5,
			- 4.5,
			- 4,
			- 3.5,
			- 3,
			- 2.5,
			- 2,
			- 1.5,
			- 1,
			- 0.5,
			0,
			0.5,
			1,
			1.5,
			2,
			2.5,
			3,
			3.5,
			4,
			4.5,
			5,
			5.5,
			5.75,
			6,
			6.5,
			7,
			7.5,
			8,
			8.5,
			8.75,
			9,
			9.5,
			10,
			10.5,
			11,
			11.5,
			12,
			12.75,
			13,
			13.75,
			14,
		];
		foreach( $offset_range as $offset ) {
			if( 0 <= $offset ) {
				$offset_name = '+' . $offset;
			} else {
				$offset_name = (string) $offset;
			}

			$offset_value      = $offset_name;
			$offset_value      = 'UTC' . $offset_value;
			$this->timezones[] = 'UTC/' . $offset_value;
		}

		$zones     = $this->timezones;
		$timezones = [];
		foreach( $zones as $zone ) {
			if( strpos( $zone, '/' ) === false ) continue;

			$zone = str_replace( '_', ' ', $zone );

			$group       = function_exists( 'mb_substr' ) ? mb_substr( $zone, 0, strpos( $zone, '/' ) ) : substr( $zone, 0, strpos( $zone, '/' ) );
			$index       = function_exists( 'mb_strlen' ) ? mb_strlen( $group ) + 1 : strlen( $group ) + 1;
			$optionValue = substr( $zone, $index );

			if( strpos( $optionValue, 'UTC' ) !== false ) {
				$optionTitle = str_replace( [ '.25', '.5', '.75', ], [ ':15', ':30', ':45', ], $optionValue );
				$optionValue = "$optionValue=>$optionTitle";
			} else {
				$optionValue = "$zone=>$optionValue";
			}

			$timezones[ $group ][] = $optionValue;
		}

		$this->timezones = $timezones;
	}

	public function get_fields( $only_defaults = false ) {
		$this->init_fields();
		$fields = $this->fields;
		if( $only_defaults ) {
			foreach( $fields as $k => $v ) if( ! wpfval( $v, 'isDefault' ) ) unset( $fields[ $k ] );
		} else {
			$this->fields = $fields = apply_filters( 'wpforo_get_fields', $fields );
		}

		return $fields;
	}

	public function get_field( $key ) {
		if( is_string( $key ) ) {
			$this->init_fields();

			return (array) wpfval( $this->fields, $key );
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

	public function fields_structure_full_array( $fields, $need_password = null ) {
		if( is_string( $fields ) ) $fields = maybe_unserialize( $fields );
		$fs = [ [ [] ] ];
		if( ! is_array( $fields ) ) return $fs;
		if( is_null( $need_password ) ) {
			foreach( $fields as $kr => $row ) {
				if( is_array( $row ) ) {
					foreach( $row as $kc => $cols ) {
						if( is_array( $cols ) ) {
							foreach( $cols as $field ) {
								$field_key                      = $this->get_field_key( $field );
								$fs[ $kr ][ $kc ][ $field_key ] = $this->get_field( $field );
							}
						}
					}
				}
			}
		} else {
			$has_password = false;
			foreach( $fields as $kr => $row ) {
				if( is_array( $row ) ) {
					foreach( $row as $kc => $cols ) {
						if( is_array( $cols ) ) {
							foreach( $cols as $field ) {
								$field_key = $this->get_field_key( $field );
								if( $field_key === 'user_pass' ) {
									if( $need_password ) {
										$has_password                   = true;
										$fs[ $kr ][ $kc ][ $field_key ] = $this->get_field( $field );
									}
								} else {
									$fs[ $kr ][ $kc ][ $field_key ] = $this->get_field( $field );
								}
							}
						}
					}
				}
			}
			if( $need_password && ! $has_password ) $fs[][][] = $this->get_field( 'user_pass' );
		}

		return $fs;
	}

	public function get_register_fields_structure( $only_defaults = false ) {
		$need_password = ! wpforo_setting( 'authorization', 'user_register_email_confirm' );
		$regform       = [ 'user_login', 'user_email' ];
		if( $need_password ) $regform[] = 'user_pass';
		$fields = [ [ $regform ] ];
		if( ! $only_defaults ) $fields = apply_filters( 'wpforo_get_register_fields', $fields );

		return $fields;
	}

	public function get_register_fields( $only_defaults = false ) {
		return $this->fields_structure_full_array( $this->get_register_fields_structure( $only_defaults ), ! wpforo_setting( 'authorization', 'user_register_email_confirm' ) );
	}

	public function get_account_fields_structure( $only_defaults = false ) {
		$fields = [
			[
				[
					'user_login',
					'display_name',
					'user_nicename',
					'user_email',
					'title',
					'groupid',
					'avatar',
					'about',
					'user_url',
					'occupation',
					'signature',
				],
			],
			[
				[
					'html_soc_net',
				],
				[
					'facebook',
					'linkedin',
					'instagram',
					'vkontakte',
				],
				[
					'twitter',
					'youtube',
					'telegram',
					'skype',
				],
			],
			[
				[
					'location',
					'timezone',
					'user_pass',
				],
			],
		];
		if( ! $only_defaults ) $fields = apply_filters( 'wpforo_get_account_fields', $fields );

		return $fields;
	}

	public function get_account_fields( $only_defaults = false ) {
		return $this->fields_structure_full_array( $this->get_account_fields_structure( $only_defaults ) );
	}

	public function get_profile_fields_structure( $only_defaults = false ) {
		$fields = [
			[
				[
					'about',
					'user_url',
				],
			],
			[
				[
					'location',
					'timezone',
					'occupation',
					'signature',
				],
			],
			[
				[
					'html_soc_net',
				],
			],
			[
				[
					'facebook',
					'linkedin',
					'instagram',
					'vkontakte',
				],
				[
					'twitter',
					'youtube',
					'telegram',
					'skype',
				],
			],
		];
		if( ! $only_defaults ) $fields = apply_filters( 'wpforo_get_profile_fields', $fields );

		return $fields;
	}

	public function get_profile_fields( $only_defaults = false ) {
		return $this->fields_structure_full_array( $this->get_profile_fields_structure( $only_defaults ) );
	}

	public function get_search_fields_structure( $only_defaults = false ) {
		$fields = [ [ [ 'display_name', 'user_nicename' ] ] ];
		if( ! $only_defaults ) $fields = apply_filters( 'wpforo_get_search_fields', $fields );

		return $fields;
	}

	public function get_search_fields( $only_defaults = false ) {
		$fields = $this->fields_structure_full_array( $this->get_search_fields_structure( $only_defaults ) );
		foreach( $fields as $row_key => $row ) {
			foreach( $row as $col_key => $col ) {
				foreach( $col as $field_key => $field ) {
					if( $this->is_field( $field ) ) {
						$fields[ $row_key ][ $col_key ][ $field_key ]['isRequired'] = 0;
						$fields[ $row_key ][ $col_key ][ $field_key ]['class']      = 'wpf-member-search-field';
						if( in_array( $field['type'], [ 'text', 'textarea', 'email', 'url' ], true ) ) {
							$fields[ $row_key ][ $col_key ][ $field_key ]['type'] = 'search';
						}
					}
				}
			}
		}

		return $fields;
	}

	public function get_search_fields_names( $only_defaults = false ) {
		$names  = [];
		$fields = $this->get_search_fields( $only_defaults );

		foreach( $fields as $row ) {
			foreach( $row as $col ) {
				foreach( $col as $field ) {
					if( $only_defaults && ! $field['isDefault'] ) continue;
					$names[] = $field['name'];
				}
			}
		}

		return $names;
	}

	public function set_guest_cookies( $args ) {
		if( ! WPF()->tools_legal['cookies'] ) return;
		if( isset( $args['name'] ) && isset( $args['email'] ) ) {
			$comment_cookie_lifetime = apply_filters( 'comment_cookie_lifetime', 30000000 );
			$secure                  = ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );
			setcookie( 'comment_author_' . COOKIEHASH, $args['name'], time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );
			setcookie( 'comment_author_email_' . COOKIEHASH, $args['email'], time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN, $secure );

			WPF()->current_user_display_name = $args['name'];
			WPF()->current_user_email        = $args['email'];
		}
	}

	public function get_guest_cookies() {
		$guest = [ 'name' => '', 'email' => '' ];
		if( WPF()->tools_legal['cookies'] ) {
			if( ! WPF()->current_userid && WPF()->current_user_email ) {
				$guest['name']  = WPF()->current_user_display_name;
				$guest['email'] = WPF()->current_user_email;
			} else {
				$guest_cookies  = wp_get_current_commenter();
				$guest['name']  = ( isset( $guest_cookies['comment_author'] ) ) ? $guest_cookies['comment_author'] : '';
				$guest['email'] = ( isset( $guest_cookies['comment_author_email'] ) ) ? $guest_cookies['comment_author_email'] : '';
			}
		}

		return $guest;
	}

	public function set_is_email_confirmed( $userid, $status ) {
		if( false !== WPF()->db->update( WPF()->tables->profiles, [ 'is_email_confirmed' => intval( $status ) ], [ 'userid' => wpforo_bigintval( $userid ) ], [ '%d' ], [ '%d' ] ) ) {
			WPF()->notice->add( 'Email has been confirmed', 'success' );

			return true;
		}
		WPF()->notice->add( 'Email confirm error', 'error' );

		return false;
	}

	public function get_is_email_confirmed( $userid ) {
		$sql = "SELECT `is_email_confirmed` FROM `" . WPF()->tables->profiles . "` WHERE `userid` = %d";

		return (bool) WPF()->db->get_var( WPF()->db->prepare( $sql, $userid ) );
	}

	/**
	 * @param int $userid
	 *
	 * @return int
	 */
	public function get_groupid( $userid ) {
		return (int) WPF()->db->get_var(
            WPF()->db->prepare(
            "SELECT `groupid` 
                FROM `" . WPF()->tables->profiles . "` 
                WHERE `userid` = %d",
                $userid
            )
        );
	}

	public function set_groupid( $userid, $groupid ) {
		$userid  = wpforo_bigintval( $userid );
		$groupid = intval( $groupid );
		if( $userid && $groupid ) {
			$sql = "UPDATE `" . WPF()->tables->profiles . "` SET `groupid` = %d WHERE `userid` = %d";
			if( false !== WPF()->db->query( WPF()->db->prepare( $sql, $groupid, $userid ) ) ) {
				$this->reset( $userid );
				do_action( 'wpforo_set_groupid', $userid, $groupid );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $userid
	 * @param array|int $groupids
	 *
	 * @return bool
	 */
	public function set_secondary_groupids( $userid, $groupids ) {
		$userid = wpforo_bigintval( $userid );
		if( $userid ) {
			$groupids = implode( ',', array_unique( array_filter( array_map( 'intval', (array) $groupids ) ) ) );
			$sql      = "UPDATE `" . WPF()->tables->profiles . "` SET `secondary_groupids` = %s WHERE `userid` = %d";
			if( false !== WPF()->db->query( WPF()->db->prepare( $sql, $groupids, $userid ) ) ) {
				$this->reset( $userid );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $userid
	 * @param array|int $groupids
	 *
	 * @return bool
	 */
	public function append_secondary_groupids( $userid, $groupids ) {
		$userid = wpforo_bigintval( $userid );
		if( $userid ) {
			if( $member = $this->get_member( $userid ) ) {
				$groupids = array_filter( array_map( 'intval', (array) $groupids ) );
				$groupids = array_merge( $member['secondary_groupids'], $groupids );

				return $this->set_secondary_groupids( $userid, $groupids );
			}
		}

		return false;
	}

	public function after_register_new_user( $userid ) {
		$default_password_nag = (bool) (int) trim( get_user_meta( $userid, 'default_password_nag', true ) );
		if( $default_password_nag ) $this->update_profile_fields( $userid, [ 'status' => 'inactive' ], false );
	}

	public function after_password_reset( $user ) {
		$this->update_profile_fields( $user->ID, [ 'status' => 'active', 'is_email_confirmed' => 1 ], false );
		$this->reset( $user->ID );
	}

	public function wp_login( $user_login, $user ) {
		$this->inactive_to_active( wpforo_bigintval( $user->ID ), $user_login );
    }

	public function get_distinct_status() {
		$sql = "SELECT DISTINCT `status` as statuses FROM `" . WPF()->tables->profiles . "`";

		return WPF()->db->get_col( $sql );
	}

	/**
	 * @param array $args
	 *
	 * @return array selected userids
	 */
	public function get_userids( $args ) {
		$sql = "SELECT `userid` FROM `" . WPF()->tables->profiles . "`";
		if( $args ) {
			$wheres = [];
			foreach( $args as $field => $value ) {
				$wheres[] = "`" . $field . "` = '" . esc_sql( $value ) . "'";
			}
			if( $wheres ) $sql .= " WHERE " . implode( " AND ", $wheres );
		}

		$userids = WPF()->db->get_col( $sql );

		return apply_filters( 'wpforo_member_after_get_userids', $userids );
	}

    public function get_inlist_enabled_statuses(){
        return wpforo_setting( 'members', 'hide_inactive' ) ? ['active'] : ['active', 'inactive', 'banned'];
    }

	/**
	 * @param int $userid
	 *
	 * @return int
	 */
	public function calc_approved_posts( $userid ) {
        if( $userid = wpforo_bigintval( $userid ) ){
	        $sqls = [];
	        foreach( WPF()->get_active_boards_tables( 'posts') as $table ){
		        $sqls[] = WPF()->db->prepare(
			        "SELECT COUNT(*) 
                        FROM `$table` 
                        WHERE `status` = 0 
                        AND `userid` = %d",
			        $userid
		        );
	        }

            if( $sqls ){
	            return (int) WPF()->db->get_var( "SELECT SUM(`COUNT(*)`) FROM (". implode( ' UNION ALL ', $sqls ) .") AS temp" );
            }
        }

        return 0;
    }

	/**
	 * @param int $userid
	 *
	 * @return int
	 */
	public function calc_approved_topics( $userid ) {
		if( $userid = wpforo_bigintval( $userid ) ){
			$sqls = [];
			foreach( WPF()->get_active_boards_tables( 'topics') as $table ){
				$sqls[] = WPF()->db->prepare(
					"SELECT COUNT(*) 
                        FROM `$table` 
                        WHERE `status` = 0 
                        AND `userid` = %d",
					$userid
				);
			}

			if( $sqls ){
				return (int) WPF()->db->get_var( "SELECT SUM(`COUNT(*)`) FROM (". implode( ' UNION ALL ', $sqls ) .") AS temp" );
			}
		}

		return 0;
	}

	/**
	 * @param int $userid
	 *
	 * @return int
	 */
	public function calc_approved_questions( $userid ) {
		if( $userid = wpforo_bigintval( $userid ) ){
			$sqls = [];
			foreach( WPF()->get_active_boards_tables( 'topics') as $table ){
                if( $forumids = Forums::get_forumids( str_replace( 'topics', 'forums', $table ), 3 ) ){
	                $sqls[] = WPF()->db->prepare(
		                "SELECT COUNT(*) 
                        FROM `$table` 
                        WHERE `status` = 0 
                        AND `userid` = %d
                        AND `forumid` IN( ". implode(',', $forumids) ." )",
		                $userid
	                );
                }
			}

			if( $sqls ){
				return (int) WPF()->db->get_var( "SELECT SUM(`COUNT(*)`) FROM (". implode( ' UNION ALL ', $sqls ) .") AS temp" );
			}
		}

		return 0;
	}

	/**
	 * @param int $userid
	 *
	 * @return int
	 */
	public function calc_approved_answers( $userid ) {
		if( $userid = wpforo_bigintval( $userid ) ){
			$sqls = [];
			foreach( WPF()->get_active_boards_tables( 'posts') as $table ){
				if( $forumids = Forums::get_forumids( str_replace( 'posts', 'forums', $table ), 3 ) ){
					$sqls[] = WPF()->db->prepare(
						"SELECT COUNT(*) 
                        FROM `$table` 
                        WHERE `status` = 0 
                        AND `is_first_post` = 0
                        AND `parentid` = 0
                        AND `userid` = %d
                        AND `forumid` IN( ". implode(',', $forumids) ." )",
						$userid
					);
				}
			}

			if( $sqls ){
				return (int) WPF()->db->get_var( "SELECT SUM(`COUNT(*)`) FROM (". implode( ' UNION ALL ', $sqls ) .") AS temp" );
			}
		}

		return 0;
	}

	/**
	 * @param int $userid
	 *
	 * @return int
	 */
	public function calc_approved_comments( $userid ) {
		if( $userid = wpforo_bigintval( $userid ) ){
			$sqls = [];
			foreach( WPF()->get_active_boards_tables( 'posts') as $table ){
				if( $forumids = Forums::get_forumids( str_replace( 'posts', 'forums', $table ), 3 ) ){
					$sqls[] = WPF()->db->prepare(
						"SELECT COUNT(*) 
                        FROM `$table` 
                        WHERE `status` = 0 
                        AND `is_first_post` = 0
                        AND `parentid` <> 0
                        AND `userid` = %d
                        AND `forumid` IN( ". implode(',', $forumids) ." )",
						$userid
					);
				}
			}

			if( $sqls ){
				return (int) WPF()->db->get_var( "SELECT SUM(`COUNT(*)`) FROM (". implode( ' UNION ALL ', $sqls ) .") AS temp" );
			}
		}

		return 0;
	}

	/**
	 * @param int $userid
	 *
	 * @return array
	 */
	public function calc_reactions_in( $userid ) {
		$reactions_in = array_map( '__return_zero', array_flip( Reactions::get_type_list() ) );
		if( $userid = wpforo_bigintval( $userid ) ){
			$sqls = [];
			foreach( WPF()->get_active_boards_tables( 'reactions') as $table ){
				$sqls[] = WPF()->db->prepare(
					"SELECT `type` 
                        FROM `$table` 
                        WHERE `post_userid` = %d",
					$userid
				);
			}

			if( $sqls ){
				$r = (array) WPF()->db->get_results( "SELECT `type`, COUNT(`type`) as `count` FROM (". implode( ' UNION ALL ', $sqls ) .") AS temp GROUP BY `type`", ARRAY_A );
                foreach( $r as $_r ) $reactions_in[$_r['type']] = intval( $_r['count'] );
			}
		}

		return $reactions_in;
	}

	/**
	 * @param int $userid
	 *
	 * @return array
	 */
	public function calc_reactions_out( $userid ) {
		$reactions_out = array_map( '__return_zero', array_flip( Reactions::get_type_list() ) );
		if( $userid = wpforo_bigintval( $userid ) ){
			$sqls = [];
			foreach( WPF()->get_active_boards_tables( 'reactions') as $table ){
				$sqls[] = WPF()->db->prepare(
					"SELECT `type` 
                        FROM `$table` 
                        WHERE `userid` = %d",
					$userid
				);
			}

			if( $sqls ){
				$r = (array) WPF()->db->get_results( "SELECT `type`, COUNT(`type`) as `count` FROM (". implode( ' UNION ALL ', $sqls ) .") AS temp GROUP BY `type`", ARRAY_A );
				foreach( $r as $_r ) $reactions_out[$_r['type']] = intval( $_r['count'] );
			}
		}

		return $reactions_out;
	}

	/**
	 * @param int $userid
	 *
	 * @return void
	 */
	public function rebuild_stats( $userid ) {
		$posts         = $this->calc_approved_posts( $userid );
		$topics        = $this->calc_approved_topics( $userid );
		$questions     = $this->calc_approved_questions( $userid );
		$answers       = $this->calc_approved_answers( $userid );
		$comments      = $this->calc_approved_comments( $userid );
		$reactions_in  = $this->calc_reactions_in( $userid );
		$reactions_out = $this->calc_reactions_out( $userid );
		$points        = $this->calc_points( compact( 'posts', 'topics', 'questions', 'answers', 'comments', 'reactions_in', 'reactions_out' ) );

		if(WPF()->db->update(
            WPF()->tables->profiles,
            [
	            'posts'         => $posts,
	            'topics'        => $topics,
	            'questions'     => $questions,
	            'answers'       => $answers,
	            'comments'      => $comments,
	            'reactions_in'  => json_encode( $reactions_in ),
	            'reactions_out' => json_encode( $reactions_out ),
                'points'        => $points,
            ],
            [ 'userid' => $userid ],
            ['%d','%d','%d','%d','%d','%s','%s','%d'],
            '%d'
        )){
			$this->reset( $userid );
		}
	}

	public function after_add_topic( $topic ) {
        if( !intval($topic['status']) ) $this->rebuild_stats( $topic['userid'] );
    }

	public function after_delete_topic( $topic ) {
		if( !intval($topic['status']) ) $this->rebuild_stats( $topic['userid'] );
	}

	public function after_topic_status_update( $topic ) {
        $this->rebuild_stats( $topic['userid'] );
    }

    public function before_merge_topic( $target, $current, $postids ){
        $sql = "SELECT DISTINCT `userid` FROM `". WPF()->tables->posts ."` WHERE `topicid` = %d";
	    $sql = WPF()->db->prepare( $sql, $current['topicid'] );
	    if( $postids ) $sql .= " AND `postid` IN(" . implode( ',', $postids ) . ")";
        $this->touched_userids = array_merge( $this->touched_userids, WPF()->db->get_col( $sql ) );
    }

    public function after_merge_topic(){
	    foreach( array_unique(array_filter( $this->touched_userids )) as $userid ) $this->rebuild_stats( $userid );
    }

	public function after_move_topic( $topic ) {
		$sql = "SELECT DISTINCT `userid` FROM `". WPF()->tables->posts ."` WHERE `topicid` = %d";
		$sql = WPF()->db->prepare( $sql, $topic['topicid'] );
        foreach( WPF()->db->get_col( $sql ) as $userid ) $this->rebuild_stats( $userid );
    }

    public function after_add_post( $post ){
        if( !intval( $post['status'] ) ) $this->rebuild_stats( $post['userid'] );
    }

	public function after_delete_post( $post ) {
        if( !intval( $post['status'] ) ) $this->rebuild_stats( $post['userid'] );
    }

	public function after_post_status_update( $post ) {
		$this->rebuild_stats( $post['userid'] );
	}

	public function after_add_reaction( $reaction ) {
        $this->rebuild_stats( $reaction['userid'] );
        $this->rebuild_stats( $reaction['post_userid'] );
    }

	public function after_edit_reaction( $fields, $where ) {
		$field_userid      = wpfval( $fields, 'userid' );
		$field_post_userid = wpfval( $fields, 'post_userid' );
		if( $field_userid || $field_post_userid ){
			$userids = [ $field_userid, $field_post_userid, wpfval( $where, 'userid' ), wpfval( $where, 'post_userid' ) ];
            $userids = array_filter( $userids );
            $userids = array_unique( $userids );
            foreach( $userids as $userid ) $this->rebuild_stats( $userid );
		}
    }

    public function before_delete_reaction( $args, $operator ){
	    $reactions = WPF()->reaction->get_reactions( $args, $operator );
        foreach( $reactions as $reaction ) {
	        $this->touched_userids[] = $reaction['userid'];
	        $this->touched_userids[] = $reaction['post_userid'];
        }
    }

    public function after_delete_reaction(){
        foreach( array_unique(array_filter( $this->touched_userids )) as $userid ) $this->rebuild_stats( $userid );
    }

	public function after_delete_user( $userid, $reassign ) {
		if( $reassign ) $this->rebuild_stats( $reassign );
	}

	public function get_activity_url( $filter = '', $boardid = null, $arg = null ) {
        if( !$arg ) $arg = WPF()->current_object['user'];
        $url = rtrim( $this->get_profile_url( $arg, 'activity' ), '/' );
        if( wpforo_is_multiboard() ){
	        if( is_null( $boardid ) ) $boardid = wpfkey( WPF()->GET, 'boardid' ) ? (int) wpfval( WPF()->GET, 'boardid' ) : WPF()->board->get_current( 'boardid' );
            if( $filter ){
	            $url .= sprintf( '/?boardid=%1$d&filter=%2$s', $boardid, $filter);
            }else{
	            $url .= sprintf( '/?boardid=%1$d', $boardid);
            }
        }elseif( $filter ){
            $url .= sprintf( '/?filter=%1$s', $filter);
        }
        return WPF()->user_trailingslashit( $url );
    }

    public function get_favored_url( $filter = '', $boardid = null, $arg = null ) {
        if( !$arg ) $arg = WPF()->current_object['user'];
        $url = rtrim( $this->get_profile_url( $arg, 'favored' ), '/' );
        if( wpforo_is_multiboard() ){
	        if( is_null( $boardid ) ) $boardid = wpfkey( WPF()->GET, 'boardid' ) ? (int) wpfval( WPF()->GET, 'boardid' ) : WPF()->board->get_current( 'boardid' );
            if( $filter ){
	            $url .= sprintf( '/?boardid=%1$d&filter=%2$s', $boardid, $filter);
            }else{
	            $url .= sprintf( '/?boardid=%1$d', $boardid);
            }
        }elseif( $filter ){
            $url .= sprintf( '/?filter=%1$s', $filter);
        }
        return WPF()->user_trailingslashit( $url );
    }
}
