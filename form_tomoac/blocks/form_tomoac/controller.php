<?php  
defined('C5_EXECUTE') or die("Access Denied.");
class FormTomoacBlockController extends BlockController {

	public $btTable = 'btFormTomoac';
	public $btQuestionsTablename = 'btFormTomoacQuestions';
	public $btAnswerSetTablename = 'btFormTomoacAnswerSet';
	public $btAnswersTablename = 'btFormTomoacAnswers'; 	
	public $btInterfaceWidth = '420';
	public $btInterfaceHeight = '590';
	public $thankyouMsg=''; 
	
	protected $noSubmitFormRedirect=0;
	protected $lastAnswerSetId=0;
		
	/** 
	 * Used for localization. If we want to localize the name/description we have to include this
	 */
	public function getBlockTypeDescription() {
		return "入力チェック、メール・日付・郵便番号検索フォームが作れます。";
	}
	
	public function getBlockTypeName() {
		return "フォーム（tomoacの機能拡張フォーム）";
	}
	
	public function getJavaScriptStrings() {
		return array(
			'delete-question' => t('Are you sure you want to delete this question?'),
			'form-name' => t('Your form must have a name.'),
			'complete-required' => t('Please complete all required fields.'),
			'ajax-error' => t('AJAX Error.'),
			'form-min-1' => t('Please add at least one question to your form.')			
		);
	}
	
	public function __construct($b = null){ 
		parent::__construct($b);
		if(is_string($this->thankyouMsg) && !strlen($this->thankyouMsg)){ 
			$this->thankyouMsg = $this->getDefaultThankYouMsg();
		}
	}
	
	public function view(){ 
		$pURI = ($_REQUEST['pURI']) ? $_REQUEST['pURI'] : str_replace(array('&ccm_token='.$_REQUEST['ccm_token'],'&btask=passthru','&method=submit_form'),'',$_SERVER['REQUEST_URI']);
		$this->set('pURI',  htmlentities( $pURI, ENT_COMPAT, APP_CHARSET));
        $this->set("bID",intval($this->_bID));
	}
	
	public function getDefaultThankYouMsg() {
		return t("Thanks!");
	}
	
	//form add or edit submit 
	//(run after the duplicate method on first block edit of new page version)
	function save( $data=array() ) { 
		if( !$data || count($data)==0 ) $data=$_POST;  
		
		$b=$this->getBlockObject(); 
		$c=$b->getBlockCollectionObject();
		
		$db = Loader::db();
		if(intval($this->bID)>0){	 
			$q = "select count(*) as total from {$this->btTable} where bID = ".intval($this->bID);
			$total = $db->getOne($q);
		} else 
			$total = 0; 
		
		if($_POST['qsID']) 
			$data['qsID'] = $_POST['qsID'];
		if( !$data['qsID'] ) 
			$data['qsID'] = time(); 	
		if(!$data['oldQsID']) 
			$data['oldQsID'] = $data['qsID']; 
		$data['bID'] = intval($this->bID); 
		
		if(!isset($data['redirect']) || $data['redirect'] <= 0) {
			$data['redirectCID'] = 0;
		} 
		
		$v = array( $data['qsID'], $data['surveyName'], intval($data['notifyMeOnSubmission']), $data['recipientEmail'], $data['senderEmail'], $data['thankyouMsg'], $data['senderSub'], $data['senderMsg'], $data['recipientSub'], $data['recipientMsg'], intval($data['displayCaptcha']), intval($data['redirectCID']), intval($this->bID) );
 		
		//is it new? 
		if( intval($total)==0 ){ 
			$q = "insert into {$this->btTable} (questionSetId, surveyName, notifyMeOnSubmission, recipientEmail, senderEmail, thankyouMsg, senderSub, senderMsg, recipientSub, recipientMsg, displayCaptcha, redirectCID, bID) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";		
		}else{
			$q = "update {$this->btTable} set questionSetId = ?, surveyName=?, notifyMeOnSubmission=?, recipientEmail=?, senderEmail=?, thankyouMsg=?, senderSub=?, senderMsg=?, recipientSub=?, recipientMsg=?, displayCaptcha=?, redirectCID=? where bID = ? AND questionSetId=".$data['qsID'];
		}		
		
		$rs = $db->query($q,$v);  
		
		//Add Questions (for programmatically creating forms, such as during the site install)
		if( count($data['questions'])>0 ){
			$miniSurvey = new MiniSurveyTomoac();
			foreach( $data['questions'] as $questionData )
				$miniSurvey->addEditQuestion($questionData,0);
		}
 
 		$this->questionVersioning($data);
		
		return true;
	}
	
	//Ties the new or edited questions to the new block number
	//New and edited questions are temporarily given bID=0, until the block is saved... painfully complicated
	protected function questionVersioning( $data=array() ){
		$db = Loader::db();
		$oldBID = intval($data['bID']);
		
		//if this block is being edited a second time, remove edited questions with the current bID that are pending replacement
		//if( intval($oldBID) == intval($this->bID) ){  
			$vals=array( intval($data['oldQsID']) );  
			$pendingQuestions=$db->getAll('SELECT msqID FROM btFormTomoacQuestions WHERE bID=0 && questionSetId=?',$vals); 
			foreach($pendingQuestions as $pendingQuestion){  
				$vals=array( intval($this->bID), intval($pendingQuestion['msqID']) );  
				$db->query('DELETE FROM btFormTomoacQuestions WHERE bID=? AND msqID=?',$vals);
			}
		//} 
	
		//assign any new questions the new block id 
		$vals=array( intval($data['bID']), intval($data['qsID']), intval($data['oldQsID']) );  
		$rs=$db->query('UPDATE btFormTomoacQuestions SET bID=?, questionSetId=? WHERE bID=0 && questionSetId=?',$vals);
 
 		//These are deleted or edited questions.  (edited questions have already been created with the new bID).
 		$ignoreQuestionIDsDirty=explode( ',', $data['ignoreQuestionIDs'] );
		$ignoreQuestionIDs=array(0);
		foreach($ignoreQuestionIDsDirty as $msqID)
			$ignoreQuestionIDs[]=intval($msqID);	
		$ignoreQuestionIDstr=join(',',$ignoreQuestionIDs); 
		
		//remove any questions that are pending deletion, that already have this current bID 
 		$pendingDeleteQIDsDirty=explode( ',', $data['pendingDeleteIDs'] );
		$pendingDeleteQIDs=array();
		foreach($pendingDeleteQIDsDirty as $msqID)
			$pendingDeleteQIDs[]=intval($msqID);		
		$vals=array( $this->bID, intval($data['qsID']), join(',',$pendingDeleteQIDs) );  
		$unchangedQuestions=$db->query('DELETE FROM btFormTomoacQuestions WHERE bID=? AND questionSetId=? AND msqID IN (?)',$vals);			
	} 
	
	//Duplicate will run when copying a page with a block, or editing a block for the first time within a page version (before the save).
	function duplicate($newBID) { 
	
		$b=$this->getBlockObject(); 
		$c=$b->getBlockCollectionObject();	 
		 
		$db = Loader::db();
		$v = array($this->bID);
		$q = "select * from {$this->btTable} where bID = ? LIMIT 1";
		$r = $db->query($q, $v);
		$row = $r->fetchRow();
		
		//if the same block exists in multiple collections with the same questionSetID
		if(count($row)>0){ 
			$oldQuestionSetId=$row['questionSetId']; 
			
			//It should only generate a new question set id if the block is copied to a new page,
			//otherwise it will loose all of its answer sets (from all the people who've used the form on this page)
			$questionSetCIDs=$db->getCol("SELECT distinct cID FROM {$this->btTable} AS f, CollectionVersionBlocks AS cvb ".
						"WHERE f.bID=cvb.bID AND questionSetId=".intval($row['questionSetId']) );
			
			//this question set id is used on other pages, so make a new one for this page block 
			if( count( $questionSetCIDs ) >1 || !in_array( $c->cID, $questionSetCIDs ) ){ 
				$newQuestionSetId=time(); 
				$_POST['qsID']=$newQuestionSetId; 
			}else{
				//otherwise the question set id stays the same
				$newQuestionSetId=$row['questionSetId']; 
			}
			
			//duplicate survey block record 
			//with a new Block ID and a new Question 
			$v = array($newQuestionSetId,$row['surveyName'],$newBID,$row['thankyouMsg'],$row['senderSub'],$row['senderMsg'],$row['recipientSub'],$row['recipientMsg'],intval($row['notifyMeOnSubmission']),$row['recipientEmail'],$row['senderEmail'],$row['displayCaptcha']);
			$q = "insert into {$this->btTable} ( questionSetId, surveyName, bID,thankyouMsg,senderSub,senderMsg,recipientSub,recipientMsg,notifyMeOnSubmission,recipientEmail,senderEmail,displayCaptcha) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$result=$db->Execute($q, $v); 
			
			$rs=$db->query("SELECT * FROM {$this->btQuestionsTablename} WHERE questionSetId=$oldQuestionSetId AND bID=".intval($this->bID) );
			while( $row=$rs->fetchRow() ){
				$v=array($newQuestionSetId,intval($row['msqID']), intval($newBID), $row['question'],$row['inputType'],$row['options'],$row['position'],$row['width'],$row['height'],$row['width2'],$row['width3'],$row['required'],$row['layout'],$row['layout2'],$row['checklevel'],$row['description'],$row['description2']);
				$sql= "INSERT INTO {$this->btQuestionsTablename} (questionSetId,msqID,bID,question,inputType,options,position,width,height,width2,width3,required,layout,layout2,checklevel,description,description2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
				$db->Execute($sql, $v);
			}
			
			return $newQuestionSetId;
		}
		return 0;	
	}
	

	//users submits the completed survey
	function action_submit_form() { 
	
		$ip = Loader::helper('validation/ip');
		Loader::library("file/importer");
		
		if (!$ip->check()) {
			$this->set('invalidIP', $ip->getErrorMessage());			
			return;
		}	

		$txt = Loader::helper('text');
		$db = Loader::db();

		if($_POST['state'] == '')
			$_POST['state'] = 0;
		if(!(mb_strpos($_POST['Submit'], '戻') === FALSE))
			$_POST['state'] = 1;
		//	$_POST['state'] = 0: フォーム表示 => 1: 確認画面
		//	$_POST['state'] = 1: フォーム表示 => 0: フォーム画面
		//	$_POST['state'] = 2: 送信

			//question set id
		$qsID=intval($_POST['qsID']); 
		if($qsID==0)
			throw new Exception(t("Oops, something is wrong with the form you posted (it doesn't have a question set id)."));
			
		//get all questions for this question set // tomoac 2011/5/31
		$rows=$db->GetArray("SELECT * FROM {$this->btQuestionsTablename} WHERE questionSetId=? AND bID=? order by position,msqID asc", array( $qsID, intval($this->bID)));  // tomoac
//		$rows=$db->GetArray("SELECT * FROM {$this->btQuestionsTablename} WHERE questionSetId=? AND bID=? order by position asc", array( $qsID, intval($this->bID)));

		// check captcha if activated
		if($_POST['state'] == 0) {
			if ($this->displayCaptcha) {
				$captcha = Loader::helper('validation/captcha');
				if (!$captcha->check()) {
					$errors['captcha'] = t("Incorrect captcha code");
					$_REQUEST['ccmCaptchaCode']='';
				}
			}
		}
		
		//checked required fields
		foreach($rows as $row){
			if( intval($row['required'])==1 ){
				$notCompleted=0;
				if($row['inputType']=='checkboxlist'){
					$answerFound=0;
					$pair = explode('%%', $_POST['Question'.$row['msqID']]);
					foreach($pair as $p) {
						if(strlen($p) > 0)
							$answerFound=1;
					}
					foreach($_POST as $key=>$val){
						if( strstr($key,'Question'.$row['msqID'].'_') && strlen($val) ){
							$answerFound=1;
						} 
					}
					if(!$answerFound)
						$notCompleted=1;
				}
					elseif($row['inputType']=='date') { // tomoac 
					$continue;
				}
				elseif($row['inputType']=='postno') {
					if(!strlen(trim($_POST['Question'.$row['msqID']])))
						$notCompleted=1;
				}
				elseif($row['inputType']=='fileupload') {
					if( !isset($_FILES['Question'.$row['msqID']]) || !is_uploaded_file($_FILES['Question'.$row['msqID']]['tmp_name']) )					
						$notCompleted=1;
				}
				elseif($row['inputType']=='mailx2') {
					if($_POST['state'] == 2) {
						if(!strlen(trim($_POST['Question'.$row['msqID']])))
							$notCompleted=1;
					} else {
						if(!strlen(trim($_POST['Question'.$row['msqID'].'a'])) || !strlen(trim($_POST['Question'.$row['msqID'].'b'])))
							$notCompleted=1;
					}
				}
				elseif( !strlen(trim($_POST['Question'.$row['msqID']])) ){
					$notCompleted=1;
				}
//				if($notCompleted) $errors['CompleteRequired'] = t("Complete required fields *") ; 
				if($notCompleted) $errors['CompleteRequired'] .= $row['question'] . ': 必須の入力項目です（＊）<br />'; // tomoac
			}
		}
// --(( tomoac@
		//checked any fields
		$mailtemp1 = "";
		$mailtemp2 = "";
		$pattern = '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/iD'; // メールチェックパターン
		foreach($rows as $row){

		  if($_POST['state'] != 2) {

			if($row['inputType']=='field' or $row['inputType']=='text'){
				$rrr = "";
				$fld = trim($_POST['Question'.$row['msqID']]);
				/*
				$f = htmlspecialchars($fld, ENT_QUOTES);
				if($fld !== $f)
					$errors['FieldError'] .= $row['question'] . ": 特殊文字は入力できません。<br />";
				*/
				$str = $row['checklevel'];
				if(mb_strpos($str, 'HN')===FALSE) { // 半角数字禁止
			        $res = preg_replace('/[0-9]+/','',$fld);
					if($fld != $res){
						//$errors['FieldError'] .= $row['question'] . ": 半角数字は入力できません。<br />";
						$fld = mb_convert_kana($fld,"N","UTF-8");
				    }
				}
				if(mb_strpos($str, 'HO')===FALSE) { // 半角英字禁止
			        $res = preg_replace('/[a-zA-Z]+/','',$fld);
					if($fld != $res){
						//$errors['FieldError'] .= $row['question'] . ": 半角英字は入力できません。<br />";
						$fld = mb_convert_kana($fld,"R","UTF-8");
					}	
				}
				if(mb_strpos($str, 'HS')===FALSE) { // 半角特殊記号禁止
			        $res = preg_replace('/[\+\-\/\*\,\. ]+/','',$fld);
					if($fld != $res){
						//$errors['FieldError'] .= $row['question'] . ": 半角特殊記号は入力できません。<br />";
				        $fld = str_replace("[","［",$fld);
				        $fld = str_replace("+","＋",$fld);
				        $fld = str_replace("-","ー",$fld);
				        $fld = str_replace("/","／",$fld);
				        $fld = str_replace("\\","＼",$fld);
				        $fld = str_replace("*","＊",$fld);
				        $fld = str_replace(",","，",$fld);
				        $fld = str_replace(".","．",$fld);
				        $fld = str_replace("]","］",$fld);
				    }
				}
				mb_regex_encoding("UTF-8");
				if(mb_strpos($str, 'ZH')===FALSE) { // 全角ひらがな禁止
					$res = mb_ereg_replace('[ぁ-ん]','',$fld);
					if($res!=$fld)
						$errors['FieldError'] .= $row['question'] . ": 全角ひらがなは入力できません。<br />";
				}
				if(mb_strpos($str, 'ZT')===FALSE) { // 全角カタカナ禁止
					$res = mb_ereg_replace('[ァ-ヶ]','',$fld);
					if($res!=$fld){
						//$errors['FieldError'] .= $row['question'] . ": 全角カタカナは入力できません。<br />";
						$fld = mb_convert_kana($fld,"k","UTF-8");
				    }
				}
				if(mb_strpos($str, 'ZK')===FALSE) { // 全角漢字禁止
					$res = mb_ereg_replace('[亜-腕]','',$fld);
					if($res!=$fld)
						$errors['FieldError'] .= $row['question'] . ": 全角漢字は入力できません。<br />";
				}
//				$errors['FieldError'] = $str."(".$fld.")(".$res.")".$rrr;
			}
			// ==== 郵便番号チェック ==========
			if($row['inputType']=='postno'){
				$fld = trim($_POST['Question'.$row['msqID']]);
				$f = htmlspecialchars($fld, ENT_QUOTES);
				if($fld !== $f)
					$errors['PostnoError'] .= $row['question'] . ": 特殊文字は入力できません。(".$fld.")<br />";
				$fld = trim($_POST['Question'.$row['msqID'].'a']);
				$f = htmlspecialchars($fld, ENT_QUOTES);
				if($fld !== $f)
					$errors['PostnoError'] .= $row['question'] . ": 特殊文字は入力できません。(".$fld.")<br />";
				$fld = trim($_POST['Question'.$row['msqID'].'b']);
				$f = htmlspecialchars($fld, ENT_QUOTES);
				if($fld !== $f)
					$errors['PostnoError'] .= $row['question'] . ": 特殊文字は入力できません。(".$fld.")<br />";
				$fld = trim($_POST['Question'.$row['msqID'].'c']);
				$f = htmlspecialchars($fld, ENT_QUOTES);
				if($fld !== $f)
					$errors['PostnoError'] .= $row['question'] . ": 特殊文字は入力できません。(".$fld.")<br />";
			}
			// ==== 日付の存在チェック ====
			if($row['inputType']=='date'){
				$yy = trim($_POST['Question'.$row['msqID'].'a']);
				$mm = trim($_POST['Question'.$row['msqID'].'b']);
				$dd = trim($_POST['Question'.$row['msqID'].'c']);
				if(!checkdate($mm,$dd,$yy))
					$errors['Date'] .= $row['question'] . ": 存在しない日付（".$yy."年".$mm."月".$dd."日）が選択されています。<br />";
			}
			// ==== メールアドレスチェック ====
			if($row['inputType']=='mail'){
				$mailtemp1 = trim($_POST['Question'.$row['msqID']]);
				if($mailtemp1 != '')
 					if(!preg_match($pattern,$mailtemp1))
						$errors['MailError'] .= $row['question'] . ": メールアドレスが間違ってます。<br />"; //.$mailtemp1;
			}
			if($row['inputType']=='mailx2') {
				$mailtemp1 = trim($_POST['Question'.$row['msqID'].'a']);
				$mailtemp2 = trim($_POST['Question'.$row['msqID'].'b']);
				if($mailtemp1 != $mailtemp2)
					$errors['MailError'] .= $row['question'] . ": メールアドレスが一致していません。(".$mailtemp1.":".$mailtemp2.")<br />";
				else
					if($mailtemp1 != '')
						if(!preg_match($pattern,$mailtemp2))
							$errors['MailError'] .= $row['question'] . ": メールアドレスが間違ってます。<br />";
			}
			// ==== チェックボックス ==========
			if($row['inputType']=='checkboxlist'){
				$cmax = $row['checklevel'];
				if($cmax > 0) {
					$keys = array_keys($_POST);
					$c = -2;
					foreach ($keys as $key){
						if (strpos($key, 'Question'.$row['msqID'].'_') === 0)
							$c++;
					}
					if($c > $cmax)
						$errors['Checkboxlist'] .= $row['question'] ."： 選択数の最大数が設定されています。チェックは".$cmax."つ以下でお願いします。<br />";
				}
			}
		  }
		}
// --)) tomoac@
		
		//try importing the file if everything else went ok	
		$tmpFileIds=array();	
		if(!count($errors))	foreach($rows as $row){
			if( $row['inputType']!='fileupload' ) continue;
			$questionName = 'Question'.$row['msqID']; 			
			if	( !intval($row['required']) && 
			   		( 
			   		!isset($_FILES[$questionName]['tmp_name']) || !is_uploaded_file($_FILES[$questionName]['tmp_name'])
			   		) 
				){
					continue;
			}
			$fi = new FileImporter();
			$resp = $fi->import($_FILES[$questionName]['tmp_name'], $_FILES[$questionName]['name']);
			if (!($resp instanceof FileVersion)) {
				switch($resp) {
					case FileImporter::E_FILE_INVALID_EXTENSION:
						$errors['fileupload'] = t('Invalid file extension.');
						break;
					case FileImporter::E_FILE_INVALID:
						$errors['fileupload'] = t('Invalid file.');
						break;
					
				}
			}else{
				$tmpFileIds[intval($row['msqID'])] = $resp->getFileID();
			}	
		}	
		// ---- エラーがあるとき
		if(count($errors)){

			$this->set('formResponse', t('Please correct the following errors:') );
			$this->set('errors',$errors);
			$this->set('Entry',$E);			

		} else { //no form errors			
		// ---- エラーがないとき
			//save main survey record	
			$u = new User();
			$uID = 0;
			if ($u->isRegistered()) {
				$uID = $u->getUserID();
			}
			$q="insert into {$this->btAnswerSetTablename} (questionSetId, uID) values (?,?)";
			$db->query($q,array($qsID, $uID));
			$answerSetID = $db->Insert_ID();
			$this->lastAnswerSetId = $answerSetID;
			
			$questionAnswerPairs=array();
			$qaPair = ''; // tomoac フォーム入力項目を入れる  key,val;key,val;key,val;...

			//loop through each question and get the answers 
			foreach( $rows as $row ){	
				//save each answer
				if($row['inputType']=='checkboxlist'){
					$answer = Array();
					$answerLong="";
					$keys = array_keys($_POST);
					$f = 0;
					foreach ($keys as $key){
						if (strpos($key, 'Question'.$row['msqID'].'_') === 0){
							$answer[] = $txt->sanitize($_POST[$key]);
							if($f == 0)
								$qaPair .= $key.','.$row['question'].','.$txt->sanitize($_POST[$key]).';';
							else
								$qaPair .= $key.',,'.$txt->sanitize($_POST[$key]).';';
							$f++;
						}
					}
				} 
				elseif($row['inputType']=='postno'){
					$answerLong = "";
					$answer = $txt->sanitize($_POST['Question'.$row['msqID']]);
//					$answer .= $txt->sanitize(' '.$_POST['Question'.$row['msqID'].'a'].' '.$_POST['Question'.$row['msqID'].'b'].' '.$_POST['Question'.$row['msqID'].'c']);

					$qaPair .= 'Question'.$row['msqID'].'_P,,;';
					$qaPair .= 'Question'.$row['msqID'].'a,,'.$txt->sanitize($_POST['Question'.$row['msqID'].'a']).';';
					$qaPair .= 'Question'.$row['msqID'].'b,,'.$txt->sanitize($_POST['Question'.$row['msqID'].'b']).';';
					$qaPair .= 'Question'.$row['msqID'].'c,,'.$txt->sanitize($_POST['Question'.$row['msqID'].'c']).';';
					$qaPair .= 'Question'.$row['msqID'].'_O,,;';
					$qaPair .= 'Question'.$row['msqID'].','.$row['question'].','.$txt->sanitize( $answer.$answerLong ).';';
				}
				elseif($row['inputType']=='date'){
					$answerLong="";
					$answer=$txt->sanitize($_POST['Question'.$row['msqID'].'a'].'年'.$_POST['Question'.$row['msqID'].'b'].'月'.$_POST['Question'.$row['msqID'].'c'].'日');

					$qaPair .= 'Question'.$row['msqID'].'_D,,;';
					$qaPair .= 'Question'.$row['msqID'].'a,'.$row['question'].','.$txt->sanitize($_POST['Question'.$row['msqID'].'a']).';';
					$qaPair .= 'Question'.$row['msqID'].'b,'.$row['question'].','.$txt->sanitize($_POST['Question'.$row['msqID'].'b']).';';
					$qaPair .= 'Question'.$row['msqID'].'c,'.$row['question'].','.$txt->sanitize($_POST['Question'.$row['msqID'].'c']).';';
					$qaPair .= 'Question'.$row['msqID'].'_T,,;';
					$qaPair .= 'Question'.$row['msqID'].','.$row['question'].','.$txt->sanitize( $answer.$answerLong ).';';
				}
				elseif($row['inputType']=='mailx2'){
					$answerLong="";
					$answer = $txt->sanitize($_POST['Question'.$row['msqID'].'a']);		// 入力後はここ
					if($answer === '')
						$answer.= $txt->sanitize($_POST['Question'.$row['msqID']]);		// 確認画面や送信時はここ
					if($row['layout'] == 1) { // 返信する
						$qaPair .= 'mQuestion'.$row['msqID'].',,Question'.$row['msqID'].';';
						$usermail_key = 'Question'.$row['msqID'];
					}
					$qaPair .= 'Question'.$row['msqID'].'a,,'.$txt->sanitize($_POST['Question'.$row['msqID'].'a']).';';
					$qaPair .= 'Question'.$row['msqID'].'b,,'.$txt->sanitize($_POST['Question'.$row['msqID'].'b']).';';
					$qaPair .= 'Question'.$row['msqID'].','.$row['question'].','.$txt->sanitize( $answer.$answerLong ).';';
				}
				elseif($row['inputType']=='mail') {
					$answerLong="";
					$answer = $txt->sanitize($_POST['Question'.$row['msqID']]);		// 入力後はここ
					if($row['layout'] == 1) { // 返信する
						$qaPair .= 'mQuestion'.$row['msqID'].',,Question'.$row['msqID'].';';
						$usermail_key = 'Question'.$row['msqID'];
					}
					$qaPair .= 'Question'.$row['msqID'].','.$row['question'].','.$txt->sanitize( $answer.$answerLong ).';';
				}
				elseif($row['inputType']=='text'){
					$answerLong=$txt->sanitize($_POST['Question'.$row['msqID']]);
					$answer='';
					$qaPair .= 'Question'.$row['msqID'].','.$row['question'].','.$txt->sanitize( $answer.$answerLong ).';';
				}
				elseif($row['inputType']=='fileupload') {
					$answer = intval( $tmpFileIds[intval($row['msqID'])] );
					$qaPair .= 'Question'.$row['msqID'].','.$row['question'].','.$_FILES['Question'.$row['msqID']]['name'];
					if(strlen($_FILES['Question'.$row['msqID']]['name']) != 0)
						$qaPair .= '　（アップロード完了）';
					$qaPair .= ';';
				}
				else{
					$answerLong="";
					$answer=$txt->sanitize($_POST['Question'.$row['msqID']]);
					$qaPair .= 'Question'.$row['msqID'].','.$row['question'].','.$txt->sanitize( $answer.$answerLong ).';';
				}
				
				if( is_array($answer) ) 
					$answer=join(',',$answer);

				$questionAnswerPairs[$row['msqID']]['question'] = $row['question'];
				$questionAnswerPairs[$row['msqID']]['answer']   = $txt->sanitize( $answer.$answerLong );

				$v=array($row['msqID'],$answerSetID,$answer,$answerLong);
				$q="insert into {$this->btAnswersTablename} (msqID,asID,answer,answerLong) values (?,?,?,?)";
				if($_POST['state'] == 2)
					$db->query($q,$v);
			}
			// ユーザ向けメールの取り出し --- by usermail_key, $usermail
			$usermail = '';
			$recipientBody = $this->recipientMsg;
			$senderBody = $this->senderMsg;
			$pair = explode(';',$qaPair);
			$i = 0;
			foreach($pair as $p) {
				$a = explode(',',$p);
				// 住所の開始
				if(strcmp(substr($a[0],-2),'_P') == 0) {
					$pm = 1;
					continue;
				}
				// 日付の要素をスキップ
				if(strcmp(substr($a[0],-2),'_D') == 0) {
					$cd = 1;
					continue;
				}
				if(strcmp(substr($a[0],-2),'_T') == 0) {
					$cd = 0;
					continue;
				}
				if($cd == 1)
					continue;

				// メール送信アドレスをピックアップ
				if(strcmp($a[0],$usermail_key) == 0)
					if($usermail == '')
						$usermail = $a[2];
					else
						$usermail .= ','.$a[2];

//				$set .= "/".$a[0]."/".$a[1]."/".$a[2]."/"."\n"; //debug

				// checkboxlist
				if(strcmp(substr($a[0],-2,1),'_') == 0 && is_numeric(substr($a[0],-1))) {
					if(strlen($a[1]) != 0)
						$set .= "\n".$a[1]."\n";
					if(strlen($a[2]) != 0)
						$set .= $a[2]."\n";
				}

				if($a[1] != '') {
					$i++;
					$recipientBody = str_replace('@'.$i.'@', $a[1].': '.$a[2], $recipientBody);
					$senderBody    = str_replace('@'.$i.'@', $a[1].': '.$a[2], $senderBody);
					$recipientBody = str_replace('@'.$i.'#', $a[1], $recipientBody);
					$senderBody    = str_replace('@'.$i.'#', $a[1], $senderBody);
					$recipientBody = str_replace('#'.$i.'@', $a[2], $recipientBody);
					$senderBody    = str_replace('#'.$i.'@', $a[2], $senderBody);
					if($pm == 1) {
						$pm = 0;
						$set .= "\n".$a[1]."\n".'〒'.substr($a[2],0,3).'-'.substr($a[2],3,4).' '.$addr[0].' '.$addr[1].' '.$addr[2]."\n";
					} else {
						if(strlen($a[1]) != 0)
							$set .= "\n".$a[1]."\n";
						if(strlen($a[2]) != 0)
							$set .= $a[2]."\n";
					}
//				} else {
//					$recipientBody = str_replace('@'.$i.'@', '', $recipientBody);
//					$senderBody = str_replace('@'.$i.'@', '', $senderBody);
				}
				if($a[1] == '' && $pm == 1) { // 住所文字列を保存
					$addr[] = $a[2];
				}
			}
			$recipientBody = str_replace('@@', $set, $recipientBody);
			$senderBody    = str_replace('@@', $set, $senderBody);

			$refer_uri=$_POST['pURI'];
			if(!strstr($refer_uri,'?')) $refer_uri.='?';			
			
			if(intval($this->notifyMeOnSubmission)>0){	
				
				if( strlen(FORM_BLOCK_SENDER_EMAIL)>1 && strstr(FORM_BLOCK_SENDER_EMAIL,'@') ){
					$formFormEmailAddress = FORM_BLOCK_SENDER_EMAIL;  
				}else{ 
					$adminUserInfo=UserInfo::getByID(USER_SUPER_ID);
					$formFormEmailAddress = $adminUserInfo->getUserEmail(); 
				}  

				if($_POST['state'] == 2) {

					$mh = Loader::helper('mail');	// 運営者向け確認メール
					$mh->to( $this->senderEmail ); 
					$mh->from( $formFormEmailAddress ); 
					if(strlen(trim($this->senderMsg)) == 0) {
						$mh->addParameter('formName', $this->surveyName);
						$mh->addParameter('questionSetId', $this->questionSetId);
						$mh->addParameter('questionAnswerPairs', $questionAnswerPairs); 
						$mh->load('block_form_submission');
						$mh->setSubject(t('%s Form Submission', $this->surveyName));
					} else {
						$mh->setBody($senderBody);
						$mh->setSubject(sprintf($this->senderSub, $this->surveyName));
					}
					@$mh->sendMail(); 

					if(strlen(trim($usermail)) > 0) { // 投稿者向け確認メール
						$mh = Loader::helper('mail');
						$mh->to( $usermail );
						$mh->from( $formFormEmailAddress ); 
						if(strlen(trim($this->recipientMsg)) == 0) {
							$mh->addParameter('formName', $this->surveyName);
							$mh->addParameter('questionSetId', $this->questionSetId);
							$mh->addParameter('questionAnswerPairs', $questionAnswerPairs); 
							$mh->load('block_form_submission');
							$mh->setSubject(t('%s Form Submission', $this->surveyName));
						} else {
							$mh->setBody($recipientBody);
							$mh->setSubject(sprintf($this->recipientSub, $this->surveyName));
						}
						@$mh->sendMail(); 
					}
				}
			} 
			// POSTデータ
			$post["cID"] = $_GET['cID'];
			$post["bID"] = $_GET['bID'];
			$post["redirectCID"] = $this->redirectCID;

			$post["surveySuccess"] = 1;
			$post["qsid"] = $this->questionSetId.'#'.$this->questionSetId;

			if($_POST['state'] == 2) 
				$post["state"] = 2; // 完了画面へ
			else if($_POST['state'] == 1) 
				$post["state"] = 0; // 入力画面へ戻る
			else
				$post["state"] = 1; // 確認画面へ

//			echo $qaPair;  // @@
//			exit;

			$pair = explode(';',$qaPair);
			foreach($pair as $p) {
				$a = explode(',',$p);
				if($a[0] == '') continue;
				$post['i'.$a[0]] = $a[1];
				$post[$a[0]] = $a[2];
			}
			$this->set('post',$post);	// hgcsyn
		}
	}		
	
	function delete() { 
	
		$db = Loader::db();

		$deleteData['questionsIDs']=array();
		$deleteData['strandedAnswerSetIDs']=array();

		$miniSurvey=new MiniSurveyTomoac();
		$info=$miniSurvey->getMiniSurveyBlockInfo($this->bID);
		
		//get all answer sets
		$q = "SELECT asID FROM {$this->btAnswerSetTablename} WHERE questionSetId = ".intval($info['questionSetId']);
		$answerSetsRS = $db->query($q); 
 
		//delete the questions
		$deleteData['questionsIDs']=$db->getAll( "SELECT qID FROM {$this->btQuestionsTablename} WHERE questionSetId = ".intval($info['questionSetId']).' AND bID='.intval($this->bID) );
		foreach($deleteData['questionsIDs'] as $questionData)
			$db->query("DELETE FROM {$this->btQuestionsTablename} WHERE qID=".intval($questionData['qID']));			
		
		//delete left over answers
		$strandedAnswerIDs = $db->getAll('SELECT fa.aID FROM `btFormTomoacAnswers` AS fa LEFT JOIN btFormTomoacQuestions as fq ON fq.msqID=fa.msqID WHERE fq.msqID IS NULL');
		foreach($strandedAnswerIDs as $strandedAnswerIDs)
			$db->query('DELETE FROM `btFormTomoacAnswers` WHERE aID='.intval($strandedAnswer['aID']));
			
		//delete the left over answer sets
		$deleteData['strandedAnswerSetIDs'] = $db->getAll('SELECT aset.asID FROM btFormTomoacAnswerSet AS aset LEFT JOIN btFormTomoacAnswers AS fa ON aset.asID=fa.asID WHERE fa.asID IS NULL');
		foreach($deleteData['strandedAnswerSetIDs'] as $strandedAnswerSetIDs)
			$db->query('DELETE FROM btFormTomoacAnswerSet WHERE asID='.intval($strandedAnswerSetIDs['asID']));		
		
		//delete the form block		
		$q = "delete from {$this->btTable} where bID = '{$this->bID}'";
		$r = $db->query($q);		
		
		parent::delete();
		
		return $deleteData;
	}
}

/**
 * Namespace for statistics-related functions used by the form block.
 *
 * @package Blocks
 * @subpackage BlockTypes
 * @author Tony Trupp <tony@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
class FormTomoacBlockStatistics {

	public static function getTotalSubmissions($date = null) {
		$db = Loader::db();
		if ($date != null) {
			return $db->GetOne("select count(asID) from btFormTomoacAnswerSet where DATE_FORMAT(created, '%Y-%m-%d') = ?", array($date));
		} else {
			return $db->GetOne("select count(asID) from btFormTomoacAnswerSet");
		}

	}
	
	public static function loadSurveys($MiniSurvey){  
		$db = Loader::db();
		return $db->query('SELECT s.* FROM '.$MiniSurvey->btTable.' AS s, Blocks AS b, BlockTypes AS bt '.
						  'WHERE s.bID=b.bID AND b.btID=bt.btID AND bt.btHandle="form" ' );
	}
	
	public static $sortChoices=array('newest'=>'created DESC','chrono'=>'created');
	
	public static function buildAnswerSetsArray( $questionSet, $orderBy='', $limit='' ){
		$db = Loader::db();
		
		if( strlen(trim($limit))>0 && !strstr(strtolower($limit),'limit')  )
			$limit=' LIMIT '.$limit;
			
		if( strlen(trim($orderBy))>0 && array_key_exists($orderBy, self::$sortChoices) ){
			 $orderBySQL=self::$sortChoices[$orderBy];
		}else $orderBySQL=self::$sortChoices['newest'];
		
		//get answers sets
		$sql='SELECT * FROM btFormTomoacAnswerSet AS aSet '.
			 'WHERE aSet.questionSetId='.$questionSet.' ORDER BY '.$orderBySQL.' '.$limit;
		$answerSetsRS=$db->query($sql);
		//load answers into a nicer multi-dimensional array
		$answerSets=array();
		$answerSetIds=array(0);
		while( $answer = $answerSetsRS->fetchRow() ){
			//answer set id - question id
			$answerSets[$answer['asID']]=$answer;
			$answerSetIds[]=$answer['asID'];
		}		
		
		//get answers
		$sql='SELECT * FROM btFormTomoacAnswers AS a WHERE a.asID IN ('.join(',',$answerSetIds).')';
		$answersRS=$db->query($sql);
		
		//load answers into a nicer multi-dimensional array 
		while( $answer = $answersRS->fetchRow() ){
			//answer set id - question id
			$answerSets[$answer['asID']]['answers'][$answer['msqID']]=$answer;
		}
		return $answerSets;
	}
}

/**
 * Namespace for other functions used by the form block.
 *
 * @package Blocks
 * @subpackage BlockTypes
 * @author Tony Trupp <tony@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
class MiniSurveyTomoac{

		public $btTable = 'btFormTomoac';
		public $btQuestionsTablename = 'btFormTomoacQuestions';
		public $btAnswerSetTablename = 'btFormTomoacAnswerSet';
		public $btAnswersTablename = 'btFormTomoacAnswers'; 	
		
		public $lastSavedMsqID=0;
		public $lastSavedqID=0;

		function __construct(){
			$db = Loader::db();
			$this->db=$db;
		}

		function addEditQuestion($values,$withOutput=1){
			$jsonVals = array();
			$values['options'] = str_replace(array("\r","\n"),'%%',$values['options']); 

			if(strtolower($values['inputType'])=='undefined')
				$values['inputType']='field';
			
			//set question set id, or create a new one if none exists
			if(intval($values['qsID'])==0)
				$values['qsID']=time(); 
			
			//validation
			if( strlen($values['question'])==0 || strlen($values['inputType'])==0  || $values['inputType']=='null' ){
				//complete required fields
				$jsonVals['success']=0;
				$jsonVals['noRequired']=1;
			}else{
				
				if( intval($values['msqID']) ){
					$jsonVals['mode']='"Edit"';
					
					//questions that are edited are given a placeholder row in btFormTomoacQuestions with bID=0, until a bID is assign on block update
					$pendingEditExists = $this->db->getOne( "select count(*) as total from btFormTomoacQuestions where bID=0 AND msqID=".intval($values['msqID']) );
					
					//hideQID tells the interface to hide the old version of the question in the meantime
					$vals=array( intval($values['msqID'])); 		
					$jsonVals['hideQID']=intval($this->db->GetOne("SELECT MAX(qID) FROM btFormTomoacQuestions WHERE bID!=0 AND msqID=?",$vals));	
				}else{
					$jsonVals['mode']='"Add"';
				}
			
				if( $pendingEditExists ){   // 一時的なＤＢ登録データあり
					// 編集更新でのDB更新処理
					$width = $height = 0;
					if ($values['inputType'] == 'text'){
						$width  = $this->limitRange(intval($values['width']), 20, 500);
						$height = $this->limitRange(intval($values['height']), 1, 100); 
					}
// --(( tomoac@
					if ($values['inputType'] == 'field'){
						$width  = $this->limitRange(intval($values['width']), 1, 500);
						$height = $this->limitRange(intval($values['height']), 1, 100); 
					}
					if ($values['inputType'] == 'postno'){
						$width  = 7;
						$height = 14;
					}
					if ($values['inputType'] == 'mail' || $values['inputType'] == 'mailx2'){
						$width  = $this->limitRange(intval($values['width']), 16, 64);
						$height = $this->limitRange(intval($values['height']), 32, 64); 
						$values['layout'] = $values['mcheck'];  // メール送信するかどうかのフラグ
					}
					$dataValues=array(intval($values['qsID']), trim($values['question']), $values['inputType'],
							    $values['options'], intval($values['position']), $width, $height,intval($values['layout']),intval($values['layout2']),
								intval($values['required']), $values['clevel'], $values['description'], $values['description2'], intval($values['msqID']) );
					$sql='UPDATE btFormTomoacQuestions 
								SET questionSetId=?, question=?, inputType=?, options=?, position=?, width=?, height=?, layout=?, layout2=?,
										required=?, checklevel=?, description=?, description2=?  WHERE msqID=? AND bID=0';

					$result=$this->db->query($sql,$dataValues);  

// --)) tomoac@
				} else { 
					if( !isset($values['position']) )
						$values['position']=1000;
// --(( tomoac@
					// 新規登録でのDB追加処理
					if($values['inputType']=='postno') {		// postno
						$ed = $values['msqID'];
						if(!intval($values['msqID']))
							$values['msqID']=intval($this->db->GetOne("SELECT MAX(msqID) FROM btFormTomoacQuestions")+1); 
						$dataValues=array($values['msqID'],intval($values['qsID']), trim($values['question']), $values['inputType'],
									     $values['options'], intval($values['position']),intval($values['width']),intval($values['hight']),intval($values['width2']),intval($values['width3']),intval($values['required']),$values['layout'],$values['layout2'],$values['clevel'],$values['description'],$values['description2']);
						$sql='INSERT INTO btFormTomoacQuestions (msqID,questionSetId,question,inputType,options,position,width,height,width2,width3,required,layout,layout2,checklevel,description,description2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'; 
						$result=$this->db->query($sql,$dataValues);  	// postno
					}
					elseif($values['inputType']=='date') {			// date
						if(!intval($values['msqID']))
							$values['msqID']=intval($this->db->GetOne("SELECT MAX(msqID) FROM btFormTomoacQuestions")+1); 
						$dataValues=array($values['msqID'],intval($values['qsID']), trim($values['question']), 'date',
									$values['options'], intval($values['position']), intval($values['width']), intval($values['height']), intval($values['required']),
									$values['layout'], '', $values['description'], $values['description2'] );			
						$sql='INSERT INTO btFormTomoacQuestions (msqID,questionSetId,question,inputType,
								options,position,width,height,required,layout,checklevel,description,description2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'; 
						$result=$this->db->query($sql,$dataValues);  
					}
					elseif($values['inputType']=='mailx2') {	// mailx2
						$values['layout'] = $values['mcheck'];   // メール送信するかどうかのフラグ
						if(!intval($values['msqID']))
							$values['msqID']=intval($this->db->GetOne("SELECT MAX(msqID) FROM btFormTomoacQuestions")+1); 
						$dataValues=array($values['msqID'],intval($values['qsID']), trim($values['question']), 'mailx2',
										$values['options'], intval($values['position']), intval($values['width']), intval($values['height']),
										intval($values['required']), $values['layout'], '', $values['description'], $values['description2'] );			
						$sql='INSERT INTO btFormTomoacQuestions (msqID,questionSetId,question,inputType,
									options,position,width,height,required,layout,checklevel,description,description2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'; 
						$result=$this->db->query($sql,$dataValues);  
					}
					elseif($values['inputType']=='field') {		// field
						if(!intval($values['msqID']))
							$values['msqID']=intval($this->db->GetOne("SELECT MAX(msqID) FROM btFormTomoacQuestions")+1); 
						$dataValues=array($values['msqID'],intval($values['qsID']), trim($values['question']), $values['inputType'],
									    $values['options'], intval($values['position']), intval($values['width']), intval($values['height']),
										intval($values['required']), $values['layout'], $values['clevel'], $values['description'], $values['description2'] );
						$sql='INSERT INTO btFormTomoacQuestions (msqID,questionSetId,question,inputType,
									options,position,width,height,required,layout,checklevel,description,description2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'; 
						$result=$this->db->query($sql,$dataValues);  
					}
					elseif($values['inputType']=='text') { 		// text
						if(!intval($values['msqID']))
							$values['msqID']=intval($this->db->GetOne("SELECT MAX(msqID) FROM btFormTomoacQuestions")+1); 
						$dataValues=array($values['msqID'],intval($values['qsID']), trim($values['question']), $values['inputType'],
									    $values['options'], intval($values['position']), intval($values['width']), intval($values['height']),
										intval($values['required']), $values['layout'], $values['clevel'], $values['description'], $values['description2'] );
						$sql='INSERT INTO btFormTomoacQuestions (msqID,questionSetId,question,inputType,
									options,position,width,height,required,layout,checklevel,description,description2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'; 
						$result=$this->db->query($sql,$dataValues);  
					}
					elseif($values['inputType']=='fileupload') { 		// file
						if(!intval($values['msqID']))
							$values['msqID']=intval($this->db->GetOne("SELECT MAX(msqID) FROM btFormTomoacQuestions")+1); 
						$dataValues=array($values['msqID'],intval($values['qsID']), trim($values['question']), $values['inputType'],
									    $values['options'], intval($values['position']), intval($values['width']), intval($values['height']),
										intval($values['required']),$values['layout'],$values['layout2'], '', $values['description'], '' );			
						$sql='INSERT INTO btFormTomoacQuestions (msqID,questionSetId,question,inputType,
									options,position,width,height,required,layout,layout2,checklevel,description,description2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'; 
						$result=$this->db->query($sql,$dataValues);  
					}else{
					   if($values['inputType']=='mail' || $values['inputType']=='mailx2') {
				            $values['layout'] = $values['mcheck'];   // メール送信するかどうかのフラグ
						}
						if(!intval($values['msqID']))
							$values['msqID']=intval($this->db->GetOne("SELECT MAX(msqID) FROM btFormTomoacQuestions")+1); 
						$dataValues=array($values['msqID'],intval($values['qsID']), trim($values['question']), $values['inputType'],
									    $values['options'], intval($values['position']), intval($values['width']), intval($values['height']),
										intval($values['required']),$values['layout'],$values['layout2'],$values['clevel'],$values['description'],$values['description2']);			
						$sql='INSERT INTO btFormTomoacQuestions (msqID,questionSetId,question,inputType,
									options,position,width,height,required,layout,layout2,checklevel,description,description2) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'; 
						$result=$this->db->query($sql,$dataValues);  
					}
// --)) tomoac@
				}
//				$result=$this->db->query($sql,$dataValues);  
				$this->lastSavedMsqID=intval($values['msqID']);	
				$this->lastSavedqID=intval($this->db->GetOne("SELECT MAX(qID) FROM btFormTomoacQuestions WHERE bID=0 AND msqID=?", array($values['msqID']) ));
				$jsonVals['qID']=$this->lastSavedqID;
				$jsonVals['success']=1;
			}
			
			$jsonVals['qsID']=$values['qsID'];
			$jsonVals['msqID']=intval($values['msqID']);
			//create json response object
			$jsonPairs=array();
			foreach($jsonVals as $key=>$val) $jsonPairs[]=$key.':'.$val;
			if($withOutput) echo '{'.join(',',$jsonPairs).'}';
		}
		
		function getQuestionInfo($qsID,$qID){
			$questionRS=$this->db->query('SELECT * FROM btFormTomoacQuestions WHERE questionSetId='.intval($qsID).' AND qID='.intval($qID).' LIMIT 1' );
			$questionRow=$questionRS->fetchRow();
			$jsonPairs=array();
			foreach($questionRow as $key=>$val){
				if($key=='options') $key='optionVals';
				$jsonPairs[]=$key.':"'.str_replace(array("\r","\n"),'%%',addslashes($val)).'"';
			}
			echo '{'.join(',',$jsonPairs).'}';
		}

		function deleteQuestion($qsID,$msqID){
			$sql='DELETE FROM btFormTomoacQuestions WHERE questionSetId='.intval($qsID).' AND msqID='.intval($msqID).' AND bID=0';
			$this->db->query($sql,$dataValues);
		} 
		
		function loadQuestions($qsID, $bID=0, $showPending=0 ){
			$db = Loader::db();
			if( intval($bID) ) {
				$bIDClause=' AND ( bID='.intval($bID).' ';			
				if( $showPending ) 
					 $bIDClause.=' OR bID=0) ';	
				else $bIDClause.=' ) ';	
			}
			return $db->query('SELECT * FROM btFormTomoacQuestions WHERE questionSetId='.intval($qsID).' '.$bIDClause.' ORDER BY position, msqID');
		}
		
		static function getAnswerCount($qsID){
			$db = Loader::db();
			return $db->getOne( 'SELECT count(*) FROM btFormTomoacAnswerSet WHERE questionSetId='.intval($qsID) );
		}		
		
		function loadSurvey( $qsID, $showEdit=false, $bID=0, $hideQIDs=array(), $showPending=0 ){
		
			//loading questions	
			$questionsRS=$this->loadQuestions( $qsID, $bID, $showPending);
		
		
			if(!$showEdit){
				echo '<!-- showEdit='.$showEdit.' -->';					
				echo '<table class="formBlockSurveyTable">';					
				while( $questionRow=$questionsRS->fetchRow() ){	
				
					if( in_array($questionRow['qID'], $hideQIDs) ) continue;
					
					// this special view logic for the checkbox list isn't doing it for me
					/*
					if ($questionRow['inputType'] == 'checkboxlist' && strpos($questionRow['options'], '%%') === false){
						echo '<tr>
						        <td valign="top" colspan="2" class="question">
						          <div class="checkboxItem">
						            <div class="checkboxPair">'.$this->loadInputType($questionRow,$showEdit).$questionRow['question'].'</div>
						          </div>
						        </td>
						      </tr>';
					} else { */
						echo '<!-- bbbb -->';					
						$requiredSymbol=($questionRow['required'])?'&nbsp;<span class="required">*</span>':'';

						if($questionRow['layout'] == 'L') {
							echo '<tr>
						        <td valign="top" class="question">'.$questionRow['question'].''.$requiredSymbol.'</td>
						        <td valign="top">'.$this->loadInputType($questionRow,showEdit);
						}
						elseif($questionRow['layout'] == 'C') {
							echo $this->loadInputType($questionRow,showEdit);
						}
						elseif($questionRow['layout'] == 'R') {
							echo $this->loadInputType($questionRow,showEdit).'</td></tr>';
						}
						else {
							echo '<tr>
						        <td valign="top" class="question">'.$questionRow['question'].''.$requiredSymbol.'</td>
						        <td valign="top">'.$this->loadInputType($questionRow,showEdit).'</td>
						      </tr>';
							if($questionRow['inputType'] == 'mailx2') {
								$questionRow['inputType'] = 'mailx22';
								echo '<tr>
							        <td valign="top" class="question">'.$questionRow['question'].'（確認）'.$requiredSymbol.'</td>
							        <td valign="top">'.$this->loadInputType($questionRow,showEdit).'</td>
							      </tr>';
								$questionRow['inputType'] = 'mailx2';
							}
						}
					//}
				}			
				$surveyBlockInfo = $this->getMiniSurveyBlockInfoByQuestionId($qsID,intval($bID));
				
				if($surveyBlockInfo['displayCaptcha']) {
					echo '<tr><td colspan="2">';
   					echo(t('Please type the letters and numbers shown in the image.'));	
   					echo '</td></tr><tr><td>&nbsp;</td><td>';
   					
   					$captcha = Loader::helper('validation/captcha');				
   					$captcha->display();
   					print '<br/>';
   					$captcha->showInput();		
   
   					//echo isset($errors['captcha'])?'<span class="error">' . $errors['captcha'] . '</span>':'';
					echo '</td></tr>';
   				}
			
				echo '<tr><td>&nbsp;</td><td><input class="formBlockSubmitButton" name="Submit" type="submit" value="'.'確認する'.'" /></td></tr>';
				echo '</table>';
				
			} else {
			
				// 編集（タブ）の最初の項目のリストアップ
			
				echo '<div id="miniSurveyTableWrap"><div id="miniSurveyPreviewTable" class="miniSurveyTable">';					

				$pattern = '/L|C|R/';
				while( $questionRow=$questionsRS->fetchRow() ){	 
				
					if( in_array($questionRow['qID'], $hideQIDs) ) continue;

					$requiredSymbol=($questionRow['required'])?'<span class="required">*</span>':'';				

//					if(!preg_match($pattern, $questionRow['layout'])) {
				?>
					<div id="miniSurveyQuestionRow<?php  echo $questionRow['msqID']?>" class="miniSurveyQuestionRow">
						<div class="miniSurveyQuestion"><?php  echo $questionRow['question'].' '.$requiredSymbol?></div>
<!--
						<?php   /* <div class="miniSurveyResponse"><?php  echo $this->loadInputType($questionRow,$showEdit)?></div> */ ?>
-->

						<div class="miniSurveyOptions">
							<div style="float:right">
								<a href="#" onclick="miniSurvey.moveUp(this,<?php  echo $questionRow['msqID']?>);return false" class="moveUpLink"></a> 
								<a href="#" onclick="miniSurvey.moveDown(this,<?php  echo $questionRow['msqID']?>);return false" class="moveDownLink"></a>						  
							</div>						
							<a href="#" onclick="miniSurvey.reloadQuestion(<?php echo intval($questionRow['qID']) ?>);return false"><?php  echo t('edit')?></a> &nbsp;&nbsp; 
							<a href="#" onclick="miniSurvey.deleteQuestion(this,<?php echo intval($questionRow['msqID']) ?>,<?php echo intval($questionRow['qID'])?>);return false"><?php echo  t('remove')?></a>
						</div>
						<div class="miniSurveySpacer"></div>
					</div>
				<?php
//					}
				}			 
				echo '</div></div>';
			}
		}
		//
		// --------------- 利用時のHTMLの出力 ------------------
		//
		function loadInputType($questionData,$showEdit){
			$options = explode('%%',$questionData['options']);
			$msqID = intval($questionData['msqID']);
			$today = getdate();
			switch($questionData['inputType']){			
				case 'checkboxlist': 
					$html .= '<div class="checkboxList">'."\r\n";
					$html .= '<input name="Question'.$msqID.'_S'.'" type="hidden" value="" />'."\n";
					for ($i = 0; $i < count($options); $i++) {
						if(strlen(trim($options[$i]))==0) continue;
						$checked=($_REQUEST['Question'.$msqID.'_'.$i]==trim($options[$i]))?'checked':'';
						$html.= '  <div class="checkboxPair"><input name="Question'.$msqID.'_'.$i.'" type="checkbox" value="'.trim($options[$i]).'" '.$checked.' />&nbsp;'.$options[$i].'</div>'."\r\n";
					}
					$html .= '<input name="Question'.$msqID.'_E'.'" type="hidden" value="" />'."\n";
					$html .= $questionData['description'];
					$html .= '</div>';
					return $html;

				case 'select':
					if($this->frontEndMode){
						$selected=(!$_REQUEST['Question'.$msqID])?'selected':'';
						$html.= '<option value="" '.$selected.'>----</option>';					
					}
					foreach($options as $option){
						$checked=($_REQUEST['Question'.$msqID]==trim($option))?'selected':'';
						$html.= '<option '.$checked.'>'.trim($option).'</option>';
					}
					$html = '<select name="Question'.$msqID.'" >'.$html.'</select>';
					$html .= $questionData['description'];
					return $html;
								
				case 'date':
					
					$yy = $today['year'];
					$mm = $today['mon'];
					$dd = $today['mday'];
					$bb = substr($questionData['layout'],-1,1);
					$iv = $questionData['layout'];
					if($bb == 'y')
						$yy += $iv;
					else if($bb == 'm') {
						$tt = mktime(0,0,0,$mm+$iv,$dd,$yy);
						$yy = date("Y",$tt);
						$mm = date("m",$tt);
						$dd = date("d",$tt);
					}
					else {
						if($bb == 'w')
							$iv *= 7;
						$tt = mktime(0,0,0,$mm,$dd,$yy);
						$tt+= $iv * 86400;
						$yy = date("Y",$tt);
						$mm = date("m",$tt);
						$dd = date("d",$tt);
					}
					if($_REQUEST['Question'.$msqID.'a'] != '')  $yy = $_REQUEST['Question'.$msqID.'a'];
					if($_REQUEST['Question'.$msqID.'b'] != '')  $mm = $_REQUEST['Question'.$msqID.'b'];
					if($_REQUEST['Question'.$msqID.'c'] != '')  $dd = $_REQUEST['Question'.$msqID.'c'];

					$h = "\n";
					foreach($options as $option){
						$checked = ($yy==trim($option)) ? ' selected':'';
						$h .= '<option '.$checked.'>'.trim($option).'</option>';
					}
					$html .= "\n".'<select name="Question'.$msqID.'a" >'.$h."\n".'</select>年&nbsp;';

					$h = "\n";
					for($i=1; $i<=12; $i++) {
						$checked = ($mm==$i) ? ' selected':'';
						$h .= '<option value="'.trim($i).'"'.$checked.'>'.trim($i).'</option>';
					}
					$html .= "\n".'<select name="Question'.$msqID.'b" >'.$h."\n".'</select>月&nbsp;';

					$h = "\n";
					for($i=1; $i<=31; $i++) {
						$checked = ($dd==$i) ? ' selected':'';
						$h .= '<option value="'.trim($i).'"'.$checked.'>'.trim($i).'</option>';
					}
					$html .= "\n".'<select name="Question'.$msqID.'c" >'.$h."\n".'</select>日&nbsp;';
					$html .= $questionData['description'];
					return $html;
					
				case 'radios':
					foreach($options as $option){
						if(strlen(trim($option))==0) continue;
						$checked=($_REQUEST['Question'.$msqID]==trim($option))?'checked':'';
						$html.= '<div class="radioPair"><input name="Question'.$msqID.'" type="radio" value="'.trim($option).'" '.$checked.' />&nbsp;'.$option.'</div>';
					}
					$html .= $questionData['description'];
					return $html;
					
				case 'fileupload': 
					$html='<input type="file" name="Question'.$msqID.'" id="" />'; 				
					$html .= $questionData['description'];
					return $html;
					
				case 'text':
					$val=($_REQUEST['Question'.$msqID])?$_REQUEST['Question'.$msqID]:'';
					if(mb_strlen(trim($questionData['description'])) > 0)
						$html .= $questionData['description'].'<br />';
					$html .= '<textarea name="Question'.$msqID.'" cols="'.$questionData['width'].'" rows="'.$questionData['height'].'" style="ime-mode:active" style="width:95%">'.$val.'</textarea>';
					if(mb_strlen(trim($questionData['description2'])) > 0)
						$html .= '<br />'.$questionData['description2'];
					return $html;
					
// --(( tomoac@
				case 'postno':
					$val = ($_REQUEST['Question'.$msqID])?$_REQUEST['Question'.$msqID]:'';
					$ad1 = stripslashes(htmlspecialchars($_REQUEST['Question'.$msqID.'a']));
					$ad2 = stripslashes(htmlspecialchars($_REQUEST['Question'.$msqID.'b']));
					$ad3 = stripslashes(htmlspecialchars($_REQUEST['Question'.$msqID.'c']));
					$html = '<script src="http://ajaxzip3.googlecode.com/svn/trunk/ajaxzip3/ajaxzip3.js" charset="UTF-8"></script>';
					$html.= '〒';
					if($questionData['layout2'] == 1) {
						$html.= '<input name="Question'.$msqID.'" id="Question'.$msqID.'" type="text" value="'.stripslashes(htmlspecialchars($val)).'" size="8" style="ime-mode:inactive" onKeyUp="AjaxZip3.zip2addr(this,'."'','Question".$msqID."a','Question".$msqID."a');".'" />';
						$html.= $questionData['description'].'<br />';
						$html.= "\n".'<input name="Question'.$msqID.'a" id="Question'.$msqID.'a" type="text" value="'.$ad1.$ad2.$ad3.'" size="'.($questionData['width']+$questionData['width2']+$questionData['width3']).'" />';
						$h = explode('_',$questionData['checklevel']);
						$html.= '<br />'.$questionData['description2'];
					} else if($questionData['layout2'] == 2) {
						$html.= '<input name="Question'.$msqID.'" id="Question'.$msqID.'" type="text" value="'.stripslashes(htmlspecialchars($val)).'" size="8" style="ime-mode:inactive" onKeyUp="AjaxZip3.zip2addr(this,'."'','Question".$msqID."a','Question".$msqID."b');".'" />';
						$html.= $questionData['description'].'<br />';
						$html.= "\n".'<input name="Question'.$msqID.'a" id="Question'.$msqID.'a" type="text" value="'.$ad1.'" size="'.$questionData['width'].'" />';
						$h = explode('_',$questionData['checklevel']);
						if($h[0] == 1)
							$html .= '<br />';
						$html.= "\n".'<input name="Question'.$msqID.'b" id="Question'.$msqID.'b" type="text" value="'.$ad2.$ad3.'" size="'.($questionData['width2']+$questionData['width3']).'" />';
						$html.= '<br />'.$questionData['description2'];
					} else {
						$html.= '<input name="Question'.$msqID.'" id="Question'.$msqID.'" type="text" value="'.stripslashes(htmlspecialchars($val)).'" size="8" style="ime-mode:inactive" onKeyUp="AjaxZip3.zip2addr(this,'."'','Question".$msqID."a','Question".$msqID."b','Question".$msqID."c');".'" />';
						$html.= $questionData['description'].'<br />';
						$html.= "\n".'<input name="Question'.$msqID.'a" id="Question'.$msqID.'a" type="text" value="'.$ad1.'" size="'.$questionData['width'].'" />';
						$h = explode('_',$questionData['checklevel']);
						if($h[0] == 1)
							$html .= '<br />';
						$html.= "\n".'<input name="Question'.$msqID.'b" id="Question'.$msqID.'b" type="text" value="'.$ad2.'" size="'.$questionData['width2'].'" />';
						if($h[1] == 1)
							$html .= '<br />';
						$html.= "\n".'<input name="Question'.$msqID.'c" id="Question'.$msqID.'c" type="text" value="'.$ad3.'" size="'.$questionData['width3'].'" />';
						$html.= '<br />'.$questionData['description2'];
					}
					return $html;
					
				case 'mail':
					$val=($_REQUEST['Question'.$msqID])?$_REQUEST['Question'.$msqID]:'';
					$html  = '<input name="Question'.$msqID.'" id="Question'.$msqID.'" type="text" value="'.stripslashes(htmlspecialchars($val)).'" size="'.$questionData['width'].'" maxlength="'.$questionData['height'].'" style="ime-mode:inactive" />';
					$html .= $questionData['description'];
					return $html;
					
				case 'mailx2':
					$val=($_REQUEST['Question'.$msqID].'a')?$_REQUEST['Question'.$msqID.'a']:'';
					$html = '<input name="Question'.$msqID.'a" id="Question'.$msqID.'a" type="text" value="'.stripslashes(htmlspecialchars($val)).'" size="'.$questionData['width'].'" maxlength="'.$questionData['height'].'" style="ime-mode:inactive" />';
					$html .= $questionData['description'];
					return $html;
					
				case 'mailx22':
					$val=($_REQUEST['Question'.$msqID].'b')?$_REQUEST['Question'.$msqID.'b']:'';
					$html = '<input name="Question'.$msqID.'b" id="Question'.$msqID.'b" type="text" value="'.stripslashes(htmlspecialchars($val)).'" size="'.$questionData['width'].'" maxlength="'.$questionData['height'].'" style="ime-mode:inactive" />';
					return $html;
					
				case 'field':
					$val=($_REQUEST['Question'.$msqID])?$_REQUEST['Question'.$msqID]:'';
					$desc = $questionData['description'];
					if(mb_strpos($questionData['checklevel'], 'Z') === FALSE)
						$style = 'style="ime-mode:inactive"';	// 半角のみ
					else
						$style = 'style="ime-mode:active"';		// 全角あり
					return '<input name="Question'.$msqID.'" id="Question'.$msqID.'" type="text" value="'.stripslashes(htmlspecialchars($val)).'" size="'.$questionData['width'].'" maxlength="'.$questionData['height'].'" '.$style.' />&nbsp;'.$desc;
					
// --)) tomoac@
				default:
					$val=($_REQUEST['Question'.$msqID])?$_REQUEST['Question'.$msqID]:'';
					return '<input name="Question'.$msqID.'" type="text" value="'.stripslashes(htmlspecialchars($val)).'" style="ime-mode:active" />';
			}
		}
		
		function getMiniSurveyBlockInfo($bID){
			$rs=$this->db->query('SELECT * FROM btFormTomoac WHERE bID='.intval($bID).' LIMIT 1' );
			return $rs->fetchRow();
		}
		
		function getMiniSurveyBlockInfoByQuestionId($qsID,$bID=0){
			$sql='SELECT * FROM btFormTomoac WHERE questionSetId='.intval($qsID);
			if(intval($bID)>0) $sql.=' AND bID='.$bID;
			$sql.=' LIMIT 1'; 
			$rs=$this->db->query( $sql );
			return $rs->fetchRow();
		}		
		
		function reorderQuestions($qsID=0,$qIDs){
			$qIDs=explode(',',$qIDs);
			if(!is_array($qIDs)) $qIDs=array($qIDs);
			$positionNum=0;
			foreach($qIDs as $qID){
				$vals=array( $positionNum,intval($qID), intval($qsID) );
				$sql='UPDATE btFormTomoacQuestions SET position=? WHERE msqID=? AND questionSetId=?';
				$rs=$this->db->query($sql,$vals);
				$positionNum++;
			}
		}		

		function limitRange($val, $min, $max){
			$val = ($val < $min) ? $min : $val;
			$val = ($val > $max) ? $max : $val;
			return $val;
		}
				
		//Run on Form block edit
		static function questionCleanup( $qsID=0, $bID=0 ){
			$db = Loader::db();
		
			//First make sure that the bID column has been set for this questionSetId (for backwards compatibility)
			$vals=array( intval($qsID) ); 
			$questionsWithBIDs=$db->getOne('SELECT count(*) FROM btFormTomoacQuestions WHERE bID!=0 AND questionSetId=? ',$vals);
			
			//form block was just upgraded, so set the bID column
			if(!$questionsWithBIDs){ 
				$vals=array( intval($bID), intval($qsID) );  
				$rs=$db->query('UPDATE btFormTomoacQuestions SET bID=? WHERE bID=0 AND questionSetId=?',$vals);
				return; 
			} 			
			
			//Then remove all temp/placeholder questions for this questionSetId that haven't been assigned to a block
			$vals=array( intval($qsID) );  
			$rs=$db->query('DELETE FROM btFormTomoacQuestions WHERE bID=0 AND questionSetId=?',$vals);			
		}
}	
?>