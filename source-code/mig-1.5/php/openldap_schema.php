<?

require_once "php/objectmodel.php";

class schema  extends objectmodel
{
	var $objectClasses;
	var $server_objectClasses;
	var $server_attributes;
	var $mig_defined_attributes;
	var $mig_defined_objectClasses;
	var $configFileDescriptor;

	function schema() 
	{
		$this->objectClasses = array();
		$this->server_objectClasses = array( array() );
		$this->server_attributes = array( array() ); 
		$this->mig_defined_attributes  = array( array() );
		$this->mig_defined_objectClasses = array( array() );
		$this->configFileDescriptor = 0;
		
		$this->load_templates();
		
	}

#####################################################################

	function load( $name="" )
	{
		unset( $this->objectClasses );
		$d = dir("schemas/");
		while($entry=$d->read())
		{
			if( substr( $entry, -8) == '.oc.conf' )
				$this->readConfiguration( "schemas/". $entry );
		}
		$d->close();
	}


#####################################################################

	function get_label( $name )
	{
		$oldname = $name;
		$name = strtolower( $name );

		while( list($key,$value) = each( $this->objectClasses ) )
		{
			if( isset($this->objectClasses[$key]["item"][$name]["title"]) )
			{
				return $this->objectClasses[$key]["item"][$name]["title"];
			}
		}
		return $oldname;
	}

#####################################################################

	function get_class_name( $name )
	{
		$oldname = $name;
		$name = strtolower( $name );

		if( isset($this->objectClasses[$name]["singlename"]) )
		{
			return $this->objectClasses[$name]["singlename"];
		}
		return $oldname;
	}

#####################################################################

	function get_type( $name )
	{
		$oldname = $name;
		$name = strtolower( $name );

		while( list($key,$value) = each( $this->objectClasses ) )
		{
			if( $this->objectClasses[$key]["item"][$name]["type"] )
			{
				return $this->objectClasses[$key]["item"][$name]["type"];
			}
		}
		return "";
	}

#####################################################################

	function check_rule( $name, $rule )
	{
		$oldname = $name;
		$name = strtolower( $name );

		while( list($key,$value) = each( $this->objectClasses ) )
		{
			if( $this->objectClasses[$key]["item"][$name][$rule] )
			{
				return true;
			}
		}
		return false;
	}

#####################################################################

	function get_names()
	{
		$returnvalue = array();
		for( $i = 0; $i < count( $attributes ); $i++ )
		{
			if( $attributes[$i][name] )
				array_push( $returnvalue, $attributes[$i][name] );
		}
		return $returnvalue;
     }

#####################################################################

	function get_objectClasses( $ds )
	{
#		if( count( $this->server_objectClasses ) ) return $this->server_objectClasses;

		$this->server_objectClasses = array(array());
		$result = ldap_read($ds, 'cn=subschema', "(objectclass=*)", array( "objectclasses" ), 0, 200, 0, LDAP_DEREF_ALWAYS );
		if( $result ) $results = ldap_get_entries($ds,$result );

		for( $att=0; $att < $results[0]["objectclasses"]["count"]; $att++ )
		{
			$class = $results[0]["objectclasses"][$att];

			preg_match( "/([0-9.]+)[\s]+NAME[\s'\(]+([a-zA-Z0-9\-_]+)[\s'\)]/" , $class, $name);			
			$key = trim(strtolower( $name[2] )) ;
			if( $key ) 
			{
				$this->server_objectClasses[$key]["oid"] = trim( $name[1] );
				$this->server_objectClasses[$key]["name"] = trim( $name[2] );
			}	
   			unset($name);

			if( $key )
			{
				preg_match( "/DESC[\s]+'([^\']*)'/" , $class, $name);
				if (isset($name[1]))
				{
					$value = trim( $name[1] );
					if( $value ) $this->server_objectClasses[$key]["desc"] = $value;
				}
				unset($name);

				preg_match( "/MUST[\s\(]+([a-zA-Z0-9\s$]+)(MAY|\))/" , $class, $name);
				if (isset($name[1]))
				{
					$fieldnames = str_replace( ' ', '', strtolower( $name[1] ) );
					$this->server_objectClasses[$key]["required"] = explode( '$', $fieldnames );
				}
				unset($name);

				preg_match( "/MAY[\s\(]+([a-zA-Z0-9\s$]+)(MUST|\))/" , $class, $name);
				if (isset($name[1]))
				{			
					$fieldnames = str_replace( ' ', '', strtolower( $name[1] ) );
					$this->server_objectClasses[$key]["allowed"] = explode( '$', $fieldnames );
				}
				unset($name);

				if( preg_match( "/SUP[\s\(]+([a-zA-Z0-9\s$]+)[\s\)]+(AUXILIARY|STRUCTURAL|ABSTRACT)/", $class, $name) )
					$this->server_objectClasses[$key]["derived"] = strtolower( $name[1] );
	   			unset($name);

			}
			unset( $key );			
		}
		return $this->server_objectClasses;
	}

#####################################################################

	function get_attributes( $ds )
	{

		$this->server_attributes = array();
		$result = ldap_read($ds, 'cn=subschema', "(objectclass=*)", array( "attributetypes" ), 0, 200, 0, LDAP_DEREF_ALWAYS );


		if( $result ) $results = ldap_get_entries($ds,$result );
		for( $att=0; $att < $results[0]["attributetypes"]["count"]; $att++ )
		{
			$class = $results[0]["attributetypes"][$att];
			
			preg_match( "/([0-9.]+)[\s]+NAME[\s'\(]+([a-zA-Z0-9\-_]+)[\s'\)]/" , $class, $name);			
			$key = trim(strtolower( $name[2] )) ;
			if( $key ) 
			{
				$this->server_attributes[$key]["oid"] = trim( $name[1] );
				$this->server_attributes[$key]["name"] = trim( $name[2] );
			}
   			unset($name);

			if( $key )
			{
				preg_match( "/DESC[\s]+'([^\']*)'/" , $class, $name);
				if (isset($name[1]))
				{
					$value = trim( $name[1] );
					if( $value ) $this->server_attributes[$key]["desc"] = $value;
				}
				unset($name);

				if( preg_match( "/EQUALITY[\s]+([a-zA-Z0-9]+)[\s$]/", $class, $name) )
					 $this->server_attributes[$key]["equality"] = strtolower( $name[1] );
	   			unset($name);
	   			
				if( preg_match( "/SUBSTR[\s]+([a-zA-Z0-9]+)[\s$]/", $class, $name) )
					 $this->server_attributes[$key]["substrmatching"] = strtolower( $name[1] );
	   			unset($name);
	   			
				if( preg_match( "/SYNTAX[\s]+([\S]+)/", $class, $name) )
					 $this->server_attributes[$key]["syntax"] = strtolower( $name[1] );
	   			unset($name);

			}
			unset( $key );			
		}
		return $this->server_attributes;
	}



	function readConfigurationFile($filename)
	{

		$this->mig_defined_attributes = array();
		$this->mig_defined_objectClasses = array();
		
		$file = fopen($filename, 'r' );
		while ($buffer = fgets($file, 4096))  
		{ 
			$results[] = $buffer;
		}
		fclose( $file );
		
		
		for( $att=0; $att < count($results); $att++ )
		{
			$class = $results[$att];

			preg_match( "/attributeType.*[\s]+NAME[\s'\(]+([a-zA-Z0-9\-_]+)[\s'\)]/" , $class, $name);			
			if (isset ($name[1]))
			{
				$key = trim(strtolower( $name[1] )) ;
				if (isset($key) && $key) $this->mig_defined_attributes[$key]["name"] = trim( $name[1] );
   				unset($name);
   			}
/*
			if( $key )
			{
				preg_match( "/DESC[\s]+'([^\']*)'/" , $class, $name);
				if (isset($name[1]))
				{
					$value = trim( $name[1] );
					if( $value ) $this->mig_defined_attributes[$key]["desc"] = $value;
				}
				unset($name);

				if( preg_match( "/EQUALITY[\s]+([a-zA-Z0-9]+)[\s$]/", $class, $name) )
					 $this->mig_defined_attributes[$key]["equality"] = strtolower( $name[1] );
	   			unset($name);
	   			
				if( preg_match( "/SUBSTR[\s]+([a-zA-Z0-9]+)[\s$]/", $class, $name) )
					 $this->mig_defined_attributes[$key]["substrmatching"] = strtolower( $name[1] );
	   			unset($name);
	   			
				if( preg_match( "/SYNTAX[\s]+([\S]+)/", $class, $name) )
					 $this->mig_defined_attributes[$key]["syntax"] = strtolower( $name[1] );
	   			unset($name);

			}
			unset( $key );			
*/			
		}
		
		for( $att=0; $att < count($results); $att++ )
		{
			$class = $results[$att];

			preg_match( "/objectClass.*[\s]+NAME[\s'\(]+([a-zA-Z0-9\-_]+)[\s'\)]/" , $class, $name);			
			if (isset ($name[1]))
			{
				$key = trim(strtolower( $name[1] )) ;
				if (isset($key) && ($key!="")) $this->mig_defined_objectClasses[$key]["name"] = trim( $name[1] );
   				unset($name);
   			}
/*
			if( $key )
			{
				preg_match( "/DESC[\s]+'([^\']*)'/" , $class, $name);
				if (isset($name[1]))
				{
					$value = trim( $name[1] );
					if( $value ) $this->mig_defined_objectClasses[$key]["desc"] = $value;
				}
				unset($name);

				preg_match( "/MUST[\s\(]+([a-zA-Z0-9\s$]+)(MAY|\))/" , $class, $name);
				if (isset($name[1]))
				{
					$fieldnames = str_replace( ' ', '', strtolower( $name[1] ) );
					$this->mig_defined_objectClasses[$key]["required"] = explode( '$', $fieldnames );
				}
				unset($name);

				preg_match( "/MAY[\s\(]+([a-zA-Z0-9\s$]+)(MUST|\))/" , $class, $name);
				if (isset($name[1]))
				{			
					$fieldnames = str_replace( ' ', '', strtolower( $name[1] ) );
					$this->mig_defined_objectClasses[$key]["allowed"] = explode( '$', $fieldnames );
				}
				unset($name);

				if( preg_match( "/SUP[\s\(]+([a-zA-Z0-9\s$]+)[\s\)]+(AUXILIARY|STRUCTURAL|ABSTRACT)/", $class, $name) )
					$this->mig_defined_objectClasses[$key]["derived"] = strtolower( $name[1] );
	   			unset($name);

			}
			unset( $key );			
*/			
		}
		return true;
	}

	function openConfigurationFileForOutput()
	{
	
		if (strcasecmp(substr(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE, 0, 6), "ftp://") == 0)
		{
			//delete file first due to a PHP bug (?) - PHP does not overwrite via FTP
			// expecting ftp://user:password@host/file
			$mystr = substr(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE, 6);

			$host = substr($mystr, strpos($mystr, "@") + 1, strpos($mystr, "/") - strpos($mystr, "@") -1);
			$user = substr($mystr, 0, strpos($mystr, ":"));
			$pwd = substr($mystr, strpos($mystr, ":") + 1, strpos($mystr, "@") - strpos($mystr, ":") -1);
			$file = substr($mystr, strpos($mystr, "/")); 
						
			$ftp = fsockopen($host, 21);
			echo fgets($ftp, 255);
			fputs($ftp,"USER $user\r\n");
			echo fgets($ftp, 255);
			fputs($ftp,"PASS $pwd\r\n");
			echo fgets($ftp, 255);
			fputs($ftp,"DELE $file\r\n");
			echo fgets($ftp, 255);
			fputs($ftp,"QUIT\r\n");
			echo fgets($ftp, 255);
			fclose($ftp);
			
		}
	
		if (($file = fopen(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE, 'w' )) == false) return $this->quit_on_error("Unable to open " . MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE . " in output mode"); 
		$this->configFileDescriptor = $file;
		return true;
	}

	function openConfigurationFileForAppend()
	{
		if (($file = fopen(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE, 'a' )) == false) return $this->quit_on_error("Unable to open " . MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE . " in output mode"); 
		$this->configFileDescriptor = $file;
		return true;
	}

	
	function closeConfigurationFile()
	{
		if (!$this->configFileDescriptor) 
			if (!fclose($this->configFileDescriptor)) return $this->quit_on_error("Unable to close " . MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE);
		return true;
	}

	function appendToConfigurationFile($text)
	{
		if (!$this->configFileDescriptor) 
			return $this->quit_on_error(MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE . " not opened");
		if (fwrite ($this->configFileDescriptor, $text) != strlen($text)) 
			return $this->quit_on_error("Append on " . MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE . " failed");
		return true;
	}
	
/*	
	function readConfiguration($OCconfigFilePath )
	{
		global $objectClass;

		if ($cf = fopen($OCconfigFilePath, "r"))
		{
			$ctr = 0;
			while (! feof($cf))
			{
				$line = fgets($cf, 1024);
				if ((chop($line) == "") || ereg("^[ \t]*#", $line, $regs)) // It's blank or ONLY a comment
					continue;
				if (eregi("[ \t]*objectclass[ \t]+([^#]+)", $line, $regs))
				{
					$oc[$ctr] = "";
					while (chop($line) != "" && ! feof($cf))
					{
						$oc[$ctr] .= $line;
						$line = fgets($cf, 10240);
					}
				}
				$ctr++;
			}
			for ($ctr = 0; $ctr < count($oc); $ctr++)
			{
				$ocdef = split("[ \n\t\r]", $oc[$ctr]);
				for ($intctr=0, $def = 0; $def < count($ocdef); $def++)
				{
					if (chop($ocdef[$def]) != "")
					{
						$intctr++;
						switch ($intctr)
						{
							case 1:
								if (strcasecmp($ocdef[$def], "objectclass"))
								{
									echo "Error in objectclass $ocdef[1]. Expected 'objectclass', got '$ocdef[$def]'<br>";
									exit();
								}
								break;
							case 2:
								$ocname = strtolower($ocdef[$def]);
								break;
							case 3:
								if (strcasecmp($ocdef[$def], "requires") && strcasecmp($ocdef[$def], "allows"))
								{
									echo "Error in objectclass $ocdef[1]. Expected 'requires' or 'allows', got '$ocdef[$def]'<br>";
									exit();
								} else
									$curarray = $ocdef[$def];
								$occtr = 0;
								break;
							default:
								if (substr($ocdef[$def], strlen($ocdef[$def])-1, 1) == ",")
								{
									// it is _NOT_ the last entry
									$this->objectClasses[$ocname][$curarray][$occtr++] = strtolower(substr($ocdef[$def], 0, strlen($ocdef[$def])-1));
								} else {
									// it _IS_ the last entry
									$this->objectClasses[$ocname][$curarray][$occtr++] = strtolower($ocdef[$def]);
									$intctr = 2;
								}
								break;
						}
					}
				}
			}
		} else {
			echo "Could not open $OCconfigFilePath for read";
		}
	}
*/	
	
	
	

#####################################################################

function ldap_format_class($oid, $name, $description, $relevant, $required)
{
	if( ($oid == "") || ($name == "") || !is_array($relevant) || !is_array($required) ) return $this->quit_on_error("Programming error!");


	$return = "objectClass ($oid NAME '$name'\n";
	if ($description != "")
		$return .= "\tDESC '$description'\n";
	$return .= "\tSTRUCTURAL\n";	
	if (count($required))
	{
		$requires = preg_grep( "/[a-zA-Z]+/", $required );
		$return .= "\tMUST (";
		$i=0;
		while( list($key, $value) = each( $requires ) )
		{
			$return .= " $value ";
			if( $i++ < count( $requires ) -1 )
				$return .= "$";
		}
		$return .= ")\n";
	}
	if (count($relevant) )
	{
		$allows = preg_grep( '/^[a-zA-Z]+$/', $relevant );
		$return .= "\tMAY (";
		$i=0;
		while( list($key, $value) = each( $allows ) )
		{
			$return .= " $value ";
			if( $i++ < count( $allows ) -1 )
				$return .= "$";

		}
		$return .= ")\n";
     }
     
    $return .= "\t)\n\n";

	return $return;
}



function ldap_format_attribute($oid, $name, $description, $type)
{
	if (($oid == "") || ($name == "")) return $this->quit_on_error("Programming error!");

	$return = "attributeType ($oid NAME '$name'\n";
	if ($description != "")
		$return .= "\tDESC '$description'\n";
	
	$return .= "\tEQUALITY caseExactIA5Match\n";
	$return .= "\tSUBSTR caseIgnoreSubstringsMatch\n";
	$return .= "\tSYNTAX 1.3.6.1.4.1.1466.115.121.1.15\n";
    $return .= "\t)\n\n";

	return $return;
}

/*
function ldap_format( $name )
{
	if( !$name || !is_array($this->objectClasses[$name] ) ) return "";

	$return = "objectClass $name\n";
	if (isset ($this->objectClasses[$name]["requires"]) && is_array( $this->objectClasses[$name]["requires"] ) && count( $this->objectClasses[$name]["requires"] ) )
	{
		$requires = preg_grep( "/[a-zA-Z]+/", $this->objectClasses[$name]["requires"] );
		$return .= "\trequires\n";
		$i=0;
		while( list($key, $value) = each( $requires ) )
		{
			$return .= "\t\t$value";
			if( $i++ < count( $requires ) -1 )
				$return .= ",";
			$return .= "\n";
		}
	}
	if (isset ($this->objectClasses[$name]["allows"]) && is_array( $this->objectClasses[$name]["allows"] ) && count( $this->objectClasses[$name]["allows"] ) )
	{
		$allows = preg_grep( '/^[a-zA-Z]+$/', $this->objectClasses[$name]["allows"] );
		$return .= "\tallows\n";
		$i=0;
		while( list($key, $value) = each( $allows ) )
		{
			$return .= "\t\t$value";
			if( $i++ < count( $allows ) -1 )
				$return .= ",";
			$return .= "\n";
		}
     }

	return $return;
}
*/

#####################################################################

	function load_templates()
	{

		# checkmasterconfig();
		# if ($objectclasses_schema[$objectclasses_number]['loaded'] == "no") {
#		$objectclasses_schema[$objectclasses_number]['loaded'] = "yes";
#		$name = $objectclasses_schema[$objectclasses_number]['objectclassname'];

		$olddir = getcwd();
		chdir(MIG_LDAP_SERVER_INSTALLATION_FOLDER);
		$f_schema = fopen( "ldaptemplates.conf" , "r");
		chdir($olddir);
		
		$sectionref = 0;
		$currentlinenum = 0;
		$section = array();
		$singlename = "";
		$pluralname = "";
		$iconmane	= "";
		$modifyable = "";
		while ($readline = fgets($f_schema, 255))
		{
			$readline = trim($readline);

			if ($readline == "END") {
				$currentlinenum = 0;
				$sectionref++;
				if( $sectionref >= 3 )
				{
					$sectionref = 0;
					$this->objectClasses = array_merge( $this->objectClasses, $section );
					$section = array();
					$singlename = "";
					$pluralname = "";
					$iconmane	= "";
					$modifyable = "";
				}
			} else {
				if  ((substr($readline,0,1) != "#") && ($readline != "") && (substr($readline,0,7) != "Version"))
				{

					# this is where the parsing takes place!
					switch ($sectionref)
					{

					# titles and stuff
					case 0:
						# echo "adding $readline as $currentlinenum to $objectclasses_number <BR>";
						switch ($currentlinenum )
						{
							case 0:
								$singlename = str_replace( '"', '', $readline);
								break;

							case 1:
								$pluralname = str_replace( '"', '', $readline );
								break;

							case 2:
#								if (file_exists($wwwrootfolder . "images/" . $readline))
if (file_exists("images/" . $readline))
								{
									$iconname = str_replace( '"', '', $readline );
								} else {
									$iconname = "ldap_question.gif";
								}
								break;

							case 3:
								$modifyable = str_replace( '"', '', $readline );
								break;

							default:
								$section[strtolower($readline)] =
									array( "singlename" => $singlename, "pluralname" => $pluralname,"iconname" => $iconname, "modifyable" => $modifyable );
							break;
						}
						break;

					# authentication and creation.
					case 1:
						switch ($currentlinenum)
						{
							case 0:
								while( list($key,$value) = each( $section ) )
									$section[$key]["authenticateas"] = $readline;
								break;

							case 1:
								while( list($key,$value) = each( $section ) )
									$section[$key]["makedn"] = $readline;
								break;

							case 2:
								while( list($key,$value) = each( $section ) )
									$section[$key]["defaultlocation"] = $readline;
								break;

							default:
								# there is a bit more here for addersdn and constants?? not used yet
								# echo "=1= OPPS MISTAKE SOMEWHERE IN LDAPTEMPLATE.CONF<BR>$readline<BR>";
								break;
						}
						break;

					# the items
					case 2:
						# echo "Got to attribute bit";
						if (!strpos($readline, '"')) {
							# echo "no quote in $readline<BR>";

							# use default attribute format
							$objectclasses_schema[$objectclasses_number]["attribute"][$currentlinenum]["name"] =  $readline;
							# echo "setting $objectclasses_number attribute $currentlinenum name to  $readline";
							$objectclasses_schema[$objectclasses_number]["attribute-lookup"][strtolower($readline)] =  $currentlinenum;
							$objectclasses_schema[$objectclasses_number]["attribute-count"] = $currentlinenum;
							# flag to read from master is title = ""
							$objectclasses_schema[$objectclasses_number]["attribute"][$currentlinenum]["title"] = "";
							echo "Ack!<BR>";
						} else {

							# get attribute! format attribute, "title" format and rules...
							$rule_line = explode('"', $readline);

							$itemname = strtolower(trim($rule_line[2]));
							while( list($key,$value) = each($section) )
							{
								$section[$key]["item"][$itemname]["attribute-lookup"][strtolower(trim($rule_line[0]))] =  $currentlinenum;
								$section[$key]["item"][$itemname]["attribute-count"] = $currentlinenum;
								$section[$key]["item"][$itemname]["title"]= trim($rule_line[1]);

								$type_list = explode(',', trim($rule_line[0]));
								$section[$key]["item"][$itemname]["type"] = trim(str_replace( "item", '',$type_list[0]) );
								for ($i = 1; $i < count($type_list); $i++)
								{
									$current_rule = trim($type_list[$i]);
									$section[$key]["item"][$itemname]["rule-" . $current_rule]= "yes";
								}
								# echo "adding $itemname to ". $key ."<BR>";
							}
						}
						break;

					# the other items, like linkact etc.
					case 3:
						$rule_line = explode(' ', $readline);
						while( list($key,$value) = each( $section ) )
						{
							$section[$key]["action-type"][$currentlinenum] =  trim($rule_line[0]);
							$section[$key]["action-value"][$currentlinenum]  =  trim($rule_line[1]);
							$section[$key]["action-count"] = $currentlinenum;
						}
						break;

					default:
						echo "=x= OOPS MISTAKE SOMEWHERE IN LDAPTEMPLATE.CONF";
						break;
					} # end switch $sectionref

					$currentlinenum++;
				} // end if a real entry
			} //  not end
		} // end loop of lines
		fclose($f_schema);



	}  // end function - Load Templates

#####################################################################
};
?>