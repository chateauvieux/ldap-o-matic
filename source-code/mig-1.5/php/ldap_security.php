<script language="php">


function normalize_dn ($dn)
{
	$arr = ldap_explode_dn ($dn, 0);
	$str = "";
	
	for ($i = 0; $i < $arr["count"]; $i++)
	{
		if ($i > 0) $str .= ",";

		$str .= $arr[$i];
	}
	return $str;
}


function lookup_admin($conn_id, $user, $password)
{
	// should be connected as admin

	$filter = "(&" . USERS_SEARCHFILTER . "(MIGCategory=MIGadmin)(uid=$user))";
    
	$sr = ldap_search ($conn_id, USERS_BASE_DIR, $filter, array());
	$result = ldap_get_entries ($conn_id, $sr);
	
	if (ldap_count_entries($conn_id, $sr) == 1) 
	{	
		if (!ldap_bind($conn_id, $result[0]["dn"], $password))
			return false;
		
		$returnarr["USER_UID"] = normalize_dn ($result[0]["dn"]);
		$returnarr["ADMIN"] = true;
		return $returnarr;
	}
	else return false;
}


function lookup_user($conn_id, $user, $password)
{		
	$filter = "(&" . USERS_SEARCHFILTER . "(uid=$user)(|(MigCategory=MIGuser)(MigCategory=MIGadmin)))";
	$sr = ldap_search ($conn_id, USERS_BASE_DIR, $filter, array("migcategory"));
	$result = ldap_get_entries ($conn_id, $sr);

	if (ldap_count_entries($conn_id, $sr) == 1) 
	{
		if (!ldap_bind($conn_id, $result[0]["dn"], $password))
			return false;
		
		$returnarr["USER_UID"] = normalize_dn ($result[0]["dn"]);
		
		for ($i = 0;$i < $result[0]["migcategory"]["count"]; $i++)
			if ($result[0]["migcategory"][$i] == "MIGadmin") 
			{ 
				$returnarr["ADMIN"] = true;
				break;
			}
		return $returnarr;
	}
	else return false;
}

function connect_for_authentication()
{
	if (($conn_id = ldap_connect(AUTH_HOSTNAME)) == false) 
		die ("<h3>Unable to connect to " . AUTH_HOSTNAME . "</h3>");
	if (!ldap_bind($conn_id, AUTH_MANAGER_UID, AUTH_MANAGER_PW))
	{
		die ("<h3>Unable to bind as a manager to host</h3>");
		return false;
	}
	return ($conn_id);
}

function disconnect_authentication($conn_id)
{
	return ldap_unbind($conn_id);
}

function connect_to_authentication_system()
{
	if (($conn_id = ldap_connect(AUTH_HOSTNAME)) == false) 
		die ("<h3>Unable to connect to " . AUTH_HOSTNAME . "</h3>");
	if (!ldap_bind($conn_id, AUTH_MANAGER_UID, AUTH_MANAGER_PW))
	{
		die ("<h3>Unable to bind as a manager to host</h3>");
		return false;
	}
	return ($conn_id);
}

function set_password($conn_id, $uid, $password)
{
	$updmod["userPassword"] = $password;
	
	$filter = "(&" . USERS_SEARCHFILTER . "(uid=$uid))";
    
	$sr = ldap_search ($conn_id, USERS_BASE_DIR, $filter, array());
	$result = ldap_get_entries ($conn_id, $sr);
	
	if (ldap_count_entries($conn_id, $sr) == 1) 
	{	
		$dn = $result[0]["dn"];
	
		$ret =  ldap_mod_replace($conn_id, $dn, $updmod);
		if ($ret == false)
		{
			die ("<h3>Internal error : Unable to set password for $uid</h3>");
			return false;
		}
		else return true;
	}
	else die ("<h3>Unable to set password for $uid: Ambiguous UID</h3>");
}


function set_hint_question_and_answer($conn_id, $uid, $question, $answer)
{
	$updmod["MigChallengeQuestion"] = $question;
	$updmod["MigChallengeQuestionAnswer"] = $answer;
	$filter = "(&" . USERS_SEARCHFILTER . "(uid=$uid))";
    
	$sr = ldap_search ($conn_id, USERS_BASE_DIR, $filter, array());
	$result = ldap_get_entries ($conn_id, $sr);
	
	if (ldap_count_entries($conn_id, $sr) == 1) 
	{	
		$dn = $result[0]["dn"];
	
		$ret =  ldap_mod_replace($conn_id, $dn, $updmod);
		if ($ret == false)
		{
			die ("<h3>Internal error : Unable to set hint question and answer for $uid</h3>");		
			return false;
		}
		else return true;
	}
	else die ("<h3>Unable to set hint question and answer for $uid: Ambiguous UID</h3>");	
}

</script>