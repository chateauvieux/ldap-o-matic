<?php

Class integrator
{
	var $url = "http://localhost?";
	var $host = "localhost";
	var $port = 0;
	var $sock = 0;
	var $errormsg = "";
	var $reply;

	function integrator ($port)
	{
		$this->port = $port;
	}
	
	function lookup ($postvars)
	{
		$url = $this->url . "oper=lookup";

		while ( list ($key, $val) = each ($postvars) ) 
		{
			if ($key != "dn") 
				$url = $url . "&$key=" . urlencode($val);
		}		
		return $this->asreq ($url);
	}

	function add ($postvars)
	{
		$url = $this->url . "oper=add";

		while ( list ($key, $val) = each ($postvars) ) 
		{
			if (is_array($val)) 
			{
				for ($i = 0; $i < sizeof($val); $i++)
					$url = $url . "&$key=" . urlencode($val[$i]);
			}
			else $url = $url . "&$key=" . urlencode($val);
		}

		return $this->asreq ($url);
	}

	function update ($postvars)
	{
		$url = $this->url . "oper=update";

		while ( list ($key, $val) = each ($postvars) ) 
		{
			if (is_array($val)) 
			{
				for ($i = 0; $i < sizeof($val); $i++)
					$url = $url . "&$key=" . urlencode($val[$i]);
			}
			else $url = $url . "&$key=" . urlencode($val);
		}

		return $this->asreq ($url);
	}
	
	function sync_password ($uid, $new_password, $rehash, $old_password = "", $new_hashed_password = "", $question = "", $answer = "")
	{
		if ($rehash = true) $rehash = "no";
		else $rehash = "yes";
		
		$url = $this->url . "oper=sync_password&dn=" . urlencode($uid) . "&password=" . urlencode($new_password) . "&rehash=" . urlencode($rehash);

		if ($old_password != "") $url .=  "&bind_password=" . urlencode($old_password);
		if ($new_hashed_password != "") $url .=  "&hashed_password=" . urlencode($new_hashed_password);
		if (($answer != "") && ($question != "")) $url .=  "&challenge_question=" . urlencode($question) . "&challenge_answer=" . urlencode($answer);
		
		

		return $this->asreq ($url);
	}
	
	function update_email ($postvars)
	{
		$url = $this->url . "oper=update_email";

		while ( list ($key, $val) = each ($postvars) ) 
		{
			if (is_array($val)) 
			{
				for ($i = 0; $i < sizeof($val); $i++)
					$url = $url . "&$key=" . urlencode($val[$i]);
			}
			else $url = $url . "&$key=" . urlencode($val);
		}

		return $this->asreq ($url);
	}


	function delete ($dn)
	{
		$url = $this->url . "oper=delete&dn=" . urlencode($dn);
		unset($this->reply);
		return $this->asreq ($url);
	}
	
	
	function read ($dn)
	{
		$url = $this->url . "oper=read&dn=" . urlencode($dn);
		unset($this->reply);
		return $this->asreq ($url);
	}
	
	function read_email ($dn)
	{
		$url = $this->url . "oper=read_email&dn=" . urlencode($dn);
		unset($this->reply);
		return $this->asreq ($url);
	}
	
	function add_user_schema_subtree($uid)
	{
		$url = $this->url . "oper=add_user_schema_subtree&uid=" . urlencode($uid);	
		return $this->asreq ($url);
	}

	function clearAttribute ($attr)
	{
		if (isset($this->reply[strtolower($attr)]))
			unset ($this->reply[strtolower($attr)]);
	}		
	
	function getAttribute ($attr)
	{
		if (isset($this->reply[strtolower($attr)]))
		{
			if (sizeof($this->reply[strtolower($attr)]) <= 1)
				return $this->reply[strtolower($attr)][0];
			else
				return $this->reply[strtolower($attr)];
		}
	}

	function setAttribute ($attr, $value)
	{
		$this->update[strtolower($attr)] = $value;
	}

	function asreq ($url)
	{
		if (!$this->openSocket ($url))
			return false;

		if (!$this->getReply())
			return false;

		return true;

	}

	function geterrmsg ()
	{
		return $this->errormsg;
	}

	function openSocket ($url)
	{
		$this->sock = fsockopen ($this->host, $this->port, &$errno, &$errstr, 30);
		if (!$this->sock) 
		{
			$this->errormsg = $errstr;
			return false;
		}

		fputs ($this->sock, "GET $url HTTP/1.0\n\n");
		return true;
	}

	function getReply ()
	{
		$str = "";
		while (!feof($this->sock)) 
		{
			$str = fgets($this->sock, 1024);
			$str = Chop($str);
			$arr = split(":", $str, 2);
			if ($arr[1][0] == ":") $arr[1] = utf8_encode(base64_decode (substr($arr[1], 2)));
			$this->reply[strtolower($arr[0])][] = trim($arr[1]);
		}
		fclose ($this->sock);
		return $this->reply;
	}
}

?>
