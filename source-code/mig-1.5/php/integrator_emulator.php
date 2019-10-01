<?php

require_once "php/objectmodel.php";

Class integrator_emulator extends objectmodel
{
	var $reply;
	var $error_msg;
	
	function integrator_emulator($conn_id)
	{
		$this->conn_id = $conn_id;
	}

	function lookup ($postvars)
	{
		return $this->quit_on_error ("The lookup function cannot be implemented without MetaMerge Integrator");
	}
	
	function add ($postvars)
	{
		$core = new core($this->conn_id);
		$dn = $postvars["dn"];
		unset ($postvars["dn"]); 

		// get rid of PHP session ID, order and mode
		$sessID = session_name();
		unset ($postvars[$sessID]);
		unset ($postvars["order"]);
		unset ($postvars["mode"]);
		unset ($postvars["class"]);
		
		for (reset($postvars); $key = key($postvars); next($postvars))
		{
			if (is_string($key) && ((substr($key, 0, 3) == "dd_") || (substr($key, 0, 3) == "mm_") || (substr($key, 0, 3) == "y4_")))
			{
 				$rest = substr($key, 3);
				if (isset($postvars["dd_" . $rest]) && isset($postvars["mm_" . $rest]) && isset($postvars["y4_" . $rest]))
				{
					$postvars[$rest] = $postvars["y4_" . $rest] . $postvars["mm_" . $rest] . $postvars["dd_" . $rest];
					unset ($postvars["dd_" . $rest]);
					unset ($postvars["mm_" . $rest]);
					unset ($postvars["y4_" . $rest]);
				}
			}

			if (is_string($key) && ((substr($key, 0, 9) == "intlcode_") || (substr($key, 0, 9) == "areacode_") || (substr($key, 0, 7) == "number_")))
			{
 				$rest = substr($key, (strpos($key, "_") +1));
				if (isset($postvars["intlcode_" . $rest]) && isset($postvars["areacode_" . $rest]) && isset($postvars["intlcode_" . $rest]))
				{
					$postvars[$rest] =  "+" . $postvars["intlcode_" . $rest] . "(" . $postvars["areacode_" . $rest] . ")" . $postvars["number_" . $rest];
					unset ($postvars["intlcode_" . $rest]);
					unset ($postvars["areacode_" . $rest]);
					unset ($postvars["number_" . $rest]);
				}
				
			}
		}
		
/*		if (isset($postvars["dd_mmBirthDate"]) && isset($postvars["mm_mmBirthDate"]) && isset($postvars["y4_mmBirthDate"]))
		{
			$postvars["mmBirthDate"] = $postvars["y4_mmBirthDate"] . $postvars["mm_mmBirthDate"] . $postvars["dd_mmBirthDate"];
			unset ($postvars["dd_mmBirthDate"]);
			unset ($postvars["mm_mmBirthDate"]);
			unset ($postvars["y4_mmBirthDate"]);
		}
*/		
		
		// get rid of the reformat array
		unset ($postvars["attr_to_reformat"]);

		if ($core->add($dn, $postvars))
		{
			$this->reply["exitstatus"] = "OK";
			return true;
		}
		else
		{
			$this->reply["exitstatus"] = "NOK";
			$this->error_msg = $core->error_msg;
			return false;
		}
	}

	function update ($postvars)
	{
		$core = new core($this->conn_id);
		$dn = $postvars["dn"];
		unset ($postvars["dn"]); 

		// get rid of PHP session ID, order and mode
		$sessID = session_name();
		unset ($postvars[$sessID]);
		unset ($postvars["order"]);
		unset ($postvars["mode"]);
		unset ($postvars["class"]);
		
		
		for (reset($postvars); $key = key($postvars); next($postvars))
		{
			if (is_string($key) && ((substr($key, 0, 3) == "dd_") || (substr($key, 0, 3) == "mm_") || (substr($key, 0, 3) == "y4_")))
			{
 				$rest = substr($key, 3);
				if (isset($postvars["dd_" . $rest]) && isset($postvars["dd_" . $rest]) && isset($postvars["dd_" . $rest]))
				{
					$postvars[$rest] = $postvars["y4_" . $rest] . $postvars["mm_" . $rest] . $postvars["dd_" . $rest];
					unset ($postvars["dd_" . $rest]);
					unset ($postvars["mm_" . $rest]);
					unset ($postvars["y4_" . $rest]);
				}
			}
			
			if (is_string($key) && ((substr($key, 0, 9) == "intlcode_") || (substr($key, 0, 9) == "areacode_") || (substr($key, 0, 7) == "number_")))
			{
 				$rest = substr($key, (strpos($key, "_") +1));
				if (isset($postvars["intlcode_" . $rest]) && isset($postvars["areacode_" . $rest]) && isset($postvars["intlcode_" . $rest]))
				{
					$postvars[$rest] =  "+" . $postvars["intlcode_" . $rest] . "(" . $postvars["areacode_" . $rest] . ")" . $postvars["number_" . $rest];
					unset ($postvars["intlcode_" . $rest]);
					unset ($postvars["areacode_" . $rest]);
					unset ($postvars["number_" . $rest]);
				}
				
			}
		}
		
		
/*		if (isset($postvars["dd_mmBirthDate"]) && isset($postvars["mm_mmBirthDate"]) && isset($postvars["y4_mmBirthDate"]))
		{
			$postvars["mmBirthDate"] = $postvars["y4_mmBirthDate"] . $postvars["mm_mmBirthDate"] . $postvars["dd_mmBirthDate"];
			unset ($postvars["dd_mmBirthDate"]);
			unset ($postvars["mm_mmBirthDate"]);
			unset ($postvars["y4_mmBirthDate"]);
		}
*/		
		
		// get rid of the reformat array
		unset ($postvars["attr_to_reformat"]);
		
		if ($core->mod_replace($dn, $postvars))
		{
			$this->reply["exitstatus"] = "OK";
			return true;
		}
		else
		{
			$this->reply["exitstatus"] = "NOK";
			$this->error_msg = $core->error_msg;
			return false;
		}
	}
	

	function update_email ($postvars)
	{
		$core = new core($this->conn_id);
		$dn = $postvars["dn"];
		unset ($postvars["dn"]); 

		// get rid of PHP session ID, order
		$sessID = session_name();
		unset ($postvars[$sessID]);
		unset ($postvars["order"]);
	
		// get rid of the reformat array
		unset ($postvars["attr_to_reformat"]);
		
		if ($core->mod_replace($dn, $postvars))
		{
			$this->reply["exitstatus"] = "OK";
			return true;
		}
		else
		{
			$this->reply["exitstatus"] = "NOK";
			$this->error_msg = $core->error_msg;
			return false;
		}
	}


	function read ($dn)
	{
		unset($this->reply);
		$core = new core($this->conn_id);
		if (($obj = $core->read($dn, "(objectclass=*)")) == true)
		{
			$this->getReply ($obj);
			$this->reply["exitstatus"] = "OK";
			return true;
		}
		else 
		{
			$this->reply["exitstatus"] = "NOK";
			$this->error_msg = $core->error_msg;
			return false;
		}
	}

	function read_email ($dn)
	{
		unset($this->reply);
		$core = new core($this->conn_id);
		if (($obj = $core->read($dn, "(objectclass=*)")) == true)
		{
			$this->getReply ($obj);
			$this->reply["exitstatus"] = "OK";
			return true;
		}
		else 
		{
			$this->reply["exitstatus"] = "NOK";
			$this->error_msg = $core->error_msg;
			return false;
		}
	}

	
	function delete ($dn)
	{
		unset($this->reply);
		$core = new core($this->conn_id);
		if ($core->delete($dn))
		{
			$this->reply["exitstatus"] = "OK";
			return true;
		}
		else 
		{
			$this->reply["exitstatus"] = "NOK";
			$this->error_msg = $core->error_msg;
			return false;
		}
	}

	
	function add_user_schema_subtree($uid)
	{
		$curr_user = new user($conn_id);
		if (!$curr_user->read($uid, array())) return $this->quit_on_error ("Non existing user");
		$curr_user->add_user();
		return true;
	}

	function clearAttribute ($attr)
	{
		if (isset($this->reply[strtolower($attr)]))
			unset ($this->reply[strtolower($attr)]);
	}		
	
	function getAttribute ($attr)
	{
		if (isset($this->reply[strtolower($attr)]))
			return $this->reply[strtolower($attr)];
	}

	function setAttribute ($attr, $value)
	{
		$this->update[strtolower($attr)] = $value;
	}


	function geterrmsg ()
	{
		return $this->error_msg;
	}

	function openSocket ($url)
	{
		return true;
	}

	function getReply ($obj)
	{
		function remove_count($var)
		{
			// Clean from LDAP result format to "a la" Integrator
			unset($var["count"]);
			unset($var["dn"]);
			for ($i = 0; $i < sizeof($var); $i++)
				if (is_string($var[$i])) unset($var[$i]);
			
			for (reset($var); $key = key($var); next($var))
			{
				if ($var[$key]["count"] == 1)
					$var[$key] = $var[$key][0];
				unset ($var[$key]["count"]);
			}
			
			return $var;
		}
		
		$this->reply = remove_count($obj);

		return $this->reply;
	}
}

?>
