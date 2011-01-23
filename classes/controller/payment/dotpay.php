<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Payment_Dotpay extends Controller_Payment {

	public function action_return() {
		$this->request->redirect(Payment::instance()->action_return());		
	}

	public function action_status() {
		$this->request->response = Request::factory(Payment::instance()->action_status())->execute()->response;
	}
	
}