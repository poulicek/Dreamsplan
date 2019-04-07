<?php
require_once('IView.php');

class Settings implements IView
{
	public function GetMenu()
	{
	}
	

	public function GetContents($account)
	{
    	require_once('helpers/Controls.Helper.php');
    	$email = self::GetAssingedEmail($account);

		$html = '<a class="button" onfocus="this.blur()" href="javascript:void(0)" onmouseup="location.href = \'?createAccount=1\'">create account<br />create a private url of your own account</a>';

		if ($account != 'beta')
			$html .= '<a class="button" onfocus="this.blur()" href="javascript:void(0)" onmouseup="document.getElementById(\'assignEmail\').style.display = \'\'">assign email<br />email address serves for account recovery</a>';

        $html .= '<a class="button" onfocus="this.blur()" href="javascript:void(0)" onmouseup="document.getElementById(\'recoverAccount\').style.display = \'\'">recover account<br />account url will be sent to given email address</a>';
        $html .= '<hr />';
		$html .= '<a class="button" onfocus="this.blur()" href="javascript:core.InvokeMailto(\'feedback\')">send a feedback<br />your feedback helps us to fulfil our goals and reach our dreams</a>';
		$html .= '<a class="button" onfocus="this.blur()" href="javascript:core.InvokeMailto(\'contribute\')">contribute<br />we are looking for help with graphics design, SEO, PHP, HTML, CSS, MYSQL, marketing, social media, ...</a>';
		$html .= '<a class="button" onfocus="this.blur()" href="javascript:document.getElementById(\'paypal\').submit()">donate<br />raising our funds keeps us running and evolving</a>';

		$html = Controls::GetWindow('userSettings', $html);

        $html .= Controls::GetWindow('recoverAccount', '<input value="'.$email.'" id="recoveryEmail" />', false, 'tasklists.RecoverAccount(this)');
        $html .= Controls::GetWindow('assignEmail', '<input value="'.$email.'" id="assignmentEmail" />', false, 'tasklists.AssignEmail(this, \''.$account.'\')');

		return $html;
	}
	

	public static function GetAssingedEmail($account)
	{
        $data = Storage::LoadConfig();
		$accounts = $data['Accounts'];

		return isset($accounts[$account]) ? $accounts[$account] : null;
	}


	public static function GetAssingedAccount($email)
	{
        $data = Storage::LoadConfig();
		$accounts = $data['Accounts'];

		foreach ($accounts as $account => $val)
			if ($val == $email)
				return $account;
	}


	public static function RecoverAccount($email)
	{
    	require_once('helpers/Email.Helper.php');

		$account = self::GetAssingedAccount($email);
		if (!$account)
			return 'Wrong email address - no account found.';

		$url = 'http://www.dreamsplan.com?s='.$account;
		$message = "Dear user of Dreamsplan,\nThis is an automatcially generated message which contains your account URL. Thank you for using the Dreamsplan, we hope it helps you to reach your dreams.\n\n".$url."\n\nYours Dreamsplan Team.";

		return '<div class="longMessage">'.nl2br("Email service is not enabled during the testing period. In final version you will privately receive an email with following contents:\n\n<i>".$message).'</i></div>';

		return Email::Send($email, 'Dreamsplan Account Url', $message);
	}


	public static function AssignEmail($account, $email)
	{
		$data = Storage::LoadConfig();

        $data['Accounts'][$account] = $email;
        Storage::SaveConfig($data);
	}
}
?>
