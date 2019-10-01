<?php

$old_error_handler = set_error_handler("userErrorHandler");

function usererrorhandler($errno, $errmsg, $filename, $linenum, $vars) 
{
    // timestamp for dating the error
    $dt = date("Y-m-d H:i:s (T)");
    
    $errortype = array(
  	            1   =>  "Error",
               2   =>  "Alert",
               4   =>  "Parsing error",
               8   =>  "Notification",
               16  =>  "Internal error",
               32  =>  "Internal warning",
               64  =>  "Compilation error",
               128 =>  "Compilation warning",
               256 =>  "User error",
               512 =>  "User warning",
               1024=>  "User Notification"
               );
	    // errors to be logged
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
   
    $err = "\n$errmsg. \nIt was " . $dt . ".";
    
    global $uid;
    $err .= "\n\nThe user was "; if (defined("USER_LOGINNAME")) $err .= USER_LOGINNAME; else $err .= $uid;
    $err .= "\nError type: " . $errortype[$errno];
    $err .= "\nError code : " . $errno;
    $err .= "\nError line : " . $linenum;
    $err .= "\nFile name : " . $filename;
    
    
    
    global $REMOTE_IP;
    global $REMOTE_ADDR;
    global $ALTERNATE_ADDR;
    
	if (isset($HTTP_COOKIE_VARS["AlternateKey"]))
	{
    	$err .= "\nAlternate key: " . $ALTERNATE_KEY;
	    $err .= "\nAlternate key stored in session: " . $HTTP_COOKIE_VARS["AlternateKey"];
	}
    
    $err .= "\nInitially declared as the following IP: " . $REMOTE_IP;


	if (getenv("HTTP_X_FORWARDED_FOR"))
	{
		$err .= "\nMay be behind a proxy: " .  getenv("HTTP_X_FORWARDED_FOR");
	}
    $err .= "\nCurrent IP: " . $REMOTE_ADDR;
	        
    $err .= "\n\n\*******************************************";
 //   $err .= "\nScript name: " . $filename;
 //   $err .= "\nIn line: " . $linenum;
	
    // sauve l'erreur dans le fichier, et emaile moi si l'erreur est critique
    error_log($err, 3, MIG_LOGFILE);
    if ($errno == E_USER_ERROR)
        mail(MIG_ADMINISTRATOR_EMAIL_ADDRESS, "Critical User Error", $err);
}

?>