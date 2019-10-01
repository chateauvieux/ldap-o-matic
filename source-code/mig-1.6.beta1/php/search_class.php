<?php

require_once "php/objectmodel.php";

Class search_class extends objectmodel
{
	var $migclassname;
	var $migclassoid;
	var $migclassdisplayname;
	var $migaccessmode;
	var $migdatabasedn;
	var $migdataobjectclass;
	var $migdatasupobjectclass;
	var $migdataauxobjectclass;	
	var $migdatanamingattribute;	
	var $migdatafilter;
	var $migdataicon;
	var $migpointedwebpage;
	var $migrelevantattribute;
	var $migrequiredattribute;
	var $migauthorizeduser;
	var $migtimestamp;
	var $migintegrationport;


	
	function search_class($conn_id)
	{
		$this->mappings = array("migclassname"		 => "single", 
								"migclassoid"		 => "single", 
								"migclassdisplayname"	 => "single",
								"migrelevantattribute" => "multiple",
								"migrequiredattribute" => "multiple",
								"migdatabasedn"	 => "single",
								"migdataobjectclass"=> "single",
								"migdatafilter"=> "single",
								"migdatasupobjectclass"=> "single",
								"migdataauxobjectclass"=> "single",
								"migdatanamingattribute"=> "single",								
								"migpointedwebpage"		 => "single",
								"migauthorizeduser"		 => "multiple",
								"migdataicon"	 => "single",
								"migaccessmode"	 => "single",
								"migtimestamp" => "single", 
								"migintegrationport" => "single");
		// inherited
		$this->conn_id = $conn_id;
		$this->referer = array("attribute" => array("migsearchcriteriaclass" => "string",
													"migsearchresultclass" => "string",
													"migdefaultcriteriaclass" => "string",
													"migcustomizedclass" => "string"),
							  "search_class" => array("migdatasupobjectclass" => "string",
							  						  "migdataauxobjectclass" => "string"),
							  "group" => array("migusingclass"	=> "string"),
							  "subsection" => array("migincludingclass" => "string"));
	}

	
############################################################################################
# RETRIEVING INFO FROM THE LOWER WORLD
############################################################################################
	
	function read($which, $result_array = "everything")
	{
		$core = new core($this->conn_id);
		if ($result_array == "everything")
			$data = $core->read_search_class($which);
		else 
			$data = $core->read_search_class($which, $result_array);
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


	function read_from_list($class_list, $index)
	{
		$core = new core($this->conn_id);
		if (is_integer($index))
		{
			if (($index >= 0) && ($index < $class_list["count"])) 
			{
				for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
				{
					if ($this->mappings[$key] == "single") 
					{
						if (isset($class_list[$index][$key][0])) $this->$key = $class_list[$index][$key][0];
						else $this->$key = "";
					}
					elseif ($this->mappings[$key] == "multiple") 
					{
						if (isset($class_list[$index][$key])) $this->$key = $class_list[$index][$key];
						else $this->$key = array();
					}
					else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
				}
				$this->dn = $core->get_dn($class_list[$index]);
				return true;
			}
			else return $this->quit_on_error("Class could not be read : bad list index");
		}
		elseif (is_string($index))
		{
			for ($i=0; $i < $class_list["count"]; $i++)
			{
				if ($index == $class_list[$i]["migclassname"][0])
				{
					for (reset($this->mappings); $key = key($this->mappings); next ($this->mappings)) 
					{
						if ($this->mappings[$key] == "single") 
						{
							if (isset($class_list[$i][$key][0])) $this->$key = $class_list[$i][$key][0];
							else $this->$key = "";
						}
						elseif ($this->mappings[$key] == "multiple") 
						{
							if (isset($class_list[$i][$key])) $this->$key = $class_list[$i][$key];
							else $this->$key = array();
						}
						else return $this->quit_on_error("read_from_list: Invalid mapping type or unrecognized property"); 
					}
					$this->dn = $core->get_dn($class_list[$i]);
					return true;
				}
			}
			return $this->quit_on_error("Class could not be read from list : bad class name");
		}
		else return $this->quit_on_error("Class could not be read from list : index must be integer or string");
	}	

############################################################################################
# INSERTING INFO INTO THE LOWER WORLD
############################################################################################

	function delete()
	{
		$core = new core($this->conn_id);

	 	if (!strcasecmp ($this->migclassname, USERS_MIG_SEARCHCLASS))
	 		return $this->quit_on_error("Deleting the default user searching class is not authorized. Operation aborted.");
		
		// referencies
		if (INFORMATION_SYSTEM_HOLDS_REFERENCIES == "no")
		{
			if (!$this->check_references("delete", $this->migclassname)) return $this->quit_on_error($this->error_msg);
			// fix attribute referencies
	/*
			include_once "php/attribute.php";
			
			$result_array = array("migattributename","migsearchcriteriaclass",
								"migsearchresultclass","migdefaultcriteriaclass", "migcustomizedclass");
			if (($info = $core->read_attribute_list("referencies", $result_array, $this->migclassname)) == false) return $this->quit_on_error($core->error_msg);

			$refarray = array ("migsearchcriteriaclass","migsearchresultclass","migdefaultcriteriaclass","migcustomizedclass");
			
			for ($j = 0; $j < $core->attribute_count; $j++)			// find each relevant
			{
				$attr = new attribute($this->conn_id);
				if (!$attr->read_from_list($info, $j)) return $this->quit_on_error($core->error_msg);
			
				for ($k = 0; $k < sizeof($refarray); $k++)   // for each reference
					for ($l = 0; $l < $attr->count($refarray[$k]); $l++)
						if ($attr->{$refarray[$k]}[$l] == $this->migclassname)
							$updref[$refarray[$k]][] = $this->migclassname;			
			
				if (isset($updref))
				{
					if (!$attr->mod_del($updref)) return $this->quit_on_error($attr->error_msg);
					unset ($updref);
				}								
			}

			// fix group referencies
			include_once "php/group.php";

			$result_array = array("miggroupname","migusingclass");
			if (($info = $core->read_group_list("referencies", $result_array, $this->migclassname)) == false) return $this->quit_on_error($core->error_msg);
			
			for ($j = 0; $j < $core->group_count; $j++)
			{
				$grp = new group($this->conn_id);
				if (!$grp->read_from_list($info, $j)) return $this->quit_on_error($grp->error_msg);
			
				if (!$grp->delete()) return $this->quit_on_error($grp->error_msg);
			}
*/
		}
		if (!$core->delete($this->dn)) return $this->quit_on_error($core->error_msg);
		
		if (MIG_LDAP_SERVER_RECONFIGURE == "yes") 
		{
			
			// remote classes syntax : CLASSNAME|HOSTNAME|PARAM; CLASSNAME2, HOSTNAME2
			$remote_classes = explode (";", REMOTE_SEARCHCLASSES);
			$go = true;
			for ($i = 0; $i < sizeof($remote_classes); $i++)
			{
				$remote_class = explode ("|", $remote_classes[$i]);
				if (strcasecmp ($this->migclassname, trim ($remote_class[0])) == 0)
				{
			
					$go = false;
					break;
				}
			}
			
			if ($go)
			{
				// LDAP schema reconfiguration
				require_once "php/ldap_schema.php";
				$ldap_schema = new schema();
		
				// get the contents of the config file
				$ldap_schema->readConfigurationFile(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE);
			
		
				// and reconfigure the server.	
				// first output the attributes definitions, 
				require_once "php/attribute.php";
				$tmp = new attribute($this->conn_id);
				if (!$tmp->write_ldap_schema($ldap_schema->mig_defined_attributes)) return $this->quit_on_error($tmp->error_msg);		

				// and then the search classes
				if (!$this->write_ldap_schema($ldap_schema->mig_defined_objectClasses)) return $this->quit_on_error($this->error_msg);
				// and reconfigure the server.
			}
		}
		
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
		if (isset($how["migclassname"])) return $this->quit_on_error("Error: cannot remove class' name property");
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
		if (isset($how["migclassname"]) && ($how["migclassname"] == ""))
			return $this->quit_on_error("mod_replace fails : replacement migclassname cannot be null");
		
		else
		{
			$core = new core($this->conn_id);
			
			if (isset($how["migclassname"])) 
			{
				if (!isset($this->migclassname)) return $this->quit_on_error("mod_replace fails : migclassname cannot be null");

				if ($this->migclassname != $how["migclassname"]) 
					if ($core->search_class_exists($how["migclassname"])) return $this->quit_on_error($core->error_msg);
				
/*				// LDAP schema reconfiguration
				require_once "php/ldap_schema.php";
				$ldap_schema = new schema();
		
				$classlist = $ldap_schema->get_objectClasses($this->conn_id);
				if (isset($classlist[$this->migdataobjectclass])) 
					if (isset($how["migdataobjectclass"]) && !$this->read($how["migdataobjectclass"], array())) return $this->quit_on_error("Not able to modify system classes: Not supported");
*/
				if ($how["migclassname"] != $this->migclassname)
				{
			 		if (!$this->rename($how["migclassname"])) return $this->quit_on_error($this->error_msg);

					// referencies
					if (INFORMATION_SYSTEM_HOLDS_REFERENCIES == "no")
						if (!$this->check_references("modify", $this->migclassname, $how["migclassname"])) return $this->quit_on_error($this->error_msg);

					echo "Refencies updated. <a href=\"index.html\" target=\"_top\">Click here to refresh.</a>";		
				}
			}

			if (!$core->mod_replace($this->dn, $how)) return $this->quit_on_error($core->error_msg);
	
			if (MIG_LDAP_SERVER_RECONFIGURE == "yes") 
			{
	
				// LDAP schema reconfiguration
				require_once "php/ldap_schema.php";
				$ldap_schema = new schema();
		
				// get the contents of the config file
				$ldap_schema->readConfigurationFile(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE);
			
				// and reconfigure the server.	
				// first output the attributes definitions, 
				require_once "php/attribute.php";
				$tmp = new attribute($this->conn_id);
				if (!$tmp->write_ldap_schema($ldap_schema->mig_defined_attributes)) return $this->quit_on_error($tmp->error_msg);	

				// and then the search classes
				if (!$this->write_ldap_schema($ldap_schema->mig_defined_objectClasses)) return $this->quit_on_error($this->error_msg);
				// and reconfigure the server.
			}
		
			return true;
		}		
	}

	function add()
	{
		$core = new core($this->conn_id);
			
		if (!(isset($this->migclassname) && ($this->migclassname != ""))) return $this->quit_on_error("Add class fails : At least migclassname must be specified");

		if ($core->search_class_exists($this->migclassname)) return $this->quit_on_error($core->error_msg);

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

		// LDAP schema reconfiguration
//		require_once "php/ldap_schema.php";
//		$ldap_schema = new schema();
		
//		$classlist = $ldap_schema->get_objectClasses($this->conn_id);
//		if (isset($classlist[$this->migdataobjectclass])) return $this->quit_on_error("add: class already in LDAP server schema. Aborted."); 
		
		// All check done. We can add 
		if (!$core->add_search_class($updadd)) return $this->quit_on_error($core->error_msg);
		$this->dn = $core->dn;
		
		if (MIG_LDAP_SERVER_RECONFIGURE == "yes") 
		{
			// LDAP schema reconfiguration
			require_once "php/ldap_schema.php";
			$ldap_schema = new schema();
		
			// get the contents of the config file
			$ldap_schema->readConfigurationFile(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE);
				
			// and reconfigure the server.	
			// first output the attributes definitions, 
			require_once "php/attribute.php";
			$tmp = new attribute($this->conn_id);
			if (!$tmp->write_ldap_schema($ldap_schema->mig_defined_attributes)) return $this->quit_on_error($tmp->error_msg);	

			// and then the search classes
			if (!$this->write_ldap_schema($ldap_schema->mig_defined_objectClasses)) return $this->quit_on_error($this->error_msg);
		}
			
		return true;
	}


	function write_ldap_schema($mig_defined_objectClasses)
	{
		$core = new core($this->conn_id);
		
		// LDAP schema reconfiguration
		require_once "php/ldap_schema.php";
		$ldap_schema = new schema();
		
		$classlist = $ldap_schema->get_objectClasses($this->conn_id);

		// reconfigure the server.
		if (($dm = $core->read_search_class_list("auto", array("migdataobjectclass", "migclassoid", "migclassdisplayname", "migrelevantattribute", "migrequiredattribute"))) == false) return $this->quit_on_error($core->error_msg);
		
		// manipulate the $dm array and recalculate new value for $core->search_class_count;
		$already_passed = array();
		for ($i = 0; $i < $core->search_class_count; $i++) 
		{
			for ($j = $i+1; $j < $core->search_class_count; $j++) 
			{
				if (($dm[$j]["migdataobjectclass"][0] == $dm[$i]["migdataobjectclass"][0]) && (!in_array($dm[$i]["migdataobjectclass"][0], $already_passed)))
				{
					$dm[$i]["migrelevantattribute"] = array_merge($dm[$i]["migrelevantattribute"], $dm[$j]["migrelevantattribute"]);
					$dm[$i]["migrequiredattribute"] = array_merge($dm[$i]["migrequiredattribute"], $dm[$j]["migrequiredattribute"]);
//					$counter--;
				}
			}
			if (!in_array($dm[$i]["migdataobjectclass"][0], $already_passed)) $already_passed[] = $dm[$i]["migdataobjectclass"][0];
		}	

		if (!$ldap_schema->openConfigurationFileForAppend()) return $this->quit_on_error($ldap_schema->error_msg);

		for ($i = 0; $i < count($already_passed); $i++) 
		{
			for ($j = 0; $j < $core->search_class_count; $j++) 
			{

				$cur_class = new search_class($this->conn_id);
				$cur_class->read_from_list($dm, $j);
				if ($cur_class->migdataobjectclass == $already_passed[$i])
				{
					if (isset($classlist[strtolower($cur_class->migdataobjectclass)])) 
					{
						if (isset($mig_defined_objectClasses[strtolower($cur_class->migdataobjectclass)]))  
{				
							$go = true;
}					
						else 
							$go = false;
					}
					else $go = true;	

					if ($go == true)
					{ 
	
						if (($out = $ldap_schema->ldap_format_class($cur_class->migclassoid, $cur_class->migdataobjectclass, $cur_class->migclassdisplayname, $cur_class->migrelevantattribute, $cur_class->migrequiredattribute) ) == false) return $this->quit_on_error($ldap_schema->error_msg);
						if (!$ldap_schema->appendToConfigurationFile($out)) die ($ldap_schema->error_msg);
					}
					break;
				}
			}
		}
		
		if (!$ldap_schema->closeConfigurationFile()) return $this->quit_on_error($ldap_schema->error_msg);
		
		return true;
	}

############################################################################################
# LOCAL GET/SET
############################################################################################
	

	function is_relevant ($attribute)
	{		
		for ($l = 0; $l < $this->count("migrelevantattribute"); $l++)
			if ($this->migrelevantattribute[$l] == $attribute)
				return true;
		return false;
	}
	
	function is_required ($attribute)
	{		
		for ($l = 0; $l < $this->count("migrequiredattribute"); $l++)
			if ($this->migrequiredattribute[$l] == $attribute)
				return true;
		return false;
	}

	function is_migauthorizeduser ($user)
	{		
		for ($l = 0; $l < $this->count("migauthorizeduser"); $l++)
			if ($this->migauthorizeduser[$l] == $user)
				return true;
		return false;
	}

	function generate_schema ()
	{
		for ($l = 0; $l < $this->count("migrelevantattribute"); $l++) {}
	}



}

?>
