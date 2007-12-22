<?PHP

class LoginDBManager {
	private $usrInfo = null;
	//Ctor
	function __construct() {
	}

	function __destruct() {
	}

	function login($user, $password) {
		global $db;

		$dbConn = $db;
		$tempuser = addslashes($user);
		$temppass = md5($password);
		$sql = "SELECT user_is_active as uia FROM users  WHERE user_title='$tempuser' AND user_password='$temppass'";
		//echo $sql;
		$result  = $dbConn->sql_query($sql); // or die("Query failed: " . mysql_error());
		if($result) {
			$row = $dbConn->sql_fetchrow($result);
			$dbConn->sql_freeresult($result);
			$uia = $row['uia'];
			if($uia == 1)
				return 1;
			else
				return 0;
		}
		return 2;
	}


	function changepwd($user, $password) {
		global $db;
		$dbConn = $db;
		$tempuser = addslashes($user);
		$temppass = addslashes($password);

		$sql = "UPDATE users set user_password='$temppass' where user_title='$tempuser'";
		//echo $sql;
		$result  = $dbConn->sql_query($sql);// or die("Query failed: " . mysql_error());

		return $result;
	}

	function getUserInfo($user) {
		global $db;
		$dbConn = $db; 

		$tempuser = addslashes($user);
		$sql = "SELECT * FROM users WHERE user_title='$tempuser'";
		$result  = $dbConn->sql_query($sql);
		$usrInfo = $dbConn->sql_fetchrowset($result);

		return $usrInfo;
	}
}
?>
