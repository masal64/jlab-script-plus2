<?php
	
	
	/*
		imagelist.php
		jlab-script-plus2 Beta3
	*/
	
	
	//manage.phpの読み込み
	$OnlyLoadSettings = true;
	require_once(__DIR__ . "/../manage.php");
	require_once("./share/class.functions.php");
	
	//PHP基本設定
	ini_set("display_errors", 0);
	header("Content-type:application/json; charset=UTF-8");
	header("Access-Control-Allow-Origin:{$FullURL}");
	header("Access-Control-Allow-Headers:*");
	header("Cache-Control: no-cache");
	
	//要求ページを取得
	$CurrentPage = $_GET["p"];
	if( empty($CurrentPage) ){
		$CurrentPage = 1;
	}
	
	//画像リストを取得し、配列に変換
	$LoadImageList = new ImageListManager();
	list($ImageList, $ListLoadMode) = $LoadImageList->Load();
	
	//Streamが存在しない場合は404を返す
	if( !$ImageList ){
		http_response_code(404);
		exit;
	}
	
	//履歴のクリーンアップ
	if( $CurrentPage == "cleanup" ){
	
		//送信された履歴を取得する
		$ImageHistory = json_decode($_POST["history"], true);
		
		//ImageListからファイル名をKeyとした(連想)配列を作成する
		$FileNameArray = array_flip(array_column($ImageList, "Name"));
		
		//履歴中のファイル名がImageList.jsonにも存在するか確認する
		$NewImageHistory = array();
		foreach( $ImageHistory as $ImageFileName ){
			if( array_key_exists($ImageFileName, $FileNameArray) ){
				$NewImageHistory[] = $ImageFileName;
			}
		}
		
		//最後にjson形式で新しい履歴をリターンする
		echo json_encode($NewImageHistory, JSON_PRETTY_PRINT);
		exit;
		
	}

	//画像が何枚あるかを数える
	$ImageCount = count($ImageList);
	
	//1ページに何枚の画像を表示させるか
	$PageDisplayedCount = $_GET["d"];
	if( empty( $PageDisplayedCount ) ){
		$PageDisplayedCount = 20;
	}
	
	//ページは何枚作成するか
	$PageCount = ceil($ImageCount/$PageDisplayedCount);
	
	//100ページリンクは必要か
	$CurrentPageBoxCount = floor($CurrentPage/100);
	$PageBoxCount = floor($PageCount/100);
	
	//現在のページボックスが1?100の場合
	//かつ表示枚数*100枚以下の画像を保持している場合
	if(( $CurrentPageBoxCount < 1 )&&( $ImageCount < $PageDisplayedCount*100 )){
		$PrevBoxLink = false;
		$NextBoxLink = false;
		$MinLink = 1;
		$MaxLink = $PageCount;
	}
	
	
	//現在のページボックスが1?100の場合
	//かつ表示枚数*100枚以上の画像を保持している場合
	else if(( $CurrentPageBoxCount < 1 )&&( $PageDisplayedCount*100 <= $ImageCount )){
		$PrevBoxLink = false;
		$NextBoxLink = 101;
		$MinLink = 1;
		$MaxLink = 100;
	}
	
	//現在のページボックスが101?の場合
	else{
		if( $CurrentPageBoxCount == 1 ){
			$PrevBoxLink = 1;
		}else{
			$PrevBoxLink = ($CurrentPageBoxCount-1). "00";
		}
		$MinLink = $CurrentPageBoxCount."00";
		
		//最終ページを含むボックスの場合
		if( $PageCount < ($CurrentPageBoxCount+1)."00" ){
			$NextBoxLink = false;
			$MaxLink = $PageCount;
		}
		
		//最終ページを含まないボックスの場合
		else{
			$NextBoxLink = ($CurrentPageBoxCount+1)."01";
			$MaxLink = ($CurrentPageBoxCount+1)."00";
		}
	
	}
	
	//出力用JSON生成連想配列
	$OutputJSON = array();
	
	//このJSONデータのメタデータ
	$OutputJSON[0]["Loader"] = $ListLoadMode;
	$OutputJSON[0]["Page"] = $CurrentPage;
	$OutputJSON[0]["Prev"] = $PrevBoxLink;
	$OutputJSON[0]["Next"] = $NextBoxLink;
	$OutputJSON[0]["Max"] = $MaxLink;
	$OutputJSON[0]["PageCount"] = $PageCount;
	$OutputJSON[0]["Min"] = $MinLink;
	$OutputJSON[0]["Active"] = $ImageCount;
	
	//ImageListの個数だけ繰り返す
	for( $i=($PageDisplayedCount*$CurrentPage)-$PageDisplayedCount, $ji=1; $i < $PageDisplayedCount*$CurrentPage; $i++, $ji++ ){
	
		//配列が存在しない場合は終了
		if( empty($ImageList[$i]["Name"]) ){
			break;
		}
		
		//出力用JSON配列に代入
		$OutputJSON[$ji]["Name"] = $ImageList[$i]["Name"];
		$OutputJSON[$ji]["Time"] = $ImageList[$i]["Time"];
		$OutputJSON[$ji]["Meta"] = "(".$ImageList[$i]["Width"]."x".$ImageList[$i]["Height"]." / ".$ImageList[$i]["Size"]."KB / ".$ImageList[$i]["Name"].")";
	
	}
	
	$OutputJSON[0]["Count"] = ($ji-1);
	
	echo json_encode($OutputJSON, JSON_PRETTY_PRINT);
	exit;
	
?>