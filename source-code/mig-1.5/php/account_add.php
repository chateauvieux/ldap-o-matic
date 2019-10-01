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
	}
}

function errorMsg(msg, sf)
{
	sf.select();
	window.alert(msg);
	sf.focus();
}


// string to check, additional forbidden characters in a string
function isThereWhiteSpace(str,addstr) 
{
	var wspace = "\n\t\r";
	var c=0;

	if(arguments.length==2) wspace+=addstr;
	for(a=0;a<str.length;a++) 
	{
		ch=str.charAt(a);
		for(b=0;b<wspace.length;b++) 
		{
			fch=wspace.charAt(b);
			if(ch==fch) 
			{
				c++;
				break;
			}
		}
	}
	return c;
}

function isPhoneNumber (sf) 
{
	allowed = "0123456789+- \(\)";
	stdmsg = "The field is supposed to contain a valid international phonenumber, which means it can contain only the following characters:\n\t";
	str = sf.value;
	
	for (a = 0; a < allowed.length; a++) 
		stdmsg = stdmsg + " " + allowed.charAt(a);
	

	for (a = 0; a < str.length; a++)
	{
		if (allowed.indexOf(str.charAt(a), 0) == -1)
		{
			errorMsg (stdmsg, sf);
			return false;
		}
	}
 	
	return true;
}

function isEmail (sf) 
{
	if (sf.value != "")
	{
		stdmsg = "This field has to contain a valid e-mail address \(e.g: username@server.com\).\n\n\t";

		existingdomains = new Array(".ac", ".ad", ".ae", ".af", ".ag", ".ai", ".al", ".am", ".an", ".ao", ".aq", ".ar", ".as", ".at", ".au", ".aw", ".az", ".ba", ".bb", ".bd", ".be", ".bf", ".bg", ".bh", ".bi", ".bj", ".bm", ".bn", ".bo", ".br", ".bs", ".bt", ".bv", ".bw", ".by", ".bz", ".ca", ".cc", ".cd", ".cf", ".cg", ".ch", ".ci", ".ck", ".cl", ".cm", ".cn", ".co", ".com", ".cr", ".cs", ".cu", ".cv", ".cx", ".cy", ".cz", ".de", ".dj", ".dk", ".dm", ".do", ".dz", ".ec", ".edu", ".ee", ".eg", ".eh", ".er", ".es", ".et", ".fi", ".fj", ".fk", ".fm", ".fo", ".fr", ".ga", ".gb", ".gd", ".ge", ".gf", ".gg", ".gh", ".gi", ".gl", ".gm", ".gn", ".gov", ".gp", ".gq", ".gr", ".gs", ".gt", ".gu", ".gw", ".gy", ".hk", ".hm", ".hn", ".hr", ".ht", ".hu", ".id", ".ie", ".il", ".im", ".in", ".int", ".io", ".iq", ".ir", ".is", ".it", ".je", ".jm", ".jo", ".jp", ".ke", ".kg", ".kh", ".ki", ".km", ".kn", ".kp", ".kr", ".kw", ".ky", ".kz", ".la", ".lb", ".lc", ".li", ".lk", ".lr", ".ls", ".lt", ".lu", ".lv", ".ly", ".ma", ".mc", ".md", ".mg", ".mh", ".mil", ".mk", ".ml", ".mm", ".mn", ".mo", ".mp", ".mq", ".mr", ".ms", ".mt", ".mu", ".mv", ".mw", ".mx", ".my", ".mz", ".na", ".nc", ".ne", ".net", ".nf", ".ng", ".ni", ".nl", ".no", ".np", ".nr", ".nu", ".nz", ".om", ".org", ".pa", ".pe", ".pf", ".pg", ".ph", ".pk", ".pl", ".pm", ".pn", ".pr", ".ps", ".pt", ".pw", ".py", ".qa", ".re", ".ro", ".ru", ".rw", ".sa", ".sb", ".sc", ".sd", ".se", ".sg", ".sh", ".si", ".sj", ".sk", ".sl", ".sm", ".sn", ".so", ".sr", ".st", ".su", ".sv", ".sy", ".sz", ".tc", ".td", ".tf", ".tg", ".th", ".tj", ".tk", ".tm", ".tn", ".to", ".tp", ".tr", ".tt", ".tv", ".tw", ".tz", ".ua", ".ug", ".uk", ".um", ".us", ".uy", ".uz", ".va", ".vc", ".ve", ".vg", ".vi", ".vn", ".vu", ".wf", ".ws", ".ye", ".yt", ".yu", ".za", ".zm", ".zr", ".zw");

		forbchars = " '\"%&# ";
		str = sf.value;
		errors = isThereWhiteSpace(str, forbchars);

		if (errors != 0) 
		{
			errorMsg(stdmsg + "The field contains whitespace characters \(line break, tab, etc...\) or one of these illegal characters: " + forbchars , sf);
			return false;
		}
	
		for (a=0, at=0; a < str.length; a++) 
		{
			ch=str.charAt(a);
			if (ch=="@")
			{
				at++;
				if(a==0 || a==(str.length-1)) 
				{
					errorMsg(stdmsg + "There must be characters before and after the '@' character!", sf);
					return false;
				}
			}
	 	}
 	
	 	if (at != 1)
	 	{
	  		errorMsg(stdmsg + 'A valid e-mail has to contain exactly one "@" character.',sf);
			return false;
		}
	
		dot = str.lastIndexOf(".");
	 	if (dot > -1) 
	 	{
			dom = str.slice(dot,str.length);
			a = 0;
			while(existingdomains[a] != dom && a < existingdomains.length)
				a++;
			
			if (a == existingdomains.length) 
			{
				errorMsg(stdmsg + 'Invalid domain!', sf);
				return false;
			}
			else if (str.charAt((dot-1)) == "@") 
			{
				errorMsg(stdmsg + 'Invalid domain!', sf);
				return false;
	 		}
		}
		if (dot == -1) 
		{
	   		errorMsg(stdmsg + 'A valid e-mail address must contain at least one dot', sf);
			return false;
		}
	 	return true;
	}
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

function addItemLimited(select, maxval) 
{
	var a, str;
	a = select.selectedIndex;
	
	if (a < maxval)
	{
		if(select[a].value == "") 
		{
			str = prompt("Enter your e-mail address below:", "");
			if ((str != null) && (str != ""))
			{
				select.options[a++] = new Option(str, str, true, true);	// text, value, defaultselected, selected
				select.options[a] = new Option("add a new address", "addnew", false, false);
			} 
		}
	}
	else alert("This field is limited to a maximum of " + maxval + " values");
}

function deleteItem (lst) 
{
	a = lst.selectedIndex;
	if (a > 0 && lst[a].value != "") 
	{
		lst.options[a] = null;
		lst.options[a-1].selected = true;
	}
}



</script>



<script language="php">

require_once "php/attribute.php";
require_once "php/search_class.php";
require_once "php/subsection.php";


function recognize_type($type, $attr, $core_conn_id)
{
		
	if ($type == "combination") 
	{
		for ($k = 0; $k < $attr->count("migauthorizedvalue"); $k++) 
		{
			if ($attr->migauthorizedvalue_couple[$k]["value"] == $attr->migauthorizedvalue[$k])
			{
				$newattr = new attribute($core_conn_id);
				if ($newattr->read($attr->migauthorizedvalue[$k], "system", array("migattributename", "migedittype", "migauthorizedvalue")) == false) die($newattr->error_msg);
				if ($newattr->process_migauthorizedvalues() == false) die ($attr->error_msg);
				recognize_type($newattr->migedittype, $newattr, $core_conn_id);
			}
			else echo $attr->migauthorizedvalue_couple[$k]["value"];
		}		
	}
	else $attr->attribute_output($type, "");	
}


function print_Form($search_class_name)
{
	$core = new core();
	if (($conn_id = $core->connect()) == false)	die($core->error_msg);

	$class = new search_class($conn_id);
	if ($class->read($search_class_name, array("migrelevantattribute", "migrequiredattribute", "migdatabasedn", "migdatanamingattribute", "migdatasupobjectclass", "migdataauxobjectclass", "migintegrationport")) == false) die($attr->error_msg);
	
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
	
	$javascript_validator = "function Form_Validator(theForm){";
	for (reset($attrs); $i = key($attrs); next($attrs))
	{
		echo "<tr ";
		if (($i % 2) == 0) echo "bgcolor=\"#000066\"";
		echo "><td colspan=\"4\"><font color=\"#FFFFFF\"><b>$i</b></font></td></tr>";
		for ($j = 0; $j < $attrs[$i]["count"]; $j++)
		{
			$currentname = $attrs[$i][$j];
			
		if ($class->is_relevant($currentname) || $class->is_required($currentname))
		{
	
			$attr = new attribute($conn_id);
			if ($attr->read($currentname, "system", array("migattributename", "migattributedisplayname", "migedittype", "migauthorizedvalue")) == false) die($attr->error_msg);
			
			// Display input box for naming attribute in case it is readonly
			if (substr($attr->migedittype, 0, 8) == "readonly")
				$attr->migedittype = "onelinetextbox";
					
			if ($attr->process_migauthorizedvalues() == false) die ($attr->error_msg);

			echo "<tr ";
			if (($j % 2) == 0) echo "class=lightgrey";
			echo " valign=\"top\">";

			echo "<td><a href=\"javascript:\" onclick=\"return showHelp('$currentname')\"><img hspace=\"3\" src=\"images/formhelpicon.gif\" border=\"0\" height=\"15\" width=\"15\" alt=\"Field Help\"></a></td>";
			echo "<td>";
			if ($class->is_required($attr->migattributename)) echo "<font color=\"#FF0000\">" . $attr->migattributedisplayname . "</font>";
			else echo $attr->migattributedisplayname;
			echo "</td>";

			echo "<td>";
			recognize_type($attr->migedittype, $attr, $conn_id);
			echo "</td>";

			echo "<td>";

			if ($class->migintegrationport != "")
			{
				echo "<input type=\"checkbox\" name=\"p_$currentname\">\n";
			}
							
			echo "</td>";
			echo "</tr>";

			if ($class->is_required($attr->migattributename)) $javascript_validator .= "if (theForm." . $attr->migattributename . ".value == \"\") { alert(\"Please enter a value for the \\\"" . $attr->migattributedisplayname . "\\\" field.\"); theForm." . $attr->migattributename . ".focus(); return (false); }\n";
		}
		else die("Faulty configuration");
		}
		
	
	} 
	echo "<tr><td></td><td colspan=\"3\"><b>Login</b></td></tr>";
	echo "<tr><td></td><td>Password</td><td colspan=\"2\"><input type=\"password\" size=\"16\" name=\"userpassword\" value=\"\"></td></tr>";

	$javascript_validator .= "if (theForm.userpassword.value == \"\") {	alert(\"Please enter a value for the \\\"User password\\\" field.\");	theForm.userpassword.focus(); return (false); }";
	$javascript_validator .= "return (true);}";
	
	if ($core->disconnect() == false) die($core->error_msg);
	return $javascript_validator;
}

</script>