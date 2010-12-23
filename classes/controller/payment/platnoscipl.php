<?php defined('SYSPATH') OR die('No direct access allowed.');

class Controller_Payment_Platnoscipl extends Controller_Payment {
	
	public function action_online() {
		if (Payment::instance()->update_status()) {
			$this->request->response = Request::factory(Payment::instance()->action_online())->execute()->response;		
		}
	}

	public function action_success() {
		$this->request->redirect(Payment::instance()->action_success());		
	}
	
	public function action_fail() {
		$this->request->redirect(Payment::instance()->action_fail());		
	}
	
}