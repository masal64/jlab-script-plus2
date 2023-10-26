<?php
	
	
	/*
		auto-deletion.php
		jlab-script-plus2 Beta3
	*/
	
	
	set_time_limit(0);
	ini_set("display_errors", 0);
	header("Content-Type:text/plain; charset=UTF-8");
	
	$OnlyLoadSettings = true;
	require_once(__DIR__ . "/../manage.php");
	require_once("./share/class.functions.php");

	//期限切れプレフィックスを設定する
	$ExpiredPrefix = date($AutoDeletionConfig, strtotime("-{$SaveDay} days") );

	//自動削除が既に実行済みか確認する
	$AutoDeletionLog = json_decode(file_get_contents("../{$LogFolder}/AutoDeletion.json"), true);
	if( $AutoDeletionLog["LastDeleted"] >=  $ExpiredPrefix ){
		echo "［ｉ］既に実行済みです\n";
		echo "［ｉ］自動削除処理が終了しました\n\n";
		echo "期限切れプレフィックス：{$ExpiredPrefix}";
		exit;
	}
	
	//インスタンス作成
	$ImageManager = new ImageManager();
	$ImageListManager = new ImageListManager();

	//期限切れ画像を取得する
	$ImageManager->ExpiredPrefix = $ExpiredPrefix;
	$ExpiredImages = $ImageManager->ScanExpiredImages();

	//期限切れ画像が無い場合
	if( count($ExpiredImages) == 0 ){

		//AutoDeletion.jsonを更新する
		$AutoDeletionData = array(
			"AutoDeletionConfig" => $AutoDeletionConfig,
			"LastDeleted" => $ExpiredPrefix
		);
		file_put_contents("../{$LogFolder}/AutoDeletion.json", json_encode($AutoDeletionData));

		echo "［ｉ］自動削除する画像はありません\n";
		echo "［ｉ］自動削除処理が終了しました\n\n";
		echo "期限切れプレフィックス：{$ExpiredPrefix}\n";
		echo "期限切れ画像数：".count($ExpiredImages)."枚";
		exit;
	}
	
	//ImageListをロックする
	$LockFilePath = "./share/ImageList.lock";
	$LockFileOpen = fopen( $LockFilePath,"a" );
	flock( $LockFileOpen,LOCK_EX );
	
	//スキャンして自動削除を開始する
	foreach( $ExpiredImages as $ImageName ){

		//画像とImageListからエントリーを削除する
		$ImageManager->DeleteImage($ImageName);
		$ImageListManager->DeleteEntry($ImageName);

		//ImageListの更新を待つ
		//高速サーバーの場合、foreachの処理を待たずStaticSaveEntryを呼び出すことがある為
		usleep(500);

	}

	//ImageListを更新する
	$ImageListManager->StaticSaveEntry();

	//AutoDeletion.jsonを更新する
	$AutoDeletionData = array(
		"AutoDeletionConfig" => $AutoDeletionConfig,
		"LastDeleted" => $ExpiredPrefix
	);
	file_put_contents("../{$LogFolder}/AutoDeletion.json", json_encode($AutoDeletionData));
	
	fclose($LockFileOpen);
	
	echo "［ｉ］自動削除ログファイルを更新しました\n";
	echo "［ｉ］自動削除処理が終了しました\n\n";
	echo "期限切れプレフィックス：{$ExpiredPrefix}\n";
	echo "期限切れ画像数：".count($ExpiredImages)."枚";
	exit;
	
?>