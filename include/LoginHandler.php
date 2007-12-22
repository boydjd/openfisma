<?PHP

include_once("LoginDBManager.php");

class LoginHandler {
	public $dbManager;
	public $userInfo;

	function __construct() {
		$this->dbManager = new LoginDBManager;
	}

	function login() {
		if(isset($_COOKIE['userName'])) {
			$user = $_COOKIE['userName'];
			$loggedIn = true;
		}
		else {
			$user   = $_POST['user'];
			$passwd = $_POST['passwd'];
			//Check for SQL injection TO BE DONE
			$loggedIn = $this->dbManager->login($user, $passwd);
		}

		if($loggedIn == 1) { // User logged in okay.
			// session_start();
			$this->getUserInfo($user);

			session_register('session_userName');
			session_register('session_userID');
			session_register('session_orgID');
			session_register('session_firstName');
			session_register('session_lastName');
			$_SESSION['session_userName']   = $this->usrInfo['username'];
			$_SESSION['session_userID']     = $this->usrInfo['userID'];
			$_SESSION['session_orgID']      = $this->usrInfo['organizationID'];
			$_SESSION['session_firstName']  = $this->usrInfo['firstName'];
			$_SESSION['session_lastName']   = $this->usrInfo['lastName'];

			if(isset($_POST['remember'])) {
				// cookie expired time is one month
				setcookie('userName', $this->usrInfo['username'], time() + (30 * 24 * 60 * 60));
			}
			
			$logMsg = $this->usrInfo['username'];
			$logMsg .= " ,".$this->usrInfo['organizationID'];
			$logMsg .= " ,".$_SERVER['REMOTE_ADDR'];
			$logMsg .= " ,".$_SERVER['HTTP_REFERER'];
			$logMsg .= " ,".date("Y-m-d H:m:s");// date("D M j G:i:s T Y");
			$logMsg .= "\n";

			$this->logUserLoginInfo($logMsg);
			
		}
		else if($loggedIn == 2) //user logged in from inactive organization
		{
			// $str = "Location: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) .
			//	"/OrganizationInactive.php";
			$str = "Location: OrganizationInactive.php";
			header($str);
			flush();
			// echo($str);
			die;
		}
	
		return $loggedIn;
	}

	function getUserInfo($user) {
		$this->userInfo = $this->dbManager->getUserInfo($user);
	}


	function changepassword($username, $oldpass, $newpass, $cfmpass) {
		if(!isset($username))
			return 1; // not login

		if($newpass != $cfmpass)
			return 2; // confirm password is invalid

		$loggedIn = $this->dbManager->login($username, $oldpass);
		if($loggedIn != 1)
			return 3;

		$res = $this->dbManager->changepwd($username, $newpass);
		if($res) {
			$logMsg = $username;
			$logMsg .= " ,".$_SESSION['session_orgID'];
			$logMsg .= " ,".$_SERVER['REMOTE_ADDR'];
			$logMsg .= " ,".$_SERVER['HTTP_REFERER'];
			$logMsg .= " ,".date("Y-m-d H:m:s");// date("D M j G:i:s T Y");
			$logMsg .= " ,".$oldpass;
			$logMsg .= " ,".$newpass;
			$logMsg .= "\n";
			$this->logUserLoginInfo($logMsg);

			return 0; // change successful
		}
		else
			return 4; // database exception
	}

	function logUserLoginInfo($msg) {
		$logfile = "./templates_c/user.log";
		if($pfile = fopen($logfile, "a")) {
			fwrite($pfile, $msg);
			fclose($pfile);
		}
	}


	function __destruct() {
	// final stuff
	}
}
?>
