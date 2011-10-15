<?php  
defined('C5_EXECUTE') or die("Access Denied.");
$uh = Loader::helper('concrete/urls'); ?>

<ul class="ccm-dialog-tabs" id="ccm-formblock-tabs">
	<li class="<?php  echo (intval($miniSurveyInfo['bID'])==0)?'ccm-nav-active':''?>"><a href="javascript:void(0)" id="ccm-formblock-tab-add"><?php  echo t('Add')?></a></li>
	<li class="<?php  echo (intval($miniSurveyInfo['bID'])>0)?'ccm-nav-active':''?>"><a href="javascript:void(0)" id="ccm-formblock-tab-edit"><?php  echo t('Edit')?></a></li>
	<li><a href="javascript:void(0)" id="ccm-formblock-tab-preview"><?php  echo t('Preview')?></a></li>
	<li><a href="javascript:void(0)" id="ccm-formblock-tab-options"><?php  echo t('Options')?></a></li>
</ul>

<input type="hidden" name="miniSurveyServices" value="<?php  echo $uh->getBlockTypeToolsURL($bt)?>/services.php" />

<?php  /* these question ids have been deleted, or edited, and so shouldn't be duplicated for block versioning */ ?>
<input type="hidden" id="ccm-ignoreQuestionIDs" name="ignoreQuestionIDs" value="" />
<input type="hidden" id="ccm-pendingDeleteIDs" name="pendingDeleteIDs" value="" />

<!-- ------------------------------ タブ（設定）：option ------------------------------- -->

<div id="ccm-formBlockPane-options" class="ccm-formBlockPane">

	<?php  
	$c = Page::getCurrentPage();
	if(strlen($miniSurveyInfo['surveyName'])==0)
		$miniSurveyInfo['surveyName']=$c->getCollectionName();
	?>
	<strong>Options:</strong>
	
	<div class="fieldRow">
		<div class="fieldLabel"><?php  echo t('Form Name')?>:</div>
		<div class="fieldValues">
			<input id="ccmSurveyName" name="surveyName" style="width: 95%" type="text" class="ccm-input-text" value="<?php  echo $miniSurveyInfo['surveyName']?>" />
		</div>
		<div class="ccm-spacer"></div>
	</div>	
	
	<div class="fieldRow">
		<div class="fieldLabel" style=""><?php  echo '完了時のメッセージ' ?>:</div>
		<div class="fieldValues"> 
			<textarea name="thankyouMsg" cols="50" rows="2" style="width: 95%" class="ccm-input-text" ><?php  echo $this->controller->thankyouMsg ?></textarea>
"@@"の行を記述すると送信内容を表示できます
		</div>
		<div class="ccm-spacer"></div>
	</div>
	
	<div class="fieldRow" style="margin-top:16px">
		<?php  echo t('Notify me by email when people submit this form')?>: 
		<input name="notifyMeOnSubmission" type="checkbox" value="1" <?php  echo (intval($miniSurveyInfo['notifyMeOnSubmission'])>=1)?'checked="checked"':''?> onchange="miniSurvey.showRecipient(this)" onclick="miniSurvey.showRecipient(this)" />
		<div id="recipientEmailWrap" class="fieldRow" style=" <?php  echo (intval($miniSurveyInfo['notifyMeOnSubmission'])==0)?'display:none':''?>">
			<div class="fieldLabel"><?php  echo '運営者宛先' ?>:</div>
			<div class="fieldValues">
			 <input name="senderEmail" value="<?php  echo $miniSurveyInfo['senderEmail']?>" type="text" size="30" maxlength="128" />
			<div class="ccm-note"></div>
			</div>

			<div class="fieldLabel"><?php  echo '運営者宛てSubject' ?>:</div>
			<div class="fieldValues">
			 <input name="senderSub" value="<?php  echo $miniSurveyInfo['senderSub']?>" type="text" size="30" maxlength="128" />
			<div class="ccm-note"></div>
			</div>

			<div class="fieldLabel" style=""><?php  echo '運営者宛て本文' ?>:</div>
			<div class="fieldValues"> 
			<textarea name="senderMsg" cols="50" rows="2" style="width: 95%" class="ccm-input-text" ><?php  echo $this->controller->senderMsg ?></textarea>
			</div>

			<div class="fieldLabel"><?php  echo '投稿者宛てSubject' ?>:</div>
			<div class="fieldValues">
			 <input name="recipientSub" value="<?php  echo $miniSurveyInfo['recipientSub']?>" type="text" size="30" maxlength="128" />
			<div class="ccm-note"></div>
			</div>

			<div class="fieldLabel" style=""><?php  echo '投稿者宛て本文' ?>:</div>
			<div class="fieldValues"> 
			<textarea name="recipientMsg" cols="50" rows="2" style="width: 95%" class="ccm-input-text" ><?php  echo $this->controller->recipientMsg ?></textarea>
			</div>
			<div class="ccm-spacer"></div>
		</div>
	</div> 
	
	<div class="fieldRow">
		<?php echo t('Solving a <a href="%s" target="_blank">CAPTCHA</a> Required to Post?', 'http://en.wikipedia.org/wiki/Captcha')?>
        <input name="displayCaptcha" value="1" <?php  echo (intval($miniSurveyInfo['displayCaptcha'])>=1)?'checked="checked"':''?> type="checkbox" />
	</div>	
	
	<div class="fieldRow">
		<?php  echo '完了画面の「戻る」ボタンでどこに移動させますか？' ?>
		<input id="ccm-form-redirect" name="redirect" value="1" <?php  echo (intval($miniSurveyInfo['redirectCID'])>=1)?'checked="checked"':''?> type="checkbox" />
		<div id="ccm-form-redirect-page" <?php  echo (intval($miniSurveyInfo['redirectCID'])>=1)?'':'style="display:none"'; ?>>
		<?php 
		$form = Loader::helper('form/page_selector');
		if ($miniSurveyInfo['redirectCID']) {
			print $form->selectPage('redirectCID', $miniSurveyInfo['redirectCID']);
		} else {
			print $form->selectPage('redirectCID');
		}
		?>
		</div>
	</div>
	
</div> 

<input type="hidden" id="qsID" name="qsID" type="text" value="<?php  echo intval($miniSurveyInfo['questionSetId'])?>" />
<input type="hidden" id="oldQsID" name="oldQsID" type="text" value="<?php  echo intval($miniSurveyInfo['questionSetId'])?>" />
<input type="hidden" id="bID" name="bID" type="text" value="<?php  echo intval($miniSurveyInfo['bID'])?>" />
<input type="hidden" id="msqID" name="msqID" type="text" value="<?php  echo intval($msqID)?>" />

<!-- ------------------------------ タブ（新規）：add ------------------------------- -->

<div id="ccm-formBlockPane-add" class="ccm-formBlockPane" style=" <?php  echo (intval($miniSurveyInfo['bID'])==0)?'display:block':''?> ">
	<div id="newQuestionBox">
	
		<div id="addNewQuestionTitle"><strong><?php  echo t('Add a New Question')?>:</strong></div>		
		
		<div id="questionAddedMsg" class="formBlockQuestionMsg"><?php  echo t('Your question has been added. To view it click the preview tab.')?></div>
		
		<div class="fieldRow">
			<div class="fieldLabel"><?php  echo t('Question')?>:</div>
			<div class="fieldValues">
				<input id="question" name="question" type="text" style="width: 265px" class="ccm-input-text" />
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		<div class="fieldRow">
			<div class="fieldLabel"><?php  echo t('Answer Type')?>: </div>
			<div class="fieldValues">
				<input name="answerType" type="radio" value="field" /> <?php  echo t('Text Field')?> &nbsp; <br>
				<input name="answerType" type="radio" value="text" /> <?php  echo t('Text Area')?> &nbsp; <br>
				<input name="answerType" type="radio" value="radios" /> <?php  echo t('Radio Buttons ')?> &nbsp; <br>
				<input name="answerType" type="radio" value="select" /> <?php  echo t('Select Box')?> &nbsp; <br>
				<input name="answerType" type="radio" value="checkboxlist" /> <?php  echo t('Checkbox List')?> &nbsp; <br>
				<input name="answerType" type="radio" value="fileupload" /> <?php  echo t('File Upload')?> &nbsp; <br>
<!-- (( tomoac@ -->
				<input name="answerType" type="radio" value="date" /> <?php  echo '日付フィールド' ?> &nbsp; <br>
				<input name="answerType" type="radio" value="postno" /> <?php  echo '郵便番号フィールド' ?> &nbsp; <br>
				<input name="answerType" type="radio" value="mail" /> <?php  echo 'メールアドレスフィールド' ?> &nbsp; <br>
				<input name="answerType" type="radio" value="mailx2" /> <?php  echo 'メールアドレスフィールド（２重チェック）' ?> &nbsp; <br>
<!-- tomoac@ )) -->
			</div>
			<div class="spacer"></div>
		</div>
		
		<div class="fieldRow" id="answerOptionsArea"><!-- ------ 選択ボックス(radios,select) ------ -->
			<div class="fieldLabel"><?php  echo t('Answer Options')?>: </div>
			<div class="fieldValues">
				<textarea id="answerOptions" name="answerOptions" cols="50" rows="4" style="width:90%"></textarea><br />
				<?php  echo '選択させる項目を１項目１行で入力してください' ?>
				<br />説明: <input id="description" name="description" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
		<div class="fieldRow" id="answerOptionsArea2"><!-- ------ 日付(date) ------ -->
			<div class="fieldLabel"><?php  echo '選択させる年' ?>: </div>
			<div class="fieldValues">
				<textarea id="answerOptions2" name="answerOptions2" cols="50" rows="4" style="width:90%"></textarea><br />
				<?php  echo '選択させる年を１項目１行で入力してください' ?>
				<br />初期日付: <input id="layout" name="layout" type="text" value="0" size="8"/>日後
				<br />説明: <input id="description1" name="description1" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
		<div class="fieldRow" id="answerOptionsArea7"><!-- ------ 選択ボックス(checklistbox) ------ -->
			<div class="fieldLabel"><?php  echo t('Answer Options')?>: </div>
			<div class="fieldValues">
				チェック可能最大数（0は制限なし）：<input id="clevel7" name="clevel7" type="text" value="0" size="3"/> <br />
				<textarea id="answerOptions7" name="answerOptions7" cols="50" rows="4" style="width:90%"></textarea><br />
				<?php  echo '選択させる項目を１項目１行で入力してください' ?>
				<br />説明: <input id="description7" name="description7" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
		<div class="fieldRow" id="answerSettings"><!-- ------ テキストボックス(text) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				説明（上部）: <input id="description51" name="description51" type="text" value="" size="32"/><br />
				<?php  echo t('Text Area Width')  ?>: <input id="width" name="width" type="text" value="50" size="3"/> <br />
				<?php  echo t('Text Area Height') ?>: <input id="height" name="height" type="text" value="3" size="2"/>
				<br />説明（下部）: <input id="description52" name="description52" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
<!-- (( tomoac@ -->
		<div class="fieldRow" id="answerSettings2"><!-- ------ テキストボックス(field) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				フォームサイズ(size): <input id="width2" name="width2" type="text" value="50" size="3"/> <br />
				最大入力文字数(maxlength): <input id="height2" name="height2" type="text" value="100" size="3"/> <br />
				許可文字: <input type="checkbox" id="clevel21" name="clevel21" value="HN" checked>半角数字
						  <input type="checkbox" id="clevel22" name="clevel22" value="HO" checked>半角英字
						  <input type="checkbox" id="clevel23" name="clevel23" value="HS" checked>半角記号<br />
						  <input type="checkbox" id="clevel24" name="clevel24" value="ZK" checked>全角漢字 
						  <input type="checkbox" id="clevel25" name="clevel25" value="ZH" checked>全角ひらかな
						  <input type="checkbox" id="clevel26" name="clevel26" value="ZT" checked>全角カタカナ <br />
				説明: <input id="description2" name="description2" type="text" value="" size="32"/> <br />
	 			テキストフィールドの右側に表示されます。
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
		<div class="fieldRow" id="answerSettings3"><!-- ------ メールフォーム(mail,mailx2) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				<?php  echo "フォームサイズ" ?>: <input id="width3" name="width3" type="text" value="32" size="3"/> <br />
				<?php  echo "最大入力文字数" ?>: <input id="height3" name="height3" type="text" value="48" size="3"/>
				<br />説明: <input id="description3" name="description3" type="text" value="" size="32"/>
				<br /><input id="mcheck2" name="mcheck2" type="checkbox" value="1" <?php echo (intval($miniSurveyInfo['notifyMeOnSubmission'])==0)?'disabled':''?> />
				<!--
				<br /><input id="mcheck2" name="mcheck2" type="checkbox" value="1" />
				-->
				<?php  echo "このメールアドレスに確認メールを送信しますか" ?> 
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
		<div class="fieldRow" id="answerSettings4"><!-- ------ 郵便番号＆住所(postno) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				郵便番号フィールド； <br />
				<input type="hidden" id="layout41" name="layout41" value="1">
<!--
				<input type="radio" id="layout41" name="layout41" value="1" checked>７桁　
				<input type="radio" id="layout41" name="layout41" value="2">３桁＋４桁に分割<br />
-->
				説明: <input id="description41" name="description41" type="text" value="" size="32"/> <br />
	 			　　※郵便番号フィールドの右側に表示されます。<br />
				住所フィールド分割；<br />
				<input type="radio" id="layout42" name="layout42" value="1">住所フィールド分割なし<br />
				<input type="radio" id="layout42" name="layout42" value="2">２分割（都道府県・以降の住所）<br />
				<input type="radio" id="layout42" name="layout42" value="3" checked>３分割（都道府県・市区町村・以降の住所）<br />
				住所フィールドサイズ（※分割しない場合加算）；<br />
				都道府県(size)：<input id="width41" name="width41" type="text" value="8" size="2"/> <input type="checkbox" id="clevel41" name="clevel41" value="1">改行する<br />
				市区町村(size)：<input id="width42" name="width42" type="text" value="16" size="2"/> <input type="checkbox" id="clevel42" name="clevel42" value="1" checked>改行する<br />
				以降の住所(size)：<input id="width43" name="width43" type="text" value="40" size="2"/><br />
				説明: <input id="description42" name="description42" type="text" value="" size="32"/> <br />
	 			　　※住所フィールドの下側に表示されます。
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
		<div class="fieldRow" id="answerSettings6"><!-- ------ ファイルアップロード(filetext) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				説明: <input id="description6" name="description6" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
<!-- tomoac@ )) -->
		<div class="fieldRow" id="questionRequired">
			<div class="fieldLabel">&nbsp;</div>
			<div class="fieldValues"> 
				<input id="required" name="required" type="checkbox" value="1" />
				<?php  echo t('This question is required.')?> 
			</div>
			<div class="ccm-spacer"></div>
		</div>		
		
		
		<div class="fieldRow" >
			<div class="fieldLabel">&nbsp; </div>
			<div class="fieldValues">
				<input type="hidden" id="position" name="position" type="text" value="1000" />
				<input id="refreshButton" name="refresh" type="button" value="Refresh" style="display:none" /> 
				<input id="addQuestion" name="add" type="button" value="<?php  echo t('Add Question')?> &raquo;" />
			</div>
		</div>
		
		
		<div class="ccm-spacer"></div>
		
	</div> 
</div> 
	
<!-- ------------------------------ タブ（編集）：edit ------------------------------- -->

<div id="ccm-formBlockPane-edit" class="ccm-formBlockPane" style=" <?php  echo (intval($miniSurveyInfo['bID'])>0)?'display:block':''?> ">
	
	<div id="questionEditedMsg" class="formBlockQuestionMsg"><?php  echo t('Your question has been edited.')?></div>
	
	<div id="editQuestionForm" style="display:none">
		<div id="editQuestionTitle" ><strong><?php  echo t('Edit Question')?>:</strong></div>
		
		<div class="fieldRow">
			<div class="fieldLabel"><?php  echo t('Question')?>:</div>
			<div class="fieldValues">
				<input id="questionEdit" name="question" type="text" style="width: 265px" class="ccm-input-text" />
			</div>
			<div class="ccm-spacer"></div>
		</div>	
		
		<div class="fieldRow">
			<div class="fieldLabel"><?php  echo t('Answer Type')?>: </div>
			<div class="fieldValues">
				<input name="answerTypeEdit" type="radio" value="field" /> <?php  echo t('Text Field')?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="text" /> <?php  echo t('Text Area')?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="radios" /> <?php  echo t('Radio Buttons')?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="select" /> <?php  echo t('Select Box')?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="checkboxlist" /> <?php  echo t('Checkbox List')?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="fileupload" /> <?php  echo t('File Upload')?> &nbsp; <br>
<!-- (( tomoac@ -->
				<input name="answerTypeEdit" type="radio" value="date" /> <?php  echo '日付フィールド' ?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="text2" /> <?php  echo t('Text Field')?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="postno" /> <?php  echo '郵便番号フィールド（３分割住所付き）' ?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="mail" /> <?php  echo 'メールアドレスフィールド' ?> &nbsp; <br>
				<input name="answerTypeEdit" type="radio" value="mailx2" /> <?php  echo 'メールアドレスフィールド（２重チェック）' ?> &nbsp; <br>
<!-- tomoac@ )) -->
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		<div class="fieldRow" id="answerOptionsAreaEdit"><!-- ------ 選択リスト(radio,select) ------ -->
			<div class="fieldLabel"><?php  echo t('Answer Options')?>: </div>
			<div class="fieldValues">
				<textarea id="answerOptionsEdit" name="answerOptionsEdit" cols="50" rows="4" style="width:90%"></textarea><br />
				<?php  echo '選択させる項目を１項目１行で入力してください' ?>
				<br />説明: <input id="descriptionEdit" name="descriptionEdit" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>
			
<!-- tomoac@ (( -->
		<div class="fieldRow" id="answerOptionsArea2Edit"><!-- ------ 日付ボックス(date) ------ -->
			<div class="fieldLabel"><?php  echo '選択させる年' ?>: </div>
			<div class="fieldValues">
				<textarea id="answerOptions2Edit" name="answerOptions2Edit" cols="50" rows="4" style="width:90%"></textarea><br />
				<?php  echo '選択させる年を１項目１行で入力してください' ?>
				<br />初期日付: <input id="layoutEdit" name="layoutEdit" type="text" value="0" size="8"/>日後
				<br />説明: <input id="description1Edit" name="description1Edit" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>

		<div class="fieldRow" id="answerOptionsArea7Edit"><!-- ------ 選択ボックス(checklistbox) ------ -->
			<div class="fieldLabel"><?php  echo t('Answer Options')?>: </div>
			<div class="fieldValues">
				チェック可能最大数（0は制限なし）：<input id="clevel7Edit" name="clevel7Edit" type="text" value="0" size="3"/> <br />
				<textarea id="answerOptions7Edit" name="answerOptions7Edit" cols="50" rows="4" style="width:90%"></textarea><br />
				<?php  echo '選択させる項目を１項目１行で入力してください' ?>
				<br />説明: <input id="description7Edit" name="description7Edit" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
			
<!-- tomoac@ )) -->
		<div class="fieldRow" id="answerSettingsEdit"><!-- ------ テキストボックス(text) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				説明（上部）: <input id="description51Edit" name="description51Edit" type="text" value="" size="32"/><br />
				<?php  echo t('Text Area Width')?>: <input id="widthEdit" name="width" type="text" value="50" size="3"/> <br />
				<?php  echo t('Text Area Height')?>: <input id="heightEdit" name="height" type="text" value="100" size="2"/>
				<br />説明（下部）: <input id="description52Edit" name="description52Edit" type="text" value="" size="32"/><br />
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
<!-- tomoac@ (( -->
		<div class="fieldRow" id="answerSettings2Edit"><!-- ------ テキストフィールド(field) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				フォームサイズ(size): <input id="width2Edit" name="width2Edit" type="text" value="" size="3"/> <br />
				最大入力文字数(maxlength): <input id="height2Edit" name="height2Edit" type="text" value="" size="3"/> <br />
				許可文字: <input type="checkbox" id="clevel21Edit" name="clevel21Edit" value="HN">半角数字
						  <input type="checkbox" id="clevel22Edit" name="clevel22Edit" value="HO">半角英字
						  <input type="checkbox" id="clevel23Edit" name="clevel23Edit" value="HS">半角記号<br />
						  <input type="checkbox" id="clevel24Edit" name="clevel24Edit" value="ZK">全角漢字 
						  <input type="checkbox" id="clevel25Edit" name="clevel25Edit" value="ZH">全角ひらかな
						  <input type="checkbox" id="clevel26Edit" name="clevel26Edit" value="ZT">全角カタカナ <br />
				説明: <input id="description2Edit" name="description2Edit" type="text" value="" size="32"/> <br />
				<input id="layoutEdit" name="layoutEdit" type="hidden" value="" /> <br />
	 			テキストフィールドの右側に表示されます。
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		<div class="fieldRow" id="answerSettings3Edit"><!-- ------ メールボックス(mail,mailx2) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				<?php  echo 'サイズ' ?>: <input id="width3Edit" name="width3Edit" type="text" value="32" size="3"/> <br />
				<?php  echo '最大数' ?>: <input id="height3Edit" name="height3Edit" type="text" value="48" size="3"/>
				<br />説明: <input id="description3Edit" name="description3Edit" type="text" value="" size="32"/>
				<br /><input id="mcheck2Edit" name="mcheck2Edit" type="checkbox" value="" <?php echo (intval($miniSurveyInfo['notifyMeOnSubmission'])==0)?'disabled':''?> />
				<!--
				<br /><input id="mcheck2Edit" name="mcheck2Edit" type="checkbox" value="" />
				-->
				<?php  echo "このメールアドレスに確認メールを送信しますか" ?> 
			</div>
			<div class="ccm-spacer"></div>
		</div>

		<div class="fieldRow" id="answerSettings4Edit"><!-- ------ 郵便番号＆住所(postno) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				郵便番号フィールド； <br />
				<input type="hidden" id="layout41Edit" name="layout41Edit" value="1">
<!--
				<input type="radio" id="layout41Edit" name="layout41Edit" value="1">７桁　
				<input type="radio" id="layout41Edit" name="layout41Edit" value="2">３桁＋４桁に分割<br />
-->
				説明: <input id="description41Edit" name="description41Edit" type="text" value="" size="32"/> <br />
	 			　　※郵便番号フィールドの右側に表示されます。<br />
				住所フィールド分割；<br />
				<input type="radio" id="layout42Edit" name="layout42Edit" value="1">住所フィールド分割なし<br />
				<input type="radio" id="layout42Edit" name="layout42Edit" value="2">２分割（都道府県・以降の住所）<br />
				<input type="radio" id="layout42Edit" name="layout42Edit" value="3">３分割（都道府県・市区町村・以降の住所）<br />
				住所フィールドサイズ（※分割しない場合加算）；<br />
				都道府県(size)：<input id="width41Edit" name="width41Edit" type="text" value="8" size="2"/> <input type="checkbox" id="clevel41Edit" name="clevel41Edit" value="1">改行する<br />
				市区町村(size)：<input id="width42Edit" name="width42Edit" type="text" value="16" size="2"/> <input type="checkbox" id="clevel42Edit" name="clevel42Edit" value="1">改行する<br />
				以降の住所(size)：<input id="width43Edit" name="width43Edit" type="text" value="40" size="2"/><br />
				説明: <input id="description42Edit" name="description42Edit" type="text" value="" size="32"/> <br />
	 			　　※住所フィールドの下側に表示されます。
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
		<div class="fieldRow" id="answerSettings6Edit"><!-- ------ ファイルアップロード(filetext) ------ -->
			<div class="fieldLabel"><?php  echo t('Settings')?>: </div>
			<div class="fieldValues">
				説明: <input id="description6Edit" name="description6Edit" type="text" value="" size="32"/>
			</div>
			<div class="ccm-spacer"></div>
		</div>
		
		
		
<!-- tomoac@ )) -->
		<div class="fieldRow" id="questionRequired">
			<div class="fieldLabel">&nbsp;</div>
			<div class="fieldValues"> 
				<input id="requiredEdit" name="required" type="checkbox" value="1" />
				<?php  echo t('This question is required.')?> 
			</div>
			<div class="ccm-spacer"></div>
		</div>		
		
		<input type="hidden" id="positionEdit" name="position" type="text" value="1000" />
		<input id="cancelEditQuestion" name="cancelEdit" type="button" value="Cancel"/>
		<input id="editQuestion" name="edit" type="button" value="Save Changes &raquo;"/>
	</div>

	<div id="miniSurvey">
		<div style="margin-bottom:16px"><strong><?php  echo t('Edit')?>:</strong>	</div>
		<div id="miniSurveyWrap"></div>
	</div>
</div>	
	
<!-- ------------------------------ タブ（プレビュー）：preview ------------------------------- -->

<div id="ccm-formBlockPane-preview" class="ccm-formBlockPane">
	<div id="miniSurvey">
		<div style="margin-bottom:16px"><strong><?php  echo t('Preview')?>:</strong></div>	
		<div id="miniSurveyPreviewWrap"></div>
	</div>
</div>

<script>
//safari was loading the auto.js too late. This ensures it's initialized
function initFormBlockWhenReady(){
	if(miniSurvey && typeof(miniSurvey.init)=='function'){
		miniSurvey.cID=parseInt(<?php  echo $c->getCollectionID()?>);
		miniSurvey.arHandle="<?php  echo $a->getAreaHandle()?>";
		miniSurvey.bID=thisbID;
		miniSurvey.btID=thisbtID;
		miniSurvey.qsID=parseInt(<?php  echo $miniSurveyInfo['questionSetId']?>);	
		miniSurvey.init();
		miniSurvey.refreshSurvey();
	}else setTimeout('initFormBlockWhenReady()',100);
}
initFormBlockWhenReady();
</script>
