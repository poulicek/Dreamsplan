<?php

abstract class Session
{
	public static $CookieSet;
	public static $SessionId;
	
	public static function DetectSession($createAccount, $defaultAccount = null)
	{
	    self::$CookieSet = !$createAccount && isset($_COOKIE['SessionId']) && $_COOKIE['SessionId'];

		if (self::$CookieSet && $_COOKIE['SessionId'] == $defaultAccount)
		{
        	setcookie('SessionId', $defaultAccount);
			unset($_COOKIE['SessionId']);
			self::$CookieSet = false;
		}

	    if (isset($_GET['s']))
	    {
        	self::$SessionId = $_GET['s'];
	        if ($defaultAccount != self::$SessionId && Storage::Exists(self::$SessionId))
					{
			        	setcookie('SessionId', self::$SessionId, time() + 31536000); // 1 year valid
		            Session::$CookieSet = true;
					}
        	return;
	    }

		session_start();
	    $account = self::$CookieSet ? $_COOKIE['SessionId'] : ($defaultAccount ? $defaultAccount : session_id());
      setcookie('SessionId', $account, time() + 31536000); // 1 year valid
	    header('Location: ?s='.$account);
		exit();
	}
}

?>
