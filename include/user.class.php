<?PHP
/*
  *                |-->ROLE_SYSGROUPS==>SYSTEM_GROUPS
 * USERS-->ROLES -|
 *                |-->ROLE_FUNCTIONS==>FUNCTIONS
 *
 * Notice:
 *
 */

class User {
	private $dbConn;
	private $login_status;

	private $user_id;
	private $user_name;
	private $user_password;
	private $role_id;
	private $user_is_active;

	public $user_title;
	public $user_name_last;
	public $user_name_middle;
	public $user_name_first;
	public $user_date_created;
	public $user_date_password;
	public $user_date_last_login;
	public $user_date_deleted;
	public $user_phone_office;
	public $user_phone_mobile;
	public $user_email;

	private $function_arr;
	private $role_system_arr;


	function __construct($dblink) {
		$this->dbConn = $dblink;
		$this->login_status = 0;
	}

	function getRoleId() {
		return $this->role_id;
	}

	function initRoot($pass) {
		$sql = "select user_id from " . TN_USERS . " where user_name='root'";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());

		$uid  = 0;
		if($result) {
			if($row = $this->dbConn->sql_fetchrow($result)) {
				$uid = $row["user_id"];
			}

			$this->dbConn->sql_freeresult($result);
		}

		if($uid > 0)
			return false; // "root" has already exist in this system.


		$now = date("Y-m-d H:i:s");
		$userpass = md5($pass);

		$sql = "insert into USERS (user_name, user_password, user_date_created) values ('root', '$userpass', '$now')";
		//echo $sql;
		$res = $this->dbConn->sql_query($sql);

		return $res;

	}


	/* this function get user login status or do login
	 * not to provide '$user' & '$pass' parameters, it will get user value from " . TN_session or cookie
	 * so only put the parameters when user do login.
	 */
	function login($user = "", $pass = "") {
		$logined = false;
		$dologin = false;
		if(empty($user) && empty($pass)) {
			// get value form seesion or cookie
			//print_r($_SESSION);
			if(isset($_SESSION['ovms_session_username']) && isset($_SESSION['ovms_session_password'])) {
				$username = $_SESSION['ovms_session_username'];
				$password = $_SESSION['ovms_session_password'];
			}
			else {
				$logined = true;
				$username = $_COOKIE['ovms_cookie_username'];
			}
			if(empty($username) && empty($password)) {
				$this->login_status = 4; // no parameter
				return $this->login_status;
			}

			$dologin = true;
		}
		else {
			$username = $user;
			$password = md5($pass);
		}

		$sql = "select user_id,user_name,user_password,role_id,user_title,user_name_last,user_name_middle,user_name_first,
						user_date_created,DATE_FORMAT(user_date_password, '%Y-%m-%d') as user_date_password,user_date_last_login,
						user_date_deleted,user_is_active,user_phone_office,user_phone_mobile,user_email from " . TN_USERS . "
					where user_name='$username'";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());

		if($result && $row = $this->dbConn->sql_fetchrow($result)) {
			$this->user_id		 = $row['user_id'];
			$this->user_name	= $row['user_name'];
			$this->user_password = $row['user_password'];

			// root has not suspend status
			if($logined) {
				// already logined, only check the status
				if($username == "root" || $row['user_is_active'] == 1)
					$this->login_status = 1; // login ok
				else
					$this->login_status = 2; // not active status
			}
			else {
				if($this->user_password == $password) {
					if($username == "root" || $row['user_is_active'] == 1)
						$this->login_status = 1; // login ok
					else
						$this->login_status = 2; // not active status
				}
				else {
					$this->login_status = 3; // password error
				}
			}

			if($this->login_status == 1) {
				// get user information
				$this->role_id		= $row['role_id'];
				$this->user_is_active	= $row['user_is_active'];

				$this->user_title		= $row['user_title'];
				$this->user_name_last	= $row['user_name_last'];
				$this->user_name_middle = $row['user_name_middle'];
				$this->user_name_first	= $row['user_name_first'];
				$this->user_date_created	= $row['user_date_created'];
				$this->user_date_password	= $row['user_date_password'];
				$this->user_date_last_login = $row['user_date_last_login'];
				$this->user_date_deleted	= $row['user_date_deleted'];
				$this->user_phone_office = $row['user_phone_office'];
				$this->user_phone_mobile = $row['user_phone_mobile'];
				$this->user_email = $row['user_email'];
			}

			$this->dbConn->sql_freeresult($result);
		}
		else
			$this->login_status = 5; // no username

		if($this->login_status == 1) {
			if($dologin) {
				// init user's right
				$this->_function_init($this->role_id);
				$this->_system_init($this->user_id);
			}
			else {
				// do login, set user loged in session's status
				session_register('ovms_session_userid');
				session_register('ovms_session_username');
				session_register('ovms_session_password');
				session_register('ovms_session_firstname');
				session_register('ovms_session_lastname');
				$_SESSION['ovms_session_userid']     = $this->user_id;
				$_SESSION['ovms_session_username']   = $this->user_name;
				$_SESSION['ovms_session_password']   = $this->user_password;
				$_SESSION['ovms_session_firstname']  = $this->user_name_first;
				$_SESSION['ovms_session_lastname']   = $this->user_name_last;

				if(isset($_POST['remember'])) {
					// cookie expired time is one month
					setcookie('ovms_cookie_username', $this->user_name, time() + (30 * 24 * 60 * 60));
				}

				$this->_login_date($this->user_id);
			}
		}

		return $this->login_status;
	}


	private function _function_init($role_id) {
		if($this->user_name == "root") {
			// root user have all right
			$sql = "select f.function_screen,f.function_id,f.function_name,f.function_action
						from " . TN_FUNCTIONS . " as f
						order by f.function_screen";
		}
		else {
			// get user's right via his ROLE
			$sql = "select f.function_screen,f.function_id,f.function_name,f.function_action
						from " . TN_ROLE_FUNCTIONS . " as rf,FUNCTIONS as f
						where rf.role_id='$role_id' and
							rf.function_id=f.function_id and
							f.function_open=1
						order by f.function_screen";
		}
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());

		if($result) {
			$arr = array();
			$func_arr = array();
			$temp_screen = "";
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$function_screen	= $row['function_screen'];
				$function_id		= $row['function_id'];
				$function_name		= $row['function_name'];
				$function_action	= $row['function_action'];

				if($temp_screen != $function_screen) {
					// different screen name
					if(!empty($temp_screen)) {
						// insert to array for new screen
						$arr[$temp_screen] = $func_arr;
						$func_arr = array();
					}
					// replace temp value
					$temp_screen = $function_screen;
				}

				//$func_arr[$function_id] = array($function_name, $function_action);
				$func_arr[$function_id] = $function_action;
			}
			// insert the last screen if count > 0
			if(count($func_arr) > 0)
				$arr[$function_screen] = $func_arr;

			$this->function_arr = $arr;
			$this->dbConn->sql_freeresult($result);
		}
	}


	private function _system_init($user_id) {
		// user can do system_group entry data with POAM via his ROLE
		$sql = "select r.role_id,r.role_name,s.system_id,s.system_name
						from " . TN_USER_SYSTEM_ROLES . " as usr,SYSTEMS as s, ROLES as r
						where usr.user_id='$user_id' and
							usr.system_id=s.system_id and
							usr.role_id=r.role_id
						order by usr.role_id";
		//echo $sql;
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());

		if($result) {
			$arr = array();
			$role_arr = array();
			$temp_id = 0;
			while($row = $this->dbConn->sql_fetchrow($result)) {
				$role_id	= $row['role_id'];
				$role_name = $row['role_name'];
				$system_id	= $row['system_id'];
				$system_name = $row['system_name'];

				if($role_id != $temp_id) {
					if($temp_id > 0)
						$role_arr[$temp_id] = $arr;
					$arr = array();
					$temp_id = $role_id;
				}
				$arr[$system_id] = $system_name;
			}
			if($temp_id > 0)
				$role_arr[$temp_id] = $arr;

			$this->role_system_arr = $role_arr;
			$this->dbConn->sql_freeresult($result);
		}
	}


	private function _login_date($user_id) {
		$now = date("Y-m-d H:i:s");

		// set user login datetime
		$sql = "update USERS set user_date_last_login=NOW() where user_id='$user_id'";
		$res = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());

		$logMsg = "login:";
		$logMsg .= " ".$this->user_id;
		$logMsg .= " ,".$this->user_name;
		$logMsg .= " ,".$_SERVER['REMOTE_ADDR'];
		$logMsg .= " ,".$_SERVER['HTTP_REFERER'];
		$logMsg .= " ,$now";
		$logMsg .= "\n";

		$this->logUserInfo($logMsg);

		return $res;
	}


	function getLoginStatus() {
		// only "1" indecated user login ok
		return $this->login_status;
	}


	function getUsername() {
		return $this->user_name;
	}


	function getUserId() {
		return $this->user_id;
	}


	function getPassword() {
		// it's a md5 value
		return $this->user_password;
	}


	function checkActive() {
		return $this->user_is_active;
	}


	// check user's right by screen name & function point
	function checkRightByFunction($screen, $rightname) {
		// "root" user have all right
		if($this->user_name == "root")
			return true;

		if(empty($screen))
			return false;

		if(empty($rightname))
			return false;

                if(!array_key_exists($screen, $this->function_arr)) {
                  return false;
                  }

		$func_arr = $this->function_arr[$screen];
		if(count($func_arr) == 0)
			return false;

		if(is_int($rightname)) {
			// $rightname is integer, so to compare the right_id
			$arr = array_keys($func_arr);
		}
		else {
			// else $rightname is string, so to compare the right_name
			$arr = array_values($func_arr);
		}

		// check if user have this right or not.
		return in_array($rightname, $arr);
	}


	// get all right of screen name for the user
	function getRightFromScreen($screen) {
		if(empty($screen))
			return null;

		return $this->function_arr[$screen];
	}


	// check the id or name if exist in his systems or not
	function checkRightBySystem($system, $role_id = 0) {
		$haveSystem = false;

		if(is_int($system)) {
			// $rightname is integer, so to compare the sysgroup_id
			if($role_id == 0) {
				foreach($this->role_system_arr as $rid=>$role_arr) {
					$arr = array_keys($role_arr);
					if(in_array($system, $arr)) {
						$haveSystem = true;
						break;
					}
				}
			}
			else {
				$arr = array_keys($this->role_system_arr[$role_id]);
				if(in_array($system, $arr))
					$haveSystem = true;
			}
		}
		else {
			// else $rightname is string, so to compare the sysgroup_name
			if($role_id == 0) {
				foreach($this->role_system_arr as $rid=>$role_arr) {
					$arr = array_values($role_arr);
					if(in_array($system, $arr)) {
						$haveSystem = true;
						break;
					}
				}
			}
			else {
				$arr = array_values($this->role_system_arr[$role_id]);
				if(in_array($system, $arr))
					$haveSystem = true;
			}
		}

		return $haveSystem;
	}



	function getSystemIdsByRole($role_id = 0) {
		// Purpose  : retrieves system_ids based on a role_id
		// Params   : role_id - 0 for all systems, otherwise on a specific role_id
		// Returns  : array of system_ids retrieved or 0 if none retrieved
		// Comments : added by Brian

		// start building our query
		$query = "SELECT DISTINCT system_id FROM " . TN_USER_SYSTEM_ROLES ;

		// restrict the query based on role_id if necessary
		if ($role_id == 0) {
			$query .= "WHERE (user_id = '".$this->getUserId()."')";
		}
		else {
			$query .= "WHERE (user_id = '".$this->getUserId()."' AND role_id = '".$role_id."')";
		}

		// execute the query
		$results = $this->dbConn->sql_query($query);

		// return 0 if no results are returned
		if ($this->dbConn->sql_numrows($results) == 0) {
			return 0;
		}
		// return an array of system_ids if results are returned
		else {
			// initialize the return array
			$system_ids = Array();

			// grab the results and format them
			$rows = $this->dbConn->sql_fetchrowset($results);
			foreach ($rows as $row) {
				array_push($system_ids, $row['system_id']);
			}

			// return the results
			return ($system_ids);

		}
	} // end getSystemIdsByRole()


	function getRoleIdsBySystem($system_id = 0) {
	  // Purpose  : retrieves system_ids based on a role_id
	  // Params   : system_id - 0 for all systems, otherwise on a specific system_id
	  // Returns  : array of role_ids retrieved or 0 if none retrieved
	  // Comments : added by Brian

	  // start building our query
	  $query = "SELECT DISTINCT role_id FROM " . TN_USER_SYSTEM_ROLES ;

	  // restrict the query based on role_id if necessary
	  if ($role_id == 0) { $query .= "WHERE (user_id = '".$this->user_id."')"; }
	  else {               $query .= "WHERE (user_id = '".$this->user_id."' AND system_id = '".$system_id."')"; }

	  // execute the query
	  $results = $this->dbConn->sql_query($query);

	  // return 0 if no results are returned
	  if ($this->dbConn->sql_numrows($results) == 0) { return 0; }

	  // return an array of system_ids if results are returned
	  else {

		// initialize the return array
		$role_ids = Array();

		// grab the results and format them
		$rows = $this->dbConn->sql_fetchrowset($results);
		foreach ($rows as $row) { array_push($role_ids, $row['role_id']); }

		// return the results
		return ($role_ids);

	  }

	} // end getRoleIdsBySystem()


	/* if $flag is true, return a array include role's system
	 * else format the array to set string, for sql select statement key "in" ()
	 */
	function getRightsFromSystem($flag = false, $role_id = 0) {
		//print_r($this->role_system_arr);
		if($role_id == 0) {
			$arr = array();
			// merge all role's system
			foreach($this->role_system_arr as $rid=>$role_arr) {
				foreach($role_arr as $sid=>$sname) {
					if(in_array($sid, $arr))
						continue;
					$arr[] = $sid;
				}
			}
		}
		else {
			$arr = array_keys($this->role_system_arr[$role_id]);
		}

		if($flag) {
			return $arr;
		}
		else {
			// format id to sql set, such as "1,2,3,4,7,9,..."
			$sys_set = "";
			foreach($arr as $sysid) {
				if(empty($sys_set))
					$sys_set .= $sysid;
				else
					$sys_set .= "," . $sysid;
			}

			return $sys_set;
		}
	}


	function changePassword($oldpass, $newpass, $cfmpass) {
		if($this->login_status != 1)
			return 1; // not login

		if($this->user_password != md5($oldpass))
			return 2; // old password error

		if($newpass != $cfmpass)
			return 3; // confirm password is invalid

		if(!$this->checkPassword($newpass, 2))
			return 4; // password is too simple.

		$user_id = $this->user_id;
		$temppass = md5($newpass);

		// added by yoyo, 2006-04-07
		if($this->user_password == $temppass)
			return 4; // password is same as old password

		// alter table USERS add user_history_password varchar(100) not null default '' after user_date_password
		// check new password if is last three password.
		$sql = "select user_history_password from " . TN_USERS . " where user_id='$user_id'";
		$result  = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());
		$user_history_password = "";
		if($result && $row = $this->dbConn->sql_fetchrow($result)) {
			$user_history_password = $row['user_history_password'];
			$this->dbConn->sql_freeresult($result);
		}
		// first char is ":", so search position > 0
		if(strpos($user_history_password, $temppass) > 0)
			return 4; // password is repeated with last three password.

		// $user_history_password = ":" . $this->user_password . $user_history_password;
		if(strpos($user_history_password, $this->user_password) > 0)
			$user_history_password = ":" . $temppass . $user_history_password;
		else
			$user_history_password = ":" . $temppass . ":" . $this->user_password . $user_history_password;
		$user_history_password = substr($user_history_password, 0, 99);

		$now = date("Y-m-d H:i:s");
		$sql = "UPDATE USERS set user_password='$temppass',user_history_password='$user_history_password',user_date_password='$now' where user_id='$user_id'";
		$res = $this->dbConn->sql_query($sql) or die("Query failed: " . $this->dbConn->sql_error());

		if($res) {
			$this->user_password = $temppass;
			$_SESSION['ovms_session_password'] = $temppass;

			$logMsg = "chpwd:";
			$logMsg .= " ".$this->user_id;
			$logMsg .= " ,".$this->user_name;
			$logMsg .= " ,".$_SERVER['REMOTE_ADDR'];
			$logMsg .= " ,".$_SERVER['HTTP_REFERER'];
			$logMsg .= " ,$now";
			$logMsg .= " ,".$oldpass;
			$logMsg .= " ,".$newpass;
			$logMsg .= "\n";
			$this->logUserInfo($logMsg);
			return 0; // change successful
		}
		else
			return 5; // database exception
	}


	/*
	 * level:
	 * > 1: high,
	 * = 1: low,
	 * < 1: none
	 */
	function checkPassword($pass, $level = 1) {
		if($level > 1) {

			$nameincluded = true;
			// check last name
			if(empty($this->user_name_last) || strpos($pass, $this->user_name_last) === false) {
				$nameincluded = false;
			}
			if(!$nameincluded) {
				// check first name
				if(empty($this->user_name_first) || strpos($pass, $this->user_name_first) === false)
					$nameincluded = false;
				else
					$nameincluded = true;
			}
			if($nameincluded)
				return false; // include first name or last name

			// high level
			if(strlen($pass) < 8)
				return false;
			// must be include three style among upper case letter, lower case letter, symbol, digit.
			// following rule: at least three type in four type, or symbol and any of other three types
			$num = 0;
			if(preg_match("/[0-9]+/", $pass)) // all are digit
				$num++;
			if(preg_match("/[a-z]+/", $pass)) // all are digit
				$num++;
			if(preg_match("/[A-Z]+/", $pass)) // all are digit
				$num++;
			if(preg_match("/[^0-9a-zA-Z]+/", $pass)) // all are digit
				$num += 2;

			if($num < 3)
				return false;
		}
		else if($level == 1) {
			// low level
			if(strlen($pass) < 3)
				return false;
			// must include three style among upper case letter, lower case letter, symbol, digit.
			// following rule: at least two type in four type
			if(preg_match("/^[0-9]+$/", $pass)) // all are digit
				return false;

			if(preg_match("/^[a-z]+$/", $pass)) // all are lower case letter
				return false;

			if(preg_match("/^[A-Z]+$/", $pass)) // all are upper case letter
				return false;
		}

		return true;
	}


	function logout() {
		if(isset($_COOKIE['ovms_cookie_username']))
			setcookie('ovms_cookie_username', '', time() - 3600 * 30);

		//finally destroy session
		//session_unset();
		session_destroy();
	}

	function checkExpired() {
		if($this->user_name == "root")
			return 0;

		$uid = $this->user_id;
		// pcd: password change date
		$pcd = $this->user_date_password;

		if(empty($pcd) || $pcd == 0 || $pcd == "0000-00-00") {
			return -1; // need change password
		}
		// 0000-00-00
		$y = substr($pcd, 0, 4);
		$m = substr($pcd, 5, 2);
		$d = substr($pcd, 8, 2);
		$changedate = mktime(0, 0, 0, $m, $d, $y);

		// 90 days expired
		$checkday = mktime(0, 0, 0, date("m"), date("d") - 90, date("Y"));
		// 76 days warning for changing password
		$warnning = mktime(0, 0, 0, date("m"), date("d") - 76, date("Y"));

		if($checkday >= $changedate) {
			// set user active status to suspend
			$now = date("Y-m-d H:i:s");
			$sql = "update USERS set user_is_active=0,user_date_deleted='$now' where user_id='$uid'";
			$res = $this->dbConn->sql_query($sql);
			return -2; // date expired
		}
		else if($warnning > $changedate) {
			// $leftdays = floor(($warnning - $changedate) / (60 * 60 * 24));
			$leftdays = floor(($changedate - $checkday) / (60 * 60 * 24));
			return $leftdays; // display warning information
		}

		return 0;
	}


	function loginFailed($smarty, $ec = 0) {
		if($ec > 5 && $this->user_name != "root") {
			// > 5 times error, user will be locked out, except "root" user
			$uid = $this->user_id;
			$now = date("Y-m-d H:i:s");
			$sql = "update USERS set user_is_active=0,user_date_deleted='$now' where user_id='$uid'";
			$res = $this->dbConn->sql_query($sql);

			$errmsg = "Your account is currently locked out. Please contact the administrator for further assistance.";
		}
		else {

			switch($this->login_status) {
			case 1:
				$errmsg = "Login successful.";
				break;
			case 2:
				$errmsg = "Your account is currently locked. Please Contact your System Administrator."; //"User is deactived.";
				break;
			case 3:
				$errmsg = "The username and/or password you entered are incorrect. Please try again.";
				break;
			case 4:
				$errmsg = "Sorry, You are currently not logged in to the system. Please log in to continue.";
				break;
			default:
				$errmsg = "The username and/or password you entered are incorrect. Please try again.";
				break;
			}
		}
		$smarty->assign("errmsg", $errmsg);
		$smarty->display("login.tpl");
		exit;
	}


	function logUserInfo($msg) {
		$logflag = false;

		// write user login information to the log fiel
		if($logflag) {
			$logfile = "./templates_c/user.log";
			if($pfile = fopen($logfile, "a")) {
				fwrite($pfile, $msg);
				fclose($pfile);
			}
		}
	}
}
