<?php

require_once "php/objectmodel.php";

Class group extends objectmodel
{
	var $miggroupname;
	var $miggroupdisplayname;
	var $migmodifyinguser;
	var $migmodifieddata;
	var $migusingclass;
	var $migdatarefresh;
	var $migdatasearchfilter;

	function group($conn_id)
	{
		$this->mappings = array("miggroupname"	  => "single", 
								"miggroupdisplayname" => "single",
								"migmodifyinguser"	  => "multiple",
								"migmodifieddata"	  => "multiple",
								"migusingclass"	  => "single",
								"migdatarefresh"=> "single",
								"migdatasearchfilter"=> "single");
		// inherited
		$this->conn_id = $conn_id;
	}

	
############################################################################################
# RETRIEVING INFO FROM THE LOWER WORLD
############################################################################################
	
	function read($which, $result_array = "everything")
	{
		$core = new core($this->conn_id);
		if ($result_array == "everything")
			$data = $core->read_group($which);
		else 
			$data = $core->read_group($which, $result_array);
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


	function read_from_list($group_list, $index)
	{
		$core = new core($this->conn_id);
		if (is_integer($index))
		{
			if (($index >= 0) && ($index < $group_list["count"])) 
			{
				for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
				{
					if ($this->mappings[$key] == "single") 
					{
						if (isset($group_list[$index][$key][0])) $this->$key = $group_list[$index][$key][0];
						else $this->$key = "";
					}
					elseif ($this->mappings[$key] == "multiple") 
					{
						if (isset($group_list[$index][$key])) $this->$key = $group_list[$index][$key];
						else $this->$key = array();
					}
					else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
				}
				$this->dn = $core->get_dn($group_list[$index]);
				return true;
			}
			else return $this->quit_on_error("Group could not be read from list : bad list index");
		}
		elseif (is_string($index))
		{
			for ($i=0; $i < $group_list["count"]; $i++)
			{
				if ($index == $group_list[$i]["miggroupname"][0])
				{
					for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
					{
						if ($this->mappings[$key] == "single") 
						{
							if (isset($group_list[$i][$key][0])) $this->$key = $group_list[$i][$key][0];
							else $this->$key = "";
						}
						elseif ($this->mappings[$key] == "multiple") 
						{
							if (isset($group_list[$i][$key])) $this->$key = $group_list[$i][$key];
							else $this->$key = array();
						}
						else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
					}
					$this->dn = $core->get_dn($group_list[$i]);
					return true;
				}
			}
			return $this->quit_on_error("Group could not be read from list : bad group name");
		}
		else return $this->quit_on_error("Group could not be read from list : index must be integer or string");
	}

############################################################################################
# INSERTING INFO INTO THE LOWER WORLD
############################################################################################

	function delete()
	{
		$core = new core($this->conn_id);
		if (!$core->delete($this->dn)) return $this->quit_on_error($core->error_msg);
		return true;
	}

	function mod_add($how)
	{
		$core = new core($this->conn_id);
		if (!$core->mod_add($this->dn, $how)) return $this->quit_on_error($core->error_msg);
		return true;
	}


	function mod_del($how)
	{
		$core = new core($this->conn_id);
		if (isset($how["miggroupname"])) return $this->quit_on_error("Error: cannot remove group's name property");
		elseif (!$core->mod_del($this->dn, $how)) return $this->quit_on_error($core->error_msg);
		return true;
	}

	function rename($new_first_rdn)
	{
		$core = new core($this->conn_id);

		$dn = $core->change_first_rdn($this->dn, $new_first_rdn);
		if (!$core->rename($this->dn, $dn)) return $this->quit_on_error($core->error_msg);
		$this->dn = $dn;
		return true;
	}

	function mod_replace($how)
	{
		if (isset($how["miggroupname"]) && ($how["miggroupname"] == ""))
			return $this->quit_on_error("mod_replace fails : replacement miggroupname cannot be null");
		else
		{
			$core = new core($this->conn_id);
			
			if (isset($how["miggroupname"])) 
			{
				if (!isset($this->miggroupname)) return $this->quit_on_error("mod_replace fails : miggroupname cannot be null");
				
				if ($this->miggroupname != $how["miggroupname"]) 
					if ($core->group_exists($how["miggroupname"])) return $this->quit_on_error($core->error_msg);
			

				if ($how["miggroupname"] != $this->miggroupname)
				{				
					if (!$this-rename($how["miggroupname"])) return $this->quit_on_error($this->error_msg);
					echo "Refencies updated. <a href=\"index.html\" target=\"_top\">Click here to refresh.</a>";					
				}
			}
		
			if (!$core->mod_replace($this->dn, $how)) return $this->quit_on_error($core->error_msg);
			
			for (reset($how); $key = key($how); next ($how)) 
				$this->$key = $how[$key];

			return true;
		}
	}	


	function add()
	{
		$core = new core($this->conn_id);
				
		if (!(isset($this->miggroupname) && ($this->miggroupname != ""))) return $this->quit_on_error("Add group fails : At least miggroupname must be specified");
		
		if ($core->group_exists($this->miggroupname)) return $this->quit_on_error($core->error_msg);

		for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
		{
			if (isset($this->$key))
			{
				if ($this->mappings[$key] == "single")
				{
					if ($this->$key != "") $updadd[$key] = $this->$key;
				}
				elseif ($this->mappings[$key] == "multiple")
				{
					if (is_array($this->$key)) $updadd[$key] = $this->$key;
				}
				else return $this->quit_on_error("add: Invalid mapping type or unrecognized property");				
			}
		}
		
		if (!$core->add_group($updadd)) return $this->quit_on_error($core->error_msg);
		$this->dn = $core->dn;
		return true;
	}


############################################################################################
# LOCAL GET/SET
############################################################################################
	

	function is_migmodifieddata ($uid)
	{		
		$core = new core($this->conn_id);
		
		for ($l = 0; $l < $this->count("migmodifieddata"); $l++)
			if ($core->normalize_dn ($this->migmodifieddata[$l]) == $core->normalize_dn ($uid))
				return true;
		return false;
	}
	
	function is_migmodifyinguser ($uid)
	{		
		$core = new core($this->conn_id);
		
		for ($l = 0; $l < $this->count("migmodifyinguser"); $l++)
			if ($core->normalize_dn ($this->migmodifyinguser[$l]) == $core->normalize_dn ($uid))
				return true;
		return false;
	}

	function is_migdatarefresh_dynamic ()
	{		
		if ($this->migdatarefresh == "dynamic")
			return true;
		return false;
	}

	function is_migdatarefresh_static ()
	{		
		if ($this->migdatarefresh == "static")
			return true;
		return false;
	}
}

?>
