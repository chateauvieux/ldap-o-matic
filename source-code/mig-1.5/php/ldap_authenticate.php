<script language="php">

	require "php/ldap_security.php";

	session_save_path(MIG_SESSION_PATH);
	session_start();

	function authenticate()
	{		
		if (!isset($HTTP_COOKIE_VARS[session_name()]))
		{
			// set session_id in cookie for not propagating the URL
			setcookie (session_name(), session_id(), "", "/");
			
			// Alternate Key
			srand((double)microtime()*1000000);
			$unique_str = md5(rand(0,9999999));
			setcookie ("AlternateKey", $unique_str, "", "/");
		}
		
		// temporary assign a logging uid
		session_register("USER_LOGINNAME");
		session_register("USER_PW");	
		session_register("REMOTE_IP");
		session_register("TIMESTAMP");
		session_register("ALTERNATE_KEY");

		// initialize session variables
		global $USER_LOGINNAME, $TIMESTAMP, $SCRIPT_NAME, $QUERY_STRING;
		$USER_LOGINNAME = "MIG_auth";
		$TIMESTAMP = time(); 

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
			global $TIMESTAMP;
			if (time() - $TIMESTAMP < MIG_SESSION_TIMEOUT)
			{
				global $USER_LOGINNAME, $USER_PW, $REMOTE_IP, $ALTERNATE_KEY;			 
				if ($USER_LOGINNAME == "MIG_auth") 
				{
					$conn_id = connect_for_authentication();

					if (!isset($uid) || !isset($password))
					{
						authenticate();
					}
					else if (($info = lookup_user($conn_id, $uid, $password)) == false)
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
						$USER_LOGINNAME = $uid;
						$USER_PW = $password;

						if (getenv("HTTP_X_FORWARDED_FOR")) $REMOTE_IP = getenv("HTTP_X_FORWARDED_FOR");
						else $REMOTE_IP = $REMOTE_ADDR;
						
						// keep Alternate key
						$ALTERNATE_KEY = $HTTP_COOKIE_VARS["AlternateKey"];
		
						// register variables defined by lookup_user (for now : UID, ADMIN) and define grants (USER_LOGINNAME, USER_PW)
						for (reset($info); $i = key($info); next($info))
						{
							session_register($i);
							global $$i;
							$$i = $info[$i];
							define ($i, $info[$i]);
						}
					}
					disconnect_authentication($conn_id);	
				
				}
				else
				{
					global $REMOTE_IP;
				
					$kickout = false;
					if (isset($HTTP_COOKIE_VARS["AlternateKey"]))
					{
						if ($HTTP_COOKIE_VARS["AlternateKey"] != $ALTERNATE_KEY)
						{
							$kickout = true;
						}
					}
					else
					{
						if (getenv("HTTP_X_FORWARDED_FOR"))
						{
							if (!in_array($REMOTE_IP, explode(",", getenv("HTTP_X_FORWARDED_FOR"))))
//					if (getenv("HTTP_X_FORWARDED_FOR") && ($REMOTE_IP != getenv("HTTP_X_FORWARDED_FOR")))
								$kickout = true;
						}						
						else if (($REMOTE_IP != $REMOTE_ADDR) && !(isset($HTTP_COOKIE_VARS["AlternateKey"]) && ($HTTP_COOKIE_VARS["AlternateKey"] == $ALTERNATE_KEY)))
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
				$TIMESTAMP = time();
				
				$defarr = array ("USER_UID", "ADMIN", "USER_PW", "USER_LOGINNAME");
				// register new variables and define grants
				for ($i = 0; $i < sizeof($defarr); $i++)
				{
					if (session_is_registered($defarr[$i]))
					{
						global ${$defarr[$i]};
						define ($defarr[$i], $$defarr[$i]);
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