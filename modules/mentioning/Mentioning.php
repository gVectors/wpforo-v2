<?php

namespace wpforo\modules\mentioning;

use wpforo\modules\mentioning\classes\Template;
use wpforo\modules\mentioning\classes\Actions;

class Mentioning {
	/* @var Template */ public $Template;
	/* @var Actions  */ public $Actions;

	public function __construct() {
		$this->init_classes();
	}

	private function init_classes() {
		$this->Template = new Template();
		$this->Actions  = new Actions();
	}
}
