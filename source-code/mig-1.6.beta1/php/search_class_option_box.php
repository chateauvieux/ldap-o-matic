<script language="php">

require_once "php/search_class.php";

## IMPORTANT NOTE : $dm and $selected_class are used in MIG web pages ##

$dm = $core->read_search_class_list("auto");
if ($dm == false) die ($core->error_msg);
</script>	

<center>			
<form>
<p>
<select size="1" name="class" ONCHANGE="if (this.options[this.selectedIndex].value != '' != 0) { page = '<?php echo $_SERVER["SCRIPT_NAME"] ?>?<?php echo SID ?>&class=' + this.options[this.selectedIndex].value; window.open(page,'_self') }">  

<script language="php">

unset ($class); if (isset ($_POST["class"])) $class = $_POST["class"]; else if (isset($_GET["class"])) $class = $_GET["class"];

if (!isset($class) && ($core->search_class_count > 1))
	$sel = "selected";
else $sel = "";

echo "<option $sel value=\"\">Select your search class</option>";
for ($i = 0; $i < $core->search_class_count; $i++) 
{
	$cur_class = new search_class($conn_id);
	$cur_class->read_from_list($dm, $i);
	if ((isset($class) && ($class == $cur_class->migclassname)) || ($core->search_class_count == 1))
	{
		echo "<option selected value=\"" . $cur_class->migclassname . "\">" . $cur_class->migclassdisplayname . "</option>";
		$selected_class = $cur_class;
	}	
	else 
		echo "<option value=\"" . $cur_class->migclassname . "\">"
			 . $cur_class->migclassdisplayname . "</option>";
}
</script>
</select>
</p>
</form>
</center>
