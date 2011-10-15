<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardFormTomoacController extends Controller {
	public function view() {
		$this->redirect('/dashboard/form_tomoac/upload');
	}
}
