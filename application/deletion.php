<?php
	
	
	/*
		deletion.php
		jlab-script-plus2 Beta3
	*/
	
	
	set_time_limit(0);
	ini_set("display_errors", 0);
	header("Content-Type:text/plain; charset=UTF-8");
	
	$OnlyLoadSettings = true;
	require_once(__DIR__ . "/../manage.php");
	require_once("./share/class.functions.php");
	
	$LockFilePath = "./share/ImageList.lock";
	$LockFileOpen = fopen( $LockFilePath,"a" );
	flock( $LockFileOpen,LOCK_EX );
	
	$DeleteImageNames = json_decode($_POST["DeleteImages"]);
	$DeleteKey = $_POST["DeleteKey"];
	$ChangedImageList = false;
	$Response = Array();
	
	//�폜�摜�t�@�C�������폜�L�[�������ꍇ�� Forbidden ��Ԃ�
	if(( !$DeleteImageNames )||( $DeleteKey == "" )){
		fclose($LockFileOpen);
		http_response_code(403);
		exit;
	}
	
	//�摜�t�@�C���������J�E���g����
	$DeleteImageCount = count($DeleteImageNames);
	
	//�摜�Ǘ��EImageList�Ǘ��C���X�^���X
	$ImageManager = new ImageManager();
	$EditImageList = new ImageListManager();
	
	//�t�@�C���������[�v
	for($ExecuteCount=0; $ExecuteCount < $DeleteImageCount; $ExecuteCount++ ){

		//�폜�L�[�̏ƍ�
		$RegistedDeleteKey = $ImageManager->GetImageInfo($DeleteImageNames[$ExecuteCount], "DeleteKey");
		if( $DeleteKey === false ){
			$Response[$DeleteImageNames[$ExecuteCount]] = 2;
			continue;
		}else if( $DeleteKey != $RegistedDeleteKey ){
			$Response[$DeleteImageNames[$ExecuteCount]] = 1;
			continue;
		}

		//�摜���폜����
		$ImageManager->DeleteImage($DeleteImageNames[$ExecuteCount]);
		$Response[$DeleteImageNames[$ExecuteCount]] = 0;

		//ImageList����Y���G���g�����폜
		$EditImageList->DeleteEntry($DeleteImageNames[$ExecuteCount]);
		$ChangedImageList = true;
		
	}
	
	//ImageList�ɕύX���������ꍇ�͕ۑ�����
	if( $ChangedImageList ){ $EditImageList->StaticSaveEntry(); }
	
	fclose($LockFileOpen);
	echo json_encode($Response);
	exit;
	
?>
		