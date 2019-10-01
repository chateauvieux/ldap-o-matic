<?php

Class objectmodel
{
	var $conn_id = 0;
	var $dn = "";
	var $error_msg = "OK";
	var $referer;

	// when adding properties, update this
	var $ldap_invisible = array("conn_id", "dn", "error_msg"); 
	
	function count($something = "")
	{
		if (isset($this->{$something}["count"])) return $this->{$something}["count"];	
		else return 0;
	}

	function quit_on_error($error, $critical = true)
	{
		$this->error_msg = $error;
		
		if ($critical)
			trigger_error("MIG error at " . MIG_INSTALLATION_NAME . " : $error", E_USER_ERROR);
		return false;
	}

	function quit_on_success($error, $critical = true)
	{
		$this->error_msg = $error;
		
		if ($critical)
			trigger_error("MIG error at " . MIG_INSTALLATION_NAME . " : $error", E_USER_ERROR);
		return true;
	}

	# The reference engine
	function check_references($order, $ref_to_replace, $new_ref = "")
	{
		if (isset($this->referer))
		{
			$core = new core($this->conn_id);
			// first a per-class loop
			for (reset($this->referer); $key = key($this->referer); next ($this->referer)) 
			{	
				include_once "php/$key.php";

				// variable variables -> function
				$reader_function = "read_" . $key . "_list";
				$counter = $key . "_count";

				// Deal with each reference	
				$result_array = array();

				// Read all "classes"
				for (reset ($this->referer[$key]); $prop = key($this->referer[$key]); next ($this->referer[$key]))
					$result_array[] = $prop;

				if (($array = $core->$reader_function("referencies", $result_array, $ref_to_replace)) == false) return $this->quit_on_error($core->error_msg);
				// For each "class"
				for ($i = 0; $i < $core->$counter; $i++) 
				{
					$current = new $key($this->conn_id);
					if (!$current->read_from_list($array, $i)) return $this->quit_on_error($current->error_msg);
					// then a per-property loop
					for (reset ($this->referer[$key]); $prop = key($this->referer[$key]); next ($this->referer[$key]))
					{
						if (!isset($current->mappings[$prop])) return $this->quit_on_error("Reference engine error: unknown property");
						
						elseif ($current->mappings[$prop] == "single")
						{
							if ($this->referer[$key][$prop] == "string")
							{
								if ($order == "delete")
								{
									if ($ref_to_replace == $current->$prop) // found
										$delref[$prop][0] = $ref_to_replace;
								}
								elseif ($order == "modify")
								{
									if ($ref_to_replace == $current->$prop) // found
										$updref[$prop][0] = $new_ref;
								}
							}
							elseif ($this->referer[$key][$prop] == "searchfilter")
							{
								if ($order == "delete")
								{
										$string = ereg_replace("\($ref_to_replace=[^)]*)", "", $current->$prop);
										$updref[$prop] = $string;
								}
								elseif ($order == "modify")
								{
									$upd = str_replace($ref_to_replace . "=", $new_ref . "=", $current->$prop);
									if ($upd != $current->$prop) $updref[$prop] = $upd;
								}
							}
							else return $this->quit_on_error("Reference engine programming error!");						
						}						
						elseif ($current->mappings[$prop] == "multiple")
						{
							for ($k = 0; $k < $current->count($prop); $k++)		 	// all values		
							{
								if ($this->referer[$key][$prop] == "string")
								{
									if ($order == "delete")
									{
										if ($ref_to_replace == $current->{$prop}[$k]) // found
											$delref[$prop][] = $ref_to_replace;
									}
									elseif ($order == "modify")
									{
										if ($ref_to_replace == $current->{$prop}[$k]) // found
											$updref[$prop][] = $new_ref;
										else
											$updref[$prop][] = $current->{$prop}[$k];
									}
								}
								elseif ($this->referer[$key][$prop] == "searchfilter")
								{
									if ($order == "delete")
									{ 							
										while (!($pos = strpos($current->{$prop}[$k], "(" . $ref_to_replace . "=")) == false)
										{
											$string = substr($current->{$prop}[$k], $pos, strpos($current->{$prop}[$k], ")", $pos) - $pos + 1);
											$updref[$prop][] = str_replace($string, '', $current->{$prop}[$k]);
										}
									}
									elseif ($order == "modify")
									{
										$upd = str_replace($ref_to_replace . "=", $new_ref . "=", $current->{$prop}[$k]);
										if ($upd != $current->{$prop}[$k]) $updref[$prop][] = $upd;
									}
								}
								else return $this->quit_on_error("Reference engine programming error!");
							}
						}
						else return $this->quit_on_error("Reference engine error: unknown attribute mapping");

						if (isset($delref))
						{
							if (!$current->mod_del($delref)) return $this->quit_on_error($current->error_msg);
							unset($delref);				
						}
						if (isset($updref))
						{
							if (!$current->mod_replace($updref)) return $this->quit_on_error($current->error_msg);
							unset($updref);						
						}
					}
				}
			}
		}
	return true;
	}
	
}

?>
