<?php

Route::set('payment', 'payment(/<action>)')
	->defaults(array(
		'controller' => "payment_".Kohana::config('payment.driver'),
		'action'     => 'index',
	));
