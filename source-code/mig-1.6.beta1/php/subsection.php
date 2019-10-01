<?php

require_once "php/objectmodel.php";

class subsection extends objectmodel
{
		
	function subsection($conn_id)
	{
		$this->mappings = array("migsubsectionname" => "single", 
								"migsubsectiondisplayname"=> "single",
								"migincludedattribute" => "multiple",
								"migincludingclass"	 => "single",
								"migposition"	 => "single");
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
			$data = $core->read_subsection($which);
		else 
			$data = $core->read_subsection($which, $result_array);
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


	function read_from_list($subsection_list, $index)
	{
		$core = new core($this->conn_id);
		if (is_integer($index))
		{
			if (($index >= 0) && ($index < $subsection_list["count"])) 
			{
				for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
				{
					if ($this->mappings[$key] == "single") 
					{
						if (isset($subsection_list[$index][$key][0])) $this->$key = $subsection_list[$index][$key][0];
						else $this->$key = "";
					}
					elseif ($this->mappings[$key] == "multiple") 
					{
						if (isset($subsection_list[$index][$key])) $this->$key = $subsection_list[$index][$key];
						else $this->$key = array();
					}
					else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
				}
				$this->dn = $core->get_dn($subsection_list[$index]);				
				return true;
			}
			else return $this->quit_on_error("Subsection could not be read from list : bad list index");		
		}
		elseif (is_string($index))
		{
			for ($i=0; $i < $subsection_list["count"]; $i++)
			{
				if ($index == $subsection_list[$i]["migsubsectionname"][0])
				{
					for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
					{
						if ($this->mappings[$key] == "single") 
						{
							if (isset($subsection_list[$i][$key][0])) $this->$key = $subsection_list[$i][$key][0];
							else $this->$key = "";
						}
						elseif ($this->mappings[$key] == "multiple") 
						{
							if (isset($subsection_list[$i][$key])) $this->$key = $subsection_list[$i][$key];
							else $this->$key = array();
						}
						else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
					}					
					return true;
				}
			}
			$this->dn = $core->get_dn($subsection_list[$i]);
			return $this->quit_on_error("Subsection could not be read from list : bad subsection name");
		}
		else return $this->quit_on_error("Subsection could not be read from list : index must be integer or string");
	}
############################################################################################
# INSERTING INFO INTO THE LOWER WORLD
############################################################################################

	function delete()
	{
		$core = new core($this->conn_id);
		
		// referencies
		if (INFORMATION_SYSTEM_HOLDS_REFERENCIES == "no")
			if (!$this->check_references("delete", $this->migsubsectionname, $how["migsubsectionname"])) return $this->quit_on_error($this->error_msg);
		
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
		if (isset($how["migsubsectionname"])) return $this->quit_on_error("Error: cannot remove subsection's name property");
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
		if (isset($how["migsubsectionname"]) && ($how["migsubsectionname"] == ""))
			return $this->quit_on_error("mod_replace fails : replacement migsubsectionname cannot be null");
		else
		{
			$core = new core($this->conn_id);
			
			if (isset($how["migsubsectionname"])) 
			{
				if (!isset($this->migsubsectionname)) return $this->quit_on_error("mod_replace fails : migsubsectionname cannot be null");
				
				if ($this->migsubsectionname != $how["migsubsectionname"]) 
					if ($core->subsection_exists($how["migsubsectionname"])) return $this->quit_on_error($core->error_msg);
			
			
				if ($how["migsubsectionname"] != $this->migsubsectionname)
				{
			 		if (!$this->rename($how["migsubsectionname"])) return $this->quit_on_error($this->error_msg);

					// referencies
					if (INFORMATION_SYSTEM_HOLDS_REFERENCIES == "no")
						if (!$this->check_references("modify", $this->migsubsectionname, $how["migsubsectionname"])) return $this->quit_on_error($this->error_msg);

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
				
		if (!(isset($this->migsubsectionname) && ($this->migsubsectionname != ""))) return $this->quit_on_error("Add subsection fails : At least migsubsectionname must be specified");

		if ($core->subsection_exists($this->migsubsectionname)) return $this->quit_on_error($core->error_msg);

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
		
		if (!$core->add_subsection($updadd)) return $this->quit_on_error($core->error_msg);
		$this->dn = $core->dn;
		return true;
	}


############################################################################################
# LOCAL GET/SET
############################################################################################
	

	function is_migincludedattribute ($attribute)
	{	
		for ($l = 0; $l < $this->count("migincludedattribute"); $l++)
			if ($this->migincludedattribute[$l] == $attribute)
				return true;
		return false;
	}
	
	
}

?>
