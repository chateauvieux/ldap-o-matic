<?php

require_once "php/objectmodel.php";

Class user extends objectmodel
{

	function user($conn_id)
	{
		$this->mappings = array("uid"	 => "single", 
								"givenname"	 => "single",
								"sn"	 => "single",
								"mail"	 => "single",
								"comments" => "single",
								"migcategory" => "single",
								"userpassword"=> "single");
		$this->conn_id = $conn_id;
	}

	
############################################################################################
# RETRIEVING INFO FROM THE LOWER WORLD
############################################################################################
	
	function read($uid, $result_array = "")
	{
		$core = new core($this->conn_id);		
		if ($result_array == "")
			$data = $core->read_user($uid);
		else 
			$data = $core->read_user($uid, $result_array);
		if (!$data) return $this->quit_on_error($core->error_msg);
		
		for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
		{
			if ($this->mappings[$key] == "single") 
			{
				if (isset($data[$key][0])) $this->$key = $data[$key][0];
				else $this->$key = "";
			}
			elseif ($this->mappings[$key] == "multiple") 
			{
				if (isset($data[$key])) $this->$key = $data[$key];
				else $this->$key = array();
			}
			else return $this->quit_on_error("read: Invalid mapping type or unrecognized property"); 
		}
		$this->dn = $core->get_dn($data);
		return true;	
	}

	function read_from_list($user_list, $index)
	{
		$core = new core($this->conn_id);
		if (is_integer($index))
		{
			if (($index >= 0) && ($index < $user_list["count"])) 
			{
				for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
				{
					if ($this->mappings[$key] == "single") 
					{
						if (isset($user_list[$index][$key][0])) $this->$key = $user_list[$index][$key][0];
						else $this->$key = "";
					}
					elseif ($this->mappings[$key] == "multiple") 
					{
						if (isset($user_list[$index][$key])) $this->$key = $user_list[$index][$key];
						else $this->$key = array();
					}
					else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
				}
				$this->dn = $core->get_dn($user_list[$index]);
				return true;
			}
			else return $this->quit_on_error("User could not be read from list : bad list index");
		}
		elseif (is_string($index))
		{
			for ($i=0; $i < $user_list["count"]; $i++)
			{
				if ($index == $user_list[$i]["Username"][0])
				{
					for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
					{
						if ($this->mappings[$key] == "single") 
						{
							if (isset($user_list[$i][$key][0])) $this->$key = $user_list[$i][$key][0];
							else $this->$key = "";
						}
						elseif ($this->mappings[$key] == "multiple") 
						{
							if (isset($user_list[$i][$key])) $this->$key = $user_list[$i][$key];
							else $this->$key = array();
						}
						else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
					}
					$this->dn = $core->get_dn($user_list[$i]);
					return true;
				}
			}
			return $this->quit_on_error("User could not be read from list : bad User name");
		}
		else return $this->quit_on_error("User could not be read from list : index must be integer or string");
	}

############################################################################################
# INSERTING INFO INTO THE LOWER WORLD
############################################################################################

	function delete_user()
	{
		$core = new core($this->conn_id);
		//erase user config
		if (!$core->delete_user_config($this->uid)) return $this->quit_on_error($core->error_msg);
	
		//erase user from schema
		if (!$core->delete_user($this->uid)) return $this->quit_on_error($core->error_msg);
		return true;
	}

	function add_user()
	{
		$core = new core($this->conn_id);
		// create user config subtree
		if (!$core->add_user_config($this->uid)) return $this->quit_on_error($core->error_msg);
		return true;
	}
		

	function mod_replace_user($how)
	{
		$core = new core($this->conn_id);
		if (!$core->mod_replace($this->dn, $how)) return $this->quit_on_error($core->error_msg);
		return true;
	}

	function mod_add_user($how)
	{
		$core = new core($this->conn_id);
		if (!$core->mod_add($this->dn, $how)) return $this->quit_on_error($core->error_msg);
		return true;
	}

	function mod_del_user($how)
	{
		$core = new core($this->conn_id);
		if (!$core->mod_del($this->dn, $how)) return $this->quit_on_error($core->error_msg);
		return true;
	}


############################################################################################
# LOCAL GET/SET
############################################################################################
	



}

?>
