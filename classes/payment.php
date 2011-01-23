<?php defined('SYSPATH') OR die('No direct access allowed.');

class Payment {
	
	const STATUS_PENDING = 1;
	const STATUS_SUCCESS = 2;
	const STATUS_FAILED  = 3;
	
	protected static $_instance;

	protected static $_config;
	
	public static $driver;
	
	public static function instance() {
		if (!isset(self::$_instance) OR !(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	final private function __construct() {
		self::$_config = Kohana::config('payment')->as_array();
		$driver = 'Payment_Driver_'.ucfirst(self::$_config['driver']);
		self::$driver = new $driver(array_merge(Arr::get(self::$_config,'config')));
	}
	
	final private function __clone() { }
	
	public function process($data = NULL) {
		return self::$driver->process($data);
	}
	
	public function update_status() {
		return self::$driver->update_status();		
	}
	
	public function fill_fields($fields = array()) {
		return self::$driver->fill_fields($fields);
	}

	public function get_fields() {
		return self::$driver->get_fields();
	}
	
	public function __call($name, $args) {
		if (method_exists(self::$driver,$name)) {
			return call_user_func_array(array(self::$driver, $name), $args));
		}
		throw new Payment_Exception('Method :method() does not exists in driver class :driver',array(
			':method' => $name,
			':driver' => get_class(self::$driver),
		));
	}
}