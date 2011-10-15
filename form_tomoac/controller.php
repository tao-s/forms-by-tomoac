<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

class FormTomoacPackage extends Package {

     protected $pkgHandle = 'form_tomoac';
     protected $appVersionRequired = '5.4.0';
     protected $pkgVersion = '2.4.3';

     public function getPackageDescription() {
          return '入力チェック、メール・日付・郵便番号検索フォームが作れます。';
     }

     public function getPackageName() {
          return 'フォーム（tomoacの機能拡張フォーム）';
     }
     
	public function install() {
		$pkg = parent::install();

		// install block 
		BlockType::installBlockTypeFromPackage('form_tomoac', $pkg); 

		Loader::model('single_page');

		// install pages
		$sp1 = SinglePage::add('/dashboard/form_tomoac', $pkg);
		$sp1->update(array('cName'=>'機能拡張フォーム', 'cDescription'=>'tomoacの機能拡張フォーム'));

		$sp2 = SinglePage::add('/dashboard/form_tomoac/upload', $pkg);
		$sp2->update(array('cName'=>'郵便番号辞書', 'cDescription'=>'郵便番号辞書のアップロード'));
	}
}
