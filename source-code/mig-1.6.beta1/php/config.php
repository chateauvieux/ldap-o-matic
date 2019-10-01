<script language="php">

header ("content-type: text/html; charset=UTF-8");

define ("REMOTE_SEARCHCLASSES", "uwcNetworkUnity|unity1.uwc.org;foo|bar");

// MIG CONFIGURATION FILE
// Please refer to documentation before modifying this file
define ("MIG_INSTALLATION_NAME", "UWC Global Directory Service");

// TYPE OF INFORMATION SYSTEM CONTAINING POINTED DATA
define ("INFORMATION_SYSTEM_TYPE", "ldap");
define ("INFORMATION_SYSTEM_COMPUTES_SETS", "no");
define ("INFORMATION_SYSTEM_HOLDS_REFERENCIES", "no");

// TYPE OF INFORMATION SYSTEM AUTHENTICATING USERS
define ("AUTHENTICATION_SYSTEM_TYPE", "ldap");

//INSTALLATION FOLDER OF THE LDAP SERVER
define ("MIG_LDAP_SERVER_RECONFIGURE", "no");
define ("MIG_LDAP_SERVER_INSTALLATION_SCHEMA_INCLUDE_FILE", "/usr/local/etc/openldap/schema/mig_defined.schema");
define ("MIG_LDAP_SERVER_INSTALLATION_FOLDER", "/usr/local/etc/openldap");


//EMAIL_SYSTEM_INFORMATION
define ("MIG_ACTIVATE_EMAIL", "yes");
define ("USERS_MAX_EMAILALIASES_NUMBER", 2);
define ("USERS_EMAILALIAS_ATTRIBUTE", "mailAlternateAddress");
define ("USERS_EMAILFORWARD_ATTRIBUTE", "mailForwardingAddress");
define ("USERS_EMAILDELIVERYMODE_ATTRIBUTE", "deliveryMode");
define ("USERS_WEBMAIL_HOSTNAME", "www.uwc.org");
define ("USERS_DOMAIN", "uwc.net");

// INFORMATION SYSTEM CONNECTED BY CORE LAYER
// SPECIFIC TO THE TYPE OF INFORMATION SYSTEM DEFINED ABOVE
define ("HOSTNAME","ldaps://www.uwc.org:636/");
//define ("HOSTNAME", "localhost");
define ("MANAGER_UID", "cn=manager,o=uwc");
define ("MANAGER_PW", "worthington");


// USER INFORMATION
define ("USERS_BASE_DIR", "o=uwc");
define ("USERS_OBJECTCLASS", "uwcperson");
define ("USERS_MIG_SEARCHCLASS", "uwcNetwork");
define ("USERS_SEARCHFILTER", "(objectclass=uwcperson)");

// INFORMATION SYSTEM CONNECTED BY CORE LAYER
// SPECIFIC TO THE TYPE OF INFORMATION SYSTEM DEFINED ABOVE
//define ("SCHEMA_HOSTNAME","ldaps://localhost:389/");
define ("SCHEMA_HOSTNAME", "ldaps://www.uwc.org:636");
define ("SCHEMA_MANAGER_UID", "cn=manager,o=uwc");
define ("SCHEMA_MANAGER_PW", "worthington");
define ("SCHEMA_BASE_DIR", "ou=schema, o=uwc");

// AUTHENTICATION SYSTEM CONNECTED BY SECURITY LAYER
// SPECIFIC TO THE TYPE OF AUTHENTICATION SYSTEM DEFINED ABOVE
define ("AUTH_HOSTNAME","ldaps://www.uwc.org:636");
//define ("AUTH_HOSTNAME", "localhost");
define ("AUTH_MANAGER_UID", "cn=manager,o=uwc");
define ("AUTH_MANAGER_PW", "worthington");

// GENERAL MIG OPTIONS
define ("MIG_LOGFILE", "/var/log/MIG_log");
define ("MIG_SESSION_TIMEOUT", 1800);
define ("MIG_SESSION_PATH", "/var/tmp/session_ID");
define ("MIG_FORCE_FRAMES", "yes");
define ("PASSW_SYNC_USES_INTEGRATOR", "no");
define ("MIG_ADMINISTRATOR_EMAIL_ADDRESS", "uwcgd-support@uwc.net");
define ("MIG_ENCRYPTS_PASSWORDS", "yes");

if (@is_file("htmlprefs/" . $_SERVER["SCRIPT_NAME"] . ".php"))	include_once ("htmlprefs/" . $_SERVER["SCRIPT_NAME"] . ".php");

require_once("php/error.php");

</script>
