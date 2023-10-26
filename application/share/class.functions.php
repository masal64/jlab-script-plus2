<?php

	/*
		class.functions.php
		jlab-script-plus2 Beta3
	*/

	class ImageManager {

		public $ExpiredPrefix;

		//画像データを取得する
		public function GetImageInfo($ImageName, $InfoType){

			global $LogFolder;

			//画像名から拡張子を外す
			list($ImageNamePure, $ImageExtension) = explode(".", $ImageName);

			//画像が存在しない場合はfalseを返す
			if( !file_exists("../{$LogFolder}/{$ImageNamePure}.json")){ return false; }

			//画像データjsonを取得する
			$ImageData = file_get_contents("../{$LogFolder}/{$ImageNamePure}.json");
			$ImageData = json_decode($ImageData, true);
			
			//要求されたデータを返す(Allの場合はそのまま配列で返す)
			if( $InfoType == "All" ){
				return $ImageData;
			}else{
				return $ImageData[$InfoType];
			}
		}

		//画像を削除する
		public function DeleteImage($ImageName){

			global $SaveFolder;
			global $ThumbSaveFolder;
			global $LogFolder;

			unlink("../{$SaveFolder}/{$ImageName}");
			unlink("../{$ThumbSaveFolder}/{$ImageName}");
			list($ImageNamePure, $ImageExtension) = explode(".", $ImageName);
			unlink("../{$LogFolder}/".$ImageNamePure.".json");

		}

		//期限切れの画像を取得する
		public function ScanExpiredImages(){
			
			$ImageListManager = new ImageListManager();
			list($ImageList, $ListLoader) = $ImageListManager->Load();

			//ファイル名だけのリストを作成する
			$FileNameList = array_column($ImageList, "Name");

			//期限切れ日付を設定する
			$ExpiredPrefixCount = mb_strlen($this->ExpiredPrefix);

			//期限切れ画像のファイル名を入れる配列
			$ExpiredImages = array();

			foreach( $FileNameList as $FileName ){

				/*
				ファイル名から、AutoDeletionConfigで設定された文字数だけ切り出す

				例：2310231224245673.jpg / $AutoDeletionConfig = 'ymdH' の時、
				　　y=23, m=10, d=23, H=12 の合計8バイトを切り出すので、
				　　$UploadedDay には 23102312 が代入される。
				*/
				$UploadedDay = substr(preg_replace("~[^0-9]~", "", $FileName), 0, $ExpiredPrefixCount);

				if( $UploadedDay <= $this->ExpiredPrefix ){
					$ExpiredImages[] = $FileName;
				}else{
					continue;
				}

			}

			return $ExpiredImages;

		}


	}

	class ImageListManager {

		//クラス内プライベート変数初期化
		private $kvs;
		private $redis;
		private $ImageListPath;
		private $TempImageList = "";
		private $FileNameArray = "";

		function __construct(){

			global $EnableRedis;
			global $LogFolder;
			global $Redis_Host;
			global $Redis_Port;

			$this->kvs = $EnableRedis;
			$this->ImageListPath = "../{$LogFolder}/ImageList.json";

			if( $this->kvs ){
				$this->redis = new Redis();
				$this->redis->connect($Redis_Host, $Redis_Port);
			}

		}

		public function Load(){

			//ImageList.jsonが存在しない(同時にRedis上にも存在しない)
			if( !file_exists( $this->ImageListPath )){ return; }

			switch( $this->kvs ){

				//Redis上から読み込む
				case true:

					$ImageList = $this->redis->get("jsp-imagelist");
					$ListLoader = "Redis";

					//ImageList.jsonは存在するが、Redis上には存在しない
					if( !$ImageList ){
						$ImageList = file_get_contents($this->ImageListPath);
						$ListLoader = "JSON File";
					}

				break;

				//ImageList.jsonから読み込む
				case false:

					$ImageList = file_get_contents($this->ImageListPath);
					$ListLoader = "JSON File";

				break;

			}

			//連想配列にして渡す
			return [json_decode($ImageList, true), $ListLoader];

		}

		//ImageListにエントリを追加する
		public function AddSaveEntry($EntryDatas){


			//ImageList.jsonが存在しない場合は新規作成する
			if( !file_exists( $this->ImageListPath )){
				$ImageList = array($EntryDatas);
			}else{
				list($ImageList, $ListLoader) = $this->Load();
				$ImageList = array_merge(array($EntryDatas), $ImageList);
			}

			//ImageList.jsonに保存する
			file_put_contents($this->ImageListPath, json_encode($ImageList));

			//Redisにもセットする
			if( $this->kvs ){ $this->redis->set("jsp-imagelist", json_encode($ImageList)); }

		}

		//ImageListから該当のエントリを削除する
		public function DeleteEntry($ImageName){

			if( empty($this->TempImageList) ){
				list($this->TempImageList, $ListLoader) = $this->Load();
				$this->FileNameArray = array_column($this->TempImageList, "Name");
			}

			$ImageNameKey = array_search($ImageName, $this->FileNameArray);
			unset($this->TempImageList[$ImageNameKey]);

		}

		//今保持しているImageListを保存する
		public function StaticSaveEntry(){

			//配列を整形する
			$this->TempImageList = array_values($this->TempImageList);

			//ImageList.jsonに保存する
			file_put_contents($this->ImageListPath, json_encode($this->TempImageList));

			//Redisにもセットする
			if( $this->kvs ){ $this->redis->set("jsp-imagelist", json_encode($this->TempImageList)); }

		}

	}


?>