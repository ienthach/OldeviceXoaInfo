<?php
function connectDB()
{
	$hostSQL = "localhost";
	$userNameSQL = "vidcasha_admin";
	$dataBaseSQL = "vidcasha_oldDevices";
	$passSQL = "i57s6%Ju+1Me";
	$conn = mysql_connect($hostSQL,$userNameSQL,$passSQL) or die("Khong the ket noi csdl!");
	mysql_select_db($dataBaseSQL,$conn) or die("Khong the select database!");
	return $conn;
}

if (isset($_POST["submitCreate"])) {
	if (strlen($_POST["username"]) > 0 && strlen($_POST["password"]) > 0) {
		createAccount($_POST["username"], $_POST["password"]);
	}
	else{
		echo "Thông tin user hoặc password không đầy đủ";
	}
}
elseif(isset($_POST["submit"])) {
	$target_dir = "uploads/";
	$target_file = $target_dir . basename($_FILES['fileToUpload']['name']);
	$uploadOk = 1;
	$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

	// Check file size
	if ($_FILES["fileToUpload"]["size"] > 5000000 || $_FILES["fileToUpload"]["size"] == 0) {
		flush();
		ob_flush();
	    echo "File phải nhỏ hơn 5MB và có dữ liệu từ FilterRRS.";
	    $uploadOk = 0;
	}
	// Allow certain file formats
	if($imageFileType != "txt" && $_FILES['fileToUpload']['error'] == UPLOAD_ERR_OK) {
		echo "Phải là file txt từ tool FilterRRS.";
	    flush();
		ob_flush();
	    $uploadOk = 0;
	}
	// Check if $uploadOk is set to 0 by an error
	if ($uploadOk == 0) {
	    echo " upload lại file xxx_data.txt";
	    flush();
		ob_flush();
	// if everything is ok, try to upload file
	} else {
		if ($_FILES['fileToUpload']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['fileToUpload']['tmp_name'])) 
		{ //checks that file is uploaded
			$accountID = getAccountIDFromUser($_POST["username"]);
			if ($accountID == 0) {
				echo "User ko tồn tại";
			    flush();
				ob_flush();
				die();
			}
			$rawString = file_get_contents($_FILES['fileToUpload']['tmp_name']);
			$arrayString = explode("\r",$rawString);

			$conn = connectDB();

			for ($i=0; $i < count($arrayString) -1; $i++) { 
				$infoDevice = $arrayString[$i];

				$infoDevice = str_replace(',', "\,", $infoDevice);

				$infos = explode("||",$infoDevice);
				if (count($infos) < 2) {
					continue;
				}
				$idfa = $infos[2];
				
				$result = mysql_query("SELECT * FROM  `PUBoldDevice` WHERE  `idfa` = '$idfa' and ownerID = '$accountID'", $conn);
				if(mysql_num_rows($result) == 0) 
				{
					$idfaDate = date('Y/m/d H:i:s', $infos[0]);

					$deviceModel = addslashes($infos[4]);
					$deviceName = addslashes($infos[3]);
					$sql = "INSERT INTO `PUBoldDevice`(`ownerID`,`date`, `country`, `idfa`, `deviceName`, `deviceModel`, `deviceVersion`, `carrierName`, `carrierCountry`, `carrierCountryISO`, `carrierNetwork`, `dateModify`, `requestCount`) 
								VALUES ('$accountID', '$idfaDate','$infos[1]','$infos[2]','$deviceName','$deviceModel','$infos[5]','$infos[6]','$infos[7]','$infos[8]','$infos[9]', NOW(), (select IFNULL((select minRequest FROM `PUBoldDeviceCountryDeploy` WHERE `country` = '$infos[1]' and ownerID = '$accountID'), 0) as minRequest))";

					$retval = mysql_query( $sql, $conn );
					if(! $retval )
					{
					  	die('Could not enter data: ' . mysql_error());
					}
					else{
						$last_id = mysql_insert_id($conn);
						//insert bundleID các app vào bảng PUBoldDeviceBundles
						if (isset($infos[10])) {
							$bundleIDs = $infos[10];
							$updateBundleIDs = $bundleIDs;
							$updateBundleIDs = str_replace(",,", ",", $updateBundleIDs);
							$updateBundleIDs = str_replace(" ", "", $updateBundleIDs);
							$updateBundleIDs = str_replace("\\", "", $updateBundleIDs);
							$updateBundleIDsArr = explode(",", $updateBundleIDs);
							$updateBundleIDsArr = array_unique($updateBundleIDsArr);
							foreach ($updateBundleIDsArr as $bundleid) {
								if (strpos($bundleid, "_groupShared") == false && strpos($bundleid, "_pluginkit") == false && strlen($bundleid)>2){
									//insert bundle id vào oldDeviceBundles
									$sql = "INSERT INTO `PUBoldDeviceBundles`(`oldDeviceID`,`createDate`, `bundleID`) 
											VALUES ('$last_id',NOW(),'$bundleid')";
									$retval = mysql_query( $sql, $conn );
									if(! $retval )
									{
									  	die('Could not enter data: ' . mysql_error());
									}
								}
							}
						}
					}
					echo "$idfa $idfaDate";
					echo str_pad("",1024," "); //BROWSER TWEAKS
					echo " <br />"; //BROWSER TWEAKS

					//telling php to show the stuff as soon as echo is done
					ob_flush();
					flush();
				}	
				else{
					echo "$idfa đã add rồi, update iOS version và bundleIDs" . "<br>";

					$bundleIDsUp = "";
					if (isset($infos[10])) {
						$bundleIDsUp = $infos[10];
					}

					if (strlen($bundleIDsUp) > 2) {
						$row = mysql_fetch_array($result) or die(mysql_error());
						$bundleIDs = $row['bundleIDs'];

						$updateBundleIDs = $bundleIDs.",".$bundleIDsUp;
						$updateBundleIDs = str_replace(",,", ",", $updateBundleIDs);
						$updateBundleIDs = str_replace(" ", "", $updateBundleIDs);
						$updateBundleIDs = str_replace("\\", "", $updateBundleIDs);
						$updateBundleIDsArr = explode(",", $updateBundleIDs);
						$updateBundleIDsArr = array_unique($updateBundleIDsArr);
						
						$last_id = $row['id'];
						$sql = "SELECT `bundleID` FROM oldDeviceBundles INNER JOIN oldDevice ON `oldDeviceBundles`.oldDeviceID = `oldDevice`.id WHERE `oldDevice`.id = '$last_id'";
						$retval = mysql_query( $sql, $conn );
						//nếu allBundle rỗng thì sẽ đc insert các bundle vào, còn có thì chỉ insert những bundle mới.
						$allBundle = array();
						while($row = mysql_fetch_array($retval)){
						  	array_push($allBundle, $row['bundleID']);
						}


						foreach ($updateBundleIDsArr as $bundleid) {
							if (strpos($bundleid, "_groupShared") == false && strpos($bundleid, "_pluginkit") == false && strlen($bundleid)>1 && in_array($bundleid, $allBundle) ==  false){
								$sql = "INSERT INTO `PUBoldDeviceBundles`(`oldDeviceID`,`createDate`, `bundleID`) VALUES ('$last_id',NOW(),'$bundleid')";

								$retval = mysql_query( $sql, $conn );
								if(! $retval )
								{
								  	die('Could not enter data: ' . mysql_error());
								}
								else{
									echo "good";
								}
							}
						}
					}

					$sql = "UPDATE PUBoldDevice set deviceVersion='$infos[5]', dateModify=NOW() where idfa='$idfa' and ownerID = '$accountID'";
					$retval = mysql_query( $sql, $conn );
					if(! $retval )
					{
					  	die('Could not enter data: ' . mysql_error());
					}

					flush();
					ob_flush();
				}
			}
			echo "DONE";
			mysql_close($conn);
		}
		else{
			echo "upload lại file.";
			flush();
			ob_flush();
		}
	}
}
elseif (isset($_GET["country"]) === false) {
	///load web lên nên ko có country
	echo '<!DOCTYPE html>
<html>
<body>
<p>Tạo account lưu trữ và upload old idfa dành cho tool XoaInfo.</p>

<p>
Bạn dùng tool FilterRRS để tạo file xxx_data.txt từ các RRS. <a href="/oldIDFA/file/FilterRRS.rar">FilterRRS.rar</a>
</p>




<form method="post" enctype="multipart/form-data">
    Chọn file xxx_data.txt:
    <input name="fileToUpload" type="file"  id="fileToUpload"><br>

    <input name="username" type="text" placeholder="username"><br>

    <input name="submit" type="submit" value="Upload old IDFA">

</form>


<p>
hoặc
</p>

<p>

</p>

<p>
 
</p>

<form method="post" >

    <input name="username" type="text" placeholder="username"><br>

    <input name="password" type="text" placeholder="password"><br>

    <input name="submitCreate" type="submit" value="Tạo account">
</form>

</body>
</html>';
}
else{/// api lấy oldIDFA
	$country  = $_GET["country"];
	$user = $_GET["username"];
	$pass = $_GET["password"];
	$bundleIDs = $_GET["bundelIDs"];



	// 0 là user ko tồn tại; 2 db public; 1 db private
	if (authenicate($user, $pass) == true) {
		if (userType($user) == 2) {
			$accountID = getAccountIDFromUser($user);
			getOldIDFAPub($country, $accountID, $bundleIDs);
		}
		elseif (userType($user) == 1) {
			
		}
		else{
			header("HTTP/1.1 402 Not Found");
			$arr = array('status' => false, 'mess' => 'User không có db!');

			echo json_encode($arr);
		}
	}
	else{
		header("HTTP/1.1 401 Not Found");
		$arr = array('status' => false, 'mess' => 'User pass Không đúng');

		echo json_encode($arr);
	}
}

function getAccountIDFromUser($user)
{
	$conn = connectDB();	

	$sql = "SELECT `id` FROM `oldDeviceAccount` WHERE `user` = '$user' AND `typeDB` = 2";
	$result = mysql_query( $sql, $conn );
	
	if(mysql_num_rows($result) <= 0){//user ko tồn tại
		return 0;
	}
	else{
		$row = mysql_fetch_array($result) or die(mysql_error());
		return $row['id'];
	}
}

function createAccount($user, $password)
{
	$conn = connectDB();

	$sql = "SELECT `id` FROM `oldDeviceAccount` WHERE `user` = '$user' AND `typeDB` = 2";
	$result = mysql_query( $sql, $conn );
	
	if(mysql_num_rows($result) <= 0){//user ko tồn tại
		$passHash = md5($password);
		$sql = "INSERT INTO `oldDeviceAccount` (`user`, `typeDB`, `hash`) VALUES ('$user', '2', '$passHash')";
		mysql_query( $sql, $conn);

		$sql = "SELECT `id` FROM `oldDeviceAccount` WHERE `user` = '$user' AND `typeDB` = 2";
		$result = mysql_query( $sql, $conn );
		$row = mysql_fetch_array($result) or die(mysql_error());
		flush();
		ob_flush();
	    echo "đã tạo account: $user";
	}
	else{
		$row = mysql_fetch_array($result) or die(mysql_error());
		flush();
		ob_flush();
	    echo "account $user đã tồn tại. tạo account khác hoặc tiếp tục upload vào account này.";
	}
}

function authenicate($user, $passHash)
{
	$conn = connectDB();

	$sql = "SELECT `hash` FROM `oldDeviceAccount` WHERE `user` = '$user'";
	$result = mysql_query( $sql, $conn );
	
	if(mysql_num_rows($result) <= 0){//user ko tồn tại
		return false;
	}
	else{
		$row = mysql_fetch_array($result) or die(mysql_error());
		$hash = $row['hash'];
		if ($hash === $passHash) {//user tồn tại và pass đúng
			return true;
		}
		else{
			return false;
		}
	}
}

function userType($user)// 0 là user ko tồn tại; 2 db public; 1 db private
{
	$conn = connectDB();

	$sql = "SELECT `typeDB` FROM `oldDeviceAccount` WHERE `user` like '$user'";
	$result = mysql_query( $sql, $conn );
	
	if(mysql_num_rows($result) <= 0){//user ko tồn tại
		return 0;
	}
	else{
		$row = mysql_fetch_array($result) or die(mysql_error());
		$typeDB = $row['typeDB'];
		return $typeDB;
	}
}

function getOldIDFAPub($country, $accountID, $bundleIDs)
{
	$conn = connectDB();

	// lấy minRequest, nếu chưa có min cho country này thì tạo country và cho min = 0
	$sql = "SELECT `minRequest` FROM `PUBoldDeviceCountryDeploy` WHERE `country` = '$country' and ownerID = '$accountID'";
	$result = mysql_query( $sql, $conn );
	$minRequest = 0;
	if(mysql_num_rows($result) <= 0){
		$sql = "INSERT INTO `PUBoldDeviceCountryDeploy` (`country`, `minRequest`, `ownerID`) VALUES ('$country', '0', '$accountID')";
		$retval = mysql_query( $sql, $conn );
		if(! $retval )
		{
		  	die('Could not enter data: ' . mysql_error());
		  	mysql_close($conn);
		}
	}
	else{
		$row = mysql_fetch_array($result) or die(mysql_error());
		$minRequest = $row['minRequest'];
	}

	$finalBundleIDs = array();
	$updateBundleIDs = $bundleIDs;
	$updateBundleIDs = str_replace(",,", ",", $bundleIDs);
	$updateBundleIDs = str_replace(" ", "", $updateBundleIDs);
	$updateBundleIDs = str_replace("\\", "", $updateBundleIDs);
	$updateBundleIDsArr = explode(",", $updateBundleIDs);
	$updateBundleIDsArr = array_unique($updateBundleIDsArr);
	foreach ($updateBundleIDsArr as $bundleid) {
		if (strpos($bundleid, "_groupShared") == false && strpos($bundleid, "_pluginkit") == false && strlen($bundleid)>2){
			array_push($finalBundleIDs, $bundleid);
		}
	}

	/// lấy device có minRequest ra, nếu ko có device nào thì tăng con số lên
	$sql = "SELECT `idfa`, `deviceName`, `deviceModel`, `deviceVersion`, `carrierName`, `carrierCountry`, `carrierCountryISO`, `carrierNetwork` FROM `PUBoldDevice` old INNER JOIN PUBoldDeviceBundles bundles ON old.id = bundles.oldDeviceID where `requestCount` <= '$minRequest' AND `country` = '$country' and ownerID = '$accountID' and bundles.bundleID NOT IN( '" . implode( "', '" , $finalBundleIDs ) . "' ) LIMIT 0, 1";

	$result = mysql_query( $sql, $conn );
	if(mysql_num_rows($result) > 0){
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$idfa = $row["idfa"];
			$row["status"] = true;
			$row["deviceModel"] = str_replace('\\', "", $row["deviceModel"]);
			$sql = "UPDATE PUBoldDevice set requestCount=requestCount+1, dateModify=NOW() where idfa='$idfa' and ownerID = '$accountID'";
			$retval = mysql_query( $sql, $conn );
			if(! $retval )
			{
			  	die('Could not enter data: ' . mysql_error());
			  	mysql_close($conn);
			}
			
			$row["status"] = true;
			$row["deviceModel"] = str_replace('\\', "", $row["deviceModel"]);
			
			echo json_encode($row);
		}
	}
	else{
		$sql = "UPDATE `PUBoldDeviceCountryDeploy` set minRequest=minRequest+1 where `country` = '$country' and ownerID = '$accountID'";
		$retval = mysql_query( $sql, $conn );
		if(! $retval )
		{
		  	die('Could not enter data: ' . mysql_error());
			mysql_close($conn);
		}

		$row = array();
		$row["status"] = false;
		echo json_encode($row);

	}

	mysql_close($conn);

}
?>
