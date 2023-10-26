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
	
	//削除画像ファイル名か削除キーが無い場合は Forbidden を返す
	if(( !$DeleteImageNames )||( $DeleteKey == "" )){
		fclose($LockFileOpen);
		http_response_code(403);
		exit;
	}
	
	//画像ファイル名数をカウントする
	$DeleteImageCount = count($DeleteImageNames);
	
	//画像管理・ImageList管理インスタンス
	$ImageManager = new ImageManager();
	$EditImageList = new ImageListManager();
	
	//ファイル数分ループ
	for($ExecuteCount=0; $ExecuteCount < $DeleteImageCount; $ExecuteCount++ ){

		//削除キーの照合
		$RegistedDeleteKey = $ImageManager->GetImageInfo($DeleteImageNames[$ExecuteCount], "DeleteKey");
		if( $DeleteKey === false ){
			$Response[$DeleteImageNames[$ExecuteCount]] = 2;
			continue;
		}else if( $DeleteKey != $RegistedDeleteKey ){
			$Response[$DeleteImageNames[$ExecuteCount]] = 1;
			continue;
		}

		//画像を削除する
		$ImageManager->DeleteImage($DeleteImageNames[$ExecuteCount]);
		$Response[$DeleteImageNames[$ExecuteCount]] = 0;

		//ImageListから該当エントリを削除
		$EditImageList->DeleteEntry($DeleteImageNames[$ExecuteCount]);
		$ChangedImageList = true;
		
	}
	
	//ImageListに変更があった場合は保存する
	if( $ChangedImageList ){ $EditImageList->StaticSaveEntry(); }
	
	fclose($LockFileOpen);
	echo json_encode($Response);
	exit;
	
?>
		