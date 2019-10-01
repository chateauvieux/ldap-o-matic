<script language="php">

	require "php/ldap_security.php";
	
	session_save_path(MIG_SESSION_PATH);
	session_start();

unset ($uid); if (isset ($_POST["uid"])) $uid = $_POST["uid"]; else if (isset($_GET["uid"])) $uid = $_GET["uid"];
unset ($password); if (isset ($_POST["password"])) $password = $_POST["password"]; else if (isset($_GET["password"])) $password = $_GET["password"];

	
	function authenticate()
	{		
		if (!isset($_COOKIE[session_name()]))
		{
			// set session_id in cookie for not propagating the URL
			setcookie (session_name(), session_id(), 0, "/");
			
			// Alternate Key
			srand((double)microtime()*1000000);
			$unique_str = md5(rand(0,9999999));
			setcookie ("AlternateKey", $unique_str, "", "/");
			$_SESSION["ALTERNATE_KEY"] = $unique_str;
		}
		
		// temporary assign a logging uid
		session_register("USER_LOGINNAME");
		session_register("USER_PW");
		session_register("REMOTE_IP");
		session_register("TIMESTAMP");
		session_register("ALTERNATE_KEY");

		// initialize session variables
//		global $USER_LOGINNAME, $TIMESTAMP, $SCRIPT_NAME, $QUERY_STRING;
		$_SESSION["USER_LOGINNAME"] = "MIG_auth";
		$_SESSION["TIMESTAMP"] = time(); 

		// display the login page
		include "login.html";
		exit;	
	}
	
	function free_security_cookie()
	{
		setcookie (session_name(), "", time() - 3600, "/");
		setcookie ("AlternateKey", "", time() - 3600, "/");
	}
	
	if (!session_is_registered("USER_LOGINNAME") || !session_is_registered("USER_PW"))
	{
		authenticate();
	}
	else
	{
		if (session_is_registered("USER_LOGINNAME") && session_is_registered("USER_PW"))
		{
//			global $TIMESTAMP;
			if (time() - $_SESSION["TIMESTAMP"] < MIG_SESSION_TIMEOUT)
			{
//				global $USER_LOGINNAME, $USER_PW, $REMOTE_IP, $ALTERNATE_KEY;			 
				if (($_SESSION["USER_LOGINNAME"] == "MIG_auth") || (!session_is_registered("ADMIN")))
				{
					$conn_id = connect_for_authentication();
					
					if (!isset($uid) || !isset($password))
					{
						authenticate();
					}
					
					else if (($info = lookup_admin($conn_id, $uid, $password)) == false)
					{
						free_security_cookie();
						session_unset();
						session_destroy();
						include "error_401.html";
						exit;
					}
					else 
					{
						// keep uid, password and IP in session
						$_SESSION["USER_LOGINNAME"] = $info["UID"];
						$_POST["uid"] = $_SESSION["USER_LOGINNAME"];

						$_SESSION["USER_PW"] = $password;

						if (getenv("HTTP_X_FORWARDED_FOR")) $_SESSION["REMOTE_IP"] = getenv("HTTP_X_FORWARDED_FOR");
						else $_SESSION["REMOTE_IP"] = $_SERVER["REMOTE_ADDR"];

						// keep Alternate key
						$_SESSION["ALTERNATE_KEY"] = $_COOKIE["AlternateKey"];

						unset ($info["UID"]);

						// register new variables and define grants
						for (reset($info); $i = key($info); next($info))
						{
							session_register($i);
//							global $$i;
							$_SESSION[$i] = $info[$i];
							define ($i, $info[$i]);
						}
					}
					disconnect_authentication($conn_id);
				}
			
				else
				{
//					global $REMOTE_IP;
					
					$kickout = false;
					if (isset($_COOKIE["AlternateKey"]))
					{
						if ($_COOKIE["AlternateKey"] != $_SESSION["ALTERNATE_KEY"])
						{
							$kickout = true;
						}
					}
					else
					{ 
						if (getenv("HTTP_X_FORWARDED_FOR"))
						{
							if (!in_array($_SESSION["REMOTE_IP"], explode(",", getenv("HTTP_X_FORWARDED_FOR"))))
//					if (getenv("HTTP_X_FORWARDED_FOR") && ($REMOTE_IP != getenv("HTTP_X_FORWARDED_FOR")))
								$kickout = true;
						}						
						else if (($_SESSION["REMOTE_IP"] != $_SERVER["REMOTE_ADDR"]))
						{
							$kickout = true;
						}		
					}						
					if ($kickout)
					{
						trigger_error("MIG error at " . MIG_INSTALLATION_NAME . " : Security violation: session information does not match key/IP,  ###", E_USER_ERROR);
						free_security_cookie();
						include "error_401.html";							
						exit;
					}
				}
				
				// update timestamp
				$_SESSION["TIMESTAMP"] = time();

				$defarr = array ("USER_UID", "ADMIN", "USER_PW", "USER_LOGINNAME");
				// register new variables and define grants
				for ($i = 0; $i < sizeof($defarr); $i++)
				{
					if (session_is_registered($defarr[$i]))
					{
//						global ${$defarr[$i]};
						if (!defined($defarr[$i])) define ($defarr[$i], $_SESSION[$defarr[$i]]);
					}
				}
							
			}
			else
			{
				include "error_timeout.html";				
				exit;
			}
		}
		else 
		{
			authenticate();
		}
	}
</script>