<?php

abstract class Storage
{
	public static function Exists($account)
	{
		return file_exists('userdata/'.$account.'.dta');
	}
	
	
	public static function LoadUserData($account)
	{
		$str = @file_get_contents('userdata/'.$account.'.dta');
		return $str ? unserialize($str) : array('Lists' => array());
	}

	public static function LoadConfig()
	{
		$str = @file_get_contents('userdata/config.dta');
		return $str ? unserialize($str) : array('Accounts' => array());
	}


	public static function SaveUserData($account, &$data)
	{
	    file_put_contents('userdata/'.$account.'.dta', serialize($data));
	}
	

	public static function SaveConfig(&$data)
	{
		file_put_contents('userdata/config.dta', serialize($data));
	}
	

	public static function CorrectDataSet($account)
	{
		$data = self::loadUserData($account);
		foreach ($data['Lists'] as $lkey => $list)
	    {
	    	$data['Lists'][$lkey]['Minimized'] = isset($data['Lists'][$lkey]['Minimized']) ? $data['Lists'][$lkey]['Minimized'] : 0;
      		foreach ($list['Tasks'] as $tkey => $task)
      		{
      			//$data['Lists'][$lkey]['Tasks'][$tkey]['State'] = !empty($data['Lists'][$lkey]['Tasks'][$tkey]['End']) && !$task['State'] ? 2 : $task['State'];
      			//$data['Lists'][$lkey]['Tasks'][$tkey]['Start'] = empty($data['Lists'][$lkey]['Tasks'][$tkey]['Start']) ? $data['Lists'][$lkey]['Tasks'][$tkey]['Assigned'] : intval($data['Lists'][$lkey]['Tasks'][$tkey]['Start']);
      			//$data['Lists'][$lkey]['Tasks'][$tkey]['End'] = isset($task['End']) ? $task['End'] : ($task['State'] > 1 ? mktime(0, 0, 0, 5, 8, 2012) : null);
      			//$data['Lists'][$lkey]['Tasks'][$tkey]['Assigned'] = isset($task['Assigned']) ? $task['Assigned'] : mktime(0, 0, 0, 5, 8, 2012);
      			//$data['Lists'][$lkey]['Tasks'][$tkey]['Deadline'] = empty($data['Lists'][$lkey]['Tasks'][$tkey]['Deadline']) ? null : intval($data['Lists'][$lkey]['Tasks'][$tkey]['Deadline']);
        	}
	    }
		self::saveUserData($account, $data);
	}
}

?>
