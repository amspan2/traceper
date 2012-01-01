<?php

class UsersController extends Controller
{


	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionIndex()
	{
		// renders the view file 'protected/views/site/index.php'
		// using the default layout 'protected/views/layouts/main.php'
		$this->render('index');
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
			echo $error['message'];
			else
			$this->render('error', $error);
		}
	}

	/*
	 * this action is used by mobile clients
	 */
	public function actionTakeMyLocation()
	{
		/*if (Yii::app()->user->isGuest) {
			echo CJSON::encode(array(
			"result"=>"Unauthorized access",
			));
			}
			*/
		$result = "Missing parameter";
		if (isset($_REQUEST['latitude']) && $_REQUEST['latitude'] != NULL
		&& isset($_REQUEST['longitude']) && $_REQUEST['longitude'] != NULL
		&& isset($_REQUEST['altitude']) && $_REQUEST['altitude'] != NULL
		&& isset($_REQUEST['deviceId']) && $_REQUEST['deviceId'] != NULL
		&& isset($_REQUEST['time']) && $_REQUEST['time'] != NULL
		&& isset($_REQUEST['email']) && $_REQUEST['email'] != NULL
		&& isset($_REQUEST['password']) && $_REQUEST['password'] != NULL
		)
		{
			$latitude = (float) $_REQUEST['latitude'];
			$longitude = (float) $_REQUEST['longitude'];
			$altitude = (float) $_REQUEST['altitude'];
			$deviceId = $_REQUEST['deviceId'];
			$calculatedTime = date('Y-m-d H:i:s',  $_REQUEST['time']);
			$email = $_REQUEST['email'];
			$password = $_REQUEST['password'];

			$sql = sprintf('SELECT Id
								FROM '.  Users::model()->tableName() .' 
							WHERE email = "%s" 
						  		  AND 
						  		  password = "%s"
							LIMIT 1', $email, md5($password));
			$userId = Yii::app()->db->createCommand($sql)->queryScalar();
			$result = "Email or password not correct";
			if ($userId != false)
			{
				$sql = sprintf('UPDATE '
				. Users::model()->tableName() .'
								SET
								  	latitude = %f , '
								  	.'	longitude = %f , '
								  	.'	altitude = %f ,	'
								  	.'	dataArrivedTime = NOW(), '
								  	.'	deviceId = "%s"	,'
								  	.'    dataCalculatedTime = "%s" '
								  	.' WHERE '
								  	.' Id = %d '
								  	.' LIMIT 1;',
								  	$latitude, $longitude, $altitude, $deviceId, $calculatedTime, $userId);
								  	$effectedRows = Yii::app()->db->createCommand($sql)->execute();
								  	$result = "Unknown Error";
								  	if ($effectedRows == 1)
								  	{
								  		$sqlWasHere = sprintf('INSERT INTO '
								  		. UserWasHere::model()->tableName() . '
									(userId, latitude, longitude, altitude, dataArrivedTime, deviceId, dataCalculatedTime)
		    						VALUES(%d,	%f, %f, %f, NOW(), "%s", "%s") 
									',
								  		$userId, $latitude, $longitude, $altitude, $deviceId, $calculatedTime);
								  		Yii::app()->db->createCommand($sqlWasHere)->execute();
								  		$result = "1";
								  	}
			}

		}
		$resultArray = array("result"=>$result);
		if ($result == "1") {
			$resultArray = array_merge($resultArray, array(
									"minDataSentInterval"=> Yii::app()->params->minDataSentInterval,
									"minDistanceInterval"=> Yii::app()->params->minDistanceInterval,
			));
		}
		echo CJSON::encode(
		$resultArray
		);

		Yii::app()->end();
	}

	public function actionGetFriendList()
	{
		$sqlCount = 'SELECT count(*)
					 FROM '. Friends::model()->tableName() . ' f 
					 WHERE (friend1 = '.Yii::app()->user->id.' 
						OR friend2 ='.Yii::app()->user->id.') AND status= 1';

		$count=Yii::app()->db->createCommand($sqlCount)->queryScalar();

		$sql = 'SELECT u.Id as id, u.realname, f.Id as friendShipId
				FROM '. Friends::model()->tableName() . ' f 
				LEFT JOIN ' . Users::model()->tableName() . ' u
					ON u.Id = IF(f.friend1 != '.Yii::app()->user->id.', f.friend1, f.friend2)
				WHERE (friend1 = '.Yii::app()->user->id.' 
						OR friend2='.Yii::app()->user->id.') AND status= 1'  ;

		$dataProvider = new CSqlDataProvider($sql, array(
		    											'totalItemCount'=>$count,
													    'sort'=>array(
						        							'attributes'=>array(
						             									'id', 'realname',
		),
		),
													    'pagination'=>array(
													        'pageSize'=>Yii::app()->params->itemCountInOnePage,
		),
		));
			
		Yii::app()->clientScript->scriptMap['jquery.js'] = false;
		$this->renderPartial('usersInfo',array('dataProvider'=>$dataProvider,'model'=>new SearchForm()), false, true);

	}

	/**
	 * this function returns users and images in xml format
	 */
	public function actionGetUserListXML()
	{
		if (Yii::app()->user->isGuest) {
			return;
		}
		$pageNo = 1;
		if (isset($_REQUEST['pageNo']) && $_REQUEST['pageNo'] > 0) {
			$pageNo = (int) $_REQUEST['pageNo'];
		}
		$offset = ($pageNo - 1) * Yii::app()->params->itemCountInDataListPage;
		$out = '';
		$dataFetchedTimeKey = "UsersController.dataFetchedTime";
		if (isset($_REQUEST['list'])) {
			if ($_REQUEST['list'] == "onlyUpdated")
			{
				$time = Yii::app()->session[$dataFetchedTimeKey];
				if ($time !== null && $time !== false)
				{
					$sqlCount = 'SELECT ceil(count(*)/'. Yii::app()->params->itemCountInDataListPage .')
					 		FROM ' . Users::model()->tableName() . ' u
					 		LEFT JOIN ' . Friends::model()->tableName() . ' f  
					 			ON (f.friend1 = '. Yii::app()->user->id .' 
									OR f.friend2 ='. Yii::app()->user->id .') AND f.status= 1
							WHERE unix_timestamp(u.dataArrivedTime) >= '. $time;

					$pageCount = Yii::app()->db->createCommand($sqlCount)->queryScalar();

					$sql = 'SELECT u.Id as id, u.realname,u.latitude, u.longitude, u.altitude, f.Id as friendShipId,
								1 isFriend
							FROM '. Users::model()->tableName() . ' u 
							LEFT JOIN ' . Friends::model()->tableName() . ' f
								ON u.Id = IF(f.friend1 != '.Yii::app()->user->id.', f.friend1, f.friend2)
							WHERE (f.friend1 = '. Yii::app()->user->id .' 
									OR f.friend2 ='. Yii::app()->user->id .') AND f.status= 1
									AND unix_timestamp(u.dataArrivedTime) >= '. $time . '
							LIMIT ' . $offset . ' , ' . Yii::app()->params->itemCountInDataListPage;

					$out = $this->prepareXML($sql, $pageNo, $pageCount, "userList");
				}

			}
		}
		else {

			$sqlCount = 'SELECT ceil(count(*)/'. Yii::app()->params->itemCountInDataListPage .')
					 FROM '. Friends::model()->tableName() . ' f 
					 WHERE (friend1 = '.Yii::app()->user->id.' 
						OR friend2 ='.Yii::app()->user->id.') AND status= 1';

			$pageCount = Yii::app()->db->createCommand($sqlCount)->queryScalar();

			$sql = 'SELECT u.Id as id, u.realname,u.latitude, u.longitude, u.altitude, f.Id as friendShipId,
						1 isFriend
				FROM '. Friends::model()->tableName() . ' f 
				LEFT JOIN ' . Users::model()->tableName() . ' u
					ON u.Id = IF(f.friend1 != '.Yii::app()->user->id.', f.friend1, f.friend2)
				WHERE (friend1 = '.Yii::app()->user->id.' 
						OR friend2 ='.Yii::app()->user->id.') AND status= 1
				LIMIT ' . $offset . ' , ' . Yii::app()->params->itemCountInDataListPage;


			$out = $this->prepareXML($sql, $pageNo, $pageCount, "userList");
		}
		echo $out;
		Yii::app()->session[$dataFetchedTimeKey] = time();
		Yii::app()->end();
	}

	public function actionGetUserPastPointsXML(){

		if (isset($_REQUEST['userId']))
		{
			$userId = (int) $_REQUEST['userId'];
			$pageNo = 1;
			if (isset($_REQUEST['pageNo']) && $_REQUEST['pageNo'] > 0) {
				$pageNo = (int) $_REQUEST['pageNo'];
			}
			$offset = ($pageNo - 1) * Yii::app()->params->itemCountInDataListPage;
			$offset++;  // to not get the last location
			$sql = 'SELECT
							longitude, latitude, deviceId, 
							date_format(dataArrivedTime,"%d %b %Y %T") as dataArrivedTime
					FROM ' . UserWasHere::model()->tableName() .'
					WHERE 
						userId = '. $userId . '
					ORDER BY 
						Id DESC
					LIMIT '. $offset . ','
					. Yii::app()->params->itemCountInDataListPage;

					// subtract 1 to not get the last location into consideration
					$sqlPageCount = 'SELECT
									ceil((count(Id)-1)/ '. Yii::app()->params->itemCountInDataListPage .')
							 FROM '. UserWasHere::model()->tableName() .'
							 WHERE 
								 	userId = '. $userId;				
					$pageCount = Yii::app()->db->createCommand($sqlPageCount)->queryScalar();

					$out = $this->prepareXML($sql, $pageNo, $pageCount, "userPastLocations", $userId);
		}
		echo $out;
	}

	public function actionSearch() {
		$model = new SearchForm();

		$dataProvider = null;
		if(isset($_REQUEST['SearchForm']))
		{
			$model->attributes = $_REQUEST['SearchForm'];
			if ($model->validate()) {

				$sqlCount = 'SELECT count(*)
					 FROM '. Users::model()->tableName() . ' u 
					 WHERE realname like "%'. $model->keyword .'%"';

				$count=Yii::app()->db->createCommand($sqlCount)->queryScalar();

				/*
				 * if status is 0 it means friend request made but not yet confirmed
				 * if status is 1 it means friend request is confirmed.
				 * if status is -1 it means there is no relation of any kind between users.
				 */
				$sql = 'SELECT u.Id as id, u.realname, f.Id as friendShipId,
								 IF(f.status = 0 OR f.status = 1, f.status, -1) as status,
								 IF(f.friend1 = '. Yii::app()->user->id .', true, false ) as requester
						FROM '. Users::model()->tableName() . ' u 
						LEFT JOIN '. Friends::model()->tableName().' f 
							ON  (f.friend1 = '. Yii::app()->user->id .' 
								 AND f.friend2 =  u.Id)
								 OR 
								 (f.friend1 = u.Id 
								 AND f.friend2 = '. Yii::app()->user->id .' ) 
						WHERE u.realname like "%'. $model->keyword .'%"' ;

				$dataProvider = new CSqlDataProvider($sql, array(
		    											'totalItemCount'=>$count,
													    'sort'=>array(
						        							'attributes'=>array(
						             									'id', 'realname',
				),
				),
													    'pagination'=>array(
													        'pageSize'=>Yii::app()->params->itemCountInOnePage,
															'params'=>array(CHtml::encode('SearchForm[keyword]')=>$model->attributes['keyword']),
				),
				));
					
			}
		}
		Yii::app()->clientScript->scriptMap['jquery.js'] = false;
		$this->renderPartial('searchResults',array('model'=>$model, 'dataProvider'=>$dataProvider), false, true);
	}

	public function actionCreateGeofence() {
		$fence=new Geofence;
		$result = "Missing parameter";
		if (isset($_REQUEST['point1Latitude']) && isset($_REQUEST['point1Longitude'])
		&& isset($_REQUEST['point2Latitude']) && isset($_REQUEST['point2Longitude'])
		&& isset($_REQUEST['point3Latitude']) && isset($_REQUEST['point3Longitude']))
		{
			$point1Lat = (float) $_REQUEST['point1Latitude'];
			$point1Long = (float) $_REQUEST['point1Longitude'];
			$point2Lat = (float) $_REQUEST['point2Latitude'];
			$point2Long = (float) $_REQUEST['point2Longitude'];
			$point3Lat = (float) $_REQUEST['point3Latitude'];
			$point3Long = (float) $_REQUEST['point3Longitude'];

			$fence->point1Latitude = $point1Lat;
			$fence->point1Longitude = $point1Long;
			$fence->point2Latitude = $point2Lat;
			$fence->point2Longitude = $point2Long;
			$fence->point3Latitude = $point3Lat;
			$fence->point3Longitude = $point3Long;

			$fence->userId = Yii::app()->user->id;

			$result = "Error in operation";
			if ($fence->save()) {
				$result = 1;
			}

			echo CJSON::encode(array(
                                         	"result"=>$result,
			));
		}
	}


	public function actionDeleteFriendShip(){
		$result = 'Missing Data';
		if (isset($_REQUEST['friendShipId']))
		{
			$friendShipId = (int) $_REQUEST['friendShipId'];
			$friendShip = Friends::model()->findByPk($friendShipId, array('condition'=>'friend1=:friend1 OR
																		  friend2=:friend2',
																		 'params'=>array(':friend1'=>Yii::app()->user->id,
																						':friend2'=>Yii::app()->user->id,
			),
			)
			);
			$result = 'Error occured';
			if ($friendShip != null && $friendShip->delete()){
				$result = 1;
			}
		}


		echo CJSON::encode(array(
								"result"=>$result,
		));

	}

	public function actionGetCallLog(){
		// we look at the friend2 field because requester id is stored in friend1 field
		// and only friend who has been requested to be a friend can approve frienship
		if (isset($_REQUEST['userId'])) {
			$userId = (int)$_REQUEST['userId'];
			Yii::app()->session["calllogUserId"] = $userId;
		}
		else if (isset(Yii::app()->session["calllogUserId"])) {
			$userId = Yii::app()->session["calllogUserId"];
		}
		$sqlCount = 'SELECT count(*)
					 FROM '. CallLog::model()->tableName() . ' f 
					 WHERE userid = '. $userId ;

		$count=Yii::app()->db->createCommand($sqlCount)->queryScalar();

		/**
		 * because we use same view in listing users, we put requester field as false
		 * to make view show approve link,
		 * requester who make friend request cannot approve request
		 */
		$sql = 'SELECT id, number, begin, end, type, latitude, longitude
				FROM '. CallLog::model()->tableName()  . ' f 
				WHERE userid = '. $userId . '
				ORDER BY id DESC';

		$dataProvider = new CSqlDataProvider($sql, array(
		    											'totalItemCount'=>$count,
													    'sort'=>array(
						        							'attributes'=>array(
						             									'id', 'number','begin','eng', 'latitude','longitude'
						             									),
						             									),
													    'pagination'=>array(
													        'pageSize'=>Yii::app()->params->itemCountInOnePage,
						             									),
						             									));
						             									 
		Yii::app()->clientScript->scriptMap['jquery.js'] = false;
		$this->renderPartial('callList',array('dataProvider'=>$dataProvider), false, true);
	}

	public function actionGetFriendRequestList(){

		// we look at the friend2 field because requester id is stored in friend1 field
		// and only friend who has been requested to be a friend can approve frienship
		$sqlCount = 'SELECT count(*)
					 FROM '. Friends::model()->tableName() . ' f 
					 WHERE friend2 = '.Yii::app()->user->id.' 
						   AND status= 0';

		$count=Yii::app()->db->createCommand($sqlCount)->queryScalar();

		/**
		 * because we use same view in listing users, we put requester field as false
		 * to make view show approve link,
		 * requester who make friend request cannot approve request
		 */
		$sql = 'SELECT u.Id as id, u.realname, f.Id as friendShipId, f.status,
					   false as requester
	
					   
				FROM '. Friends::model()->tableName() . ' f 
				LEFT JOIN ' . Users::model()->tableName() . ' u
					ON u.Id = f.friend1
				WHERE friend2='.Yii::app()->user->id.' AND status= 0'  ;

		$dataProvider = new CSqlDataProvider($sql, array(
		    											'totalItemCount'=>$count,
													    'sort'=>array(
						        							'attributes'=>array(
						             									'id', 'realname',
		),
		),
													    'pagination'=>array(
													        'pageSize'=>Yii::app()->params->itemCountInOnePage,
		),
		));
			
		Yii::app()->clientScript->scriptMap['jquery.js'] = false;
		$this->renderPartial('userListDialog',array('dataProvider'=>$dataProvider), false, true);

	}

	public function actionTakeCallInfo()
	{
		//		echo "takeCallInfo";

		//$string=file_get_contents(filepath);


		// �rnek olarak gelen veri tipi
		//$string='[{"ylocation":0,"number":"552255555","xlocation":0,"type":0,"contact":0,"end":1322324987381,"begin":1322324984079},
		//{"ylocation":0,"number":"5553332211","xlocation":0,"type":0,"contact":0,"end":1322324994078,"begin":1322324990438},
		//{"ylocation":0,"number":"5553332211","xlocation":0,"type":0,"contact":0,"end":1322324999737,"begin":1322324996553}]';

		$email = $_REQUEST['email'];
		$password = $_REQUEST['password'];

		$sql = sprintf('SELECT Id
								FROM '.  Users::model()->tableName() .' 
							WHERE email = "%s" 
						  		  AND 
						  		  password = "%s"
							LIMIT 1', $email, md5($password));
		$userId = Yii::app()->db->createCommand($sql)->queryScalar();
		$result = "Email or password not correct";
		if ($userId != false) {

			$json_o=json_decode($_REQUEST['data']);
			print_r($json_o);
			$i = 0;

			foreach ($json_o as $v) {

				// ��z�mleme  ,veri taban�na kay�t i�lemi
				$number = $json_o[$i]->number;
				$type = $json_o[$i]->type;
				$contact =  $json_o[$i]->contact;
				$end = date("Y-m-d H:i:s", (int)($json_o[$i]->end/1000));
				$begin =  date("Y-m-d H:i:s", (int)($json_o[$i]->begin/1000));
				$lati =  $json_o[$i]->xlocation;
				$longi = $json_o[$i]->ylocation;


				$sqlWasHere = sprintf('INSERT INTO traceper_call_logg
									(userid,  number, latitude, longitude, begin, end,type)
		    						VALUES(%d, %s, %f, %f, "%s", "%s", "%s") 
									',
				$userId, $number, $lati, $longi, $begin, $end ,$type);
				Yii::app()->db->createCommand($sqlWasHere)->execute();

				// veri taban�na kay�t i�lemi

				$i++;
			}
		}


	}

	public function actionApproveFriendShip(){
		$result = 'Missing Data';
		if (isset($_REQUEST['friendShipId']))
		{

			$friendShipId = (int) $_REQUEST['friendShipId'];
			// only friend2 can approve friendship because friend1 makes the request
			$friendShip = Friends::model()->findByPk($friendShipId, array('condition'=>'friend2=:friend2',
																		  'params'=>array(':friend2'=>Yii::app()->user->id,
			),
			)
			);
			$result = 'Error occured';
			if ($friendShip != null){
				$friendShip->status = 1;
				if ($friendShip->save()) {
					$result = 1;
				}
			}
		}
		echo CJSON::encode(array(
								"result"=>$result,
		));

	}

	public function actionAddAsFriend()
	{
		$result = 'Missing parameter';
		if (isset($_REQUEST['friendId'])) {
			$friendId = (int)$_REQUEST['friendId'];

			$friend = new Friends();
			$friend->friend1 = Yii::app()->user->id;
			$friend->friend2 = $friendId;
			$friend->status = 0;
			$result = 'Error occured';
			if ($friend->save()) {
				$result = 1;
			}
		}
		echo CJSON::encode(array(
								"result"=>$result,
		));
	}

	private function prepareXML($sql, $pageNo, $pageCount, $type="userList", $userId=NULL)
	{
		$dataReader = NULL;
		// if page count equal to 0 then there is no need to run query
		//		echo $sql;
		if ($pageCount >= $pageNo && $pageCount != 0) {
			$dataReader = Yii::app()->db->createCommand($sql)->query();
		}


		$str = NULL;
		if ($dataReader != NULL )
		{
			if ($type == "userList")
			{
				while ( $row = $dataReader->read() )
				{
					$str .= $this->getUserXMLItem($row);
				}
			}
			else if ($type == "userPastLocations")
			{
				while ( $row = $dataReader->read() )
				{
					$row['latitude'] = isset($row['latitude']) ? $row['latitude'] : null;
					$row['longitude'] = isset($row['longitude']) ? $row['longitude'] : null;
					$row['altitude'] = isset($row['altitude']) ? $row['altitude'] : null;
					$row['dataArrivedTime'] = isset($row['dataArrivedTime']) ? $row['dataArrivedTime'] : null;
					$row['deviceId'] = isset($row['deviceId']) ? $row['deviceId'] : null;

					$str .= '<location latitude="'.$row['latitude'].'"  longitude="'. $row['longitude'] .'" altitude="'.$row['altitude'].'" >'
					.'<time>'. $row['dataArrivedTime'] .'</time>'
					.'<deviceId>'. $row['deviceId'] .'</deviceId>'
					.'</location>';
				}
			}
		}


		$extra = "";
		if ($type == "userPastLocations" && $userId != NULL) {
			$extra = ' userId="' . $userId .'"';
		}
		$pageNo = $pageCount == 0 ? 0 : $pageNo;
		/*		$out = '<?xml version="1.0" encoding="UTF-8"?>'
		 //				.'<page '. $pageStr . ' >'
		 //					. $str
		 //			   .'</page>';
		 */
		return $this->addXMLEnvelope($pageNo, $pageCount, $str, $extra);
	}

	private function addXMLEnvelope($pageNo, $pageCount, $str, $extra = ""){
			
		$pageStr = 'pageNo="'.$pageNo.'" pageCount="' . $pageCount .'"' ;

		header("Content-type: application/xml; charset=utf-8");
		$out = '<?xml version="1.0" encoding="UTF-8"?>'
		.'<page '. $pageStr . '  '. $extra .' >'
		. $str
		.'</page>';

		return $out;
	}

	private function getUserXMLItem($row)
	{
		$row['id'] = isset($row['id']) ? $row['id'] : null;
		//		$row->username = isset($row->username) ? $row->username : null;
		$row['isFriend'] = isset($row['isFriend']) ? $row['isFriend'] : 0;
		$row['realname'] = isset($row['realname']) ? $row['realname'] : null;
		$row['latitude'] = isset($row['latitude']) ? $row['latitude'] : null;
		$row['longitude'] = isset($row['longitude']) ? $row['longitude'] : null;
		$row['altitude'] = isset($row['altitude']) ? $row['altitude'] : null;
		$row['dataArrivedTime'] = isset($row['dataArrivedTime']) ? $row['dataArrivedTime'] : null;
		$row['message'] = isset($row['message']) ? $row['message'] : null;
		$row['deviceId'] = isset($row['deviceId']) ? $row['deviceId'] : null;
		$row['status_message'] = isset($row['status_message']) ? $row['status_message'] : null;
		$row['dataCalculatedTime'] = isset($row['dataCalculatedTime']) ? $row['dataCalculatedTime'] : null;
			
		$str = '<user>'
		. '<Id isFriend="'.$row['isFriend'].'">'. $row['id'] .'</Id>'
		//		. '<username>' . $row->username . '</username>'
		. '<realname>' . $row['realname'] . '</realname>'
		. '<location latitude="' . $row['latitude'] . '"  longitude="' . $row['longitude'] . '" altitude="' . $row['altitude'] . '" calculatedTime="' . $row['dataCalculatedTime'] . '"/>'
		. '<time>' . $row['dataArrivedTime'] . '</time>'
		. '<message>' . $row['message'] . '</message>'
		. '<status_message>' . $row['status_message'] . '</status_message>'
		. '<deviceId>' . $row['deviceId'] . '</deviceId>'
		.'</user>';

		return $str;
	}



}