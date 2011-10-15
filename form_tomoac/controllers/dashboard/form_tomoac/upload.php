<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardFormTomoacUploadController extends Controller {

	public function all_postno_upload() {
		$u = new User();
		if ($u->isSuperUser()) {
			$sfn = $_FILES['archive']['tmp_name'];
			$sfp = fopen($sfn, "r");
			$db = Loader::db();
			$errmes  = '';
			while($csv = fgetcsv($sfp,512,',')) {
				$enc = mb_detect_encoding($csv[6],'UTF-8');
				if(strcmp($enc,'UTF-8')) {
					$errmes = 'ダウンロードしたファイルは、UTF-8に変換してからアップロードしてください。';
					break;
				}
				$csv[3] = mb_convert_kana($csv[3], 'KV');
				$csv[4] = mb_convert_kana($csv[4], 'KV');
				$csv[5] = mb_convert_kana($csv[5], 'KV');
				$h3 = explode("(",$csv[5]);
				$csv[5] = $h3[0];
				$csv[8] = str_replace('以下に掲載がない場合','', $csv[8]);
				$k3 = explode("（",$csv[8]);
				$csv[8] = $k3[0];
				$db->Execute("insert into btPostNoPersonal
					 (pno1,pno2,postno,h1,h2,h3,k1,k2,k3,f1,f2,f3,f4,f5,f6) values
					 ('$csv[0]','$csv[1]','$csv[2]','$csv[3]','$csv[4]','$csv[5]','$csv[6]','$csv[7]','$csv[8]',$csv[9],$csv[10],$csv[11],$csv[12],$csv[13],$csv[14])");
			}
			fclose($sfp);
			if($errmes == '')
				$this->set('message', '"'.$_FILES['archive']['name'].'" をアップロードしました。');
			else
				$this->set('message', '<font color="red">'.$errmes.'</font>');
		}
	}
}
