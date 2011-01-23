<?php defined('SYSPATH') or die('No direct script access.');

return array(

	// Choose driver
	'driver' => 'platnoscipl',
	// Additional config for chosen driver ie. Platnosci.pl
	'config' => array(
		'pos_id' => 12345,
		'pos_auth_key' => '123abc',
		'key' => '',
		'key2' => '',
		// actions in yours application to process payment data
		'actions' => array(
			'online'  => 'welcome/online',
			'success' => 'welcome/success',
			'fail'    => 'welcome/fail',
		),
	),
	/*
	'driver' => 'dotpay',
	// Additional config for chosen driver ie. DotPay
	'config' => array(
		'id' => 12345,
		'PIN' => '0123456789abcdef',
		// actions in yours application to process payment data
		'actions' => array(
			'return' => 'welcome/return',
			'status' => 'welcome/status',
		),
	),
	*/
);
