<?
	require "includes/shared.inc";
	require "includes/schema.inc";
	$ldap_schema = new schema();
	require "schemas/namelist.inc";

#####################################################################

	# Import a whole schema from the text area
	if( $data ) {
		$converted_data = parse_ldap_format( $data );
		$ldap_schema->objectClass[$class] = $converted_data;
		$file = fopen( "schemas/$class.oc.conf", 'w' );
		fwrite( $file, $ldap_schema->ldap_format( $class ) );
		fclose( $file );
	}

	# Load (or reload) all of the schemas
	$ldap_schema->load();

	# Add
	if( $type && ( $new_field || $old_field ) )
	{
		if( ! is_array( $ldap_schema->objectClass[$class][$type] ) )
			$ldap_schema->objectClass[$class][$type] = array();
		if( $new_field ) {
			array_push( $ldap_schema->objectClass[$class][$type], $new_field );
		} else {
			array_push( $ldap_schema->objectClass[$class][$type], $old_field );
		}
		$file = fopen( "schemas/$class.oc.conf", 'w' );
		fwrite( $file, $ldap_schema->ldap_format( $class ) );
		fclose( $file );
		$ldap_schema->load();
	}

	# Delete
	if( $deletefield )
	{
		for( $i = 0; $i < count( $ldap_schema->objectClass[$class]["allows"] ); $i++ )
		{
			if($ldap_schema->objectClass[$class]["allows"][$i] == $deletefield)
				unset( $ldap_schema->objectClass[$class]["allows"][$i] );
		}
		for( $i = 0; $i < count( $ldap_schema->objectClass[$class]["requires"] ); $i++ )
		{
			if($ldap_schema->objectClass[$class]["requires"][$i] == $deletefield)
				unset( $ldap_schema->objectClass[$class]["requires"][$i] );
		}
		$file = fopen( "schemas/$class.oc.conf", 'w' );
		fwrite( $file, $ldap_schema->ldap_format( $class ) );
		fclose( $file );
		$ldap_schema->load();
	}

#####################################################################

	# Print the page header
	site_header( "Schema Editor - ". $class);

	echo '<TABLE>';

	if( $changename )
	{
		if( $change )
		{
			$namelist[$changename] = $newname;
			$namelist["objectclass"] = "Class";
			$file = fopen( "schemas/namelist.inc", 'w' );
			fwrite( $file, namelist_format( $namelist ) );
			fclose( $file );
		} else {
			echo '<TR><TD colspan=2 align=right><FORM method=post>';
			echo '('. $changename .')<INPUT type=text name=newname value="'. $ldap_schema->get_label( $changename ) .'">';
			echo '<INPUT type=hidden name=changename value="'. $changename .'">';
			echo '<INPUT type=hidden name=class value="'. $class .'">';
			echo '<INPUT type=submit name=change value="Change"></FORM>';
		}
	}

	# Print Required Fields
	echo '<TR><TD align=left valign=top>';
	echo '<h4>Required Fields</h4>';
	if( is_array( $ldap_schema->objectClass[$class]["requires"] ) )
		while( list( $key, $value ) = each( $ldap_schema->objectClass[$class]["requires"] ) )
		{
			if( strtolower( $value ) != "objectclass" ) {
				echo "<A HREF=\"$PHP_SELF?class=$class&deletefield=$value\"><IMG src=\"images/deleteperson-small.gif\" alt=\"[delete]\" border=0></A>&nbsp;";
				echo "<A HREF=\"$PHP_SELF?class=$class&changename=$value\">". $ldap_schema->get_label( $value ) .'</A>  ('. $value .")<BR>\n";
          	}
		}
	echo '<FORM method=POST>';
	echo '<input type=text name="new_field"><BR>';
	echo '<SELECT name="old_field"><OPTION selected value="">Select Field';
	reset( $namelist );
	while( list( $key, $value ) = each( $namelist )  )
	{
		echo "<OPTION value=\"$key\">$value\n";
	}
	echo '</SELECT>';
	echo '<INPUT type=submit>';
	echo '<INPUT type=hidden name="schema" value="'. $class .'">';
	echo '<INPUT type=hidden name="type" value="requires"></FORM>';

	# Print Allowed Fields
	echo '<h4>Allowed Fields</h4>';
	if( is_array( $ldap_schema->objectClass[$class]["allows"] ) )
		while( list( $key, $value ) = each( $ldap_schema->objectClass[$class]["allows"] ) )
		{
			echo "<A HREF=\"$PHP_SELF?class=$class&deletefield=$value\"><IMG src=\"images/deleteperson-small.gif\" alt=\"[delete]\" border=0></A>&nbsp;";
			echo "<A HREF=\"$PHP_SELF?class=$class&changename=$value\">". $ldap_schema->get_label( $value ) .'</A>  ('. $value .")<BR>\n";
		}
	echo '<FORM method=POST>';
	echo '<input type=text name="new_field"><BR>';
	echo '<SELECT name="old_field"><OPTION selected value="">Select Field';
	reset( $namelist );
	while( list( $key, $value ) = each( $namelist ) )
	{
		echo "<OPTION value=\"$key\">$value\n";
	}
	echo '</SELECT>';
	echo '<INPUT type=submit>';
	echo '<INPUT type=hidden name="class" value="'. $class .'">';
	echo '<INPUT type=hidden name="type" value="allows"></FORM>';

	# Print list of known Classes
	echo '</TD><TD align=right valign=top><h4>Classes:</h4>';
	while( list( $key ) = each( $ldap_schema->objectClass ) )
	{
		echo '<A HREF="'. $PHP_SELF .'?class='. $key ."\">$key</a><br>";
	}

	# Print Import Field
	echo '<FORM method=POST>';
	echo '</TD></TR><TR><TD colspan=2>';
	echo '<FORM method=POST>';
	echo '<TEXTAREA name=data rows=15 cols=40>';
	echo $ldap_schema->ldap_format( $class );
	echo '</TEXTAREA>';
	echo '<INPUT type=hidden name="name" value="'. $class .'">';
	echo '<INPUT type=submit value="Import">';
	echo '</FORM></TD></TR></TABLE>';

	site_footer();

#####################################################################

function parse_ldap_format( $data )
{
	global $class;
	trim( $data );
	$data = ereg_replace("[[:space:]]+"," ",$data );
	$data_array = explode( " ", $data );

	if( strtolower( array_shift( $data_array ) ) == "objectclass" )
		$class = array_shift( $data_array );
	do
	{
		$next = array_shift( $data_array );
	} while( strtolower( $next ) != "requires" && $next );

	do
	{
		$next = ereg_replace( "[\t\,]", "", array_shift( $data_array ) );
		if( $next && $next != "allows" ) $return["requires"][$next] = $next;
	} while( $next != "allows" && $next );
	do
	{
		$next = ereg_replace( "[\t\,]", "", array_shift( $data_array ) );
		if( $next ) $return["allows"][$next] = $next;
	} while( $next );

	return $return;
}

#####################################################################

function namelist_format( $namelist )
{
	if(  !is_array($namelist) ) return "";

	$return = "<?\n\t\$namelist = array(\n";
	$i=0;
	while( list($key, $value) = each( $namelist ) )
	{
		if( $value )
		{
			$return .= "\t\t\"$key\" => \"$value\"";
			if( $i < count( $namelist ) -1 )
				$return .= ",";
			else
				$return .= " )";
			$return .= "\n";
		}
		$i++;
	}
	$return .= "?>";

	return $return;
}


#####################################################################
?>