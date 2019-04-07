<?php

abstract class Email
{
	public static function Send($to, $subject, $message)
	{
		try
		{
			$headers =
				'From: noreply@dreamsplan.com'."\r\n".
				'MIME-Version: 1.0'."\r\n".
				'Content-type: text/html; charset="utf-8"'."\r\n".
				'X-Mailer: PHP/' . phpversion()."\r\n";
	        mail($to, $subject, $message, $headers);
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
}

?>
