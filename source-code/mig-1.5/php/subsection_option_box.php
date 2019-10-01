<script language="php">

require_once "php/subsection.php";

## IMPORTANT NOTE : $subsctn and $selected are used in MIG web pages ##
## This will only work if a class has been previously selected (i.e, if the $selected_class object is set)

$subsctn = $core->read_subsection_list("auto");
if ($subsctn == false) die ($core->error_msg);
</script>	

<center>			
<form>
<p>
<select size="1" name="subsection" ONCHANGE="if (this.options[this.selectedIndex].value != '') { page = '<?php echo $SCRIPT_NAME ?>?<?php echo SID ?>&subsection=' + this.options[this.selectedIndex].value + '&class=<?php echo $class ?>'; window.open(page,'_self') }">  

<script language="php">

if (!isset($subsection) && ($core->subsection_count > 1))
	$sel = "selected";
else $sel = "";	
	
echo "<option $sel value=\"\">Select your subsection</option>";
for ($i = 0; $i < $core->subsection_count; $i++) 
{
	$cur = new subsection($conn_id);
	$cur->read_from_list($subsctn, $i);
	if ((isset($subsection) && ($subsection == $cur->migsubsectionname)) || ($core->subsection_count == 1))
	{
		echo "<option selected value=\"" . $cur->migsubsectionname . "\">" . $cur->migsubsectiondisplayname . "</option>";
		$selected_subsection = $cur;
	}	
	elseif ($cur->migincludingclass == $selected_class->migclassname)
		echo "<option value=\"" . $cur->migsubsectionname . "\">"
			 . $cur->migsubsectiondisplayname . "</option>";
}
</script>
</select>
</p>
</form>
</center>
