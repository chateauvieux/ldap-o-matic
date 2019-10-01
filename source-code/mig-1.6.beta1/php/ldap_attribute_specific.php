<script language="php">

function strmaxwordlen($input,$len) 
{
	$l = 0;
	$output = "";
	for ($i = 0; $i < strlen($input); $i++) 
	{
		$char = substr($input,$i,1);
		if (($char != " ")&&($char != "\n")) $l++; else $l = 0;
		if ($l == $len)
		{
			$l = 0;
			$output .= " ";
		}
		$output .= $char;
	}
	return($output);
}

function format_text(&$text, $wrapmargin)
{
	// to remove when PHP 4.0.4 and replace by adding the cut param in wordwrap
	$text = strmaxwordlen($text, $wrapmargin);
	$text = wordwrap($text, $wrapmargin,"\n");
}


function ldap_specific_attribute_formatting(&$array)
{
	# LDAP specific attribute formatting

	# postalAddress
	if (isset($array["postalAddress"]))	format_text($array["postalAddress"], 30); 

    #homePostalAddress
   	if (isset($array["homePostalAddress"]))	format_text($array["homePostalAddress"], 30); 

	for ($i = 0; $i < sizeof($array["attr_to_reformat"]); $i++)
		$array[$array["attr_to_reformat"][$i]] = ereg_replace("\r\n|\r|\n", "$", $array[$array["attr_to_reformat"][$i]]);
}


function ldap_attribute_formatting(&$array)
{
		
	function stripslashes_array($arr = array()) 
	{
  		$rs = array();
  		while (list($key,$val) = each($arr)) 
  		{
		    if (is_array($val)) 
		    {
				$rs[$key] = stripslashes_array($val);
			}
			else
			{
      			$rs[$key] = stripslashes($val);
      		}
  		}
		return $rs;
	}
	
//		if ($key != "dn") $array[$key] = str_replace(array("\\", ",", "+" ,"\"", "<", ">", ";", '#'), array("\\\\", "\,", "\+", "\\\"", "\<", "\>", "\;", "\#"), $array[$key]);
		$array = stripslashes_array($array);

}


</script>