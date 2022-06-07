<?php

namespace wpforo\classes;

use WP_Error;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class API {
	const  FB_SDK_VERSION = 'v2.10';
	public $locale     = 'en_US';
	public $locale_iso = 'en';
	public $fb_local   = [
		'af_ZA',
		'ar_AR',
		'az_AZ',
		'be_BY',
		'bg_BG',
		'bn_IN',
		'bs_BA',
		'ca_ES',
		'cs_CZ',
		'cy_GB',
		'da_DK',
		'de_DE',
		'el_GR',
		'en_US',
		'en_GB',
		'eo_EO',
		'es_ES',
		'es_LA',
		'et_EE',
		'eu_ES',
		'fa_IR',
		'fb_LT',
		'fi_FI',
		'fo_FO',
		'fr_FR',
		'fr_CA',
		'fy_NL',
		'ga_IE',
		'gl_ES',
		'he_IL',
		'hi_IN',
		'hr_HR',
		'hu_HU',
		'hy_AM',
		'id_ID',
		'is_IS',
		'it_IT',
		'ja_JP',
		'ka_GE',
		'km_KH',
		'ko_KR',
		'ku_TR',
		'la_VA',
		'lt_LT',
		'lv_LV',
		'mk_MK',
		'ml_IN',
		'ms_MY',
		'nb_NO',
		'ne_NP',
		'nl_NL',
		'nn_NO',
		'pa_IN',
		'pl_PL',
		'ps_AF',
		'pt_PT',
		'pt_BR',
		'ro_RO',
		'ru_RU',
		'sk_SK',
		'sl_SI',
		'sq_AL',
		'sr_RS',
		'sv_SE',
		'sw_KE',
		'ta_IN',
		'te_IN',
		'th_TH',
		'tl_PH',
		'tr_TR',
		'uk_UA',
		'vi_VN',
		'zh_CN',
		'zh_HK',
		'zh_TW',
	];
	public $tw_local   = [
		'en',
		'ar',
		'bn',
		'cs',
		'da',
		'de',
		'el',
		'es',
		'fa',
		'fi',
		'fil',
		'fr',
		'he',
		'hi',
		'hu',
		'id',
		'it',
		'ja',
		'ko',
		'msa',
		'nl',
		'no',
		'pl',
		'pt',
		'ro',
		'ru',
		'sv',
		'th',
		'tr',
		'uk',
		'ur',
		'vi',
		'zh-cn',
		'zh-tw',
	];
	public $ok_local   = [ "ru", "en", "uk", "hy", "mo", "ro", "kk", "uz", "az", "tr" ];

	public function __construct() {
		add_action( 'wpforo_after_init', function() {
			if( ! is_admin() ) {
				$this->init_wp_recaptcha();
				$this->hooks();
			}
		} );
	}

	private function hooks() {
		$template = WPF()->current_object['template'];

		###############################################################################
		############### Facebook & Twitter API ########################################
		###############################################################################

		if( ! is_user_logged_in() ) {
			if( wpforo_setting( 'authorization', 'fb_login' ) ) {
				if( in_array( $template, ['login','register'], true ) ) {
					add_action( 'wp_enqueue_scripts', [ $this, 'fb_enqueue' ] );
					add_action( 'wpforo_bottom_hook', [ $this, 'fb_login_sdk' ], 9 );
				}
				if( wpforo_setting( 'authorization', 'fb_api_id' ) && wpforo_setting( 'authorization', 'fb_api_secret' ) ) {
					if( wpforo_setting( 'authorization', 'fb_lb_on_lp' ) ) {
						add_action( 'wpforo_login_form_end', [ $this, 'fb_login_button' ] );
					}
					if( wpforo_setting( 'authorization', 'fb_lb_on_rp' ) ) {
						add_action( 'wpforo_register_form_end', [ $this, 'fb_login_button' ] );
					}
				}
				add_action( 'wp_ajax_wpforo_facebook_auth', [ $this, 'fb_auth' ] );
				add_action( 'wp_ajax_nopriv_wpforo_facebook_auth', [ $this, 'fb_auth' ] );
			}
		}

		if( is_wpforo_page() ) {
			if( apply_filters( 'wpforo_api_fb_load_sdk', true ) ) {
				add_action( 'wpforo_bottom_hook', [ $this, 'fb_sdk' ], 10 );
			}
			if( apply_filters( 'wpforo_api_tw_load_wjs', true ) && wpforo_setting( 'social', 'sb', 'tw' ) ) {
				add_action( 'wpforo_top_hook', [ $this, 'tw_wjs' ], 11 );
			}
			if( apply_filters( 'wpforo_api_vk_load_js', true ) && wpforo_setting( 'social', 'sb', 'vk' ) ) {
				add_action( 'wpforo_top_hook', [ $this, 'vk_js' ], 13 );
			}
		}

		###############################################################################
		############### reCAPTCHA API #################################################
		###############################################################################

		$site_key   = wpforo_setting( 'recaptcha', 'site_key' );
		$secret_key = wpforo_setting( 'recaptcha', 'secret_key' );

		if( ! is_user_logged_in() && $site_key && $secret_key ) {

			$wpf_reg_form      = wpforo_setting( 'recaptcha', 'wpf_reg_form' );
			$wpf_login_form    = wpforo_setting( 'recaptcha', 'wpf_login_form' );
			$wpf_lostpass_form = wpforo_setting( 'recaptcha', 'wpf_lostpass_form' );

			add_filter( 'script_loader_tag', [ &$this, 'rc_enqueue_async' ], 10, 3 );

			//Verification Hooks: Login / Register / Reset Pass
			if( $wpf_login_form ) add_filter( 'wp_authenticate_user', [ $this, 'rc_verify_wp_login' ], 15, 2 );
			if( $wpf_reg_form ) add_filter( 'registration_errors', [ $this, 'rc_verify_wp_register' ], 10, 3 );
			if( $wpf_lostpass_form ) add_action( 'lostpassword_post', [ $this, 'rc_verify_wp_lostpassword' ], 10 );

			//Load reCAPTCHA API on wpForo pages: Login / Register / Reset Pass
			if( in_array( $template, [ 'login', 'register', 'lostpassword' ], true ) ) {
				if( $wpf_reg_form || $wpf_login_form || $wpf_lostpass_form ) {
					add_action( 'wp_enqueue_scripts', [ $this, 'rc_enqueue' ] );
				}
			}

			//Load reCAPTCHA Widget wpForo forms: Login / Register / Reset Pass
			if( $wpf_login_form && $template === 'login' ) add_action( 'login_form', [ $this, 'rc_widget' ] );
			if( $wpf_reg_form && $template === 'register' ) add_action( 'register_form', [ $this, 'rc_widget' ] );
			if( $wpf_lostpass_form && $template === 'lostpassword' ) {
				add_action( 'lostpassword_form', [ $this, 'rc_widget' ] );
			}

			//Load reCAPTCHA API and Widget for Topic and Post Editor
			if( $template === 'forum' || $template === 'topic' || $template === 'post' ) {
				add_action( 'wp_enqueue_scripts', [ $this, 'rc_enqueue' ] );
				add_action( 'wpforo_verify_form_end', [ $this, 'rc_verify' ] );
				if( wpforo_setting( 'recaptcha', 'topic_editor' ) ) {
					add_action( 'wpforo_topic_form_extra_fields_after', [ $this, 'rc_widget' ] );
				}
				if( wpforo_setting( 'recaptcha', 'post_editor' ) ) {
					add_action( 'wpforo_reply_form_extra_fields_after', [ $this, 'rc_widget' ] );
					add_action( 'wpforo_portable_form_extra_fields_after', [ $this, 'rc_widget' ] );
				}
			}
		}

		###############################################################################
	}

	private function init_wp_recaptcha() {
		$template   = WPF()->current_object['template'];
		$site_key   = wpforo_setting( 'recaptcha', 'site_key' );
		$secret_key = wpforo_setting( 'recaptcha', 'secret_key' );

		if( ! is_user_logged_in() && $site_key && $secret_key ) {
			$reg_form      = wpforo_setting( 'recaptcha', 'reg_form' );
			$login_form    = wpforo_setting( 'recaptcha', 'login_form' );
			$lostpass_form = wpforo_setting( 'recaptcha', 'lostpass_form' );

			//Verification Hooks: Login / Register / Reset Pass
			if( $login_form ) add_filter( 'wp_authenticate_user', [ $this, 'rc_verify_wp_login' ], 15, 2 );
			if( $reg_form ) add_filter( 'registration_errors', [ $this, 'rc_verify_wp_register' ], 10, 3 );
			if( $lostpass_form ) add_action( 'lostpassword_post', [ $this, 'rc_verify_wp_lostpassword' ], 10 );

			//Load reCAPTCHA API and Widget on wp-login.php
			if( $reg_form || $login_form || $lostpass_form ) {
				add_action( 'login_enqueue_scripts', [ $this, 'rc_enqueue' ] );
				add_action( 'login_enqueue_scripts', [ $this, 'rc_enqueue_css' ] );
				if( $login_form && $template !== 'login' ) add_action( 'login_form', [ $this, 'rc_widget' ] );
				if( $reg_form && $template !== 'register' ) add_action( 'register_form', [ $this, 'rc_widget' ] );
				if( $lostpass_form && $template !== 'lostpassword' ) {
					add_action( 'lostpassword_form', [ $this, 'rc_widget' ] );
				}
			}
		}
	}

	public function local( $api ) {
		$wplocal     = get_locale();
		$wplocal_iso = substr( $wplocal, 0, 2 );

		if( $api === 'fb' ) {
			if( in_array( $wplocal, $this->fb_local ) ) {
				return $wplocal;
			} else {
				return $this->locale;
			}
		} elseif( $api === 'tw' ) {
			if( in_array( $wplocal_iso, $this->tw_local ) ) {
				return $wplocal_iso;
			} else {
				return $this->locale_iso;
			}
		} elseif( $api === 'vk' ) {
			return $wplocal_iso;
		} elseif( $api === 'ok' ) {
			if( in_array( $wplocal_iso, $this->ok_local ) ) {
				return $wplocal_iso;
			} else {
				return $this->locale_iso;
			}
		}

        return $wplocal;
	}

	public function fb_enqueue() {
		$app_id = wpforo_setting( 'authorization', 'fb_api_id' );
		wp_register_script( 'wpforo-snfb', WPFORO_URL . '/assets/js/snfb.js', [ 'jquery' ], WPFORO_VERSION, false );
		wp_enqueue_script( 'wpforo-snfb' );
		wp_localize_script( 'wpforo-snfb', 'wpforo_fb', [
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'site_url' => home_url(),
			'scopes'   => 'email,public_profile',
			'appId'    => $app_id,
			'l18n'     => [
				'chrome_ios_alert' => __(
					'Please login into Facebook and then click connect button again',
					'wpforo'
				),
			],
		] );
	}

	public function fb_auth() {

		$app_version = 'v2.10';
		$app_secret  = wpforo_setting( 'authorization', 'fb_api_secret' );
		check_ajax_referer( 'wpforo-fb-nonce', 'security' );
		$fb_token = isset( $_POST['fb_response']['authResponse']['accessToken'] ) ? $_POST['fb_response']['authResponse']['accessToken'] : '';
		$fb_url   = add_query_arg(
			[ 'fields' => 'id,first_name,last_name,email,link,about,locale,birthday', 'access_token' => $fb_token ],
			'https://graph.facebook.com/' . $app_version . '/' . $_POST['fb_response']['authResponse']['userID']
		);

		###################################################################################################################
		// Verifying Graph API Calls with appsecret_proof
		// Graph API calls can be made from clients or from your server on behalf of clients.
		// Calls from a server can be better secured by adding a parameter called appsecret_proof.
		// https://developers.facebook.com/docs/graph-api/securing-requests/
		if( $app_secret ) {
			$appsecret_proof = hash_hmac( 'sha256', $fb_token, trim( $app_secret ) );
			$fb_url          = add_query_arg( [ 'appsecret_proof' => $appsecret_proof ], $fb_url );
		}
		###################################################################################################################

		$fb_response = wp_remote_get( esc_url_raw( $fb_url ), [ 'timeout' => 30 ] );
		if( is_wp_error( $fb_response ) ) wpforo_ajax_response( [ 'error' => $fb_response->get_error_message() ] );
		$fb_user = json_decode( wp_remote_retrieve_body( $fb_response ), true );
		if( isset( $fb_user['error'] ) ) {
			wpforo_ajax_response( [ 'error' => 'Error code: ' . $fb_user['error']['code'] . ' - ' . $fb_user['error']['message'] ] );
		}
		if( empty( $fb_user['email'] ) ) {
			wpforo_ajax_response( [
				                      'error' => __(
					                      'Your email is required to be able authorize you here. Please try loging again. ',
					                      'wpforo'
				                      ),
				                      'fb'    => $fb_user,
			                      ] );
		}
		$fb_user['link']   = ( isset( $fb_user['link'] ) ) ? $fb_user['link'] : '';
		$fb_user['about']  = ( isset( $fb_user['about'] ) ) ? $fb_user['about'] : '';
		$fb_user['locale'] = ( isset( $fb_user['locale'] ) ) ? $fb_user['locale'] : '';
		$user              = [
			'fb_user_id'   => $fb_user['id'],
			'first_name'   => $fb_user['first_name'],
			'last_name'    => $fb_user['last_name'],
			'user_email'   => $fb_user['email'],
			'user_url'     => $fb_user['link'],
			'user_pass'    => wp_generate_password(),
			'description'  => $fb_user['about'],
			'locale'       => $fb_user['locale'],
			'rich_editing' => 'true',
		];
		$message           = [ 'error' => __( 'Invalid User', 'wpforo' ) ];
		if( empty( $user['fb_user_id'] ) ) wpforo_ajax_response( $message );
		$member       = wpforo_get_fb_user( $user );
		$meta_updated = false;

		if( $member ) {
			$user_id = $member->ID;
			$message = [ 'success' => $user_id, 'method' => 'login' ];
			if( empty( $member->user_email ) ) {
				wp_update_user( [ 'ID' => $user_id, 'user_email' => $user['user_email'] ] );
			}
		} else {
			if( ! wpforo_setting( 'authorization', 'user_register' ) ) {
				wpforo_ajax_response( [ 'error' => __( 'User registration is disabled', 'wpforo' ) ] );
			}
			$username              = wpforo_unique_username( $user['user_email'] );
			$user['user_login']    = str_replace( '.', '', $username );
			$user['user_nicename'] = sanitize_title( $username );
			$user['display_name']  = ( $user['first_name'] || $user['last_name'] ) ? trim(
				$user['first_name'] . ' ' . $user['last_name']
			) : ucfirst( str_replace( '-', ' ', $user['user_nicename'] ) );
			$user_id               = wp_insert_user( $user );
			if( ! is_wp_error( $user_id ) ) {
				wp_new_user_notification( $user_id, null, 'admin' );
				wp_new_user_notification( $user_id, '', 'user' );
				update_user_meta( $user_id, '_fb_user_id', $user['fb_user_id'] );
				if( isset( $fb_user['birthday'] ) && $fb_user['birthday'] ) {
					update_user_meta( $user_id, '_fb_user_birthday', $fb_user['birthday'] );
				}
				$meta_updated = true;
				$message      = [ 'success' => $user_id, 'method' => 'registration' ];
			}
		}
		if( is_numeric( $user_id ) ) {
			wp_set_auth_cookie( $user_id, true );
			if( ! $meta_updated ) update_user_meta( $user_id, '_fb_user_id', $user['fb_user_id'] );
		}
		wpforo_ajax_response( $message );
	}

	public function fb_redirect() {
		if( wpforo_setting( 'authorization', 'fb_redirect' ) === 'custom' && wpforo_setting( 'authorization', 'fb_redirect_url' ) != '' ) {
			return esc_url( wpforo_setting( 'authorization', 'fb_redirect_url' ) );
		} elseif( wpforo_setting( 'authorization', 'fb_redirect' ) === 'profile' ) {
			return wpforo_home_url( "account/" );
		} else {
			return wpforo_home_url();
		}
	}

	public function fb_sdk() {
		?>
        <div id="fb-root"></div>
        <script type='text/javascript'>(function (d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0]
                if (d.getElementById(id)) return
                js = d.createElement(s)
                js.id = id
                js.src = "//connect.facebook.net/<?php echo $this->local(
					'fb'
				) ?>/sdk.js#xfbml=1&version=<?php echo self::FB_SDK_VERSION ?>&appId=<?php echo wpforo_setting( 'authorization', 'fb_api_id' ) ?>&autoLogAppEvents=1"
                fjs.parentNode.insertBefore(js, fjs)
            }(document, 'script', 'facebook-jssdk'))</script>
		<?php
	}

	public function fb_login_sdk() {
		?>
        <script type='text/javascript'>function statusChangeCallback (response) { if (response.status === 'connected') {} else if (response.status === 'not_authorized') {} else {}}

            function checkLoginState () { FB.getLoginStatus(function (response) { statusChangeCallback(response) })}

            window.fbAsyncInit = function () {
                FB.init({
                    appId: '<?php echo trim(
						wpforo_setting( 'authorization', 'fb_api_id' )
					) ?>',
                    cookie: <?php echo ( WPF()->tools_legal['cookies'] ) ? 'true' : 'false'; ?>,
                    xfbml: true,
                    status: false,
                    version: '<?php echo self::FB_SDK_VERSION ?>'
                })
                FB.getLoginStatus(function (response) {statusChangeCallback(response)})
            }</script>
		<?php
	}

	public function fb_login_button() {
		$checkbox       = WPF()->tools_legal['checkbox_fb_login'];
		$public_profile = '<a href="https://developers.facebook.com/docs/facebook-login/permissions#reference-public_profile" target="_blank" rel="nofollow" title="' . wpforo_phrase(
				'Read more about Facebook public_profile properties.',
				false
			) . '">public_profile</a>';
		?>
		<?php if( $checkbox ): ?>
            <div class="wpforo-fb-info">
                <span class="wpforo-fb-info-title">
                    <i class="fas fa-info-circle wpfcl-5" aria-hidden="true"
                       style="font-size:16px;"></i> &nbsp;<?php wpforo_phrase( 'Facebook Login Information' ); ?>
                </span>
                <span class="wpforo-fb-info-text">
                    <?php echo apply_filters(
	                    'wpforo_fb_login_privacy_info',
	                    sprintf(
		                    wpforo_phrase(
			                    'When you login first time using Facebook Login button, we collect your account %s information shared by Facebook, based on your privacy settings. We also get your email address to automatically create a forum account for you. Once your account is created, you\'ll be logged-in to this account and you\'ll receive a confirmation email.',
			                    false
		                    ),
		                    $public_profile
	                    )
                    ); ?>
                </span>
                <label class="wpforo-legal-checkbox wpflegal-fblogin">
                    <input id="wpflegal_fblogin" name="legal[agree-fb-login]" value="1" type="checkbox"> &nbsp;
                    <span><?php wpforo_phrase( 'I allow to create an account and send confirmation email.' ); ?></span>
                </label>
            </div>
		<?php endif; ?>
        <div class="wpforo_fb-button wpforo-fb-login-wrap" data-redirect="<?php echo $this->fb_redirect() ?>"
             data-fb_nonce="<?php echo wp_create_nonce(
			     'wpforo-fb-nonce'
		     ) ?>" <?php if( $checkbox ) echo 'style="pointer-events: none; opacity:0.6;"'; ?>>
            <div class="fb-login-button" data-max-rows="1" onlogin="wpforo_fb_check_auth" data-size="medium"
                 data-button-type="login_with" data-show-faces="false" data-auth-type="rerequest"
                 data-auto-logout-link="false" data-use-continue-as="true" data-scope="email,public_profile"></div>
            <img data-no-lazy="1" src="<?php echo WPFORO_URL . '/assets/images/loading.gif'; ?>"
                 class="wpforo_fb-spinner" style="display:none" alt="loading...">
        </div>
		<?php
	}

	public function fb_share_button( $url = '', $type = 'custom' ) {
		if( ! wpforo_setting( 'social', 'sb', 'fb' ) || ! wpforo_setting( 'authorization', 'fb_api_id' ) ) return;
		$url = $url ?: WPF()->current_url;
		if( $type === 'custom' ) { ?>
            <span class="wpforo-share-button wpf-fb" data-wpfurl="<?php echo $url ?>"
                  title="<?php wpforo_phrase( 'Share to Facebook' ); ?>">
                <?php if( wpforo_setting( 'social', 'sb_icon' ) === 'figure' ): ?>
                    <i class="fab fa-facebook-f" aria-hidden="true"></i>
                <?php elseif( wpforo_setting( 'social', 'sb_icon' ) === 'square' ): ?>
                    <i class="fab fa-facebook-square" aria-hidden="true"></i>
                <?php else: ?>
                    <i class="fab fa-facebook" aria-hidden="true"></i>
                <?php endif; ?>
            </span>
			<?php
		} else {
			?>
            <div class="wpf-sbw wpf-sbw-fb">
				<?php if( wpforo_setting( 'social', 'sb_type' ) === 'button_count' ): ?>
                    <div class="fb-share-button" data-href="<?php echo esc_url( $url ) ?>" data-layout="button_count"
                         data-size="small" data-mobile-iframe="true">
                        <a target="_blank"
                           href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( $url ) ?>"
                           class="fb-xfbml-parse-ignore"><?php wpforo_phrase( 'Share' ); ?></a>
                    </div>
				<?php elseif( wpforo_setting( 'social', 'sb_type' ) === 'button' ): ?>
                    <span class="wpf-sb-button wpf-fb" data-wpfurl="<?php echo esc_url( $url ) ?>">
                        <i class="fab fa-facebook-f" aria-hidden="true"></i> <span><?php echo wpforo_phrase(
								'Share'
							) ?></span>
                    </span>
				<?php else: ?>
                    <span class="wpf-sb-button wpf-sb-icon wpf-fb" data-wpfurl="<?php echo esc_url( $url ) ?>">
                        <i class="fab fa-facebook-f" aria-hidden="true"></i>
                    </span>
				<?php endif; ?>
            </div>
			<?php
		}
	}

	public function tw_wjs() {
		?>
        <script type="text/javascript">window.twttr = (function (d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0], t = window.twttr || {}
                if (d.getElementById(id)) return t
                js = d.createElement(s)
                js.id = id
                js.src = 'https://platform.twitter.com/widgets.js'
                fjs.parentNode.insertBefore(js, fjs)
                t._e = []
                t.ready = function (f) { t._e.push(f) }
                return t
            }(document, 'script', 'twitter-wjs'))</script>
		<?php
	}

	public function tw_share_button( $url = '', $type = 'custom', $text = '' ) {
		if( ! wpforo_setting( 'social', 'sb', 'tw' ) ) return;
		$url    = $url ?: WPF()->current_url;
		$n_url  = strlen( $url );
		$n_text = 280 - $n_url;
		$text   = $text ?: wpfval( WPF()->current_object, 'og_text' );
		$text   = urlencode( wpforo_text( strip_shortcodes( strip_tags( $text ) ), $n_text, false ) );
		if( $type == 'custom' ) { ?>
            <a class="wpforo-share-button wpf-tw"
               href="https://twitter.com/intent/tweet?text=<?php echo $text ?>&url=<?php echo urlencode( $url ) ?>"
               title="<?php wpforo_phrase( 'Tweet this post' ); ?>">
				<?php if( wpforo_setting( 'social', 'sb_icon' ) === 'figure' ): ?>
                    <i class="fab fa-twitter" aria-hidden="true"></i>
				<?php elseif( wpforo_setting( 'social', 'sb_icon' ) === 'square' ): ?>
                    <i class="fab fa-twitter-square" aria-hidden="true"></i>
				<?php else: ?>
                    <i class="fab fa-twitter" aria-hidden="true"></i>
				<?php endif; ?>
            </a>
			<?php
		} else { ?>
            <div class="wpf-sbw wpf-sbw-tw">
				<?php if( wpforo_setting( 'social', 'sb_type' ) === 'button_count' ): ?>
                    <a href="https://twitter.com/share?ref_src=twsrc%5Etfw" class="twitter-share-button"
                       data-lang="<?php $this->local( 'tw' ) ?>" data-show-count="true"><?php wpforo_phrase(
							'Tweet'
						); ?></a>
				<?php elseif( wpforo_setting( 'social', 'sb_type' ) === 'button' ): ?>
                    <a class="wpf-sb-button wpf-tw"
                       href="https://twitter.com/intent/tweet?text=<?php echo $text ?>&url=<?php echo urlencode(
						   $url
					   ) ?>">
                        <i class="fab fa-twitter" aria-hidden="true"></i> <span><?php echo wpforo_phrase(
								'Tweet'
							) ?></span>
                    </a>
				<?php else: ?>
                    <a class="wpf-sb-button wpf-sb-icon wpf-tw"
                       href="https://twitter.com/intent/tweet?text=<?php echo $text ?>&url=<?php echo urlencode(
						   $url
					   ) ?>">
                        <i class="fab fa-twitter" aria-hidden="true"></i>
                    </a>
				<?php endif; ?>
            </div>
			<?php
		}
	}

	public function vk_js() {
		?>
        <script type="text/javascript" src="https://vk.com/js/api/share.js?95" charset="windows-1251"></script>
		<?php
	}

	public function wapp_share_button( $url = '', $type = 'custom', $text = '' ) {
		if( ! wpforo_setting( 'social', 'sb', 'wapp' ) ) return;
		$url    = $url ?: WPF()->current_url;
		$domain = wp_is_mobile() ? 'https://api.whatsapp.com' : 'https://web.whatsapp.com';
		$text   = $text ?: ( wpfval( WPF()->current_object, 'og_text' ) ? WPF()->current_object['og_text'] : WPF()->board->get_current( 'settings' )['title'] );
		$text   = urlencode( wpforo_text( strip_shortcodes( strip_tags( $text ) ), 100, false ) ) . ' URL: ' . urlencode( $url );
		if( $type === 'custom' ) { ?>
            <a class="wpforo-share-button wpf-wapp" href="<?php echo $domain ?>/send?text=<?php echo $text ?>"
               title="<?php wpforo_phrase( 'Share to WhatsApp' ); ?>" target="_blank"
               data-action="share/whatsapp/share">
                <i class="fab fa-whatsapp" aria-hidden="true"></i>
            </a>
			<?php
		} else { ?>
            <div class="wpf-sbw wpf-sbw-wapp">
				<?php if( wpforo_setting( 'social', 'sb_type' ) === 'button_count' ): ?>
                    <!-- WhatsApp is not available -->
				<?php elseif( wpforo_setting( 'social', 'sb_type' ) === 'button' ): ?>
                    <a class="wpf-sb-button wpf-wapp" href="<?php echo $domain ?>/send?text=<?php echo $text ?>"
                       target="_blank" data-action="share/whatsapp/share">
                        <i class="fab fa-whatsapp" aria-hidden="true"></i> <span><?php echo wpforo_phrase( 'Share' ) ?></span>
                    </a>
				<?php else: ?>
                    <a class="wpf-sb-button wpf-sb-icon wpf-wapp"
                       href="<?php echo $domain ?>/send?text=<?php echo $text ?>" target="_blank"
                       data-action="share/whatsapp/share">
                        <i class="fab fa-whatsapp" aria-hidden="true"></i>
                    </a>
				<?php endif; ?>
            </div>
			<?php
		}
	}

	public function lin_share_button( $url = '', $type = 'custom', $text = '' ) {
		if( ! wpforo_setting( 'social', 'sb', 'lin' ) ) return;
		$url   = $url ?: WPF()->current_url;
		$title = wpfval( WPF()->current_object, 'topic', 'title' );
		$text  = $text ?: ( wpfval( WPF()->current_object, 'og_text' ) ? WPF()->current_object['og_text'] : WPF()->board->get_current(
			'settings'
		)['title'] );
		$text  = urlencode( wpforo_text( strip_shortcodes( strip_tags( $text ) ), 500, false ) );
		if( $type == 'custom' ) { ?>
            <a class="wpforo-share-button wpf-lin"
               href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(
				   $url
			   ) ?>&title=<?php echo urlencode( $title ) ?>&summary=<?php echo $text ?>"
               title="<?php wpforo_phrase( 'Share to LinkedIn' ); ?>" target="_blank">
                <i class="fab fa-linkedin-in" aria-hidden="true"></i>
            </a>
			<?php
		} else { ?>
            <div class="wpf-sbw wpf-sbw-lin">
				<?php if( wpforo_setting( 'social', 'sb_type' ) === 'button_count' ): ?>
                    <!-- LinkedIn is not available -->
				<?php elseif( wpforo_setting( 'social', 'sb_type' ) === 'button' ): ?>
                    <a class="wpf-sb-button wpf-lin"
                       href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(
						   $url
					   ) ?>&title=<?php echo urlencode( $title ) ?>&summary=<?php echo $text ?>" target="_blank">
                        <i class="fab fa-linkedin-in" aria-hidden="true"></i> <span><?php echo wpforo_phrase(
								'Share'
							) ?></span>
                    </a>
				<?php else: ?>
                    <a class="wpf-sb-button wpf-sb-icon wpf-lin"
                       href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(
						   $url
					   ) ?>&title=<?php echo urlencode( $title ) ?>&summary=<?php echo $text ?>" target="_blank">
                        <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                    </a>
				<?php endif; ?>
            </div>
			<?php
		}
	}

	public function vk_share_button( $url = '', $type = 'custom', $text = '' ) {
		if( ! wpforo_setting( 'social', 'sb', 'vk' ) ) return;
		$url  = $url ?: WPF()->current_url;
		$text = $text ?: wpfval( WPF()->current_object, 'og_text' );
		$text = urlencode( wpforo_text( strip_shortcodes( strip_tags( $text ) ), 500, false ) );
		if( $type === 'custom' ) { ?>
            <a class="wpforo-share-button wpf-vk" onclick="return VK.Share.click(0, this);"
               href="https://vk.com/share.php?url=<?php echo urlencode( $url ) ?>&description=<?php echo $text ?>"
               title="<?php wpforo_phrase( 'Share to VK' ); ?>" target="_blank">
                <i class="fab fa-vk" aria-hidden="true"></i>
            </a>
			<?php
		} else { ?>
            <div class="wpf-sbw wpf-sbw-vk">
				<?php if( wpforo_setting( 'social', 'sb_type' ) === 'button_count' ): ?>
                    <script type="text/javascript">document.write(VK.Share.button(false, {
                            type: 'round',
                            text: "<?php wpforo_phrase( 'Share' ); ?>"
                        }))</script>
				<?php elseif( wpforo_setting( 'social', 'sb_type' ) === 'button' ): ?>
                    <a class="wpf-sb-button wpf-vk" onclick="return VK.Share.click(0, this);"
                       href="https://vk.com/share.php?url=<?php echo urlencode(
						   $url
					   ) ?>&description=<?php echo $text ?>" target="_blank">
                        <i class="fab fa-vk" aria-hidden="true"></i> <span><?php echo wpforo_phrase( 'Share' ) ?></span>
                    </a>
				<?php else: ?>
                    <a class="wpf-sb-button wpf-sb-icon wpf-vk" onclick="return VK.Share.click(0, this);"
                       href="https://vk.com/share.php?url=<?php echo urlencode(
						   $url
					   ) ?>&description=<?php echo $text ?>" target="_blank">
                        <i class="fab fa-vk" aria-hidden="true"></i>
                    </a>
				<?php endif; ?>
            </div>
			<?php
		}
	}

	public function ok_share_button( $url = '', $type = 'custom', $text = '' ) {
		if( ! wpforo_setting( 'social', 'sb', 'ok' ) ) return;
		$url = $url ?: WPF()->current_url;
		if( preg_match( '|\#post-(\d+)|s', $url, $a ) ) {
			$pid = ( isset( $a[1] ) ) ? intval( $a[1] ) : mt_rand( 100000, 999999 );
		} else {
			$pid = mt_rand( 100000, 999999 );
		}
		$text = $text ?: wpfval( WPF()->current_object, 'og_text' );
		$text = wpforo_text( strip_shortcodes( strip_tags( $text ) ), 500, false );
		if( $type == 'custom' ) { ?>
            <a class="wpforo-share-button wpf-ok"
               href="https://connect.ok.ru/offer?url=<?php echo urlencode( $url ) ?>&description=<?php echo urlencode(
				   $text
			   ) ?>" title="<?php wpforo_phrase( 'Share to OK' ); ?>" target="_blank">
				<?php if( wpforo_setting( 'social', 'sb_icon' ) === 'figure' ): ?>
                    <i class="fab fa-odnoklassniki" aria-hidden="true"></i>
				<?php elseif( wpforo_setting( 'social', 'sb_icon' ) === 'square' ): ?>
                    <i class="fab fa-odnoklassniki-square" aria-hidden="true"></i>
				<?php else: ?>
                    <i class="fab fa-odnoklassniki-square" aria-hidden="true"></i>
				<?php endif; ?>
            </a>
			<?php
		} else { ?>
            <div class="wpf-sbw wpf-sbw-ok">
				<?php if( wpforo_setting( 'social', 'sb_type' ) === 'button_count' ): ?>
                    <div id="<?php echo 'wpfokb_' . $pid ?>"></div>
                    <script>
                        !function (d, id, did, st, title, description, image) {
                            var js = d.createElement('script')
                            js.src = 'https://connect.ok.ru/connect.js'
                            js.onload = js.onreadystatechange = function () {
                                if (!this.readyState || this.readyState === 'loaded' || this.readyState === 'complete') {
                                    if (!this.executed) {
                                        this.executed = true
                                        setTimeout(function () { OK.CONNECT.insertShareWidget(id, did, st, title, description, image) }, 0)
                                    }
                                }
                            }
                            d.documentElement.appendChild(js)
                        }(document, "<?php echo 'wpfokb_' . $pid ?>", "<?php echo esc_attr(
							$url
						) ?>", '{"sz":20,"st":"straight","ck":2,"lang":"<?php echo $this->local(
							'ok'
						) ?>"}', '', '', '')
                    </script>
				<?php elseif( wpforo_setting( 'social', 'sb_type' ) === 'button' ): ?>
                    <a class="wpf-sb-button wpf-ok" href="https://connect.ok.ru/offer?url=<?php echo urlencode(
						$url
					) ?>&description=<?php echo urlencode( $text ) ?>" title="<?php wpforo_phrase( 'Share to OK' ); ?>"
                       target="_blank">
                        <i class="fab fa-odnoklassniki" aria-hidden="true"></i> <span><?php echo wpforo_phrase(
								'Share'
							) ?></span>
                    </a>
				<?php else: ?>
                    <a class="wpf-sb-button wpf-sb-icon wpf-ok"
                       href="https://connect.ok.ru/offer?url=<?php echo urlencode(
						   $url
					   ) ?>&description=<?php echo urlencode( $text ) ?>"
                       title="<?php wpforo_phrase( 'Share to OK' ); ?>" target="_blank">
                        <i class="fab fa-odnoklassniki" aria-hidden="true"></i>
                    </a>
				<?php endif; ?>
            </div>
			<?php
		}
	}

	public function share_toggle( $url = '', $text = '', $type = 'custom' ) {
		WPF()->api->fb_share_button( $url, $type );
		WPF()->api->tw_share_button( $url, $type, $text );
		WPF()->api->wapp_share_button( $url, $type, $text );
		WPF()->api->lin_share_button( $url, $type, $text );
		WPF()->api->vk_share_button( $url, $type, $text );
		WPF()->api->ok_share_button( $url, $type, $text );
	}

	public function share_buttons( $url = '', $type = 'default', $text = '' ) {
		$template = wpfval( WPF()->current_object, 'template' );
		$exclude  = [ 'lostpassword', 'resetpassword' ];
		if( $template && ! in_array( $template, $exclude ) ) {
			WPF()->api->fb_share_button( $url, $type );
			WPF()->api->tw_share_button( $url, $type, $text );
			WPF()->api->wapp_share_button( $url, $type, $text );
			WPF()->api->lin_share_button( $url, $type, $text );
			WPF()->api->vk_share_button( $url, $type, $text );
			WPF()->api->ok_share_button( $url, $type, $text );
		}
	}

	public function rc_enqueue() {
		$theme    = wpforo_setting( 'recaptcha', 'theme' );
		$site_key = wpforo_setting( 'recaptcha', 'site_key' );
		wp_register_script(
			'wpforo_recaptcha',
			'https://www.google.com/recaptcha/api.js?onload=wpForoReCallback&render=explicit'
		);
		wp_add_inline_script(
			'wpforo_recaptcha',
			"var wpForoReCallback = function(){
		    setTimeout(function () { 
                if( typeof grecaptcha !== 'undefined' && typeof grecaptcha.render === 'function' ){
                    var rc_widgets = document.getElementsByClassName('wpforo_recaptcha_widget');
                    if( rc_widgets.length ){
                        var i;
                        for (i = 0; i < rc_widgets.length; i++) {
                            if( rc_widgets[i].firstElementChild === null ){
                                rc_widgets[i].innerHtml = '';
                                grecaptcha.render(
                                    rc_widgets[i], { 'sitekey': '" . $site_key . "', 'theme': '" . $theme . "' }
                                );
                            }
                        }
                    }
                }
            }, 800);
		}"
		);
		wp_enqueue_script( 'wpforo_recaptcha' );
	}

	public function rc_enqueue_async( $tag, $handle ) {
		if( $handle === 'wpforo_recaptcha' ) return str_replace( '<script', '<script async defer', $tag );

		return $tag;
	}

	public function rc_enqueue_css() {
		wp_register_style( 'wpforo-rc-style', false );
		wp_enqueue_style( 'wpforo-rc-style' );
		$custom_css = ".wpforo_recaptcha_widget{ -webkit-transform:scale(0.9); transform:scale(0.9); -webkit-transform-origin:left 0; transform-origin:left 0; }";
		wp_add_inline_style( 'wpforo-rc-style', $custom_css );
	}

	public function rc_widget() {
		$site_key = wpforo_setting( 'recaptcha', 'site_key' );
		if( $site_key ) {
			echo '<div class="wpforo_recaptcha_widget"></div><div class="wpf-cl"></div>';
			echo "\r\n<script>wpForoReCallback();</script>";
		}
	}

	public function rc_check() {
		if( isset( $_POST['g-recaptcha-response'] ) ) {
			$secret_key = wpforo_setting( 'recaptcha', 'secret_key' );
			$url        = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $_POST['g-recaptcha-response'];
			$response   = wp_remote_get( $url );
			if( is_wp_error( $response ) || empty( $response['body'] ) ) {
				$error = wpforo_phrase( "ERROR: Can't connect to Google reCAPTCHA API", false );
				if( WP_DEBUG === true ) $error .= ' ( ' . $response->get_error_message() . ' )';

				return $error;
			}
			$response = json_decode( $response['body'], true );
			if( $response['success'] == true ) {
				return 'success';
			} else {
				return wpforo_phrase( 'Google reCAPTCHA verification failed', false );
			}
		} else {
			return wpforo_phrase( 'Google reCAPTCHA data are not submitted', false );
		}
	}

	public function rc_verify() {
		if( ! wpforo_setting( 'recaptcha', 'post_editor' ) || ! wpforo_setting( 'recaptcha', 'topic_editor' ) ) {
			if( ! wpforo_setting( 'recaptcha', 'post_editor' ) && in_array(
					wpfval( $_POST, 'wpfaction' ),
					[ 'post_add', 'post_edit' ],
					true
				) ) {
				return true;
			} elseif( ! wpforo_setting( 'recaptcha', 'topic_editor' ) && in_array(
					wpfval( $_POST, 'wpfaction' ),
					[ 'topic_add', 'topic_edit' ],
					true
				) ) {
				return true;
			}
		}
		$result = $this->rc_check();
		if( $result === 'success' ) {
			return true;
		} else {
			WPF()->notice->add( $result, 'error' );
			wp_safe_redirect( wpforo_get_request_uri() );
			exit();
		}
	}

	public function rc_verify_wp_login( $user ) {
		if( ! isset( $_POST['log'] ) && ! isset( $_POST['pwd'] ) ) return $user;
		if( ! wpforo_setting( 'recaptcha', 'login_form' ) || ! wpforo_setting( 'recaptcha', 'wpf_login_form' ) ) {
			if( ! wpfval( $_POST, 'wpforologin' ) && ! wpforo_setting( 'recaptcha', 'login_form' ) ) {
				return $user;
			} elseif( wpfval( $_POST, 'wpforologin' ) && ! wpforo_setting( 'recaptcha', 'wpf_login_form' ) ) {
				return $user;
			}
		}
		$errors = is_wp_error( $user ) ? $user : new WP_Error();
		$result = $this->rc_check();
		if( $result != 'success' ) {
			$errors->add( 'wpforo-recaptcha-error', $result );
			$user = is_wp_error( $user ) ? $user : $errors;
			remove_filter( 'authenticate', 'wp_authenticate_username_password' );
			remove_filter( 'authenticate', 'wp_authenticate_cookie' );
		}

		return $user;
	}

	public function rc_verify_wp_register( $errors = '' ) {
		if( ! is_wp_error( $errors ) ) $errors = new WP_Error();
		if( ! wpforo_setting( 'recaptcha', 'reg_form' ) || ! wpforo_setting( 'recaptcha', 'wpf_reg_form' ) ) {
			if( ! wpfval( $_POST, 'wpfreg' ) && ! wpforo_setting( 'recaptcha', 'reg_form' ) ) {
				return $errors;
			} elseif( wpfval( $_POST, 'wpfreg' ) && ! wpforo_setting( 'recaptcha', 'wpf_reg_form' ) ) {
				return $errors;
			}
		}
		$result = $this->rc_check();
		if( $result != 'success' ) {
			$errors->add( 'wpforo-recaptcha-error', $result );
		}

		return $errors;
	}

	public function rc_verify_wp_lostpassword( $errors = '' ) {
		if( ! is_wp_error( $errors ) ) $errors = new WP_Error();
		if( ! wpforo_setting( 'recaptcha', 'lostpass_form' ) || ! wpforo_setting( 'recaptcha', 'wpf_lostpass_form' ) ) {
			if( ! wpfval( $_POST, 'wpfororp' ) && ! wpforo_setting( 'recaptcha', 'lostpass_form' ) ) {
				return;
			} elseif( wpfval( $_POST, 'wpfororp' ) && ! wpforo_setting( 'recaptcha', 'wpf_lostpass_form' ) ) {
				return;
			}
		}
		$result = $this->rc_check();
		if( $result !== 'success' ) {
			if( isset( $_POST['wc_reset_password'] ) && isset( $_POST['_wp_http_referer'] ) ) {
				//$errors->add('wpforo-recaptcha-error', $result);
				//return $errors;
			} else {
				wp_die( $result, 'reCAPTCHA ERROR', [ 'back_link' => true ] );
			}
		}
	}
}
