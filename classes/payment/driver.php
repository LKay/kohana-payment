<?php defined('SYSPATH') OR die('No direct access allowed.');

abstract class Payment_Driver {
	
	protected $_fields = array();
	protected $_fields_required = array();
	
	public abstract function process();
	public abstract function update_status();
	
	public function fill_fields($fields = array()) {
		foreach ($fields as $key => $value) {
			if (array_key_exists($key, $this->_fields)) {
				$this->_fields[$key] = $value;
			}
			if (array_key_exists($key, $this->_fields_required)) {
				$this->_fields_required[$key] = $value;				
			}
		}
	}
	
	public function get_fields() {
		return array('fields_required'=>$this->_fields_required,'fields'=>$this->_fields);
	}

}