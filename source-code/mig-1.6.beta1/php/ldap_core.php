




<?php

if (!defined("OBJECTMODEL")) require "php/objectmodel.php";

Class core extends objectmodel
{
	var $class_count = 0;
	var $attribute_count = 0;
	var $user_count = 0;
	var $group_count = 0;
	var $subsection_count = 0;


	function core($conn_id = "")
	{
		$this->conn_id = $conn_id;
	}


############################################################################################
# RETRIEVING INFO FROM THE LOWER WORLD
############################################################################################

	function override_config(&$config, $user_config, $what)
	{
		// Security enhancement for PHP 4.1 and higher
		// Compatibility with systems having register_globals turned off (default in PHP 4.2 and higher)
		// Compatibility for systems having variables_order equal to EGPCS and EPGCS

		//unset ($group); if (isset ($_POST["group"])) $group = $_POST["group"]; else if (isset($_GET["group"])) $group = $_GET["group"];

		if (isset($config["count"]) && isset($user_config["count"]))
		{
			for ($i = 0; $i < $config["count"]; $i++)
			{
				for ($j = 0; $j < $user_config["count"]; $j++)
				{
					if ($config[$i]["migattributename"][0] == $user_config[$j]["migattributename"][0])
					{
						for ($k = 0; $k < sizeof($what); $k++)
							$config[$i][$what[$k]] = $user_config[$j][$what[$k]];
						break;
					}
				}
			}
			return true;
		}
		return $this->quit_on_error("Unable to override config");
	}


	
	function read_user($uid, $result_array = "everything")
	{
		$what = "(&" . USERS_SEARCHFILTER . "(uid=$uid))";
		
		if ($result_array == "everything") 	$sr = $this->search(USERS_BASE_DIR, $what);
		else $sr = $this->search(USERS_BASE_DIR, $what, $result_array);
		
		if ($sr["count"] == 1) return $sr[0];
		else return $this->quit_on_error("Unable to read user $uid: Ambiguous identifier");
	}

	function read_user_list($type, $result_array = "everything")
	{
		$searchbase = USERS_BASE_DIR;
			
		if ($type == "active")
		{
			$filter = "(&" . USERS_SEARCHFILTER . "(|(MIGcategory=MIGuser)(MIGcategory=MIGadmin)))";
		}
		elseif ($type == "inactive")
		{
			$filter = "(&" . USERS_SEARCHFILTER . "(!(MIGcategory=MIGuser))(!(MIGcategory=MIGadmin)))";
		}
		else return $this->quit_on_error("Programming error !!");

		if ($result_array == "everything") $sr = $this->listing ($searchbase, $filter);
		else $sr = $this->listing ($searchbase, $filter, $result_array);
		
		$this->user_count = $sr["count"];
		
		return $sr;

	}

	function read_attribute($which, $type, $result_array = "")
	{
		if ($type == "system")		
			$searchbase = "migattributename=$which, ou=systemdefaults, " . SCHEMA_BASE_DIR;		

		elseif ($type == "user")
			$searchbase = "migattributename=$which, uid=" . USER_LOGINNAME . ", ou=userdefaults, " . SCHEMA_BASE_DIR;

		else return $this->quit_on_error("Programming error !!");

		$filter = "(objectclass=MIGAttribute)";
		
		if ($result_array != "") return $this->read($searchbase, $filter, $result_array);
		else return $this->read($searchbase, $filter);
	}

	
	function read_attribute_list($cmd, $result_array = "everything", $string = "")
	{		
		if ($string == "")
		{
			$filter = "(objectclass=MIGAttribute)";
			if ($cmd == "user")
			{	
				// USER SPECIFIC CONFIG				
				$searchbase = "uid=" . USER_LOGINNAME . ", ou=userdefaults, ". SCHEMA_BASE_DIR;
				if ($result_array != "everything") $sr = $this->listing ($searchbase, $filter, $result_array);
				else $sr = $this->listing ($searchbase, $filter);
			}
			elseif ($cmd == "system")
			{
				// SYSTEM DEFAULTS
				$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
				if ($result_array != "everything") $sr = $this->listing ($searchbase, $filter, $result_array);
				else $sr = $this->listing ($searchbase, $filter);
			}
			elseif ($cmd == "both")
			{
				// SYSTEM DEFAULTS + USER CFG
				$searchbase = SCHEMA_BASE_DIR;
				if ($result_array != "everything") $sr = $this->search ($searchbase, $filter, $result_array);
				else $sr = $this->search ($searchbase, $filter);
			}			
			else return $this->quit_on_error("Programming error !!");
		}
		else
		{
			if ($cmd == "referencies")
			{
				$refarray = array("migsearchcriteriaclass", "migdefaultcriteriaclass", "migsearchresultclass", "migcustomizedclass");
				// build a list of attributes to update
				$filter = "(&(objectclass=MIGAttribute)(|";
				for ($a = 0; $a < sizeof($refarray); $a++)
					$filter .= "($refarray[$a]=$string)";
				$filter .= "))";
				
				$searchbase = SCHEMA_BASE_DIR;				
				
				$sr = $this->search ($searchbase, $filter, $result_array);
			}
			elseif ($cmd == "user")
			{
				// USER SPECIFIC CONFIG	FOR THE CLASS
				$filter = "(&(objectclass=MIGAttribute)(migcustomizedclass=$string))";
				$searchbase = "uid=" . USER_LOGINNAME . ", ou=userdefaults, ". SCHEMA_BASE_DIR;
				$sr = $this->listing($searchbase, $filter, $result_array);				
			}
			elseif ($cmd == "system")
			{
				if (($sel_class = $this->read_search_class($string, array("migrelevantattribute"))) == false) return $this->quit_on_error("read_attribute_list : Unable to read class : $string");
 						
				// SYSTEM DEFAULTS FOR THE CLASS
				$filter = "(&(objectclass=MIGAttribute)(|";
				for ($a = 0; $a < $sel_class["migrelevantattribute"]["count"]; $a++) 
					$filter .= "(migattributename=" . $sel_class["migrelevantattribute"][$a] . ")";
				$filter .= "))";		
	
				$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
				$sr = $this->listing($searchbase, $filter, $result_array);			
			}
			elseif ($cmd == "combinations")
			{
				$refarray = array("migsearchtype", "migdisplaytype", "migedittype");

				$filter = "(&(objectclass=MIGAttribute)(option=$string)(|";
				for ($a = 0; $a < count($refarray); $a++)
					$filter .= "($refarray[$a]=combination)";
				$filter .= "))";
				
				$searchbase = SCHEMA_BASE_DIR;				
				
				$sr = $this->search ($searchbase, $filter, $result_array);
			}
			else return $this->quit_on_error("Programming error !!");
			
		}
		$this->attribute_count = $sr["count"];
		return $sr;
	}

	function read_search_class($which, $result_array = "everything")
	{
		$searchbase = "migclassname=$which, ou=systemdefaults, " . SCHEMA_BASE_DIR;
		$filter = "(objectclass=MIGSearchClass)";

		if ($result_array != "everything") return $this->read($searchbase, $filter, $result_array);
		else return $this->read($searchbase, $filter);
	}

	function read_search_class_list($cmd, $result_array = "everything", $oldmigattributename = "")
	{
		if ($cmd == "auto")
		{	
			$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
			
			if (defined("ADMIN") && (ADMIN == true))
			{
				$filter = "(objectclass=MIGSearchClass)";
			
				if ($result_array != "everything") $sr = $this->listing ($searchbase, $filter, $result_array);
				else $sr = $this->listing ($searchbase, $filter);
			}
			else
			{
				$filter = "(&(objectclass=MIGSearchClass)(|(&(migaccessmode=private)(migauthorizeduser=" . USER_LOGINNAME . "))(migaccessmode=public)))";
				if ($result_array != "everything") $sr = $this->listing ($searchbase, $filter, $result_array);
				else $sr = $this->listing ($searchbase, $filter);
			}			
		}

		elseif ($cmd == "referencies")
		{
			if ($oldmigattributename != "")
			{
				// build a list of classes to update
				$classfilter = "(&(objectclass=MIGSearchClass)(|";
				$refarray = array ("migrelevantattribute","migrequiredattribute");
				
				for ($j = 0; $j < sizeof($refarray); $j++)   // for each reference
					$classfilter = $classfilter . "($refarray[$j]=$oldmigattributename)";
				$classfilter = $classfilter . "))";
				// end of building
	
				$classsearchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;

				$sr = $this->listing ($classsearchbase, $classfilter, $result_array);
			}
			else return $this->quit_on_error("References not updated, attribute not specified");
		}
		else return $this->quit_on_error("Programming error !!");
		
		$this->search_class_count = $sr["count"];
		return $sr;
	}

	function read_subsection($which, $result_array = "everything")
	{
		$searchbase = "migsubsectionname=$which, ou=systemdefaults, " . SCHEMA_BASE_DIR;
		$filter = "(objectclass=MIGSubSection)";

		if ($result_array != "everything") return $this->read($searchbase, $filter, $result_array);
		else return $this->read($searchbase, $filter);
	}

	function read_subsection_list($cmd, $result_array = "everything", $oldclassname = "")
	{
		if ($cmd == "auto")
		{	
			$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
		
			$filter = "(objectclass=MIGSubSection)";
			if ($result_array != "everything") $sr = $this->listing ($searchbase, $filter, $result_array);
			else $sr = $this->listing ($searchbase, $filter);
		}

		elseif ($cmd == "referencies")
		{
			if ($oldclassname != "") 
			{
				// build a list of subsections to update
				$subsectionfilter = "(&(objectclass=MIGSubSection)(|(migincludingclass=$oldclassname)(migincludedattribute=$oldclassname)))";
				$subsectionsearchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;

				$sr = $this->listing ($subsectionsearchbase, $subsectionfilter, $result_array);
			}
			else return $this->quit_on_error("References not updated, class not specified");
		}
		else return $this->quit_on_error("Programming error !!");
		
		$this->subsection_count = $sr["count"];
		return $sr;
	}


	function read_group($which, $result_array = "everything")
	{
		$searchbase = "miggroupname=$which, ou=systemdefaults, " . SCHEMA_BASE_DIR;
		$filter = "(objectclass=MIGGroup)";

		if ($result_array != "everything") return $this->read($searchbase, $filter, $result_array);
		else return $this->read($searchbase, $filter);
	}

	function read_group_list($cmd, $result_array = "everything", $olditemname = "")
	{
		if ($cmd == "auto")
		{	
			$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
			if (defined("ADMIN") && (ADMIN == true))
				$filter = "(objectclass=MIGGroup)";
			else 
				$filter = "(&(objectclass=MIGGroup)(migmodifyinguser=" . USER_UID . "))";		

			if ($result_array != "everything") $sr = $this->listing ($searchbase, $filter, $result_array);
			else $sr = $this->listing ($searchbase, $filter);
		}

		elseif ($cmd == "referencies")
		{
			if ($olditemname != "")
			{
				// build a list of groups to update
				$groupfilter = "(&(objectclass=MIGGroup)(|(migusingclass=$olditemname)(migdatasearchfilter=*$olditemname*)))";
				$groupsearchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
				$sr = $this->listing ($groupsearchbase, $groupfilter, $result_array);
			}
			else return $this->quit_on_error("References not updated, item not specified");
		}
		else return $this->quit_on_error("Programming error !!");
		
		$this->group_count = $sr["count"];
		return $sr;
	}
	
	function attribute_exists($migattributename)
	{
		$filter = "(&(objectclass=MIGAttribute)(migattributename=$migattributename))";
   	
		$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
		$result_array = array();
 
		$info = $this->listing ($searchbase, $filter, $result_array);
		$this->attribute_count = $info["count"];
	
		if ($this->attribute_count != 0) return $this->quit_on_success("Another attribute uses same Display and/or LDAP name. Please choose others names.");
		else return false;
	}
	
	
	function is_userdefault_object($dn)
	{
		$arr = ldap_explode_dn($dn, 0);
		$sysroot = explode(",", SCHEMA_BASE_DIR);

		if ($arr[$arr["count"] - count($sysroot) - 1] == "ou=userdefaults") return true;
		else return false;
	}

	

	function search_class_exists($migclassname)
	{
		$filter = "(&(objectclass=MIGSearchClass)(migclassname=$migclassname))";
   	
		$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
		$result_array = array();
 
		$info = $this->listing ($searchbase, $filter, $result_array);
		$this->search_class_count = $info["count"];

		if ($this->search_class_count != 0) return $this->quit_on_success("Display name and/or LDAP name already exists. Please choose others names.");
		else return false;
	}

	function subsection_exists($migsubsectionname)
	{
		$filter = "(&(objectclass=MIGSubSection)(subsectioname=$migsubsectionname))";
   	
		$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
		$result_array = array();
 
		$info = $this->listing ($searchbase, $filter, $result_array);
		$this->search_class_count = $info["count"];

		if ($this->subsection_count != 0) return $this->quit_on_success("LDAP name already exists. Please choose another name.");
		else return false;
	}

	function group_exists($miggroupname)
	{
		$filter = "(&(objectclass=MIGGroup)(miggroupname=$miggroupname))";

		$searchbase = "ou=systemdefaults, " . SCHEMA_BASE_DIR;
		$result_array = array();
 
		$info = $this->listing ($searchbase, $filter, $result_array);
		$this->group_count = $info["count"];

		if ($this->group_count != 0) return $this->quit_on_success("Display name and/or LDAP name already exists. Please choose others names.");
		else return false;
	}
	
	
# LOWLEVEL
	
	function listing($from, $what, $result_array = "everything")
	{
		if ($result_array == "everything") $sr = ldap_list ($this->conn_id, $from, $what);
		else $sr = ldap_list ($this->conn_id, $from, $what, $result_array);

		if (!$sr) return $this->quit_on_error("Internal error while doing a listing operation : $from : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		else return ldap_get_entries ($this->conn_id, $sr);
	}

	function search($from, $what, $result_array = "everything")
	{
		if ($result_array == "everything") $sr = ldap_search ($this->conn_id, $from, $what);
		else $sr = ldap_search ($this->conn_id, $from, $what, $result_array);

		if (!$sr) return $this->quit_on_error("Internal error while doing a search operation : $from : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		else return ldap_get_entries ($this->conn_id, $sr);

	}

	function read($from, $what, $result_array = "everything")
	{
		if ($result_array == "everything") $sr = ldap_read ($this->conn_id, $from, $what);
		else $sr = ldap_read ($this->conn_id, $from, $what, $result_array);

		if (!$sr) return $this->quit_on_error("Internal error while doing a read operation : $from : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		else 
		{
			$info = ldap_get_entries($this->conn_id, $sr);
			if ($info["count"] == 1) return ($info[0]);
			else return $this->quit_on_error("More than one entry was return for a read operation : $from. Number of results is " . $info["count"]);
		}
	}

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
	
	
	function get_dn($something)
	{
		$arr = ldap_explode_dn ($something["dn"], 0);
		$str = "";
	
		for ($i = 0; $i < $arr["count"]; $i++)
		{
			if ($i > 0) $str .= ",";	

			$str .= $arr[$i];
		}
		return $str;
	}
	
	function get_error()
	{	
		return ldap_errno($this->conn_id);
	}

############################################################################################
# INSERTING DATA TO THE LOWER WORLD
############################################################################################


//	function add_attribute($type, $how, $dn = "")
	function add_attribute($type, $how)	
	{		
		$how["objectclass"] = "MIGAttribute";
		if ($type == "user") 
			return $this->add("migattributename=" . $how["migattributename"] . ", uid=" . USER_LOGINNAME . ", ou=userdefaults, " . SCHEMA_BASE_DIR,
						 $how);
		elseif ($type == "system") 
			return $this->add("migattributename=" . $how["migattributename"] . ", ou=systemdefaults, " . SCHEMA_BASE_DIR,
						 $how);		
		else return $this->quit_on_error("Programming error !!");
	}


	function add_search_class($how)
	{
		$how["objectclass"] = "MIGSearchClass";
		return $this->add("migclassname=" . $how["migclassname"] . ", ou=systemdefaults, " . SCHEMA_BASE_DIR, 
							$how);		
	}

	function add_subsection($how)
	{
		$how["objectclass"] = "MIGSubSection";
		return $this->add("migsubsectionname=" . $how["migsubsectionname"] . ", ou=systemdefaults, " . SCHEMA_BASE_DIR, 
							$how);		
	}

	
	function add_group($how)
	{
		$how["objectclass"] = "MIGGroup";
		return $this->add("miggroupname=" . $how["miggroupname"] . ", ou=systemdefaults, " . SCHEMA_BASE_DIR, 
							$how);		
	}


	function delete_user_config($uid)
	{
		$result = array();
		$filter = "(objectclass=MIGAttribute)";
		$searchbase = "uid=$uid, ou=userdefaults, " . SCHEMA_BASE_DIR;
		
		$todel = $this->listing ($searchbase, $filter, $result);
		if (!$todel) return false;
		
		for ($l = 0; $l < $todel["count"]; $l++)
			if (!$this->delete($this->get_dn($todel[$l]))) return false;
		return true;
	}

	function delete_user($uid)
	{
		$dn = "uid=$uid, ou=userdefaults, " . SCHEMA_BASE_DIR;
		return $this->delete($dn);
	}

	function add_user_config($uid)
	{
		$usercfg["objectclass"][0] = "uidObject";
		$usercfg["objectclass"][1] = "top";
		$usercfg["uid"] = $uid;
		return $this->add("uid=$uid, ou=userdefaults, " . SCHEMA_BASE_DIR, $usercfg);
	}

# LOWLEVEL

	function change_first_rdn($dn_source, $with)
	{
		// dn_source is a string
		// with is a string
		// returns dn_destination which is a string	

		// enlight this if PHP version > 4 !!!! by using array functions
	
		$from = strpos($dn_source, "=");
		$to = strpos($dn_source, ",");
		$dn_destination = "";
							
		for ($j = 0; $j <= $from; $j++) 
			$new_dn[$j] = $dn_source[$j];
		for ($j = 0; $j < strlen($with); $j++) 
			$new_dn[$j + $from +1] = $with[$j];
		for ($j = 0; $j < strlen($dn_source) - $to; $j++) 
			$new_dn[$from + strlen($with) + $j +1] = $dn_source[$j + $to];
		
		for ($j = 0; $j < sizeof($new_dn); $j++) 
			$dn_destination = $dn_destination . $new_dn[$j];

		return $dn_destination;
	}

	
	function rename($old_dn, $new_dn)
	{
		if (!ldap_rename($this->conn_id, $old_dn, $new_dn, "", false)) return $this->quit_on_error("Unable to rename $old_dn: error " .ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		return true;
	}

	
	function delete($something)
	{		
		if (!ldap_delete($this->conn_id, $something)) return $this->quit_on_error("Unable to delete $something : error " .ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		return true;
	}

	function add($something, $how)
	{
		// cope with some nasty v3 behaviours
		$array = $this->delete_empty_strings ($how);

		if (!ldap_add($this->conn_id, $something, $array)) return $this->quit_on_error("Internal error : Unable to add $something : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));

		$this->dn = $something;
		return true;
	}


	function mod_replace($something, $how)
	{
		// cope with some nasty v3 behaviours
		$array = $this->delete_empty_strings ($how);

		if (!ldap_mod_replace($this->conn_id, $something, $array)) return $this->quit_on_error("Internal error : Unable to replace attributes to $something : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		return true;
	}

	function mod_add($something, $how)
	{
		// cope with some nasty v3 behaviours
		$array = $this->delete_empty_strings ($how);

		if (!ldap_mod_add($this->conn_id, $something, $array)) return $this->quit_on_error("Internal error : Unable to add attributes to $something : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		return true;
	}

	function mod_del($something, $how)
	{
		// cope with some nasty v3 behaviours
		$array = $this->delete_empty_strings ($how);
		
		if (!ldap_mod_del($this->conn_id, $something, $array)) return $this->quit_on_error("Internal error : Unable to delete attributes of $something : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		return true;
	}

############################################################################################
# CONNECTION WITH THE LOWER WORLD
############################################################################################

	function delete_empty_strings ($array)
	{
		while ( list ($key, $val) = each ($array) ) 
		{
			if (is_array($val))
			{
				$array[$key] = $this->delete_empty_strings ($val);
				if (sizeof($array[$key]) == 0) unset ($array[$key]);
			}
			if ($array[$key] == "")
				unset($array[$key]);
		}	
		return $array;
	}
	
	function encode_utf8 ($array)
	{
		while ( list ($key, $val) = each ($array) ) 
		{
			if (!is_array($val))
				$array[$key] = utf8_encode($val);
			else $array[$key] = $this->encode_utf8 ($val);
		}	
		
		return $array;
	}


	function connect($uid = "auto", $passwd = "auto")
	{
		$this->conn_id = ldap_connect(SCHEMA_HOSTNAME);
		if (!$this->conn_id) return $this->quit_on_error("Unable to connect to host : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		
		if ($uid == "auto")
		{
			if (defined("ADMIN") && (ADMIN == true))
			{
				if (!ldap_bind($this->conn_id, SCHEMA_MANAGER_UID, SCHEMA_MANAGER_PW)) return $this->quit_on_error("Unable to bind as a manager to schema host : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
			}
			else
			{
				if (!ldap_bind($this->conn_id, USER_UID, USER_PW))
				{
					define ("USER_SCHEMA_IS_READONLY", "true");
					if (!ldap_bind($this->conn_id, SCHEMA_MANAGER_UID, SCHEMA_MANAGER_PW)) return $this->quit_on_error("Unable to bind as a manager (on behalf of user) to schema host : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
				}
			} 
		}
		elseif (!ldap_bind($this->conn_id, $uid, $passwd)) return $this->quit_on_error("Unable to bind as a user to schema host : error "  .ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));

//if (ldap_set_option($this->conn_id, LDAP_OPT_PROTOCOL_VERSION, 3))
//    echo "Using LDAPv3";
//else
//    echo "Failed to set protocol version to 3";


		return $this->conn_id;
	}

	function connect_to_datastore($uid = "auto", $passwd = "auto")
	{
		if (defined("REMOTE_HOSTNAME"))
			$this->conn_id = ldap_connect(REMOTE_HOSTNAME);
		else $this->conn_id = ldap_connect(AUTH_HOSTNAME);
		
		if (!$this->conn_id) return $this->quit_on_error("Unable to connect to host : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
		
		if ($uid == "auto")
		{
			if (defined("ADMIN") && (ADMIN == true))
			{
				if (defined("REMOTE_MANAGER_UID") && defined("REMOTE_MANAGER_PW"))
				{
					if (!ldap_bind($this->conn_id, REMOTE_MANAGER_UID, REMOTE_MANAGER_PW)) return $this->quit_on_error("Unable to bind as a manager to datastore host with " . MANAGER_UID . " : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
				}
				else if (!ldap_bind($this->conn_id, AUTH_MANAGER_UID, AUTH_MANAGER_PW)) return $this->quit_on_error("Unable to bind as a manager to datastore host : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
			}
			else
			{
				if (!ldap_bind($this->conn_id, USER_UID, USER_PW)) return $this->quit_on_error("Unable to bind as a user to datastore host : error " .ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
			}
		}
		elseif (!ldap_bind($this->conn_id, $uid, $passwd)) return $this->quit_on_error("Unable to bind as user to datastore host : error "  .ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));

//if (ldap_set_option($this->conn_id, LDAP_OPT_PROTOCOL_VERSION, 3))
//    echo "Using LDAPv3";
//else
//    echo "Failed to set protocol version to 3";


		return $this->conn_id;
	}



	function rebind($uid = "auto", $passwd = "auto")
	{
		if ($uid == "auto")
		{
			if (defined("ADMIN") && (ADMIN == true))
			{
				if (!ldap_bind($this->conn_id, MANAGER_UID, MANAGER_PW)) return $this->quit_on_error("Unable to rebind: Unable to bind as a manager to <same> host : error " . ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
				
			}
			else
			{
				if (!ldap_bind($this->conn_id, USER_UID, USER_PW)) return $this->quit_on_error("Unable to rebind rebind: Unable to bind as a user to <same> host : error " .ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));
			} 
		}
		elseif (!ldap_bind($this->conn_id, $uid, $passwd)) return $this->quit_on_error("Unable to bind as a user to <same> host : error "  .ldap_errno($this->conn_id) ." : " . ldap_error($this->conn_id));

//if (ldap_set_option($this->conn_id, LDAP_OPT_PROTOCOL_VERSION, 3))
//    echo "Using LDAPv3";
//else
//    echo "Failed to set protocol version to 3";

		return true;
	}
	
	function unbind($conn_id)
	{
		return ldap_unbind($conn_id);
	}

	function disconnect()
	{
		return ldap_unbind($this->conn_id);
	}


	
}

?>