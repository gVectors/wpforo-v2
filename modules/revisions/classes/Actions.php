<?php

namespace wpforo\modules\revisions\classes;

class Actions {
	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		if( wpforo_setting( 'posting', 'is_preview_on' )
		    || wpforo_setting( 'posting', 'is_draft_on' )
		) {
			if( wpforo_setting( 'posting', 'is_preview_on' ) ) {
				add_action( 'wp_ajax_wpforo_post_preview', [ $this, 'ajax_post_preview' ] );
			}

			if( wpforo_setting( 'posting', 'is_draft_on' ) ) {
				add_action( 'wpforo_after_add_topic',                      [ $this, 'after_submit' ] );
				add_action( 'wpforo_after_add_post',                       [ $this, 'after_submit' ] );
				add_action( 'wpforo_after_edit_topic',                     [ $this, 'after_submit' ] );
				add_action( 'wpforo_after_edit_post',                      [ $this, 'after_submit' ] );

				add_action( 'wp_ajax_wpforo_save_revision',                [ $this, 'ajax_save_revision' ] );
				add_action( 'wp_ajax_wpforo_get_revisions_history',        [ $this, 'ajax_get_revisions_history' ] );
				add_action( 'wp_ajax_wpforo_get_revision',                 [ $this, 'ajax_get_revision' ] );
				add_action( 'wp_ajax_wpforo_delete_revision',              [ $this, 'ajax_delete_revision' ] );

				add_action( 'wp_ajax_nopriv_wpforo_save_revision',         [ $this, 'ajax_save_revision' ] );
				add_action( 'wp_ajax_nopriv_wpforo_get_revisions_history', [ $this, 'ajax_get_revisions_history' ] );
				add_action( 'wp_ajax_nopriv_wpforo_get_revision',          [ $this, 'ajax_get_revision' ] );
				add_action( 'wp_ajax_nopriv_wpforo_delete_revision',       [ $this, 'ajax_delete_revision' ] );
			}
		}
	}

	public function ajax_post_preview() {
		$revision = WPF()->revision->parse_revision( $_POST );
		ob_start();
		WPF()->revision->Template->show_preview( $revision );
		$html = trim( ob_get_clean() );
		if( $html ) {
			wp_send_json_success( $html );
		} else {
			wp_send_json_error( $html );
		}
	}

	public function ajax_save_revision() {
		$args = [
			'textareaid' => (string) wpfval( $_POST, 'textareaid' ),
			'postid'     => wpforo_bigintval( wpfval( $_POST, 'postid' ) ),
			'body'       => (string) wpfval( $_POST, 'body' ),
		];

		$revision            = WPF()->revision->parse_revision( $args );
		$revision['created'] = current_time( 'timestamp', 1 );
		$revision['url']     = WPF()->revision->get_current_url_query_vars_str();
		$revision['userid']  = WPF()->current_userid;
		$revision['email']   = WPF()->current_user_email;

		if( $revisionid = (int) WPF()->revision->add( $revision ) ) {
			$args            = [
				//			    'textareaids_include' => $revision['textareaid'],
				'postids_include' => $revision['postid'],
				'userids_include' => $revision['userid'],
				'emails_include'  => $revision['email'],
				'urls_include'    => $revision['url'],
			];
			$revisions_count = WPF()->revision->get_count( $args );
			if( $revisions_count > wpforo_setting( 'posting', 'max_drafts_per_page' ) ) {
				$sql = "DELETE FROM " . WPF()->tables->post_revisions . WPF()->revision->build_sql_where( $args ) . " 
			        ORDER BY `revisionid` ASC LIMIT %d";
				$sql = WPF()->db->prepare( $sql, ( $revisions_count - wpforo_setting( 'posting', 'max_drafts_per_page' ) ) );
				if( WPF()->db->query( $sql ) !== false ) $revisions_count = wpforo_setting( 'posting', 'max_drafts_per_page' );
			}
		} else {
			$revisions_count = 0;
			$revisionid      = 0;
		}

		$revision['revisionid'] = $revisionid;

		$response = [
			'revisionid'      => $revisionid,
			'revisions_count' => $revisions_count,
			'revisionhtml'    => WPF()->revision->Template->build_revision( $revision ),
		];
		if( $revisionid ) {
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $response );
		}
	}

	public function ajax_get_revisions_history() {
		$args = [
			//			'textareaids_include' => (string) wpfval( $_POST, 'textareaid' ),
			'postids_include' => wpforo_bigintval( wpfval( $_POST, 'postid' ) ),
			'userids_include' => WPF()->current_userid,
			'emails_include'  => WPF()->current_user_email,
			'urls_include'    => WPF()->revision->get_current_url_query_vars_str(),
		];

		$revisionhtml = '';
		if( $revisions = WPF()->revision->get_revisions( $args ) ) {
			foreach( $revisions as $revision ) $revisionhtml .= WPF()->revision->Template->build_revision( $revision );
		}

		$revisions_count = count( $revisions );
		$response        = [
			'revisions_count' => $revisions_count,
			'revisionhtml'    => $revisionhtml,
		];
		if( $revisions_count ) {
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $response );
		}
	}

	public function ajax_get_revision() {
		if( $revisionid = wpforo_bigintval( wpfval( $_POST, 'revisionid' ) ) ) {
			if( $revision = WPF()->revision->get_revision( [ 'include' => $revisionid ] ) ) wp_send_json_success( $revision );
		}
		wp_send_json_error();
	}

	public function ajax_delete_revision() {
		if( $revisionid = wpforo_bigintval( wpfval( $_POST, 'revisionid' ) ) ) {
			if( WPF()->revision->delete( $revisionid ) ) {
				$args            = [
					//        			'textareaids_include' => (string) wpfval( $_POST, 'textareaid' ),
					'postids_include' => wpforo_bigintval( wpfval( $_POST, 'postid' ) ),
					'userids_include' => WPF()->current_userid,
					'emails_include'  => WPF()->current_user_email,
					'urls_include'    => WPF()->revision->get_current_url_query_vars_str(),
				];
				$revisions_count = WPF()->revision->get_count( $args );
				wp_send_json_success( compact( 'revisions_count' ) );
			}
		}
		wp_send_json_error();
	}

	public function after_submit() {
		WPF()->revision->delete( [ 'userid' => WPF()->current_userid, 'email' => WPF()->current_user_email, 'url' => WPF()->revision->get_current_url_query_vars_str() ] );
		$sql = "SELECT EXISTS( SELECT * FROM " . WPF()->tables->post_revisions . " ) AS is_exists";
		if( ! WPF()->db->get_var( $sql ) ) WPF()->db->query( "TRUNCATE " . WPF()->tables->post_revisions );
	}
}
