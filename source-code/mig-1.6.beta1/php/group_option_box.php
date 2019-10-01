<script language="php">

require_once "php/group.php";

## IMPORTANT NOTE : $gp and $selected_group are used in MIG web pages ##

$gp = $core->read_group_list("auto");
if ($gp == false) die ($core->error_msg);
</script>	

<center>			
<form>
<p>
<select size="1" name="group" ONCHANGE="if (this.options[this.selectedIndex].value != '') {	page = '<?php echo $_SERVER["SCRIPT_NAME"] ?>?<?php echo SID ?>&group=' + this.options[this.selectedIndex].value; window.open(page,'_self') }">  

<script language="php">

unset ($group); if (isset ($_POST["group"])) $group = $_POST["group"]; else if (isset($_GET["group"])) $group = $_GET["group"];


if (!isset($group) && ($core->group_count > 1))
	$sel = "selected";
else $sel = "";

echo "<option $sel value=\"\">Select your group</option>";
for ($i = 0; $i < $core->group_count; $i++) 
{
	$cur_group = new group($conn_id);	
	$cur_group->read_from_list($gp, $i);
	if ((isset($group) && ($group == $cur_group->miggroupname)) || ($core->group_count == 1))
	{
		echo "<option selected value=\"" . $cur_group->miggroupname . "\">" . $cur_group->miggroupdisplayname . "</option>";
		$selected_group = $cur_group;
	}	
	else 
		echo "<option value=\"" . $cur_group->miggroupname . "\">"
			 . $cur_group->miggroupdisplayname . "</option>";
}
</script>
</select>
</p>
</form>
</center>
