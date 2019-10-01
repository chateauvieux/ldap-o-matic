<script>

function addItem(list) 
{
 var newItemText = prompt("New item: " , "");
 if (newItemText)
 { 
  var newIndexNumber = list.length;
  newItem = new Option(newItemText, newIndexNumber, true, true);
  list.options[newIndexNumber]=newItem;
  list.options[newIndexNumber].value=newItemText;
  list.options[newIndexNumber].selected = true;  
 } //end if;
}

function showHelp(name) 
{
  url = "form_help.html?<?php echo SID ?>#" + name;
   windowLeft = screen.width - 450;
  features = "scrollbars=yes,width=400,height=350,top=100,left=" + windowLeft;
  helpWindow = window.open(url, "InputHelp", features);
  helpWindow.focus();
  return(false);
}

</script>



<script language="php">

require_once "php/attribute.php";
require_once "php/search_class.php";
require_once "php/subsection.php";

function recognize_type($type, $attr, $data, $core_conn_id)
{
	if (isset($data[strtolower($attr->migattributename)])) $val = $data[strtolower($attr->migattributename)];
	else $val = "";
		
	if ($type == "combination") 
	{
		for ($k = 0; $k < $attr->count("migauthorizedvalue"); $k++) 
		{
			if ($attr->migauthorizedvalue_couple[$k]["value"] == $attr->migauthorizedvalue[$k])
			{
				$newattr = new attribute($core_conn_id);
				if ($newattr->read($attr->migauthorizedvalue[$k], "system", array("migattributename", "migdisplaytype", "migauthorizedvalue")) == false) die($newattr->error_msg);
				if ($newattr->process_migauthorizedvalues() == false) die ($attr->error_msg);
				recognize_type($newattr->migdisplaytype, $newattr, $data, $core_conn_id);
			}
			else echo $attr->migauthorizedvalue_couple[$k]["value"];
		}		
	}
	else $attr->attribute_output($type, $val);	
}


function print_result($data, $search_class_name)
{
	$core = new core();
	if (($conn_id = $core->connect()) == false)	die($core->error_msg);

	$class = new search_class($conn_id);
	if ($class->read($search_class_name, array("migrelevantattribute", "migrequiredattribute")) == false) die($attr->error_msg);
	
	include_once "php/quicksort.php";
	if (($subsctn = $core->read_subsection_list("auto")) == false) die ($core->error_msg);
	quicksort($subsctn, 0, $core->subsection_count -1, "migposition");	
	
	$attrs = array();
	for ($s = 0; $s < $core->subsection_count; $s++) 
	{
		$cur_subsection = new subsection($conn_id);
		if (!$cur_subsection->read_from_list($subsctn, $s)) die($cur_subsection->error_msg);
		
		if ($cur_subsection->migincludingclass == $search_class_name)
			$attrs[$cur_subsection->migsubsectiondisplayname] = $cur_subsection->migincludedattribute;
	}
	
	for (reset($attrs); $i = key($attrs); next($attrs))
	{
		echo "<tr ";
		if (($i % 2) == 0) echo "bgcolor=\"#000066\"";
		echo "><td colspan=\"4\"><font color=\"#FFFFFF\"><b>$i</b></font></td></tr>";
		for ($j = 0; $j < $attrs[$i]["count"]; $j++)
		{
			$currentname = $attrs[$i][$j];
			
			$attr = new attribute($conn_id);
			if ($attr->read($currentname, "system", array("migattributename", "migattributedisplayname", "migdisplaytype", "migauthorizedvalue")) == false) die($attr->error_msg);
			if ($attr->process_migauthorizedvalues() == false) die ($attr->error_msg);

			echo "<tr ";
			if (($j % 2) == 0) echo "class=lightgrey";
			echo " valign=\"top\">";


			echo "<td><a href=\"javascript:\" onclick=\"return showHelp('$currentname')\"><img hspace=\"3\" src=\"images/formhelpicon.gif\" border=\"0\" height=\"15\" width=\"15\" alt=\"Field Help\"></a></td>";
			echo "<td>" . $attr->migattributedisplayname . "</td>";

			echo "<td>";
			recognize_type($attr->migdisplaytype, $attr, $data, $conn_id);
			echo "</td>";
			echo "</tr>";
		}
	} 
	if ($core->disconnect() == false) die($core->error_msg);
}

</script>