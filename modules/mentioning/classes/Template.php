<?php

namespace wpforo\modules\mentioning\classes;

class Template {
	public function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		add_filter( 'wpforo_member_get_actions', [ $this, 'add_member_action' ], 9, 2 );
	}

	public function get_currentstate_ico( $currentstate ) {
		return ( $currentstate ? '<i class="fas fa-bell-slash"></i>' : '<i class="fas fa-bell"></i>' );
	}

	public function add_member_action( $actions, $user ) {
		$userid = $user['userid'];
		return array_merge( [ 'mute_mention' => [
			'label'            => wpforo_phrase( 'Mute Mentioning Emails', false ),
			'ico'              => $this->get_currentstate_ico( $user['is_mention_muted'] ),
			'callback_for_can' => function() use ($userid){
				return $userid === WPF()->current_userid;
			},
			'callback_for_get_url' => function() use ( $userid ){
				return '';
			},
			'data' => [
				'currentstate' => (int) $user['is_mention_muted'],
			],
		] ], $actions );
	}
}
