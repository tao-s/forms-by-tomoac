<?php  
defined('C5_EXECUTE') or die("Access Denied.");

$miniSurvey= new MinisurveyTomoac();

//Permissions Check
if($_GET['cID'] && $_GET['arHandle']){
	$c = Page::getByID($_GET['cID'], 'RECENT');
	$a = Area::get($c, $_GET['arHandle']);  
	if(intval($_GET['bID'])==0){ 
		//add survey mode
		$ap = new Permissions($a);	
		$bt = BlockType::getByID($_GET['btID']);	
		if(!$ap->canAddBlock($bt)) $badPermissions=true;
	}else{
		//edit survey mode
		$b = Block::getByID($_GET['bID'], $c, $a);
		$bp = new Permissions($b);
		if( !$bp->canWrite() ) $badPermissions=true;
	}
}else $badPermissions=true;
if($badPermissions){
	echo t('Invalid Permissions');
	die;
} 


switch ($_GET['mode']){

	case 'addQuestion':
		$miniSurvey->addEditQuestion($_POST);
		break;
		
	case 'getQuestion':
		$miniSurvey->getQuestionInfo( intval($_GET['qsID']), intval($_GET['qID']) );
		break;			
		
	case 'delQuestion':
		$miniSurvey->deleteQuestion(intval($_GET['qsID']),intval($_GET['msqID']));
		break;			
		
	case 'reorderQuestions':
		$miniSurvey->reorderQuestions(intval($_POST['qsID']),$_POST['qIDs']);
		break;
				
	case 'refreshSurvey':
	default: 
		$showEdit=(intval($_REQUEST['showEdit'])==1)?true:false; 
		$miniSurvey->loadSurvey( intval($_GET['qsID']), $showEdit, intval($_GET['bID']), explode(',',$_GET['hide']), 1 ); 
}

?>
