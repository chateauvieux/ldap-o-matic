<?php

require_once "php/objectmodel.php";

Class attribute extends objectmodel
{
	var $migattributename;
	var $migattributeoid;
	var $migattributedisplayname;
	var $migsearchcriteriaclass;
	var $migsearchresultclass;
	var $migdefaultcriteriaclass;
	var $migcustomizedclass;
	var $migdisplaytype;
	var $migsearchtype;
	var $migedittype;
	var $migauthorizedvalue;
	var $migdescription;
	var $migexamplevalue;
	
	// value added components
	
	var $mappings;
	var $migauthorizedvalue_couple;
	var $combination_set;
	
	function attribute($conn_id)
	{
		$this->mappings = array("migattributename"			=> "single", 
								"migattributeoid"			=> "single", 
								"migattributedisplayname"	=> "single",
								"migsearchcriteriaclass"	=> "multiple",
								"migsearchresultclass"		=> "multiple",
								"migdefaultcriteriaclass"	=> "multiple",
								"migcustomizedclass"		=> "multiple",
								"migdisplaytype"			=> "single",
								"migsearchtype"				=> "single",
								"migedittype"				=> "single",
								"migauthorizedvalue"		=> "multiple",
								"migdescription"			=> "single",
								"migexamplevalue"			=> "single");

		// inherited
		$this->conn_id = $conn_id;	
		$this->referer = array("search_class" => array("migrelevantattribute" => "string",
												"migrequiredattribute" => "string",
												"migdatanamingattribute" => "string"),
							  "group" => array("migdatasearchfilter"	=> "searchfilter"),
							  "subsection" => array("migincludedattribute" => "string"));

	}

	 

############################################################################################
# RETRIEVING INFO FROM THE LOWER WORLD
############################################################################################

	function read($which, $type, $result_array = "")
	{
		$core = new core($this->conn_id);
		if ($result_array == "")
			$data = $core->read_attribute($which, $type);
		else 
			$data = $core->read_attribute($which, $type, $result_array);
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
	
	
	function read_from_list($attr_list, $index)
	{
		$core = new core($this->conn_id);
		if (is_integer($index))
		{
			if (($index >= 0) && ($index < $attr_list["count"])) 
			{
				for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
				{
					if ($this->mappings[$key] == "single") 
					{
						if (isset($attr_list[$index][$key][0])) $this->$key = $attr_list[$index][$key][0];
						else $this->$key = "";
					}
					elseif ($this->mappings[$key] == "multiple") 
					{
						if (isset($attr_list[$index][$key])) $this->$key = $attr_list[$index][$key];
						else $this->$key = array();
					}
					else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
				}
				$this->dn = $core->get_dn($attr_list[$index]);
				return true;
			}
			else return $this->quit_on_error("Attribute could not be read from list : $index : bad list index");
		}
		elseif (is_string($index))
		{
			for ($i=0; $i < $attr_list["count"]; $i++)
			{
				if ($index == $attr_list[$i]["migattributename"][0])
				{
					for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
					{
						if ($this->mappings[$key] == "single") 
						{
							if (isset($attr_list[$i][$key][0])) $this->$key = $attr_list[$i][$key][0];
							else $this->$key = "";
						}
						elseif ($this->mappings[$key] == "multiple") 
						{
							if (isset($attr_list[$i][$key])) $this->$key = $attr_list[$i][$key];
							else $this->$key = array();
						}
						else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
					}
					$this->dn = $core->get_dn($attr_list[$i]);
					return true;
				}
			}
			return $this->quit_on_error("Attribute could not be read from list : $index : bad attribute name");
		}
		else return $this->quit_on_error("Attribute could not be read from list : index must be integer or string");
	}

############################################################################################
# LOCAL GET/SET
############################################################################################

	function process_migauthorizedvalues()
	{		
		for ($i = 0; $i < $this->count("migauthorizedvalue"); $i++)
		{
			unset($name);
			unset($value);
			
			$firstcar = substr (ltrim($this->migauthorizedvalue[$i]), 0, 1);

			if ($firstcar == "#")
			{
				$pos = strpos (ltrim($this->migauthorizedvalue[$i]), " ");
				if (!($pos === false))
				{
					$keyword[0] = substr (ltrim($this->migauthorizedvalue[$i]), 1, $pos - 1); 
					$keyword[1] = substr (ltrim($this->migauthorizedvalue[$i]), $pos + 1);
				}

				// support for INCLUDE Keyword
				if ($keyword[0] == "INCLUDE")
				{
					if (($fd = fopen($keyword[1], "r")) == false) return $this->quit_on_error("Unable to open " . $keyword[1] . " for reading");
					$first_time = true;
					while (!feof ($fd)) 
					{
			
						$buffer = fgets($fd, 4096);
						if ($first_time)
						{
							$this->migauthorizedvalue[0] = $buffer;
							$first_time = false;
						}
						else
						{
							$this->migauthorizedvalue[] = $buffer;
							$this->migauthorizedvalue["count"] = $this->migauthorizedvalue["count"] + 1;
						}

					}
					fclose ($fd);      
				}

				// support for STRING Keyword
				elseif ($keyword[0] == "STRING") 
				{
					$name = $keyword[1];
					$value = $keyword[1];
				}			
				
				else return $this->quit_on_error("Programming error ! Unrecognized keyword:" . $keyword[0]);
			}
//			else
//			{
			if (!isset($name) && !isset($value)) 		// was not set by the STRING keyword
			{

				$str = $this->migauthorizedvalue[$i];  // copy the option string
				$pos = strpos ($str, "=");
				if ($pos === false) 
				{
					// if there is not a "=" character
					$name = $str;
					$value = $str;
				}
				else
				{
					$name = "";
					$value = "";
					for ($j = 0; $j< $pos; $j++)	
						$value = $value . $str[$j];
					for ($j = $pos+1; $j < strlen($str); $j++)		
						$name = $name . $str[$j];
				}			
			}			
//			}
			if (isset($name)) $this->migauthorizedvalue_couple[$i]["name"] = $name;
			if (isset($value)) $this->migauthorizedvalue_couple[$i]["value"] = $value;	
		}
		$this->migauthorizedvalue_couple["count"] = $this->count("migauthorizedvalue");
		return true;
	}

	function get_combination_set()
	{
		//must be called after a process_migauthorizedvalues() call
		$count = 0;
		for ($i = 0; $i < $this->count("migauthorizedvalue"); $i++) 
			if ($this->migauthorizedvalue_couple[$i]["value"] == $this->migauthorizedvalue[$i])
			{
				$this->combination_set[] = $this->migauthorizedvalue[$i];
				$count++;
			}
		$this->combination_set["count"] = $count;
		return true;
	}		

	function is_migsearchcriteriaclass ($class)
	{		
		for ($l = 0; $l < $this->count("migsearchcriteriaclass"); $l++)
			if ($this->migsearchcriteriaclass[$l] == $class)
				return true;
		return false;
	}
	
	function is_migsearchresultclass ($class)
	{
		for ($l = 0; $l < $this->count("migsearchresultclass"); $l++)
			if ($this->migsearchresultclass[$l] == $class)
				return true;
		return false;
	}
	
	function is_migdefaultcriteriaclass ($class)
	{
		for ($l = 0; $l < $this->count("migdefaultcriteriaclass"); $l++)
			if ($this->migdefaultcriteriaclass[$l] == $class)
				return true;
		return false;
	}
		
	function customize_class ($class)
	{
		for ($l = 0; $l < $this->count("migcustomizedclass"); $l++)
			if ($this->migcustomizedclass[$l] == $class)
				return true;
		return false;
	}

	function is_migauthorizedvalue ($string)
	{
		for ($l = 0; $l < $this->count("migauthorizedvalue"); $l++)
			if ($this->migauthorizedvalue[$l] == $string)
				return true;
		return false;
	}
	
	function is_userdefault_attribute ()
	{
		$core = new core($this->conn_id);
		
		return $core->is_userdefault_object($this->dn);
	}

############################################################################################
# INSERTING DATA TO THE LOWER WORLD
############################################################################################

	function delete($type = "system")
	{
		$core = new core($this->conn_id);
		
		$rewrite = false;
		if ($type == "system") 
		{
			if (INFORMATION_SYSTEM_HOLDS_REFERENCIES == "no")
			{
			// referencies
				if (!$this->check_references("delete", $this->migattributename)) return $this->quit_on_error($this->error_msg);

				// search for the attributes that are a combination of the modified one
				$result_array = array ("migattributename", "migauthorizedvalue");
				if (($combin_attr = $core->read_attribute_list("combinations", $result_array, $this->migattributename)) == false) return $this->quit_on_error($core->error_msg);
	
				for ($i = 0; $i < $core->attribute_count; $i++) 
				{
					$curr_attr = new attribute($this->conn_id);
					if (!$curr_attr->read_from_list($combin_attr, $i)) return $this->quit_on_error($curr_attr->error_msg);
					
					for ($j = 0; $j < $curr_attr->count("migauthorizedvalue"); $j++)		 	// all values		
						if ($this->migattributename == $curr_attr->migauthorizedvalue[$j]) // found
							$updref["migauthorizedvalue"][] = $this->migattributename;
			
					if (isset($updref))
					{
						if (!$curr_attr->mod_del($updref)) return $this->quit_on_error($curr_attr->error_msg);
						unset($updref);				
					}
				}
			}
			$rewrite = true;
		}

		if (!$core->delete($this->dn)) return $this->quit_on_error($core->error_msg);
	
		if ((MIG_LDAP_SERVER_RECONFIGURE == "yes") && ($type == "system")) 
		{
		
			// LDAP schema reconfiguration
			require_once "php/ldap_schema.php";
			$ldap_schema = new schema();
		
			// get the contents of the config file
			$ldap_schema->readConfigurationFile(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE);

		
			// and reconfigure the server.		
			if (!$this->write_ldap_schema($ldap_schema->mig_defined_attributes)) return $this->quit_on_error($this->error_msg);

			// then output the classes definitions, 
			require_once "php/search_class.php";
			$tmp = new search_class($this->conn_id);
			if (!$tmp->write_ldap_schema($ldap_schema->mig_defined_objectClasses)) return $this->quit_on_error($tmp->error_msg);	
		}
		return true;
	}

	function add($type)
	{
		$core = new core($this->conn_id);

	 	if (!(isset($this->migattributename) && ($this->migattributename != ""))) return $this->quit_on_error("Add attribute fails : At least migattributename must be specified");

		
		if ($type != "user")
			if ($core->attribute_exists($this->migattributename)) return $this->quit_on_error($core->error_msg);

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

		if (($type != "user") && (MIG_LDAP_SERVER_RECONFIGURE == "yes"))
		{
			// LDAP schema reconfiguration
			require_once "php/ldap_schema.php";
			$ldap_schema = new schema();
		
			$attrlist = $ldap_schema->get_attributes($this->conn_id);
			
			$already_defined = false;
			if (isset($attrlist[strtolower($this->migattributename)])) 
			{ 
				$already_defined = true;
// at this stage it can only be a standard attribute
$updadd["migattributeoid"] = $attrlist[strtolower($this->migattributename)]["oid"];
			} 				
			
/*			if (!$already_defined) 
			{
				for (reset($attrlist); $ind = key($how); next ($ind))
				{
					if ($attrlist[$ind]["oid"] == $this->migattributeoid)
					{
						$already_defined = true;
						break;
					}
				}
			}
		
			if ($already_defined) return $this->quit_on_error("add: attribute already in LDAP server schema. Aborted."); 
*/			
		}
		
		// All check done. We can add 
		if (!$core->add_attribute($type, $updadd)) return $this->quit_on_error($core->error_msg);
		$this->dn = $core->dn;

		if (($type != "user")  && (MIG_LDAP_SERVER_RECONFIGURE == "yes"))
		{
			// get the contents of the config file
			$ldap_schema->readConfigurationFile(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE);
			
			// and reconfigure the server.		
			if (!$this->write_ldap_schema($ldap_schema->mig_defined_attributes)) return $this->quit_on_error($this->error_msg);

			// then output the classes definitions, 
			require_once "php/search_class.php";
			$tmp = new search_class($this->conn_id);
			if (!$tmp->write_ldap_schema($ldap_schema->mig_defined_objectClasses)) return $this->quit_on_error($tmp->error_msg);	
		}
		return true;
	}

	function write_ldap_schema($mig_defined_attributes)
	{
		$core = new core($this->conn_id);
		
		// LDAP schema reconfiguration
		require_once "php/ldap_schema.php";
		$ldap_schema = new schema();
		
		$attrlist = $ldap_schema->get_attributes($this->conn_id);
		$ldap_schema->readConfigurationFile(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE);
	
		// reconfigure the server.
		if (($dm = $core->read_attribute_list("system", array("migattributename", "migattributedisplayname", "migattributeoid", "migdisplaytype"))) == false) return $this->quit_on_error($core->error_msg);
		
		if (!$ldap_schema->openConfigurationFileForOutput()) return $this->quit_on_error($ldap_schema->error_msg);
		
		for ($i = 0; $i < $core->attribute_count; $i++) 
		{
			$cur_attr = new attribute($this->conn_id);
			$cur_attr->read_from_list($dm, $i);
			if (isset($attrlist[strtolower($cur_attr->migattributename)]))
			{
				if (isset($mig_defined_attributes[strtolower($cur_attr->migattributename)]))  
					$go = true;
				else 
					$go = false;
			}
			else $go = true;
			
			if ($go == true)
			{

				if (($out = $ldap_schema->ldap_format_attribute($cur_attr->migattributeoid, $cur_attr->migattributename, $cur_attr->migattributedisplayname, $cur_attr->migdisplaytype)) == false) return $this->quit_on_error($ldap_schema->error_msg);
				if (!$ldap_schema->appendToConfigurationFile($out)) die ($ldap_schema->error_msg);
			}
		}
		if (!$ldap_schema->closeConfigurationFile()) return $this->quit_on_error($ldap_schema->error_msg);

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
		if (isset($how["migattributename"]) && ($how["migattributename"] == ""))
			return $this->quit_on_error("mod_replace fails : replacement migattributename cannot be null");
		else	
		{
			$core = new core($this->conn_id);
			
			if (isset($how["migattributename"])) 
			{
				if (!isset($this->migattributename)) return $this->quit_on_error("mod_replace fails : migattributename cannot be null");
				
				if ($how["migattributename"] != $this->migattributename)
					if ($core->attribute_exists($how["migattributename"])) return $this->quit_on_error($core->error_msg);
			
/*				// LDAP schema reconfiguration
				require_once "php/ldap_schema.php";
				$ldap_schema = new schema();
		
				$attrlist = $ldap_schema->get_attributes($this->conn_id);
				if (isset($attrlist[$how["migattributename"]])) 
					if (!$this->read($how["migattributename"], array())) return $this->quit_on_error("Not able to modify system attribute " . $how["migattributename"] . " : Not supported");
*/		
				if ($how["migattributename"] != $this->migattributename)
				{
					if (!$this->rename($how["migattributename"])) return $this->quit_on_error($this->error_msg);

					// referencies
					if (INFORMATION_SYSTEM_HOLDS_REFERENCIES == "no")
					{			
						if (!$this->check_references("modify", $this->migattributename, $how["migattributename"])) return $this->quit_on_error($this->error_msg);

						// search for the attributes that are a combination of the modified one
						$result_array = array ("migattributename", "migauthorizedvalue");
						if (($combin_attr = $core->read_attribute_list("combinations", $result_array, $this->migattributename)) == false) return $this->quit_on_error($core->error_msg);
						for ($i = 0; $i < $core->attribute_count; $i++) 
						{
							$curr_attr = new attribute($this->conn_id);
							if (!$curr_attr->read_from_list($combin_attr, $i)) return $this->quit_on_error($curr_attr->error_msg);
	
							for ($j = 0; $j < $curr_attr->count("migauthorizedvalue"); $j++)		 	// all values		
								if ($this->migattributename == $curr_attr->migauthorizedvalue[$j]) // found
									$updref["migauthorizedvalue"][$j] = $how["migattributename"];
								else
									$updref["migauthorizedvalue"][$j] = $curr_attr->migauthorizedvalue[$j];
		
							if (isset($updref)) 
							{
								$updref["migattributename"] = $curr_attr->migattributename;
								if (!$curr_attr->mod_replace($updref)) return $this->quit_on_error($curr_attr->error_msg);
								unset($updref);				
							}
						}
					}
					
					
				}
			}
		
			if (!$core->mod_replace($this->dn, $how)) return $this->quit_on_error($core->error_msg);

			for (reset($how); $key = key($how); next ($how)) 
				$this->$key = $how[$key];
			
			if (MIG_LDAP_SERVER_RECONFIGURE == "yes") 
			{
				if (!$this->is_userdefault_attribute())
				{
					// LDAP schema reconfiguration
					require_once "php/ldap_schema.php";
					$ldap_schema = new schema();
		
					// get the contents of the config file
					$ldap_schema->readConfigurationFile(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE);
				
					// and reconfigure the server.		
					if (!$this->write_ldap_schema($ldap_schema->mig_defined_attributes)) return $this->quit_on_error($this->error_msg);

					// then output the classes definitions, 
					require_once "php/search_class.php";
					$tmp = new search_class($this->conn_id);
					if (!$tmp->write_ldap_schema($ldap_schema->mig_defined_objectClasses)) return $this->quit_on_error($tmp->error_msg);	
				}
			}
			return true;						
		}
	}

	function mod_add($what)
	{
		$core = new core($this->conn_id);
		if (!$core->mod_add($this->dn, $what)) return $this->quit_on_error($core->error_msg);
		return true;
	}


	function mod_del($what)
	{
		$core = new core($this->conn_id);
		if (isset($what["migattributename"])) return $this->quit_on_error("Error: cannot remove attribute's name property");
		elseif (!$core->mod_del($this->dn, $what)) return $this->quit_on_error($core->error_msg);
		return true;		
	}


	function attribute_output($type, $val)
	{ 
		$bcc = "";
		if (is_array ($val) && !(($type == "dropdownmenumultiple") || ($type == "tripleemailalias")))
		{
			if (isset($val["count"])) $total = $val["count"];
			else $total = sizeof($val);

			for ($i = 0; $i < $total; $i++)
			{
				if ($i > 0)
				{
					echo "<br>";
					if ($type == "mail") $bcc .= ",";
				}
				$bcc .= $this->attribute_output($type, $val[$i]);				
			}
		}
		else
		{
			if ($type == "readonlytext")
			{
				if ($this->count("migauthorizedvalue") == 0)
				{
					if ($val == "") echo "&nbsp;";
					else echo $val;
/*
{
$trans = get_html_translation_table(HTML_ENTITIES);
$encoded = strtr($val, $trans);
echo $encoded;
} */
					echo "<input type=\"hidden\" name=\"" . $this->migattributename . "\" 	value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">";
				}
				else // do the opposit of a dropdownmenu
				{
					$found = false;
					for ($i = 0; $i < $this->count("migauthorizedvalue"); $i++) 
					{
//	if (isset($this->migauthorizedvalue_couple[$i]["value"])) $value = $this->migauthorizedvalue_couple[$i]["value"];
//	if (isset($this->migauthorizedvalue_couple[$i]["name"])) $name = $this->migauthorizedvalue_couple[$i]["name"];
//	if (isset($value) && ($val == $value))
						$value = $this->migauthorizedvalue_couple[$i]["value"];
						$name = $this->migauthorizedvalue_couple[$i]["name"];
						if ($val == $value)
						{
							echo $name . "<input type=\"hidden\" name=\"" . $this->migattributename . "\" value=\"" . htmlspecialchars($value, ENT_QUOTES) . "\">";
							$found = true;
							break;
						}
					}
					if (!$found) 
						echo $val . "<input type=\"hidden\" name=\"" . $this->migattributename . "\" 	value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">";
				}
			}

			elseif ($type  == "onelinetextbox") 
				echo "<input type=\"text\" size=\"40\" name=\"" . $this->migattributename . "\" value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">";		

			elseif ($type == "dropdownmenu") 
			{
				echo "<select name=\"" . $this->migattributename . "\">\n";
				$found = false;
				for ($i = 0; $i < $this->count("migauthorizedvalue"); $i++) 
				{
//	if (isset($this->migauthorizedvalue_couple[$i]["value"])) $value = $this->migauthorizedvalue_couple[$i]["value"];
//	if (isset($this->migauthorizedvalue_couple[$i]["name"])) $name = $this->migauthorizedvalue_couple[$i]["name"];
//	if (isset($value) && ($val == $value))
					$value = $this->migauthorizedvalue_couple[$i]["value"];
					$name = $this->migauthorizedvalue_couple[$i]["name"];
					if ($val == $value)
					{
						echo "<option value=\"" . htmlspecialchars($value, ENT_QUOTES) . "\" selected>$name</option>\n";
						$found = true;
					}
					else
						echo "<option value=\"" . htmlspecialchars($value, ENT_QUOTES) . "\">$name</option>\n";
				}
				if (!$found)
					echo "<option value=\"\" selected></option>\n";
				
				echo "</select>\n";
			}
				
			elseif ($type == "dropdownmenumultiple") 
			{
				echo "<select size=\"4\" name=\"" . $this->migattributename . "[]\" multiple>\n";			
				for ($i = 0; $i < $this->count("migauthorizedvalue"); $i++) 
				{
					$selected = false;
//					if (isset($this->migauthorizedvalue_couple[$i]["value"])) $value = $this->migauthorizedvalue_couple[$i]["value"];
//					if (isset($this->migauthorizedvalue_couple[$i]["name"])) $name = $this->migauthorizedvalue_couple[$i]["name"];
					$value = $this->migauthorizedvalue_couple[$i]["value"];
					$name = $this->migauthorizedvalue_couple[$i]["name"];

					echo "<option value=\"" . htmlspecialchars($value, ENT_QUOTES) . "\"";
					if (is_array($val))
					{
						if (isset($val["count"])) $total = $val["count"];
						else $total = sizeof($val);

						for ($j = 0; $j < $total; $j++) 
						{
							if ($val[$j] == $value) 
							{
								$selected = true;
								break;
							}
						}
					}
					elseif ($val == $value)	$selected = true;
					if ($selected) 
						echo " selected>$name</option>\n";
					else
						echo ">$name</option>\n";
				}
				echo "</select>\n";
			}			
	
			elseif ($type == "date") 
			{
				echo "<input type=\"text\" size=\"2\" name=\"dd_" . $this->migattributename . "\" value=\"" . htmlspecialchars(substr($val,6,2), ENT_QUOTES) . "\">";
				echo "/";
				echo "<input type=\"text\" size=\"2\" name=\"mm_" . $this->migattributename . "\" value=\"" . htmlspecialchars(substr($val,4,2), ENT_QUOTES) . "\">";
				echo "/";
				echo "<input type=\"text\" size=\"4\" name=\"y4_" . $this->migattributename . "\" value=\"" . htmlspecialchars(substr($val,0,4), ENT_QUOTES) . "\">";
				echo " (dd/mm/yyyy)";
			}	

			elseif ($type == "readonlydate") 
			{
				if ($val == "") $val = "      ";
				echo "<input type=\"hidden\" name=\"dd_" . $this->migattributename . "\" value=\"" . htmlspecialchars(substr($val,6,2), ENT_QUOTES) . "\">";
				echo substr($val,6,2) . "/";
				echo "<input type=\"hidden\" name=\"mm_" . $this->migattributename . "\" value=\"" . htmlspecialchars(substr($val,4,2), ENT_QUOTES) . "\">";
				echo substr($val,4,2) . "/";
				echo "<input type=\"hidden\" name=\"y4_" . $this->migattributename . "\" value=\"" . htmlspecialchars(substr($val,0,4), ENT_QUOTES) . "\">";
				echo substr($val,0,4) . " (dd/mm/yyyy)";
			}	
		
			elseif ($type == "dropdownmenuother") 
			{
				echo "<select name=\"" . $this->migattributename . "\" onchange=\"if (this.options[this.selectedIndex].value=='Other...'){addItem(this)};\">\n";
				$found = false;
				for ($i = 0; $i < $this->count("migauthorizedvalue"); $i++) 
				{
					$value = $this->migauthorizedvalue_couple[$i]["value"];
					$name = $this->migauthorizedvalue_couple[$i]["name"];
					if ($val == $value) 
					{
						echo "<option value=\"" . htmlspecialchars($name, ENT_QUOTES) . "\" selected>$value</option>\n";
						$found = true;
					}
					else
						echo "<option value=\"" . htmlspecialchars($name, ENT_QUOTES) . "\">$value</option>\n";
 				}	
				if ($found == false) echo "<option value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\" selected>$val</option>\n";
				echo "<option value=\"Other...\">Other...</option>\n";
				echo "</select>\n";
			}

			elseif ($type == "tripleemailalias") 
			{
/*				echo "<select name=\"" . $this->migattributename . "\" multiple onchange=\"addItemLimited(this, 3);\">\n";
				$address = strtolower(USER_LOGINNAME . "@" . USERS_DOMAIN);
				echo "<option value=\"$address\" selected>$address</option>";
				
				if (is_array($val))
				{
					if (isset($val["count"])) $total = $val["count"];
					else $total = sizeof($val);
				
					for ($i = 0; $i < $total; $i++) 
					{
						if (($val[$i] != "") && (!strcasecmp($val[$i], $address)))
							echo "<option value=\"$val[$i]\" selected>$val[$i]</option>\n";
					}
				}
				elseif ($val != "")
					echo "<option value=\"$val\" selected>$val</option>\n";
				
				echo "<option value=\"\">Add a new alias</option>";
				echo "</select>\n";
				echo "<input type=\"button\" value=\"Delete selected alias\" onClick=\"deleteItem(this.form." . $this->migattributename . ");\">";
*/
				for ($i = 0; $i < 3; $i++) 
				{
					if ($i > 0) echo "<br>";
					if (is_array($val))	echo "<input type=\"text\" size=\"40\" name=\"" . $this->migattributename . "[]\" value=\"" . htmlspecialchars($val[$i], ENT_QUOTES) . "\">";
					else
					{
						echo "<input type=\"text\" size=\"40\" name=\"" . $this->migattributename . "[]\" value=\"";
						if ($i == 0) echo htmlspecialchars($val, ENT_QUOTES);
						echo "\">";
					}
				}
				
			}
	
			elseif ($type == "imagejpeg") 
			{
				if ($val)
				{
					$file = tempnam("/tmp", "MIGgif");
					if (!$file)
						die("Unable to create image temporary file");
					
					$fd = fopen($file, "w");
					fwrite ($fd, $val, sizeof($val) -1);
					fclose($fd);
				}
				else $file = "images/no_image.jpg";
				
				echo "<img src=\"php/jpeg_image.php?filename=$file\" border=\"0\" alt=\"\">";
				if ($val) unlink($file);		
			}

			elseif ($type == "imageurl") 
			{
				echo "<img src=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">";
			}
		
	
			elseif ($type == "fourdigittext") 
				echo "<input type=\"text\" size=\"4\" name=\"" . $this->migattributename . "\" value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">"; 				
			 
			elseif ($type == "fifteendigittext") 
				echo "<input type=\"text\" size=\"15\" name=\"" . $this->migattributename . "\" value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">"; 				
			
			elseif ($type == "mail")
			{
				if ($val == "") echo "&nbsp; ";
				else echo "<a href=\"mailto:$val\">$val</a><input type=\"hidden\" name=\"" . $this->migattributename . "\" value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">";
				if (sizeof($val) > 0)
					$bcc =  $val;		
			}
			
			elseif ($type == "onelinemail")
			{
				echo "<input type=\"text\" onBlur=\"isEmail(this);\" name=\"" . $this->migattributename . "\" value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">";
			}
		
			elseif ($type == "telephonenumber")
			{
				$matches = array();
				$ret = preg_match ("/\+?\s*([0-9]*)\s*\(?([0-9]*)\)?\s*(.*)/", $val, $matches);
				
				if (isset($matches[1])) $intlcode = $matches[1]; else $intlcode = "";
				if (isset($matches[2])) $areacode = $matches[2]; else $areacode = "";
				if (isset($matches[3])) $number = $matches[3]; else $number = "";
				
				echo "+";
				echo "<input type=\"text\" size=\"4\" name=\"intlcode_" . $this->migattributename . "\" value=\"". htmlspecialchars($intlcode, ENT_QUOTES) . "\">";
				echo "(";
				echo "<input type=\"text\" size=\"4\" name=\"areacode_" . $this->migattributename . "\" value=\"" . htmlspecialchars($areacode, ENT_QUOTES) . "\">";
				echo ")";
				echo "<input type=\"text\" size=\"15\" name=\"number_" . $this->migattributename . "\" value=\"" . htmlspecialchars($number, ENT_QUOTES) . "\">";
//				 onBlur=\"isPhoneNumber(this);\"
			}
			
			elseif ($type == "twodigittext") 
				echo "<input type=\"text\" size=\"2\" name=\"" . $this->migattributename . "\" value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">";	
		
			elseif ($type == "url") 
				echo "<input type=\"hidden\" name=\"" . $this->migattributename . "\" 	value=\"" . htmlspecialchars($val, ENT_QUOTES) . "\"><a href=\"" . htmlspecialchars($val, ENT_QUOTES) . "\">$val</a>";	
		
			elseif ($type == "scrollingtextbox") 
			{
				$addr = explode("$", $val);
				echo "<textarea rows=5 cols=34 name=\"" . $this->migattributename . "\">";
				echo implode("\r", $addr);				
				echo "</textarea>";
				echo "<input type=\"hidden\" name=\"attr_to_reformat[]\" value=\"" . $this->migattributename . "\">";
			}

			elseif ($type == "readonlyscrollingtextbox") 
			{
				$addr = explode("$", $val);
				echo "<input type=\"hidden\" name=\"" . $this->migattributename . "\" value=\"" . htmlspecialchars(implode("\r", $addr), ENT_QUOTES) . "\">";
				echo implode("<br>", $addr);				
				echo "<input type=\"hidden\" name=\"attr_to_reformat[]\" value=\"" . $this->migattributename . "\">";
			}

			else echo "Programming error !! invalid attribute type";
		}
		return $bcc;
	}
	
}

?>
