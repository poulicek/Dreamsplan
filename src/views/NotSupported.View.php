<?php
require_once('IView.php');

class NotSupported implements IView
{
	public function GetContents($account)
	{
	    return '<div class="notsupported">YOUR INTERNET BROWSER IS NOT SUPPORTED. PLEASE UPGRADE TO A NEWER VERSION.</div>';
	}
}
?>
