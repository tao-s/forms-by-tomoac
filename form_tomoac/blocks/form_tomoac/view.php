<?php    
defined('C5_EXECUTE') or die("Access Denied.");
$survey=$controller;  
//echo $survey->surveyName.'<br>';
$miniSurvey=new MiniSurveyTomoac($b);
$miniSurvey->frontEndMode=true;

?>
<a name="<?php  echo $survey->questionSetId ?>"></a><br/>
<?php  if ($invalidIP) { ?>
<div class="ccm-error"><p><?php echo $invalidIP?></p></div>
<?php  } ?>
<?php
	if($post['state'] == 1 || $post['state'] == 2) {  // 確認画面 || 完了画面
		// 本文に挿入
		$tag = "\n".'<table class="formBlockSurveyTable">'."\n";
		$abc = array();
		$pm = 0;
		$cm = 0;
		foreach($post as $key => $value) {
//			$tag .= '<!--'.$key.'='.$value.' dm='.$dm.' pm='.$pm.' -->'."\n";
			$keyid = substr($key,-2);
			if(substr($key,-1) == 'a' || substr($key,-1) == 'b' || substr($key,-1) == 'c') {
				$tag .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
				if($pm == 1 || $dm == 1)
					$abc[] = $value;
				continue;
			}
			if(strncmp($key,'iQuestio',8) == 0) {
				if($cm == 0 && $dm == 0 && $pm == 0) {
					if(!($keyid === '_D' || $keyid === '_D' ||$keyid === '_P' ||$keyid === '_O')) {
						// without checkbox
						$tag .= "\n".'<tr>'."\n";
						$tag .= '<td valign="top" class="question">'.$value.'</td>'."\n";
					}
				} else if($pm == 2 || $dm == 2) {
					$tag .= $value.'</td>'."\n";
				}
				continue;
			}
			if(strncmp($key,'Question',8) == 0) {
				if($keyid === '_S') {
					// checkbox start
					$tag .= '<td valign="top">'."\n";
					$tag .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
					$cm = 1;
				} else if($keyid === '_E') {
					// checkbox end
					$tag .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
					$tag .= '</td>';
					$tag .= "\n".'</tr>'."\n";
					$cm = 0;
				} else if($cm == 1) {
					// checkbox item
					$tag .= $value.'<input type="hidden" name="'.$key.'" value="'.$value.'"><br />'."\n";
				} else if($pm == 2) {
					// postno item
					$tag .= '<td valign="top">〒'.substr($value,0,3).'-'.substr($value,3,4).' '.$abc[1].' '.$abc[3].' '.$abc[5];
					$tag .= '<input type="hidden" name="'.$key.'" value="'.$value.'"></td>'."\n";
					$tag .= "\n".'</tr>'."\n";
					$pm = 0;
					$abc = array();
				} else if($dm == 2) {
					// date item
					$tag .= '<td valign="top">'.$abc[1].'年'.$abc[3].'月'.$abc[5].'日';
					$tag .= '<input type="hidden" name="'.$key.'" value="'.$value.'"></td>'."\n";
					$tag .= "\n".'</tr>'."\n";
					$dm = 0;
					$abc = array();
				} else if($keyid === '_D') {
					$dm = 1;
					$tag .= "\n".'<tr>'."\n";
					$tag .= '<td valign="top" class="question">'."\n";
				} else if($keyid === '_T') {
					$dm = 2;
				} else if($keyid === '_P') {
					$pm = 1;
					$tag .= "\n".'<tr>'."\n";
					$tag .= '<td valign="top" class="question">'."\n";
				} else if($keyid === '_O') {
					$pm = 2;
				} else {
					if($dm == 0 && $pm == 0) {
						// Others
						$tag .= '<td valign="top">'.$value.'<input type="hidden" name="'.$key.'" value="'.$value.'"></td>';
						$tag .= "\n".'</tr>'."\n";
					}
				}
			}
		}
	}
	if( $post['state'] == 1) {  // 確認画面
		$tag .= '<tr>'."\n";
		$tag .= '<td colspan="2">';
		$tag .= '<input type="hidden" name="state" value="2">';
		$tag .= '<br />'."\n";
		$tag .= '<input class="formBlockSubmitButton" name="Submit" type="submit" value="送信する" />'."\n";
		$tag .= '<input class="formBlockSubmitButton" name="Submit" type="submit" value="戻って修正する" />';
		$tag .= '</td>'."\n";
		$tag .= '</tr>'."\n";
		$tag .= '</table>'."\n";

		$msg = '<div id="msg">内容を確認してください</div>'.$tag;
	}
	if( $post['state'] == 2) {  // 完了画面
		$tag .= '</table>'."\n";

		$msg = '<div id="msg">'.nl2br($survey->thankyouMsg).'</div>';
		$msg = str_replace('@@', '</div>'.$tag.'<div id="msg">', $msg);
		$msg = str_replace('<div id="msg"></div>', '', $msg);
		if($post['redirectCID'] != 0) {
			$c = Page::getByID($post['redirectCID']);
			$msg .= '<div id="msg2"><p><a href="'.BASE_URL.DIR_REL.'?cID='.$post['redirectCID'].'">'.$c->getCollectionName().'へ</a></p></div>';
		}
	}
?>
<form enctype="multipart/form-data" id="miniSurveyView<?php echo intval($bID)?>" class="miniSurveyView" method="post" action="<?php  echo $this->action('submit_form').'#'.$survey->questionSetId?>">
	<?php
		if( $post['surveySuccess'] && $post['qsid']==intval($survey->questionSetId)) {
			echo $msg;
		} elseif(strlen($formResponse)) {
			echo '<div id="msg">'.$formResponse;
			if(is_array($errors) && count($errors)) foreach($errors as $error){
				echo '<div class="error">'.$error.'</div>';
			}
			echo '</div>';
		}
	?>
	<input name="qsID" type="hidden" value="<?php echo  intval($survey->questionSetId)?>" />
	<input name="pURI" type="hidden" value="<?php echo  $pURI ?>" />
	<?php
		if($post['surveySuccess'] == '')
			$miniSurvey->loadSurvey( $survey->questionSetId, 0, intval($bID) );
		else if($post['surveySuccess'] == 1 && $post['state'] == 0)
			$miniSurvey->loadSurvey( $survey->questionSetId, 0, intval($bID) );
	?> 
</form>
