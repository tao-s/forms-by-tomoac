<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardFormTomoacUploadController extends Controller {

	public function all_postno_upload() {
		$u = new User();
		if ($u->isSuperUser()) {
		    set_time_limit(0);
			$sfn = $_FILES['archive']['tmp_name'];
			$sfp = fopen($sfn, "r");
			$db = Loader::db();
			$errmes  = '';
			$line = fgets($sfp,4096);
			$enc = mb_detect_encoding($line,"JIS, eucjp-win, sjis-win, sjis, EUC-JP, UTF-8");
			if($enc != 'UTF-8') {
                $fp = fopen($_FILES['archive']['tmp_name'].".enc","w+");
                fseek($sfp, 0);
				while(($line = fgets($sfp,4096)) !== false){
				    fwrite($fp,mb_convert_encoding($line,"UTF-8",$enc));
				}
                fseek($fp, 0);
                fclose($sfp);
                $sfp = $fp;
			}
            $db->begin();
            $db->Execute("TRUNCATE TABLE btPostNoPersonal");
			while($csv = fgetcsv($sfp,512,',')) {
				$csv[3] = mb_convert_kana($csv[3], 'KV');
				$csv[4] = mb_convert_kana($csv[4], 'KV');
				$csv[5] = mb_convert_kana($csv[5], 'KV');
				$h3 = explode("(",$csv[5]);
				$csv[5] = $h3[0];
				$csv[8] = str_replace('以下に掲載がない場合','', $csv[8]);
				$k3 = explode("（",$csv[8]);
				$csv[8] = $k3[0];
				$db->Execute("insert into btPostNoPersonal
					 (pno1,pno2,post no,h1,h2,h3,k1,k2,k3,f1,f2,f3,f4,f5,f6) values
					 (? , ? , ? , ? , ? , ? , ? , ? , ? ,? ,? , ? , ? , ? , ?)",$csv);
			}
            $db->commit();
            set_time_limit(ini_get("max_execution_time"));
            
			fclose($sfp);
			if($errmes == '')
				$this->set('message', '"'.$_FILES['archive']['name'].'" をアップロードしました。');
			else
				$this->set('message', '<font color="red">'.$errmes.'</font>');
		}
	}
}
