<script>
function openSearchWindow(link, args)
{
	var url = link + '?<?php echo SID?>';
	if (args != "") url += '&' + args; 
	popupWin = window.open(url, 'open_window', 'status,scrollbars,resizable,independent');
	popupWin.focus();
}
</script>

<script language="php">  

$core_file = "php/" . INFORMATION_SYSTEM_TYPE . "_core.php"; 
require_once $core_file;

require_once "php/attribute.php";
include_once "php/quicksort.php";


Class search_engine extends objectmodel
{
	// Externals (used by web pages)
	var $search_class;	// classname of the search class (IN)
	var	$base_dest;		// page where the result icons will point at (IN)
	var	$searchfilter;	// initial searchfilter	(IN)	
	var $total_displayed = 0;	// number of attributes displayed in the select box (OUT)
	var $error_msg;		// error message (OUT)

	// Internals
	var $total_system = 0;// number of system attributes
	var $system_attributes;// array of system attributes
	var $attributearray;	// array containg the selected search criteria
	var $result;			// array containing the search results
	var $result_array; 		// array containing the return attributes
	var $result_to_display;// array containing the search results attributes to display 


	function search_engine($core_conn_id, $data_conn_id, $classname, $searchbase, $base_dest = "", $searchfilter = "")
	{
		$this->conn_id = $core_conn_id;
		$this->data_conn_id = $data_conn_id;
		$this->search_class = $classname;
		
		$this->base_dest = $base_dest;
		$this->searchfilter = $searchfilter;
		$this->searchbase = $searchbase;
		$core = new core($this->conn_id);
		## ATTRIBUTES TO LOOK AFTER
		$result_array = array("migattributename", "migattributedisplayname",
								"migdefaultcriteriaclass","migsearchcriteriaclass","migsearchresultclass",
								"migauthorizedvalue", "migedittype", "migsearchtype", "migdisplaytype");
		
		if (($this->system_attributes = $core->read_attribute_list("system", $result_array, $this->search_class)) == false) return $this->quit_on_error($core->error_msg);
		$this->total_system = $core->attribute_count;
					
		if (($info_user = $core->read_attribute_list("user", $result_array, $this->search_class)) == false) return $this->quit_on_error($core->error_msg);
		$total_user = $core->attribute_count;
			
		## override with user config, if any
		$optionboxes = array("migsearchcriteriaclass","migsearchresultclass","migdefaultcriteriaclass");
		if (!$core->override_config($this->system_attributes, $info_user, $optionboxes)) return $this->quit_on_error($core->error_msg);

		quicksort($this->system_attributes, 0, $this->total_system -1, "migattributedisplayname"); 
	}

	function set_internal_criteria($criteria = array())
	{
		$core = new core($this->conn_id);
		for ($i = 0; $i < $this->total_system; $i++)  // look all possible attributes
		{
			$attr = new attribute($core->conn_id);
			if (!$attr->read_from_list($this->system_attributes, $i)) return $this->quit_on_error($attr->error_msg);
			
			$is_selected = false;
			if (sizeof($criteria) > 0) // build a search attribute list + HIGHLIGHT user previous choice
			{
				if (in_array($attr->migattributename, $criteria))
					$is_selected = true;
			}
			else  // no criteria have been submitted yet, build a default list for the user + HIGHLIGHT standard defaults		      	
    			if ($attr->is_migdefaultcriteriaclass($this->search_class)) 
	   				$is_selected = true;
				
			if ($attr->is_migsearchcriteriaclass($this->search_class)) 
   			{
				if ($is_selected == true)
				{
					$this->attributearray[] = $attr->migattributename;
				}	
				$this->total_displayed++;			
      		}
		}
		return true;
	}
	
	function show_criteria($criteria = array())
	{
		$core = new core($this->conn_id);
		for ($i = 0; $i < $this->total_system; $i++)  // look all possible attributes
		{
			$attr = new attribute($core->conn_id);
			if (!$attr->read_from_list($this->system_attributes, $i)) return $this->quit_on_error($attr->error_msg);
			
			if ($attr->is_migsearchcriteriaclass($this->search_class)) 
   			{
				$is_selected = false;
				if (sizeof($criteria) > 0) // build a search attribute list + HIGHLIGHT user previous choice
				{
					if (in_array($attr->migattributename, $criteria))
						$is_selected = true;
				}
				else  // no criteria have been submitted yet, build a default list for the user + HIGHLIGHT standard defaults		      	
    				if ($attr->is_migdefaultcriteriaclass($this->search_class)) 
	   					$is_selected = true;
				if ($is_selected != true)
					echo "<option value=\"" . $attr->migattributename . "\">" . $attr->migattributedisplayname . "</option>";
				else
				{
					$this->attributearray[] = $attr->migattributename;
					echo "<option selected value=\"" . $attr->migattributename . "\">" . $attr->migattributedisplayname . "</option>";
				}	
				$this->total_displayed++;			
      		}
		}
		echo "<input type=\"hidden\" name=\"class\" value=\"$this->search_class\">";
		return true;
	}


	function recognize_input_type($type, $attr)
	{	
		$core = new core($this->conn_id);
		if ($type == "combination") 
		{					
			for ($k = 0; $k < $attr->count("migauthorizedvalue"); $k++) 
			{
				if ($attr->migauthorizedvalue_couple[$k]["value"] == $attr->migauthorizedvalue[$k])
				{
					$newattr = new attribute($core->conn_id);
					if (!$newattr->read($attr->migauthorizedvalue[$k], "system", array("migattributename", "migsearchtype", "migauthorizedvalue"))) die ($newattr->error_msg);
					if (!$newattr->process_migauthorizedvalues()) die ($newattr->error_msg);
					$this->recognize_input_type($newattr->migsearchtype, $newattr);
				}
				else echo $attr->migauthorizedvalue_couple[$k]["value"];
			}		
		}
		elseif ($type == "date")
		{
		//	global ${"dd_" . $attr->migattributename};
		//	global ${"mm_" . $attr->migattributename};
		//	global ${"y4_" . $attr->migattributename};

			unset (${"dd_" . $attr->migattributename}); if (isset ($_POST["dd_" . $attr->migattributename])) ${"dd_" . $attr->migattributename} = $_POST["dd_" . $attr->migattributename]; else if (isset($_GET["dd_" . $attr->migattributename])) ${"dd_" . $attr->migattributename} = $_GET["dd_" . $attr->migattributename];
			unset (${"mm_" . $attr->migattributename}); if (isset ($_POST["mm_" . $attr->migattributename])) ${"mm_" . $attr->migattributename} = $_POST["mm_" . $attr->migattributename]; else if (isset($_GET["mm_" . $attr->migattributename])) ${"mm_" . $attr->migattributename} = $_GET["mm_" . $attr->migattributename];
			unset (${"y4_" . $attr->migattributename}); if (isset ($_POST["y4_" . $attr->migattributename])) ${"y4_" . $attr->migattributename} = $_POST["y4_" . $attr->migattributename]; else if (isset($_GET["y4_" . $attr->migattributename])) ${"y4_" . $attr->migattributename} = $_GET["y4_" . $attr->migattributename];
			
			$attr->attribute_output($type, ${"y4_" . $attr->migattributename} . ${"mm_" . $attr->migattributename} . ${"dd_" . $attr->migattributename});
		}
		elseif ($type == "telephonenumber")
		{
		//	global ${"intlcode_" . $attr->migattributename};
		//	global ${"areacode_" . $attr->migattributename};
		//	global ${"number_" . $attr->migattributename};

			unset (${"intlcode_" . $attr->migattributename}); if (isset ($_POST["intlcode_" . $attr->migattributename])) ${"intlcode_" . $attr->migattributename} = $_POST["intlcode_" . $attr->migattributename]; else if (isset($_GET["intlcode_" . $attr->migattributename])) ${"intlcode_" . $attr->migattributename} = $_GET["intlcode_" . $attr->migattributename];
			unset (${"areacode_" . $attr->migattributename}); if (isset ($_POST["areacode_" . $attr->migattributename])) ${"areacode_" . $attr->migattributename} = $_POST["areacode_" . $attr->migattributename]; else if (isset($_GET["areacode_" . $attr->migattributename])) ${"areacode_" . $attr->migattributename} = $_GET["areacode_" . $attr->migattributename];
			unset (${"number_" . $attr->migattributename}); if (isset ($_POST["number_" . $attr->migattributename])) ${"number_" . $attr->migattributename} = $_POST["number_" . $attr->migattributename]; else if (isset($_GET["number_" . $attr->migattributename])) ${"number_" . $attr->migattributename} = $_GET["number_" . $attr->migattributename];


			$attr->attribute_output($type, "+" . ${"intlcode_" . $attr->migattributename} . "(" . ${"areacode_" . $attr->migattributename} . ")" .  ${"number_" . $attr->migattributename});
		}
		else 
		{
		//	global ${$attr->migattributename};
			unset (${$attr->migattributename}); if (isset ($_POST[$attr->migattributename])) ${$attr->migattributename} = $_POST[$attr->migattributename]; else if (isset($_GET[$attr->migattributename])) ${$attr->migattributename} = $_GET[$attr->migattributename];

			$attr->attribute_output($type, ${$attr->migattributename});
		}
	}
				

	function show_input($sortattribute = "")
	{
		$core = new core($this->conn_id);
		$choice_labels = array("contains"		=> "contains", 
								"not_contains"	=> "does not contain",
								"equals"		=> "equals to",
								"not_equals"	=> "does not equal to",
								"begins"		=> "begins with",
								"not_begins"	=> "does not begin with",
								"ends"			=> "ends by",
								"not_ends"		=> "does not end by",
								"greater"		=> "greater than",
								"less"			=> "less than",
								"approx"		=> "approximates");								

		$date_choice_labels = array("begins"		=> "ends on",
								"not_begins"	=> "does not end on",
								"equals"		=> "equals to",
								"not_equals"	=> "does not equal to",
								"greater"		=> "greater than",
								"less"			=> "less than",
								"approx"		=> "approximates");								

		if (isset($this->attributearray))
		{
			for ($i = 0; $i < count($this->attributearray); $i++) 
			{	
				$choice = "choice" . $i; //construct a variable variable.
			//	global ${$choice};
				
				unset (${$choice}); if (isset ($_POST[$choice])) ${$choice} = $_POST[$choice]; else if (isset($_GET[$choice])) ${$choice} = $_GET[$choice];
								
				$attr = new attribute($core->conn_id);
				if (!$attr->read_from_list($this->system_attributes, $this->attributearray[$i])) return $this->quit_on_error($attr->error_msg);
				if (!$attr->process_migauthorizedvalues()) return $this->quit_on_error($attr->error_msg);
				
				echo "<tr bgcolor=\"#eeeeee\"><td width=\"29%\">$attr->migattributedisplayname</td>";
 
				echo "<td width=\"28%\" align=\"center\"><select name=\"choice$i\">";
		        			
				$target_labels = &$choice_labels;
				if ($attr->migsearchtype == "date") $target_labels = &$date_choice_labels;
		        			
				for (reset($target_labels); $j = key($target_labels); next($target_labels))
				{
					if ($$choice == $j)	$selected = " selected "; else $selected = " "; 
					echo "<option $selected value=\"$j\">$target_labels[$j]</option>";
				}      				
				echo "</select></td>";
				
			
				echo "<td width=\"29%\" align=\"center\">";
				$this->recognize_input_type($attr->migsearchtype, $attr);	      				
				echo "</td>";
				echo "<td width=\"14%\" align=\"center\"><input type=\"radio\" name=\"sortattribute\" value=\"$attr->migattributename\" ";
				if ($sortattribute == $attr->migattributename) echo "checked";
				echo "></td></tr>";
			
				echo "<input type=\"hidden\" name=\"criteria[]\" value=\"" . $this->attributearray[$i] . "\">";
			}
		}
		echo "<input type=\"hidden\" name=\"class\" value=\"$this->search_class\">";			
		
		return true;
	}
	
	function show_input_as_text($criteria, $sortattribute = "")
	{
		$core = new core($this->conn_id);
		$choice_labels = array("contains"		=> "contains", 
								"not_contains"	=> "does not contain",
								"equals"		=> "equals to",
								"not_equals"	=> "does not equal to",
								"begins"		=> "begins with",
								"not_begins"	=> "does not begin with",
								"ends"			=> "ends by",
								"not_ends"		=> "does not end by",
								"greater"		=> "greater than",
								"less"			=> "less than",
								"approx"		=> "approximates");								

		$date_choice_labels = array("begins"		=> "ends on",
								"not_begins"	=> "does not end on",
								"equals"		=> "equals to",
								"not_equals"	=> "does not equal to",
								"greater"		=> "greater than",
								"less"			=> "less than",
								"approx"		=> "approximates");								
		
		$str = ""; 
		if (isset($criteria))
		{
			$str = "Searching entries where ";
			for ($i = 0; $i < count($criteria); $i++) 
			{	
				$choice = "choice" . $i; //construct a variable variable.
			//	global ${$choice};
				unset (${$choice}); if (isset ($_POST[$choice])) ${$choice} = $_POST[$choice]; else if (isset($_GET[$choice])) ${$choice} = $_GET[$choice];

				
				$attr = new attribute($core->conn_id);
				if (!$attr->read_from_list($this->system_attributes, $criteria[$i])) return $this->quit_on_error($attr->error_msg);
				if (!$attr->process_migauthorizedvalues()) return $this->quit_on_error($attr->error_msg);
				
				if ($i > 0) $str .= ", ";
				$str .= "the $attr->migattributedisplayname ";
 
				$target_labels = &$choice_labels;
				if ($attr->migsearchtype == "date") $target_labels = &$date_choice_labels;
		        			
				for (reset($target_labels); $j = key($target_labels); next($target_labels))
				{
					if ($$choice == $j)	$str .= " $target_labels[$j]";
				}      				
			
			//	global ${$attr->migattributename};
				unset (${$attr->migattributename}); if (isset ($_POST[$attr->migattributename])) ${$attr->migattributename} = $_POST[$attr->migattributename]; else if (isset($_GET[$attr->migattributename])) ${$attr->migattributename} = $_GET[$attr->migattributename];

				if (!isset(${$attr->migattributename}) || (${$attr->migattributename} == ""))
					$str .= " anything";
				else $str .= " ${$attr->migattributename}";
				
				if ($sortattribute == $attr->migattributename) $sorting_by =  ", sorting by $attr->migattributedisplayname.";
			}
			$str .= $sorting_by;
		}
		
		return $str;
	}
	
	

	function build_filter($selected_class)
	{
		$core = new core($this->conn_id);
		function calculate_end($name, $choice, $value)
		{
			
			$input = str_replace(array("\\", "(", ")", "*"), array("\\5c", "\\28", "\\29", "\\2a"), $value);
		
			$endfilter = "(";
		
			if ($choice == "equals") $endfilter .= $name . "=" . $input;
			elseif ($choice == "not_equals") $endfilter .= "!(" . $name . "=" . $input . ")";
			elseif ($choice == "begins") $endfilter .= $name . "=" . $input . "*";
			elseif ($choice == "not_begins") $endfilter .= "!(" . $name . "=" . $input . "*)";
			elseif ($choice == "ends") $endfilter .= $name . "=*" . $input;
			elseif ($choice == "not_ends") $endfilter .= "!(" . $name . "=*" . $input . ")";
			elseif ($choice == "greater") $endfilter .= $name . ">=" . $input;
			elseif ($choice == "less") $endfilter .= $name . "<=" . $input;
			elseif ($choice == "approx") $endfilter .= $name . "~=" . $input;
			elseif ($choice == "contains") 
			{
				$endfilter .= $name . "=*";
				if ($input != "") $endfilter .= $input . "*";
			}
			elseif ($choice == "not_contains") 
			{
				$endfilter .= "!(" . $name . "=*";
				if ($input != "") $endfilter .= $input . "*)";
			}
			else return null;

			$endfilter .= ")";
			
			return $endfilter;
		}
				
		$filter = $selected_class->migdatafilter;
	
		## build the filter if not manually specified
		if ($this->searchfilter == "")
		{
			$this->searchfilter = "(&" . $filter;
			for ($i = 0; $i < count($this->attributearray); $i++) 
			{
				$choice = "choice" . $i; //construct a variable variable.
			//	global ${$choice};
				unset (${$choice}); if (isset ($_POST[$choice])) ${$choice} = $_POST[$choice]; else if (isset($_GET[$choice])) ${$choice} = $_GET[$choice];

				

				$attr = new attribute($core->conn_id);
				if (!$attr->read_from_list($this->system_attributes, $this->attributearray[$i])) return $this->quit_on_error($attr->error_msg);
				
				if ($attr->migsearchtype == "combination")
				{
					$this->searchfilter .= "(|";
					if (!$attr->get_combination_set()) return $this->quit_on_error($attr->error_msg);

					for ($j = 0; $j < $attr->count("combination_set"); $j++)
					{

					//	global ${$attr->combination_set[$j]};
						unset (${$attr->combination_set[$j]}); if (isset ($_POST[$attr->combination_set[$j]])) ${$attr->combination_set[$j]} = $_POST[$attr->combination_set[$j]]; else if (isset($_GET[$attr->combination_set[$j]])) ${$attr->combination_set[$j]} = $_GET[$attr->combination_set[$j]];

						if (is_array(${$attr->combination_set[$j]}))
							for ($k = 0; $k < sizeof (${$attr->combination_set[$j]}); $k++)
							{
								$this->searchfilter .= calculate_end($attr->combination_set[$j], $$choice, ${$attr->combination_set[$j]}[$k]);
							}
						else
						{
							$this->searchfilter .= calculate_end($attr->combination_set[$j], $$choice, ${$attr->combination_set[$j]});
						}
					}
					$this->searchfilter .= ")";
				}	
				elseif ($attr->migsearchtype == "date")
				{
				//	global ${"dd_" . $attr->migattributename};
				//	global ${"mm_" . $attr->migattributename};
				//	global ${"y4_" . $attr->migattributename};
					unset (${"dd_" . $attr->migattributename}); if (isset ($_POST["dd_" . $attr->migattributename])) ${"dd_" . $attr->migattributename} = $_POST["dd_" . $attr->migattributename]; else if (isset($_GET["dd_" . $attr->migattributename])) ${"dd_" . $attr->migattributename} = $_GET["dd_" . $attr->migattributename];
					unset (${"mm_" . $attr->migattributename}); if (isset ($_POST["mm_" . $attr->migattributename])) ${"mm_" . $attr->migattributename} = $_POST["mm_" . $attr->migattributename]; else if (isset($_GET["mm_" . $attr->migattributename])) ${"mm_" . $attr->migattributename} = $_GET["mm_" . $attr->migattributename];
					unset (${"y4_" . $attr->migattributename}); if (isset ($_POST["y4_" . $attr->migattributename])) ${"y4_" . $attr->migattributename} = $_POST["y4_" . $attr->migattributename]; else if (isset($_GET["y4_" . $attr->migattributename])) ${"y4_" . $attr->migattributename} = $_GET["y4_" . $attr->migattributename];

					$this->searchfilter .= calculate_end($attr->migattributename, $$choice, ${"y4_" . $attr->migattributename} . ${"mm_" . $attr->migattributename} . ${"dd_" . $attr->migattributename});
				}
				else
				{
				//	global ${$attr->migattributename};
					unset (${$attr->migattributename}); if (isset ($_POST[$attr->migattributename])) ${$attr->migattributename} = $_POST[$attr->migattributename]; else if (isset($_GET[$attr->migattributename])) ${$attr->migattributename} = $_GET[$attr->migattributename];

					if (is_array(${$attr->migattributename}))
						for ($j = 0; $j < sizeof (${$attr->migattributename}); $j++)
						{
							$this->searchfilter .= calculate_end($attr->migattributename, $$choice, ${$attr->migattributename}[$j]);
						}
					else 
					{
						$this->searchfilter .= calculate_end($attr->migattributename, $$choice, ${$attr->migattributename});
					}
				}				
			}
			$this->searchfilter .= ")";
		}				
		return true;
	}
	
	
	
	function build_returnAttributesArray($returnattr = "")
	{
		$core = new core($this->data_conn_id);
	
		//  build the result array
		
		$result_array = array();
		
		if (!is_array($returnattr) && !isset($this->result_to_display))
		{
			$this->result_to_display = array();
			for ($i = 0; $i < $this->total_system; $i++) // look all possible attributes
			{
				$attr = new attribute($core->conn_id);
				if (!$attr->read_from_list($this->system_attributes, $i)) return $this->quit_on_error($attr->error_msg);
				if ($attr->is_migsearchresultclass($this->search_class))
				{
					$this->result_to_display[] = $attr->migattributename;
					if ($attr->migdisplaytype == "combination")
					{
						if (!$attr->get_combination_set()) return $this->quit_on_error($attr->error_msg);
						for ($j = 0; $j < $attr->count("combination_set"); $j++)
							$this->result_array[] = $attr->combination_set[$j];
					}
					else $this->result_array[] = $attr->migattributename;				
				}	
			}
		}
		else
		{
			$this->result_to_display = array();
			for ($i = 0; $i < $this->total_system; $i++) // look all possible attributes
			{
				$attr = new attribute($core->conn_id);
				if (!$attr->read_from_list($this->system_attributes, $i)) return $this->quit_on_error($attr->error_msg);
				if (in_array($attr->migattributename, $returnattr))
				{
					$this->result_to_display[] = $attr->migattributename;
					if ($attr->migdisplaytype == "combination")
					{
						if (!$attr->get_combination_set()) return $this->quit_on_error($attr->error_msg);
						for ($j = 0; $j < $attr->count("combination_set"); $j++)
							$this->result_array[] = $attr->combination_set[$j];
					}
					else $this->result_array[] = $attr->migattributename;				
				}	
			}
		}
		return true;						
	}

	function find_results($sortattribute = "", $sortorder = "asc")
	{
		function getmicrotime()
		{ 
		    list($usec, $sec) = explode(" ",microtime()); 
		    return ((float)$usec + (float)$sec); 
	    } 

		$core = new core($this->data_conn_id);

		//  build the result array
		if (!isset($this->result_array)) $this->build_returnAttributesArray();				

		// search
//		$start_time = getmicrotime();
		if (($this->result = $core->search($this->searchbase, $this->searchfilter, $this->result_array)) == false) return $this->quit_on_error($core->error_msg);
//		$middle_time = getmicrotime();
		if (($this->count("result") > 0) && ($sortattribute != ""))
			quicksort($this->result, 0, $this->count("result") -1, strtolower($sortattribute), $sortorder);  

//		$end_time = getmicrotime();
		
//		echo "search in " . ($middle_time - $start_time);
//		echo "sort in " . ($end_time - $middle_time);
		if ($core->get_error() == 4)
			echo "The directory size limit has been exceeded. Please refine your search.";
		return true;
	}


	function is_search_result($dn)
	{
		$core = new core($this->conn_id);
		
		for ($i = 0; $i < $this->count("result"); $i++)  // all the results
			if ($core->get_dn ($this->result[$i]) == $core->normalize_dn ($dn))
				return true;
		return false;
	}

	function recognize_result_type($type, $attr, $val, $curr_result_index)
	{
		$core = new core($this->conn_id);
		if ($type == "combination") 
		{
			if (!$attr->process_migauthorizedvalues()) return $this->quit_on_error($attr->error_msg);
			for ($k = 0; $k < $attr->count("migauthorizedvalue"); $k++) 
			{
				if ($attr->migauthorizedvalue_couple[$k]["value"] == $attr->migauthorizedvalue[$k])
				{
					$newattr = new attribute($core->conn_id);
					if (!$newattr->read($attr->migauthorizedvalue[$k], "system", array("migattributename", "migdisplaytype", "migauthorizedvalue"))) die ($newattr->error_msg);
					if (!$newattr->process_migauthorizedvalues()) die ($newattr->error_msg);
					$bcc = $this->recognize_result_type($newattr->migdisplaytype, $newattr, $this->result[$curr_result_index][strtolower($newattr->migattributename)], $curr_result_index);
				}
				else echo $attr->migauthorizedvalue_couple[$k]["value"];
			}		
		}
		else $bcc = $attr->attribute_output($type, $val);
		return $bcc;
	}
	
	
	function display_results_pages($current_page = 1)
	{
		if ($this->count("result") > 0)
		{
			echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr class=lightgrey><td align=\"left\">";
			
			if ($this->count("result") == 1) echo "<b>1</b> result matches your search criteria. ";
			else echo "<b>" . $this->count("result") . "</b> results match your search criteria. ";
			
			$low = (($current_page - 1) * 30) + 1;
			if ($this->count("result") < $current_page * 30) $high = $this->count("result");
			else $high = $current_page * 30;
			
			echo "Displaying <b>$low</b> - <b>$high</b></td>";
			
		//	global $HTTP_GET_VARS; 
		//	global $SCRIPT_NAME;
			 
			$postString = "";

			for (reset($_GET); $key = key($_GET); next($_GET))
			{
				$val = $_GET[$key];
				if ($key != "page") 
				{
					$key = urlencode(stripslashes($key));
					if (is_array($val))
					{
						$key .= "[]";
						for ($i = 0; $i < sizeof($val); $i++) 
						{
							$printval = urlencode(stripslashes($val[$i]));
							$postString .= "$key=$printval&";
						}
					}
					else
					{
						$val = urlencode(stripslashes($val));
						$postString .= "$key=$val&";
					}
				
				}
			}
			$print_previous = false;
			$print_next = false;
			
			if ($current_page == 1) 
			{
				$print_previous = false;
			}
			else 
			{
				$print_previous = true;
			}
			
			if (((int) ($this->count("result") / 30) ) <= $current_page)
			{
				$print_next = false;
			}
			else 
			{
				$print_next = true;
			}
			
			echo "<td align=\"right\">"; 
			 
			if ($print_previous) echo "<a href=\"" . $_SERVER["SCRIPT_NAME"] . "?page=" . ($current_page - 1) . "&$postString\">Previous</a> ";

			if ($current_page <= 10) 
			{
				$start = 0;
			}
			else 
			{
				$start = $current_page - 10;
			}

			if ($print_next || print_previous)
			{
				for ($i = $start; (($i < (int) $this->count("result") / 30) && ($i < $current_page + 10 - 1)); $i++)
				{
					$pagenum = $i + 1;
					if ($pagenum != $current_page)
						echo "<a href=\"" . $_SERVER["SCRIPT_NAME"] . "?page=$pagenum&$postString\">$pagenum</a> ";
					else echo $pagenum . " ";
				}  
			}		
			if ($print_next) echo "<a href=\"" . $_SERVER["SCRIPT_NAME"] . "?page=" . ($current_page + 1) . "&$postString\">Next</a> ";
			
			echo "</td></tr></table><p></p>";
		}
		return true;
	}
	
	
	function display_results($icon_path, $link_path, $pagenumber = 1)
	{
		$core = new core($this->data_conn_id);
		if ($this->count("result") > 0)
		{ 
			echo "<font size=\"-2\"> To view a record, click the <img border=\"0\" src=\"$icon_path\"> beside it.</font>";
			echo "<table border=\"0\" width=\"100%\" cellspacing=\"0\">	<tr bgcolor=\"#000066\">";
			
			
		//	global $HTTP_GET_VARS; 
		//	global $SCRIPT_NAME;
			 
			$postString = "";

			for (reset($_GET); $key = key($_GET); next($_GET))
			{
				$val = $_GET[$key];
				if (($key != "sortattribute") && ($key != "sortorder")) 
				{
					$key = urlencode(stripslashes($key));
					if (is_array($val))
					{
						$key .= "[]";
						for ($i = 0; $i < sizeof($val); $i++) 
						{
							$printval = urlencode(stripslashes($val[$i]));
							$postString .= "$key=$printval&";
						}
					}
					else
					{
						$val = urlencode(stripslashes($val));
						$postString .= "$key=$val&";
					}
				
				}
			}
		 	echo "<td></td>";		
			// give a human name to search result attributes
			for ($i = 0; $i < sizeof($this->result_to_display); $i++) 
			{			
				$attr = new attribute($core->conn_id);
				if ($attr->read_from_list($this->system_attributes, $this->result_to_display[$i]) == true)
				{
					echo "<td rowspan=\"2\"><a href=\"" . $_SERVER["SCRIPT_NAME"] . "?sortattribute=" . $attr->migattributename . "&sortorder=asc&$postString\"><img border=\"0\" src=\"images/arrow_up.gif\"></a><br><a href=\"" . $_SERVER["SCRIPT_NAME"] . "?sortattribute=" . $attr->migattributename . "&sortorder=desc&$postString\"><img border=\"0\" src=\"images/arrow_down.gif\"></a></td>";
					echo "<td rowspan=\"2\"><font color=\"#FFFFFF\"><b>" . $attr->migattributedisplayname . "</b></font></td>";
				}
			}
			echo "</tr>";
			
			echo "<tr bgcolor=\"#000066\">";
			echo "<td></td>";
			echo "</tr>";
		   	 			
			// display data
			$bcc = "";
			for ($i = (($pagenumber - 1) * 30); ($i < $this->count("result")) && ($i < $pagenumber * 30); $i++)  // all the results 
			{	
				echo "<tr ";
				if (($i % 2) == 0) echo "class=lightgrey";
				echo ">";
				
//				echo "<td><a href=\"$link_path?dn=" . urlencode($core->get_dn($this->result[$i])) . "&class=$this->search_class\" target=\"main\" onclick=\"top.focus()\"><img border=\"0\" src=\"$icon_path\"></a></td>";
				echo "<td valign=\"top\" align=\"left\"><a href=\"javascript:openSearchWindow('" . $link_path . "', 'dn=" . urlencode(utf8_decode($core->get_dn($this->result[$i]))) . "&class=$this->search_class')\" onclick=\"top.focus()\"><img border=\"0\" src=\"$icon_path\"></a></td>";
				for ($j = 0; $j < sizeof($this->result_to_display); $j++)   // all attributes
				{
					echo "<td colspan=\"2\" valign=\"top\" align=\"left\">";
//					if (($i % 2) == 0) echo "#eeeeee";
					$attr = new attribute($core->conn_id);					
					if (!$attr->read_from_list($this->system_attributes, $this->result_to_display[$j])) return $this->quit_on_error($attr->error_msg);
					if (!$attr->process_migauthorizedvalues()) return $this->quit_on_error($attr->error_msg);
					
					if (isset($this->result[$i][strtolower($this->result_to_display[$j])])) $val = $this->result[$i][strtolower($this->result_to_display[$j])];
					else $val = "";

					$newbcc = $this->recognize_result_type($attr->migdisplaytype, $attr, $val, $i);
					
					if (strlen ($newbcc) > 0 ) 
						if (strlen ($bcc) > 0)
							$bcc = $bcc . ";" . $newbcc;
						else $bcc .= $newbcc;

					unset($val);
					echo "</td>";
				}	
				echo "</tr>\n";
			}
			echo "</table><p>";
			if (strlen ($bcc) > 0) echo "<a href=\"mailto:?bcc=$bcc\">Send an e-mail to the whole group</a></p>";
		}
		else 
		{
   			echo "<img src=\"images/warning.gif\">";
			echo "Your search did not produce any result.";  		  
  		}	  			
		return true;	
	}
}

</script>