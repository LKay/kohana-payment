<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * 
 * W ustawieniach punktu płatności trzeba ustawić następujące adresy:
 * 
 * URL:  http://<domena>/index.php/payment/return
 * URLC: http://<domena>/index.php/payment/status
 *
 */

class Payment_Driver_Dotpay extends Payment_Driver {
	
	const PAYMENT_URL     = 'https://ssl.dotpay.pl/';
	
	protected $_pos_data = array(
		'id'  => NULL,
		'PIN' => NULL,
	);
	protected $_actions = array(
		'return' => NULL,
		'status' => NULL,
	);
	
	protected $_fields_required = array(
		'id'          => NULL,
		'amount'      => NULL,
		'currency'    => NULL,
		'description' => NULL,
		'lang'        => NULL,
	);
	
	protected $_fields = array(
		'channel'         => NULL,
		'ch_lock'         => NULL,
		'online_transfer' => NULL,
		'URL'             => NULL,
		'type'            => NULL,
		'buttontext'      => NULL,
		'URLC'            => NULL,
		'firstname'       => NULL,
		'lastname'        => NULL,
		'email'           => NULL,
		'street'          => NULL,
		'street_n1'       => NULL,
		'street_n2'       => NULL,
		'addr2'           => NULL,
		'addr3'           => NULL,
		'city'            => NULL,
		'postcode'        => NULL,
		'phone'           => NULL,
		'code'            => NULL,
		'p_info'          => NULL,
		'p_email'         => NULL,
		'tax'             => NULL,
		'control'         => NULL,
	);
	
	public function __construct($config = NULL) {
		$this->_actions = Arr::overwrite($this->_actions, Arr::get($config, 'actions', array()));
		$this->_pos_data = Arr::overwrite($this->_pos_data, $config);
		
	}
	
	public function process($only_fields = FALSE) {
		$this->_fields_required['id'] = $this->_pos_data['id'];
		if (empty($this->_fields['URL'])) {
			$this->_fields['URL'] = url::site($this->action_return());
		}
		foreach ($this->_fields_required as $field) {
			if (empty($field)) return FALSE;
		}
		
		$fields = array_merge($this->_fields_required, $this->_fields);
		
		$form = '';
		
		if (!$only_fields) {
			$form .= Form::open(self::PAYMENT_URL, array('method' => 'post', 'name' => 'dotpay'));
		}
		
		foreach ($fields as $key => $value) {
			if (!empty($value)) {
				$form .= Form::hidden($key, $value);
			}
		}
		
		if (!$only_fields) {
			$form .= Form::button('platnosci', __("Przejdź do Dotpay.pl"), array('type' => 'submit'));
			$form .= Form::close();
			$form .= '<script type="text/javascript">document.dotpay.submit();</script>';
		}
		
		return $form;
	}
		
	// TODO: zwracanie innych danych poza statusem
	public function update_status() {
		if ($_POST) {
			if ($this->_check_control(Arr::get($_POST,'md5'))) {
				return $this->_get_status();			
			}
		}
		return FALSE;
	}

	public function set_actions($online, $success, $fail) {
		$this->_actions['online']  = $online;
		$this->_actions['success'] = $success;
		$this->_actions['fail']    = $fail;
	}
	
	public function action_return() {
		return $this->_actions['return'];
	}

	public function action_status() {
		return $this->_actions['status'];
	}
	
	private function _get_status() {
		$status = Arr::get($_POST,'t_status');
					
		$pending = array(1);
		$success = array(2);
		$fail = array(3,4,5);
		
		if (in_array($status, $pending))
			return Payment::STATUS_PENDING;
		if (in_array($status, $success))
			return Payment::STATUS_SUCCESS;
		if (in_array($status, $fail))
			return Payment::STATUS_FAILED;
		
		return FALSE;
	}
	
	private function _check_control($control) {
		$data = array(
			'PIN' => $this->_pos_data['PIN'],
			'id' => Arr::get($_POST,'id'),
			'control' => Arr::get($_POST,'control'),
			't_id' => Arr::get($_POST,'t_id'),
			'amount' => Arr::get($_POST,'amount'),
			'email' => Arr::get($_POST,'email'),
			'service' => Arr::get($_POST,'service'),
			'code' => Arr::get($_POST,'code'),
			'username' => Arr::get($_POST,'username'),
			'password' => Arr::get($_POST,'password'),
			't_status' => Arr::get($_POST,'t_status'),
		);
		
		return ($control == md5(implode(':',$data)));
	}
	
}
