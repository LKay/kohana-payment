<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * 
 * W ustawieniach punktu płatności trzeba ustawić następujące adresy:
 * 
 * UrlOnline:   http://<domena>/index.php/payment_platnoscipl/online
 * UrlPoprawny: http://<domena>/index.php/payment_platnoscipl/success?pos_id=%posId%&session_id=%sessionId%
 * UrlBledny:   http://<domena>/index.php/payment_platnoscipl/fail?pos_id=%posId%&session_id=%sessionId%&error=%error%
 *
 */

class Payment_Driver_Platnoscipl extends Payment_Driver {
	
	const PAYMENT_URL     = 'https://www.platnosci.pl/paygw/';
	const PAYMENT_NEW     = 'NewPayment';
	const PAYMENT_GET     = 'Payment/get';
	const PAYMENT_CONFIRM = 'Payment/confirm';
	const PAYMENT_CANCEL  = 'Payment/cancel';
	
	const PAYMENT_CODE_UTF = 'UTF';
	const PAYMENT_CODE_ISO = 'ISO';
	const PAYMENT_CODE_WIN = 'WIN';

	const PAYMENT_FORMAT_XML = 'xml';
	const PAYMENT_FORMAT_TXT = 'txt';
	
	protected $_codepage = NULL;
	protected $_format   = NULL;
	
	protected $_pos_data = array(
		'pos_id'       => NULL,
		'pos_auth_key' => NULL,
		'key'          => NULL,
		'key2'         => NULL,
	);
	protected $_actions = array(
		'online'  => NULL,
		'success' => NULL,
		'fail'    => NULL,
	);
	
	protected $_fields_required = array(
		'pos_id'       => NULL,
		'pos_auth_key' => NULL,
		'session_id'   => NULL,
		'amount'       => NULL,
		'desc'         => NULL,
		'first_name'   => NULL,
		'last_name'    => NULL,
		'email'        => NULL,
		'client_ip'    => NULL,
	);
	
	protected $_fields = array(
		'pay_type'      => NULL,
		'order_id'      => NULL,
		'desc2'         => NULL,
		'trsDesc'       => NULL,
		'street'        => NULL,
		'street_hn'     => NULL,
		'street_an'     => NULL,
		'city'          => NULL,
		'post_code'     => NULL,
		'country'       => NULL,
		'phone'         => NULL,
		'language'      => NULL,
		'js'            => NULL,
		'payback_login' => NULL,
		'sig'           => NULL,
		'ts'            => NULL,
	);
	
	public function __construct($config = NULL) {
		$this->_codepage = self::PAYMENT_CODE_UTF;
		$this->_format   = self::PAYMENT_FORMAT_XML;
		
		$this->_actions = Arr::overwrite($this->_actions, Arr::get($config, 'actions', array()));
		$this->_pos_data = Arr::overwrite($this->_pos_data, $config);
		
		$this->_fields_required['client_ip'] = Request::$client_ip;
	}
	
	public function process($only_fields = FALSE) {
		if (empty($this->_fields_required['pos_id']) && empty($this->_fields_required['pos_auth_key'])) {
			$this->_set_active_pos();		
		}
		foreach ($this->_fields_required as $field) {
			if (empty($field)) return FALSE;
		}
		
		$this->_fields['ts'] = time();
		$fields = array_merge($this->_fields_required, $this->_fields);
		
		$data = array(
			'pos_id' => $fields['pos_id'],
			'pay_type' => $fields['pay_type'],
			'session_id' => $fields['session_id'],
			'pos_auth_key' => $fields['pos_auth_key'],
			'amount' => $fields['amount'],
			'desc' => $fields['desc'],
			'desc2' => $fields['desc2'],
			'order_id' => $fields['order_id'],
			'first_name' => $fields['first_name'],
			'last_name' => $fields['last_name'],
			'payback_login' => $fields['payback_login'],
			'street' => $fields['street'],
			'street_hn' => $fields['street_hn'],
			'street_an' => $fields['street_an'],
			'city' => $fields['city'],
			'post_code' => $fields['post_code'],
			'country' => $fields['country'],
			'email' => $fields['email'],
			'phone' => $fields['phone'],
			'language' => $fields['language'],
			'client_ip' => $fields['client_ip'],
			'ts' => $fields['ts'],
			'key' => $this->_pos_data['key'],
		);
		
		$fields['sig'] = $this->_get_sig($data);
		unset($fields['key']);
		
		if (!$only_fields) {
			$form = Form::open(self::PAYMENT_URL . $this->_codepage . '/' . self::PAYMENT_NEW, array('method' => 'post', 'name' => 'platnoscipl'));
		}
		foreach ($fields as $key => $value) {
			if (!empty($value)) {
				$form .= Form::hidden($key, $value);
			}
		}

		if (!$only_fields) {
			$form .= Form::close();
			$form .= '<script type="text/javascript">document.platnoscipl.submit();</script>';
		}
		return $form;
	}
	
	public function set_format($format) {
		if (in_array($format, array(self::PAYMENT_FORMAT_XML,self::PAYMENT_FORMAT_TXT))) {
			$this->_format = $format;
		}
	}
	
	public function set_codepage($codepage) {
		if (in_array($codepage, array(self::PAYMENT_CODE_UTF,self::PAYMENT_CODE_ISO,self::PAYMENT_CODE_WIN))) {
			$this->_codepage = $codepage;
		}
	}
	
	// TODO: zwracanie innych danych poza statusem
	public function update_status() {
		if ($_POST) {
			$data = array(
				'pos_id' => Arr::get($_POST,'pos_id'),
				'session_id' => Arr::get($_POST,'session_id'),
				'ts' => Arr::get($_POST,'ts'),
			);
			
			if ($this->_check_sig(Arr::get($_POST,'sig'), $data)) {
				return $this->_get_status();			
			}
		}
		return FALSE;
	}
	
	public function set_pos($pos_id, $pos_auth_key, $key, $key2) {
		$this->_pos_data['pos_id'] = $pos_id;
		$this->_pos_data['pos_auth_key'] = $pos_auth_key;
		$this->_pos_data['key'] = $key;
		$this->_pos_data['key2'] = $key2;
	}

	public function set_actions($online, $success, $fail) {
		$this->_actions['online']  = $online;
		$this->_actions['success'] = $success;
		$this->_actions['fail']    = $fail;
	}
	
	public function action_online() {
		return $this->_actions['online'];
	}

	public function action_success() {
		return $this->_actions['success'];
	}

	public function action_fail() {
		return $this->_actions['fail'];
	}
	
	public function get_pos() {
		return $this->_pos_data;
	}
	
	private function _get_status() {
		$data = array(
			'pos_id' => Arr::get($_POST,'pos_id'),
			'session_id' => Arr::get($_POST,'session_id'),
			'ts' => time(),
			'key' => $this->_pos_data['key'],
		);
		$data['sig'] = $this->_get_sig($data);
		unset($data['key']);
		
		$url = self::PAYMENT_URL . $this->_codepage . '/' . self::PAYMENT_GET . '/' . $this->_format;
		$response = Remote::get($url, array(
			CURLOPT_POST       => TRUE,
			CURLOPT_POSTFIELDS => http_build_query($data),
		));
		
		
		if ($this->_format == self::PAYMENT_FORMAT_XML) {
			$xml = new SimpleXMLElement($response);
			
			$pending = array(1,4,5);
			$success = array(99);
			$fail = array(2,3,7,888);
			
			if (in_array((int)$xml->trans->status, $pending))
				return Payment::STATUS_PENDING;
			if (in_array((int)$xml->trans->status, $success))
				return Payment::STATUS_SUCCESS;
			if (in_array((int)$xml->trans->status, $fail))
				return Payment::STATUS_FAILED;
			
			return FALSE;
		}
	}
	
	private function _set_active_pos() {
		 $this->_fields_required = Arr::overwrite($this->_fields_required, $this->_pos_data);
	}
	
	private function _get_sig($data = array()) {
		return md5(implode('',$data));
	}
	
	private function _check_sig($sig, $data = array()) {
		return ($sig == md5(implode('',$data).$this->_pos_data['key2']));
	}
	
}