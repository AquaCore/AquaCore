<?php
return array(
	'title'              => 'AquaCore',
	'ssl'                => 0,
	'timezone'           => '',
	'date_format'        => '%x',
	'time_format'        => '%X',
	'datetime_format'    => '%x <small>%X</small>',
	'language'           => 'en',
	'domain'             => '',
	'base_dir'           => '',
	'cron_key'           => '',
	'tasks'              => true,
	'rewrite_url'        => false,
	'output_compression' => true,
	'ragnarok'           => array(
		'pincode_min_len'      => 4,
		'pincode_max_len'      => 4,
		'cash_shop_max_amount' => 99,
		'acc_username_url'     => false,
		'char_name_url'        => false,
		'display_item_script'  => true
	),
	'captcha'            => array(
		'use_recaptcha'         => false,
		'recaptcha_ssl'         => true,
		'recaptcha_public_key'  => '',
		'recaptcha_private_key' => '',
		'width'                 => 310,
		'height'                => 100,
		'font_file'             => '/uploads/admin/passion-one.ttf',
		'font_color'            => 0x6b9ad0,
		'font_color_variation'  => 0x8e98a9,
		'font_size'             => 27,
		'use_font_shadow'       => true,
		'background_color'      => 0xe8e9ea,
		'background_image'      => '/uploads/admin/captcha-bg.png',
		'noise_level'           => 5,
		'noise_color'           => 0x6b9ad0,
		'noise_tint_color'      => 0x8e98a9,
		'min_lines'             => 3,
		'max_lines'             => 8,
		'min_length'            => 5,
		'max_length'            => 7,
		'characters'            => 'abcdefghkmnpqrstuvxwyzABCDEFGHKMNPQRSTUVXWYZ123456789+=?!@%&#',
		'case_sensitive'        => false,
		'expire'                => 30,
		'gc_probability'        => 5,
	),
	'cache'              => array(
		'storage_adapter' => 'File',
		'storage_options' => array(
			'prefix' => '',
			'hash' => null,
			'directory' => \Aqua\ROOT . '/tmp/cache',
			'gc_probability' => 0,
		)
	),
	'donation'           => array(
		'pp_business_email' => isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : 'example@example.com',
		'pp_receiver_email' => array(),
		'pp_sandbox'        => true,
		'exchange_rate'     => 1,
		'min_donation'      => 2,
		'currency'          => 'USD',
		'goal'              => 0,
		'goal_interval'     => 'weekly'
	),
	'account'            => array(
		'case_sensitive_login'    => false,
		'case_sensitive_username' => false,
		'email_login'             => true,
		'default_avatar'          => '/uploads/admin/avatar.png',
		'registration'            => array(
			'require_tos'           => true,
			'captcha_confirmation'  => true,
			'email_validation'      => false,
			'validation_time'       => 48,
			'activation_key_length' => 32,
		),
		'username'                => array(
			'min_length' => 3,
			'max_length' => 26,
			'regex'      => '/[^\\p{L}\\p{M}\\p{N}\\p{P}\\p{S} ]/u',
		),
		'display_name'            => array(
			'min_length'   => 3,
			'max_length'   => 26,
			'regex'        => '/[^\\p{L}\\p{M}\\p{N}\\p{P}\\p{S} ]/u',
			'update_limit' => 3,
			'update_days'  => 30,
		),
		'password'                => array(
			'min_length' => 5,
			'max_length' => 50,
			'regex'      => '',
		),
		'email'                   => array(),
		'birthday'                => array(),
		'avatar'                  => array(
			'max_size'   => '180KB',
			'max_height' => 200,
			'max_width'  => 200,
		),
		'persistent_login'        => array(
			'enable'          => true,
			'name'            => 'aquacore_remember_me',
			'key_length'      => 128,
			'timeout'         => 315360000,
			'http_only'       => true,
			'secure'          => false,
			'expire'          => 5,
			'logout_cooldown' => 10800,
		),
		'phpass'                  => array(
			'adapter' => array(
				'adapter'            => 'bcrypt',
				'iterationcountlog2' => 12,
				'identifier'         => '2a',
			),
		),
	),
	'session'            => array(
		'name'             => 'aquacore_sess_id',
		'http_only'        => true,
		'secure'           => false,
		'regenerate_id'    => 10,
		'expire'           => 240,
		'match_ip_address' => false,
		'match_user_agent' => true,
		'max_collision'    => 3,
		'gc_probability'   => 1
	),
	'cms'                => array(
		'post' => array (
				'enable_archiving_by_default' => false,
				'enable_anonymous_by_default' => false,
				'enable_comments_by_default'  => true,
				'enable_rating_by_default'    => true,
			),
		'page' => array (
				'enable_rating_by_default' => true,
			),
	),
	'rss'                => array(
		'title'       => 'AquaCore News',
		'category'    => array('Ragnarok'),
		'description' => '',
		'ttl'         => 10,
		'copyright'   => '',
		'image'       => null,
	),
	'chargen'            => array(
		'emblem' => array(
			'cache_ttl'     => 300,
			'cache_browser' => true,
			'compression'   => 0,
		),
		'sprite' => array(
			'cache_ttl'     => 300,
			'cache_browser' => true,
			'compression'   => 9,
		),
	),
	'email'              => array(
		'from_address' => isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : 'example@example.com',
		'from_name'    => 'AquaCore',
		'use_smtp'     => false,
	),
	'db'                 => array(),
);
