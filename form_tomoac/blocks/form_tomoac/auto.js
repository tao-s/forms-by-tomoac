var miniSurvey ={
	bid:0,
	serviceURL: $("input[name=miniSurveyServices]").val() + '?block=form&',
	init: function(){ 
			this.tabSetup();
			this.answerTypes=document.forms['ccm-block-form'].answerType;
			this.answerTypesEdit=document.forms['ccm-block-form'].answerTypeEdit; 

			for(var i=0;i<this.answerTypes.length;i++){
				this.answerTypes[i].onclick=function(){
					miniSurvey.optionsCheck(this);
					miniSurvey.settingsCheck(this);
				}
				this.answerTypes[i].onchange=function(){
					miniSurvey.optionsCheck(this);
					miniSurvey.settingsCheck(this);
				}
			} 
			for(var i=0;i<this.answerTypesEdit.length;i++){
				this.answerTypesEdit[i].onclick=function(){
					miniSurvey.optionsCheck(this,'Edit');
					miniSurvey.settingsCheck(this,'Edit');
				}
				this.answerTypesEdit[i].onchange=function(){
					miniSurvey.optionsCheck(this,'Edit');
					miniSurvey.settingsCheck(this,'Edit');
				}
			} 			
			$('#refreshButton').click( function(){ miniSurvey.refreshSurvey() } );
			$('#addQuestion').click(   function(){ miniSurvey.addQuestion()   } );
			$('#editQuestion').click(  function(){ miniSurvey.addQuestion('Edit')   } );
			$('#cancelEditQuestion').click(   function(){ $('#editQuestionForm').css('display','none') } );			
			this.serviceURL+='cID='+this.cID+'&arHandle='+this.arHandle+'&bID='+this.bID+'&btID='+this.btID+'&';
			miniSurvey.refreshSurvey();
		},	
	tabSetup: function(){
		$('ul#ccm-formblock-tabs li a').each( function(num,el){ 
			el.onclick=function(){
				var pane=this.id.replace('ccm-formblock-tab-','');
				miniSurvey.showPane(pane);
			}
		});		
	},
	showPane:function(pane){
		$('ul#ccm-formblock-tabs li').each(function(num,el){ $(el).removeClass('ccm-nav-active') });
		$(document.getElementById('ccm-formblock-tab-'+pane).parentNode).addClass('ccm-nav-active');
		$('div.ccm-formBlockPane').each(function(num,el){ el.style.display='none'; });
		$('#ccm-formBlockPane-'+pane).css('display','block');
	},
	refreshSurvey : function(){
			$.ajax({ 
					url: this.serviceURL+'mode=refreshSurvey&qsID='+parseInt(this.qsID)+'&hide='+miniSurvey.hideQuestions.join(','),
					success: function(msg){ $('#miniSurveyPreviewWrap').html(msg); }
				});
			$.ajax({ 
					url: this.serviceURL+'mode=refreshSurvey&qsID='+parseInt(this.qsID)+'&showEdit=1&hide='+miniSurvey.hideQuestions.join(','),
					success: function(msg){	$('#miniSurveyWrap').html(msg); }
				});			
		},
// --(( tomoac@
	// オプション入力領域の表示
	optionsCheck : function(radioButton,mode){
			if(mode!='Edit') mode='';
			if( radioButton.value=='select' || radioButton.value=='radios') {
				 $('#answerOptionsArea'+mode).css('display','block');
				 $('#answerOptionsArea2'+mode).css('display','none');
				 $('#answerOptionsArea7'+mode).css('display','none');
			} else if( radioButton.value=='date' ) {
				 $('#answerOptionsArea'+mode).css('display','none');
				 $('#answerOptionsArea2'+mode).css('display','block');
				 $('#answerOptionsArea7'+mode).css('display','none');
			} else if( radioButton.value=='checkboxlist') {
				 $('#answerOptionsArea'+mode).css('display','none');
				 $('#answerOptionsArea2'+mode).css('display','none');
				 $('#answerOptionsArea7'+mode).css('display','block');
			} else {
				$('#answerOptionsArea'+mode).css('display','none');			
				$('#answerOptionsArea2'+mode).css('display','none');
				$('#answerOptionsArea7'+mode).css('display','none');
			}
		},
// --(( tomoac@
	// オプション入力領域の表示
	settingsCheck : function(radioButton,mode) {
			if(mode!='Edit') mode='';
			if( radioButton.value=='text' ) {
				$('#answerSettings'+mode).css('display','block');
				$('#answerSettings2'+mode).css('display','none');
				$('#answerSettings3'+mode).css('display','none');
				$('#answerSettings4'+mode).css('display','none');
				$('#answerSettings5'+mode).css('display','none');
				$('#answerSettings6'+mode).css('display','none');
			} else if( radioButton.value=='field' ) {
				$('#answerSettings'+mode).css('display','none');
				$('#answerSettings2'+mode).css('display','block');
				$('#answerSettings3'+mode).css('display','none');
				$('#answerSettings4'+mode).css('display','none');
				$('#answerSettings5'+mode).css('display','none');
				$('#answerSettings6'+mode).css('display','none');
			} else if( radioButton.value=='mail' || radioButton.value=='mailx2' ) {
				$('#answerSettings'+mode).css('display','none');
				$('#answerSettings2'+mode).css('display','none');
				$('#answerSettings3'+mode).css('display','block');
				$('#answerSettings4'+mode).css('display','none');
				$('#answerSettings5'+mode).css('display','none');
				$('#answerSettings6'+mode).css('display','none');
			} else if( radioButton.value=='postno' ) {
				$('#answerSettings'+mode).css('display','none');
				$('#answerSettings2'+mode).css('display','none');
				$('#answerSettings3'+mode).css('display','none');
				$('#answerSettings4'+mode).css('display','block');
				$('#answerSettings5'+mode).css('display','none');
				$('#answerSettings6'+mode).css('display','none');
			} else if( radioButton.value=='fileupload' ) {
				$('#answerSettings'+mode).css('display','none');
				$('#answerSettings2'+mode).css('display','none');
				$('#answerSettings3'+mode).css('display','none');
				$('#answerSettings4'+mode).css('display','none');
				$('#answerSettings5'+mode).css('display','none');
				$('#answerSettings6'+mode).css('display','block');
			} else {
				$('#answerSettings'+mode).css('display','none');
				$('#answerSettings2'+mode).css('display','none');
				$('#answerSettings3'+mode).css('display','none');
				$('#answerSettings4'+mode).css('display','none');
				$('#answerSettings5'+mode).css('display','none');
				$('#answerSettings6'+mode).css('display','none');
			}
		},
// --)) tomoac@
	// 「新規追加」ボタンでの登録処理
	addQuestion : function(mode){ 
			var msqID=0;
			if(mode!='Edit') mode='';
			else msqID=parseInt($('#msqID').val())

// --(( tomoac@
			var form=document.getElementById('ccm-block-form'); 
			var opts=form['answerType'+mode];
			var answerType='';
			for(var i=0;i<opts.length;i++){
				if(opts[i].checked){
					answerType=opts[i].value;
					break;
				}
			} 
			var postStr='question='+encodeURIComponent($('#question'+mode).val());
			
			if(answerType=='date') {
				postStr+='&options='+encodeURIComponent($('#answerOptions2'+mode).val());
			} else if(answerType=='checkboxlist') {
				postStr+='&options='+encodeURIComponent($('#answerOptions7'+mode).val());
			} else {
				postStr+='&options='+encodeURIComponent($('#answerOptions'+mode).val());
			}
			if(answerType=='text') {
				postStr+='&width='+escape($('#width'+mode).val());
				postStr+='&height='+escape($('#height'+mode).val());
			} else if(answerType=='field') {
				postStr+='&width='+escape($('#width2'+mode).val());
				postStr+='&height='+escape($('#height2'+mode).val());
			} else if(answerType=='mail' || answerType=='mailx2') {
				postStr+='&width='+escape($('#width3'+mode).val());
				postStr+='&height='+escape($('#height3'+mode).val());
			} else if(answerType=='postno') {
				postStr+='&width='+escape($('#width41'+mode).val());
				postStr+='&height=0';
				postStr+='&width2='+escape($('#width42'+mode).val());
				postStr+='&width3='+escape($('#width43'+mode).val());
			} else {
				postStr+='&width=50';
				postStr+='&height=3';
			}
			if(answerType=='postno') {
				if(mode == '') {
					postStr+='&layout='+escape($("input[name='layout41']:checked").val());
					postStr+='&layout2='+escape($("input[name='layout42']:checked").val());
				} else {
					postStr+='&layout='+escape($("input[name='layout41Edit']:checked").val());
					postStr+='&layout2='+escape($("input[name='layout42Edit']:checked").val());
				}
			} else {
				postStr+='&layout='+encodeURIComponent($('#layout'+mode).val());
			}

			if(answerType=='radios' || answerType=='select') {
				postStr+='&description='+encodeURIComponent($('#description'+mode).val());
			} else if(answerType=='date') {
				postStr+='&description='+encodeURIComponent($('#description1'+mode).val());
			} else if(answerType=='field') {
				postStr+='&description='+encodeURIComponent($('#description2'+mode).val());
			} else if(answerType=='mail' || answerType=='mailx2') {
				postStr+='&description='+encodeURIComponent($('#description3'+mode).val());
			} else if(answerType=='postno') {
				postStr+='&description='+encodeURIComponent($('#description41'+mode).val());
				postStr+='&description2='+encodeURIComponent($('#description42'+mode).val());
			} else if(answerType=='text') {
				postStr+='&description='+encodeURIComponent($('#description51'+mode).val());
				postStr+='&description2='+encodeURIComponent($('#description52'+mode).val());
			} else if(answerType=='fileupload') {
				postStr+='&description='+encodeURIComponent($('#description6'+mode).val());
			} else if(answerType=='checkboxlist') {
				postStr+='&description='+encodeURIComponent($('#description7'+mode).val());
			}

			var mck=($('#mcheck2'+mode).get(0).checked)?1:0;
			postStr+='&mcheck='+mck;

			if(answerType=='postno') {
				postStr+='&clevel=';
					if($('#clevel41'+mode).is(":checked")==true) postStr+=escape($('#clevel41'+mode).val());
					if($('#clevel42'+mode).is(":checked")==true) postStr+='_'+escape($('#clevel42'+mode).val());
			} else if(answerType=='checkboxlist') {
				postStr+='&clevel='+encodeURIComponent($('#clevel7'+mode).val());
			} else {
				postStr+='&clevel=';
					if($('#clevel21'+mode).is(":checked")==true) postStr+=escape($('#clevel21'+mode).val())+'_';
					if($('#clevel22'+mode).is(":checked")==true) postStr+=escape($('#clevel22'+mode).val())+'_';
					if($('#clevel23'+mode).is(":checked")==true) postStr+=escape($('#clevel23'+mode).val())+'_';
					if($('#clevel24'+mode).is(":checked")==true) postStr+=escape($('#clevel24'+mode).val())+'_';
					if($('#clevel25'+mode).is(":checked")==true) postStr+=escape($('#clevel25'+mode).val())+'_';
					if($('#clevel26'+mode).is(":checked")==true) postStr+=escape($('#clevel26'+mode).val())+'_';
			}
			var req=($('#required'+mode).get(0).checked)?1:0;
			postStr+='&required='+req;
			postStr+='&position='+escape($('#position'+mode).val());
// --)) tomoac@
			postStr+='&inputType='+answerType;//$('input[name=answerType'+mode+']:checked').val()
			postStr+='&msqID='+msqID+'&qsID='+parseInt(this.qsID);			
			$.ajax({ 
					type: "POST",
					data: postStr,
					url: this.serviceURL+'mode=addQuestion&qsID='+parseInt(this.qsID),
					success: function(msg){ 
						eval('var jsonObj='+msg);
						if(!jsonObj){
						   alert(ccm_t('ajax-error'));
						}else if(jsonObj.noRequired){
						   alert(ccm_t('complete-required'));
						}else{
						   if(jsonObj.mode=='Edit'){
							   $('#questionEditedMsg').slideDown('slow');
							   setTimeout("$('#questionEditedMsg').slideUp('slow');",5000);
							   if(jsonObj.hideQID){
								   miniSurvey.hideQuestions.push( miniSurvey.edit_qID ); //jsonObj.hideQID); 
								   miniSurvey.edit_qID=0;
							   }
						   }else{
							   $('#questionAddedMsg').slideDown('slow');
							   setTimeout("$('#questionAddedMsg').slideUp('slow');",5000);
							   //miniSurvey.saveOrder();
						   }
						   $('#editQuestionForm').css('display','none');
						   miniSurvey.qsID=jsonObj.qsID;
						   miniSurvey.ignoreQuestionId(jsonObj.msqID);
						   $('#qsID').val(jsonObj.qsID);
						   miniSurvey.resetQuestion();
						   miniSurvey.refreshSurvey();						  
						   //miniSurvey.showPane('preview');
						}
					}
				});
	},
	//prevent duplication of these questions, for block question versioning
	ignoreQuestionId:function(msqID){
		var msqID, ignoreEl=$('#ccm-ignoreQuestionIDs');
		if(ignoreEl.val()) msqIDs=ignoreEl.val().split(',');
		else msqIDs=[];
		msqIDs.push( parseInt(msqID) );
		ignoreEl.val( msqIDs.join(',') );
	},
	reloadQuestion : function(qID){
			
			$.ajax({ 
				url: this.serviceURL+"mode=getQuestion&qsID="+parseInt(this.qsID)+'&qID='+parseInt(qID),
				success: function(msg){				
						eval('var jsonObj='+msg);
						$('#editQuestionForm').css('display','block')
						$('#questionEdit').val(jsonObj.question);
						$('#answerOptionsEdit').val(jsonObj.optionVals.replace(/%%/g,"\r\n") );
						$('#answerOptions2Edit').val(jsonObj.optionVals.replace(/%%/g,"\r\n") ); // tomoac
						$('#answerOptions7Edit').val(jsonObj.optionVals.replace(/%%/g,"\r\n") ); // tomoac
						$('#widthEdit').val(jsonObj.width);
						$('#heightEdit').val(jsonObj.height); 
						$('#width2Edit').val(jsonObj.width);
						$('#height2Edit').val(jsonObj.height); 
						$('#width3Edit').val(jsonObj.width);
						$('#height3Edit').val(jsonObj.height); 
						$('#width41Edit').val(jsonObj.width);
						$('#height43Edit').val(jsonObj.height); 
						$('#width42Edit').val(jsonObj.width2);
						$('#width43Edit').val(jsonObj.width3);
// --(( tomoac@
						$('#descriptionEdit').val(jsonObj.description);
						$('#description1Edit').val(jsonObj.description);
						$('#description2Edit').val(jsonObj.description);
						$('#description3Edit').val(jsonObj.description);
						$('#description41Edit').val(jsonObj.description);
						$('#description42Edit').val(jsonObj.description2);
						$('#description51Edit').val(jsonObj.description);
						$('#description52Edit').val(jsonObj.description2);
						$('#description6Edit').val(jsonObj.description2);
						$('#description7Edit').val(jsonObj.description);

						$('#layoutEdit').val(jsonObj.layout);

						if(parseInt(jsonObj.layout) == 1) {
							$("input[name='layout41Edit']").val(['1']);
						} else {
							$("input[name='layout41Edit']").val(['2']);
						}
						if(parseInt(jsonObj.layout2) == 1) {
							$("input[name='layout42Edit']").val(['1']);
						} else if(parseInt(jsonObj.layout2) == 2) {
							$("input[name='layout42Edit']").val(['2']);
						} else {
							$("input[name='layout42Edit']").val(['3']);
						}

						$('#mcheck2Edit').val(jsonObj.layout); // checkbox: メール送信
						if( parseInt(jsonObj.layout)==1 ) 
							 $('#mcheck2Edit').get(0).checked=true;
						else $('#mcheck2Edit').get(0).checked=false;

						$('#clevel21Edit').attr('checked',false); // 一旦リセット
						$('#clevel22Edit').attr('checked',false);
						$('#clevel23Edit').attr('checked',false);
						$('#clevel24Edit').attr('checked',false);
						$('#clevel25Edit').attr('checked',false);
						$('#clevel26Edit').attr('checked',false);
						if(jsonObj.checklevel.indexOf("HN",0) >= 0) $('#clevel21Edit').attr('checked',true); // checkon
						if(jsonObj.checklevel.indexOf("HO",0) >= 0) $('#clevel22Edit').attr('checked',true);
						if(jsonObj.checklevel.indexOf("HS",0) >= 0) $('#clevel23Edit').attr('checked',true);
						if(jsonObj.checklevel.indexOf("ZK",0) >= 0) $('#clevel24Edit').attr('checked',true);
						if(jsonObj.checklevel.indexOf("ZH",0) >= 0) $('#clevel25Edit').attr('checked',true);
						if(jsonObj.checklevel.indexOf("ZT",0) >= 0) $('#clevel26Edit').attr('checked',true);

						$('#clevel41Edit').attr('checked',false); // 一旦リセット
						$('#clevel42Edit').attr('checked',false);
						w = jsonObj.checklevel.split('_');
						if(w[0] == 1) $('#clevel41Edit').attr('checked',true);
						if(w[1] == 1) $('#clevel42Edit').attr('checked',true);

						$('#clevel7Edit').val(jsonObj.checklevel);  // checkboxlist
// --)) tomoac@
						$('#positionEdit').val(jsonObj.position); 
						if( parseInt(jsonObj.required)==1 ) 
							 $('#requiredEdit').get(0).checked=true;
						else $('#requiredEdit').get(0).checked=false;
						$('#msqID').val(jsonObj.msqID);    
						for(var i=0;i<miniSurvey.answerTypesEdit.length;i++){							
							if(miniSurvey.answerTypesEdit[i].value==jsonObj.inputType){
								miniSurvey.answerTypesEdit[i].checked=true; 
								miniSurvey.optionsCheck(miniSurvey.answerTypesEdit[i],'Edit');
								miniSurvey.settingsCheck(miniSurvey.answerTypesEdit[i],'Edit');
							}
						}
						if(parseInt(jsonObj.bID)>0) 
							miniSurvey.edit_qID = parseInt(qID) ;
						scroll(0,165);
					}
			});
	},	
	//prevent duplication of these questions, for block question versioning
	pendingDeleteQuestionId:function(msqID){
		var msqID, el=$('#ccm-pendingDeleteIDs');
		if(el.val()) msqIDs=ignoreEl.val().split(',');
		else msqIDs=[];
		msqIDs.push( parseInt(msqID) );
		el.val( msqIDs.join(',') );
	},	
	hideQuestions : [], 
	deleteQuestion : function(el,msqID,qID){
			if(confirm(ccm_t('delete-question'))) { 
				$.ajax({ 
					url: this.serviceURL+"mode=delQuestion&qsID="+parseInt(this.qsID)+'&msqID='+parseInt(msqID),
					success: function(msg){	miniSurvey.resetQuestion(); miniSurvey.refreshSurvey();  }			
				});
				
				miniSurvey.ignoreQuestionId(msqID);
				miniSurvey.hideQuestions.push(qID); 
				miniSurvey.pendingDeleteQuestionId(msqID)
			}
	},
	resetQuestion : function(){
			$('#question').val('');
			$('#answerOptions').val('');
			$('#answerOptions2').val('');
			$('#answerOptions7').val('');
			$('#width').val('50');
			$('#height').val('3');
			$('#width2').val('50');
			$('#height2').val('100');
			$('#width3').val('32');
			$('#height3').val('48');
			$('#width41').val('8');
			$('#height41').val('0');
			$('#width42').val('16');
			$('#height42').val('0');
			$('#width43').val('40');
			$('#height43').val('0');
			$('#msqID').val('');
			for(var i=0;i<this.answerTypes.length;i++){
				this.answerTypes[i].checked=false;
			}
			$('#answerOptionsArea').hide();
			$('#answerOptionsArea2').hide();
			$('#answerOptionsArea7').hide();
			$('#answerSettings').hide();
			$('#answerSettings2').hide();
			$('#answerSettings3').hide();
			$('#answerSettings4').hide();
			$('#answerSettings5').hide();
			$('#answerSettings6').hide();
			$('#required').get(0).checked=0;
	},
	
	validate:function(){
			var failed=0;
			
			var n=$('#ccmSurveyName');
			if( !n || parseInt(n.val().length)==0 ){
				alert(ccm_t('form-name'));
				this.showPane('options');
				n.focus();
				failed=1;
			}
			
			var Qs=$('.miniSurveyQuestionRow'); 
			if( !Qs || parseInt(Qs.length)<1 ){
				alert(ccm_t('form-min-1'));
				failed=1;
			}
			
			if(failed){
				ccm_isBlockError=1;
				return false;
			}
			return true;
	},
	
	moveUp:function(el,thisQID){
		var qIDs=this.serialize();
		var previousQID=0;
		for(var i=0;i<qIDs.length;i++){
			if(qIDs[i]==thisQID){
				if(previousQID==0) break; 
				$('#miniSurveyQuestionRow'+thisQID).after($('#miniSurveyQuestionRow'+previousQID));
				break;
			}
			previousQID=qIDs[i];
		}	
		this.saveOrder();
	},
	moveDown:function(el,thisQID){
		var qIDs=this.serialize();
		var thisQIDfound=0;
		for(var i=0;i<qIDs.length;i++){
			if(qIDs[i]==thisQID){
				thisQIDfound=1;
				continue;
			}
			if(thisQIDfound){
				$('#miniSurveyQuestionRow'+qIDs[i]).after($('#miniSurveyQuestionRow'+thisQID));
				break;
			}
		}
		this.saveOrder();
	},
	serialize:function(){
		var t = document.getElementById("miniSurveyPreviewTable");
		var qIDs=[];
		for(var i=0;i<t.childNodes.length;i++){ 
			if( t.childNodes[i].className && t.childNodes[i].className.indexOf('miniSurveyQuestionRow')>=0 ){ 
				var qID=t.childNodes[i].id.substr('miniSurveyQuestionRow'.length);
				qIDs.push(qID);
			}
		}
		return qIDs;
	},
	saveOrder:function(){ 
		var postStr='qIDs='+this.serialize().join(',')+'&qsID='+parseInt(this.qsID);
		$.ajax({ 
			type: "POST",
			data: postStr,
			url: this.serviceURL+"mode=reorderQuestions",			
			success: function(msg){	
				miniSurvey.refreshSurvey();
			}			
		});
	},
	showRecipient:function(cb){
		if(cb.checked) {
			$('#recipientEmailWrap').css('display','block');
			$('#mcheck2').removeAttr('disabled');
			$('#mcheck2Edit').removeAttr('disabled');
		} else {
			$('#recipientEmailWrap').css('display','none');
			$('#mcheck2').attr('disabled','true');
			$('#mcheck2Edit').attr('disabled','true');
		}
	} 
}
ccmValidateBlockForm = function() { return miniSurvey.validate(); }
$(document).ready(function(){
	//miniSurvey.init();
	$('#ccm-form-redirect').change(function() {
		if($(this).is(':checked')) {
			$('#ccm-form-redirect-page').show();
		} else {
			$('#ccm-form-redirect-page').hide();
		}
	});
		
});
