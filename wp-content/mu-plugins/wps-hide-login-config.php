<?php

defined( 'ABSPATH' ) || exit;

add_filter(
	'pre_option_whl_page',
	static function () {
		return 'backstage';
	}
);